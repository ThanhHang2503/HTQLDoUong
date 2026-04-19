<?php
/**
 * HR Services - Business logic layer
 */

namespace HR\Services;

use HR\Models\{Position, PositionHistory, SalaryRecord, LeaveRequest, ResignationRequest};
use HR\Repositories\{PositionRepository, PositionHistoryRepository, SalaryRepository, LeaveRepository, ResignationRepository};

/**
 * Employee Service - Quản lý nhân viên
 */
class EmployeeService {
    private $posRepo;
    private $posHistRepo;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->posRepo = new PositionRepository($conn);
        $this->posHistRepo = new PositionHistoryRepository($conn);
    }

    /**
     * Quy tắc ánh xạ Trạng thái tổng hợp (Mapping Logic)
     * Chỉ dựa vào hr_status, không xét đến system_status cho việc tính lương.
     */
    private function mapStatus($hr_status): string {
        if ($hr_status === 'resigned') return 'resigned';
        if ($hr_status === 'on_leave') return 'on_leave';
        return 'active';
    }

    /**
     * Đồng bộ hóa Trạng thái vào bảng lịch sử (employee_status_history)
     * Đảm bảo không có overlap và là nguồn duy nhất để tính lương.
     */
    public function syncStatusHistory(int $account_id, string $change_date = null): void {
        $aid = (int)$account_id;
        if (!$change_date) $change_date = date('Y-m-d');

        // Lấy thông tin hiện tại từ bảng accounts
        $acc_q = mysqli_query($this->conn, "SELECT hr_status FROM accounts WHERE account_id = $aid");
        $acc = mysqli_fetch_assoc($acc_q);
        if (!$acc) return;

        $new_status = $this->mapStatus($acc['hr_status']);

        // Lấy bản ghi lịch sử cuối cùng
        $q = mysqli_query($this->conn, "SELECT history_id, status, start_date, end_date 
                                       FROM employee_status_history 
                                       WHERE account_id = $aid 
                                       ORDER BY start_date DESC, history_id DESC LIMIT 1");
        $last = mysqli_fetch_assoc($q);

        if ($last) {
            // Nếu trạng thái giống hệt bản ghi cuối -> Không cần làm gì
            if ($last['status'] === $new_status) return;

            $last_id = (int)$last['history_id'];
            $last_start = $last['start_date'];

            // Nếu thay đổi xảy ra chính vào ngày bắt đầu của bản ghi cũ -> Chỉ cần UPDATE trạng thái đó
            if ($last_start === $change_date) {
                mysqli_query($this->conn, "UPDATE employee_status_history SET status = '$new_status' WHERE history_id = $last_id");
                return;
            }

            // Đóng bản ghi cũ: end_date = chính ngày thay đổi
            // (Cho phép overlap để ngày đó vẫn tính là công nếu có trạng thái active)
            mysqli_query($this->conn, "UPDATE employee_status_history SET end_date = '$change_date' WHERE history_id = $last_id");
        }

        // Tạo bản ghi trạng thái mới
        mysqli_query($this->conn, "INSERT INTO employee_status_history (account_id, status, start_date, end_date) 
                                   VALUES ($aid, '$new_status', '$change_date', NULL)");
    }

    /**
     * Lấy thông tin nhân viên
     */
    public function getEmployee(int $account_id): ?array {
        $aid = (int)$account_id;
        $query = "SELECT a.*, r.name as role_name, p.position_name, p.base_salary 
                  FROM accounts a
                  LEFT JOIN roles r ON a.role_id = r.id
                  LEFT JOIN positions p ON a.position_id = p.position_id
                  WHERE a.account_id = $aid";
        
        $result = mysqli_query($this->conn, $query);
        return mysqli_fetch_assoc($result);
    }

    /**
     * Lấy danh sách nhân viên
     */
    public function getEmployees($role_id = null): array {
        $query = "SELECT a.account_id, a.full_name, a.email, r.name as role_name, 
                         p.position_name, p.base_salary, a.status, a.created_at
                  FROM accounts a
                  JOIN roles r ON a.role_id = r.id
                  LEFT JOIN positions p ON a.position_id = p.position_id
                  WHERE a.role_id != 1"; // exclude admin
        
        if ($role_id) {
            $role_id = (int)$role_id;
            $query .= " AND a.role_id = $role_id";
        }
        
        $query .= " ORDER BY a.full_name";
        
        $result = mysqli_query($this->conn, $query);
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        return $employees;
    }

    /**
     * Tạo nhân viên mới
     * 
     * @param array $data Dữ liệu nhân viên (full_name, email, password, position_id, ...)
     * @return array Kết quả xử lý
     */
    public function createEmployee(array $data): array {
        // 1. Validate & Escape cơ bản
        $full_name = trim(mysqli_real_escape_string($this->conn, $data['full_name'] ?? ''));
        $email = trim(mysqli_real_escape_string($this->conn, $data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $position_id = (int)($data['position_id'] ?? 0);
        
        if (!$full_name || !$email || !$password || !$position_id) {
            return ['success' => false, 'error' => 'Vui lòng cung cấp đầy đủ thông tin bắt buộc (Tên, Email, Mật khẩu, Chức vụ).'];
        }

        // Validate Email định dạng chuẩn, không chứa khoảng trắng/ký tự không hợp lệ
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/\s/', $email)) {
            return ['success' => false, 'error' => 'Email không đúng định dạng'];
        }

        // 2. Kiểm tra email đã tồn tại hay chưa
        $check_query = "SELECT account_id FROM accounts WHERE email = '$email'";
        $check_result = mysqli_query($this->conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            return ['success' => false, 'error' => 'Email đã tồn tại trong hệ thống.'];
        }

        // 3. Chuẩn hóa dữ liệu phụ
        $phone = trim(mysqli_real_escape_string($this->conn, $data['phone'] ?? ''));
        $address = trim(mysqli_real_escape_string($this->conn, $data['address'] ?? ''));
        $gender = in_array($data['gender'] ?? '', ['nam', 'nữ', 'khác']) ? $data['gender'] : 'nam';

        // Validate số điện thoại: Chỉ cho phép ký tự số, độ dài 9-11 số
        if ($phone !== '' && !preg_match('/^[0-9]{9,11}$/', $phone)) {
            return ['success' => false, 'error' => 'Số điện thoại không hợp lệ (phải chỉ gồm 9-11 chữ số)'];
        }
        
        // Chuẩn hóa và Validate ngày sinh (Birth Date)
        $birth_date_raw = trim((string)($data['birth_date'] ?? ''));
        if ($birth_date_raw !== '') {
            $birthYear = (int)date('Y', strtotime($birth_date_raw));
            $currentYear = (int)date('Y');
            if ($currentYear - $birthYear < 18) {
                return ['success' => false, 'error' => 'Nhân viên phải từ 18 tuổi trở lên (tính theo năm sinh)'];
            }
        }
        $birth_date = $birth_date_raw !== '' ? "'" . mysqli_real_escape_string($this->conn, $birth_date_raw) . "'" : "NULL";
        
        // Chuẩn hóa ngày vào làm (Backend handles default)
        $hire_date_val = !empty($data['hire_date']) ? $data['hire_date'] : date('Y-m-d');
        $hire_date = "'" . mysqli_real_escape_string($this->conn, $hire_date_val) . "'";

        // 4. Đồng bộ Role và Status
        $role_id = $position_id; // Thống nhất Vai trò = Chức vụ cho 1-4
        $system_status = mysqli_real_escape_string($this->conn, $data['system_status'] ?? 'active');
        $hr_status = 'active';

        // 5. Bảo mật: Mã hóa password bằng BCRYPT (Không dùng MD5)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 6. Thực hiện INSERT
        $query = "INSERT INTO accounts (
                    full_name, email, password, role_id, position_id, 
                    phone, address, birth_date, gender, 
                    hire_date, system_status, hr_status
                  ) VALUES (
                    '$full_name', '$email', '$hashed_password', $role_id, $position_id, 
                    '$phone', '$address', $birth_date, '$gender', 
                    $hire_date, '$system_status', '$hr_status'
                  )";
        
        if (mysqli_query($this->conn, $query)) {
            $account_id = (int)mysqli_insert_id($this->conn);
            
            // 7. Khởi tạo lịch sử chức vụ ban đầu
            $hist = new PositionHistory([
                'account_id' => $account_id,
                'position_id' => $position_id,
                'start_date' => $hire_date_val,
            ]);
            $this->posHistRepo->create($hist);
            
            // 8. Khởi tạo lịch sử trạng thái đầu tiên (Dựa trên hire_date)
            $this->syncStatusHistory($account_id, $hire_date_val);

            return ['success' => true, 'account_id' => $account_id];
        }
        
        // Xử lý lỗi trùng lặp (nếu có race condition)
        if (mysqli_errno($this->conn) == 1062) {
            return ['success' => false, 'error' => 'Email đã tồn tại trong hệ thống.'];
        }

        return ['success' => false, 'error' => 'Không thể tạo nhân viên: ' . mysqli_error($this->conn)];
    }

    /**
     * Cập nhật thông tin nhân viên (Hỗ trợ Partial Update)
     */
    public function updateEmployee(int $account_id, array $data): array {
        $aid = (int)$account_id;
        $updates = [];

        // 1. Kiểm tra tồn tại
        $current = $this->getEmployee($aid);
        if (!$current) {
            return ['success' => false, 'error' => 'Không tìm thấy tài khoản nhân viên.'];
        }

        // 2. Kiểm tra email nếu có thay đổi
        if (!empty($data['email']) && $data['email'] !== $current['email']) {
            $emailRaw = trim((string)$data['email']);
            if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL) || preg_match('/\s/', $emailRaw)) {
                return ['success' => false, 'error' => 'Email không đúng định dạng'];
            }
            $email = mysqli_real_escape_string($this->conn, $emailRaw);
            $check = mysqli_query($this->conn, "SELECT account_id FROM accounts WHERE email = '$email' AND account_id != $aid");
            if (mysqli_num_rows($check) > 0) {
                return ['success' => false, 'error' => 'Email đã tồn tại trong hệ thống.'];
            }
            $updates[] = "email = '$email'";
        }

        // 3. Xử lý các trường thông tin cơ bản (Nếu có trong $data)
        if (isset($data['full_name'])) {
            $val = mysqli_real_escape_string($this->conn, trim($data['full_name']));
            if ($val !== '') $updates[] = "full_name = '$val'";
        }

        if (isset($data['phone'])) {
            $phoneRaw = trim((string)$data['phone']);
            // Validate số điện thoại: Chỉ cho phép ký tự số, độ dài 9-11 số
            if ($phoneRaw !== '' && !preg_match('/^[0-9]{9,11}$/', $phoneRaw)) {
                return ['success' => false, 'error' => 'Số điện thoại không hợp lệ (phải chỉ gồm 9-11 chữ số)'];
            }
            $val = mysqli_real_escape_string($this->conn, $phoneRaw);
            $updates[] = "phone = '$val'";
        }

        if (isset($data['birth_date'])) {
            $birth_date_raw = trim((string)$data['birth_date']);
            if ($birth_date_raw !== '') {
                $birthYear = (int)date('Y', strtotime($birth_date_raw));
                $currentYear = (int)date('Y');
                if ($currentYear - $birthYear < 18) {
                    return ['success' => false, 'error' => 'Nhân viên phải từ 18 tuổi trở lên (tính theo năm sinh)'];
                }
                $updates[] = "birth_date = '" . mysqli_real_escape_string($this->conn, $birth_date_raw) . "'";
            } else {
                $updates[] = "birth_date = NULL";
            }
        }

        if (isset($data['address'])) {
            $val = mysqli_real_escape_string($this->conn, trim($data['address']));
            $updates[] = "address = '$val'";
        }

        if (isset($data['gender'])) {
            $val = in_array($data['gender'], ['nam', 'nữ', 'khác']) ? $data['gender'] : 'nam';
            $updates[] = "gender = '$val'";
        }

        if (isset($data['hire_date'])) {
            $val = !empty($data['hire_date']) ? "'" . mysqli_real_escape_string($this->conn, $data['hire_date']) . "'" : "CURDATE()";
            $updates[] = "hire_date = $val";
        }

        if (isset($data['system_status'])) {
            $val = mysqli_real_escape_string($this->conn, $data['system_status']);
            $updates[] = "system_status = '$val'";
        }

        // 4. Xử lý Chức vụ & Role (Đồng bộ)
        if (!empty($data['position_id'])) {
            $pid = (int)$data['position_id'];
            $updates[] = "position_id = $pid";
            $updates[] = "role_id = $pid"; // Sync Role
        }

        // 5. Bảo mật: Chỉ update password nếu được cung cấp
        if (!empty($data['password'])) {
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
            $updates[] = "password = '$hashed'";
        }

        if (empty($updates)) {
            return ['success' => true, 'message' => 'Không có thông tin nào cần cập nhật.'];
        }

        // 6. Thực thi UPDATE
        $query = "UPDATE accounts SET " . implode(', ', $updates) . " WHERE account_id = $aid";
        
        if (mysqli_query($this->conn, $query)) {
            // Đồng bộ lịch sử trạng thái nếu có thay đổi liên quan
            if (isset($data['hr_status']) || isset($data['system_status'])) {
                $this->syncStatusHistory($aid);
            }
            return ['success' => true];
        }
        
        if (mysqli_errno($this->conn) == 1062) {
            return ['success' => false, 'error' => 'Email đã tồn tại trong hệ thống.'];
        }

        return ['success' => false, 'error' => 'Lỗi cập nhật: ' . mysqli_error($this->conn)];
    }

    /**
     * Xóa nhân viên (soft delete)
     */
    public function deleteEmployee(int $account_id): array {
        $aid = (int)$account_id;
        $query = "UPDATE accounts SET status = 'inactive' WHERE account_id = $aid";
        
        if (mysqli_query($this->conn, $query)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Lỗi xóa nhân viên'];
    }

    /**
     * Đổi chức vụ nhân viên theo mô hình Timeline
     */
    public function changePosition(int $account_id, int $new_position_id, string $start_date = null, string $reason = ''): array {
        $aid = (int)$account_id;
        $new_pos = (int)$new_position_id;
        
        // 1. Ràng buộc: Phải là ngày 01 của tháng
        if (!$start_date || substr($start_date, -2) !== '01') {
            return ['success' => false, 'error' => "Chỉ được thăng chức từ ngày đầu tiên của tháng (Ngày 01)."];
        }

        // 2. Ràng buộc: Phải là tháng trong tương lai (lớn hơn mốc đầu tháng hiện tại)
        $current_month_start = date('Y-m-01');
        if ($start_date <= $current_month_start) {
            return ['success' => false, 'error' => "Chỉ cho phép thăng chức vào các tháng trong tương lai (Tháng tiếp theo trở đi)."];
        }

        mysqli_begin_transaction($this->conn);
        
        try {
            // Lấy toàn bộ lịch sử sắp xếp tăng dần
            $all_history = $this->posHistRepo->getByAccountId($aid, 'ASC');
            
            // 3. Kiểm tra trùng lặp liên tiếp (không được thăng chức vào chức vụ đang có)
            // Lấy chức vụ đang hiệu lực ngay trước ngày bắt đầu mới
            $prev_record = null;
            foreach ($all_history as $h) {
                if ($h->start_date < $start_date) {
                    $prev_record = $h;
                }
            }
            if ($prev_record && (int)$prev_record->position_id === $new_pos) {
                throw new \Exception("Chức vụ mới không được trùng với chức vụ ở mốc thời gian liền trước.");
            }

            // 4. Xử lý Timeline theo quy tắc đơn giản
            // Nếu đã có bản ghi bắt đầu đúng ngày này -> Cập nhật (Sửa lịch hẹn)
            $same_day = null;
            foreach ($all_history as $h) {
                if ($h->start_date === $start_date) {
                    $same_day = $h;
                    break;
                }
            }

            if ($same_day) {
                $same_day->position_id = $new_pos;
                $same_day->reason = $reason;
                $this->posHistRepo->update($same_day);
            } else {
                // Nếu chưa có -> Tạo mốc mới
                
                // Tìm bản ghi kế sau (nếu có) để chốt ngày kết thúc cho bản ghi mới
                $next_record = null;
                foreach ($all_history as $h) {
                    if ($h->start_date > $start_date) {
                        $next_record = $h;
                        break; 
                    }
                }

                // A. Chốt ngày kết thúc cho bản ghi liền trước
                if ($prev_record) {
                    $prev_record->end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
                    $this->posHistRepo->update($prev_record);
                }

                // B. Tạo bản ghi mới
                $new_end = $next_record ? date('Y-m-d', strtotime($next_record->start_date . ' -1 day')) : null;
                $new_hist = new PositionHistory([
                    'account_id' => $aid,
                    'position_id' => $new_pos,
                    'start_date' => $start_date,
                    'end_date' => $new_end,
                    'reason' => $reason
                ]);
                $this->posHistRepo->create($new_hist);
            }

            // 5. Đồng bộ vào bảng accounts
            $this->syncCurrentPosition($aid);
            
            mysqli_commit($this->conn);
            return [
                'success' => true, 
                'message' => "Đã lên lịch thăng chức thành công vào ngày $start_date"
            ];

        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Công cụ dọn dẹp và gộp timeline (Public)
     */
    public function cleanupTimeline(int $account_id): void {
        mysqli_begin_transaction($this->conn);
        $this->mergePositionTimeline($account_id);
        $this->syncCurrentPosition($account_id);
        mysqli_commit($this->conn);
    }

    /**
     * Tự động gộp các bản ghi trùng lặp hoặc nối tiếp cùng chức vụ
     */
    private function mergePositionTimeline(int $account_id): void {
        $history = $this->posHistRepo->getByAccountId($account_id, 'ASC');
        if (count($history) < 2) return;
        
        $changed = false;
        for ($i = 0; $i < count($history) - 1; $i++) {
            $curr = $history[$i];
            $next = $history[$i+1];
            
            // 1. Trùng mốc thời gian -> Giữ lại cái sau, xóa cái trước
            if ($curr->start_date === $next->start_date) {
                $this->posHistRepo->delete($curr->history_id);
                $changed = true;
                break;
            }
            
            // 2. Cùng chức vụ và nối tiếp nhau -> Gộp vào cái trước
            if ($curr->position_id === $next->position_id) {
                $curr->end_date = $next->end_date;
                $this->posHistRepo->update($curr);
                $this->posHistRepo->delete($next->history_id);
                $changed = true;
                break;
            }
            
            // 3. Đảm bảo tính liên tục của Timeline
            $expected_end = date('Y-m-d', strtotime($next->start_date . ' -1 day'));
            if ($curr->end_date !== $expected_end) {
                // Chỉ sửa nếu ngày kết thúc bị sai lệch so với mốc bắt đầu kế tiếp (Xử lý Overlap)
                $curr->end_date = $expected_end;
                $this->posHistRepo->update($curr);
                $changed = true;
                break;
            }
        }
        
        if ($changed) {
            $this->mergePositionTimeline($account_id);
        }
    }

    /**
     * Đồng bộ chức vụ hiện tại từ Timeline vào bảng accounts
     */
    public function syncCurrentPosition(int $account_id): void {
        $aid = (int)$account_id;
        $current = $this->posHistRepo->getByDate($aid, date('Y-m-d'));
        if ($current) {
            $pid = (int)$current->position_id;
            $query = "UPDATE accounts SET position_id = $pid WHERE account_id = $aid";
            mysqli_query($this->conn, $query);
        }
    }

    /**
     * Lấy lịch sử chức vụ của nhân viên
     */
    public function getPositionHistory(int $account_id): array {
        return $this->posHistRepo->getByAccountId($account_id);
    }

    /**
     * Lấy chức vụ hiện tại
     */
    public function getCurrentPosition(int $account_id): ?array {
        $hist = $this->posHistRepo->getCurrentPosition($account_id);
        if (!$hist) return null;
        
        $pos = $this->posRepo->getById($hist->position_id);
        return $pos ? $pos->toArray() : null;
    }

    /**
     * Tính số ngày làm việc active trong tháng
     * Quy tắc: Nếu ngày đó có bất kỳ trạng thái 'active' nào thì tính là 1 ngày công.
     */
    public function getActiveWorkingDaysCount(int $account_id, int $month, int $year): int {
        $aid = (int)$account_id;
        $startOfMonth = sprintf("%04d-%02d-01", $year, $month);
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
        $totalDaysInMonth = (int)date('t', strtotime($startOfMonth));

        $sql = "SELECT status, start_date, end_date 
                FROM employee_status_history 
                WHERE account_id = $aid 
                AND start_date <= '$endOfMonth' 
                AND (end_date IS NULL OR end_date >= '$startOfMonth')";
        $res = mysqli_query($this->conn, $sql);
        $history = mysqli_fetch_all($res, MYSQLI_ASSOC);

        $activeDays = 0;
        $todayTs = strtotime(date('Y-m-d'));

        for ($d = 1; $d <= $totalDaysInMonth; $d++) {
            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $d);
            $ts = strtotime($dateStr);
            
            // Không tính ngày trong tương lai
            if ($ts > $todayTs) continue;

            $isActive = false;
            foreach ($history as $h) {
                $sTs = strtotime($h['start_date']);
                $eTs = $h['end_date'] ? strtotime($h['end_date']) : strtotime('2099-12-31');
                
                if ($ts >= $sTs && $ts <= $eTs && $h['status'] === 'active') {
                    $isActive = true;
                    break;
                }
            }

            if ($isActive) {
                $activeDays++;
            }
        }

        return $activeDays;
    }
}

/**
 * Salary Service - Tính lương
 */
class SalaryService {
    private $salaryRepo;
    private $posRepo;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->salaryRepo = new SalaryRepository($conn);
        $this->posRepo = new PositionRepository($conn);
    }

    /**
     * Tính lương cho nhân viên
     * Formula: (effective_days / 30) * base_salary + allowance + bonus - deductions
     */
    public function calculateSalary(int $account_id, int $month, int $year, 
                                    int $allowance = 0, int $bonus = 0, int $deductions = 0): array {
        $aid = (int)$account_id;
        
        // 1. Lấy thông tin nhân viên & chức vụ
        $empService = new EmployeeService($this->conn);
        $employee = $empService->getEmployee($aid);
        if (!$employee) return ['success' => false, 'error' => 'Không tìm thấy nhân viên'];

        $position_id = (int)$employee['position_id'];
        $position = $this->posRepo->getById($position_id);
        if (!$position) return ['success' => false, 'error' => 'Không tìm thấy chức vụ'];

        $base_salary = $position->base_salary;

        // 2. Lấy dữ liệu lịch sử trạng thái và lịch sử chức vụ (Dùng cho tính lương theo thời gian)
        $startOfMonth = sprintf("%04d-%02d-01", $year, $month);
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
        
        // A. Lịch sử trạng thái
        $status_sql = "SELECT status, start_date, end_date FROM employee_status_history 
                       WHERE account_id = $aid AND start_date <= '$endOfMonth' 
                       AND (end_date IS NULL OR end_date >= '$startOfMonth')";
        $st_res = mysqli_query($this->conn, $status_sql);
        $statusHistory = mysqli_fetch_all($st_res, MYSQLI_ASSOC);

        // B. Lịch sử chức vụ (Lấy base_salary tương ứng từng mốc)
        $pos_sql = "SELECT eph.start_date, eph.end_date, p.base_salary 
                    FROM employee_positions_history eph
                    JOIN positions p ON p.position_id = eph.position_id
                    WHERE eph.account_id = $aid AND eph.start_date <= '$endOfMonth'
                    AND (eph.end_date IS NULL OR eph.end_date >= '$startOfMonth')";
        $ps_res = mysqli_query($this->conn, $pos_sql);
        $posHistory = mysqli_fetch_all($ps_res, MYSQLI_ASSOC);

        $days_in_month = (int)date('t', strtotime($startOfMonth));
        $actual_active_days = 0;
        $total_pro_rated_base = 0;
        $todayTs = strtotime(date('Y-m-d'));

        // 3. Duyệt từng ngày để tính lương gộp (Dựa trên lịch sử)
        for ($d = 1; $d <= $days_in_month; $d++) {
            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $d);
            $ts = strtotime($dateStr);
            if ($ts > $todayTs) continue;

            // Kiểm tra Active (Ưu tiên: chỉ cần ANY record là active)
            $isActive = false;
            foreach ($statusHistory as $sh) {
                $sTs = strtotime($sh['start_date']);
                $eTs = $sh['end_date'] ? strtotime($sh['end_date']) : strtotime('2099-12-31');
                if ($ts >= $sTs && $ts <= $eTs && $sh['status'] === 'active') {
                    $isActive = true;
                    break;
                }
            }

            if ($isActive) {
                $actual_active_days++;
                
                // Tìm lương cơ bản của chức vụ tại ngày này
                $day_salary = $base_salary; // Default fallback
                foreach ($posHistory as $ph) {
                    $sTs = strtotime($ph['start_date']);
                    $eTs = $ph['end_date'] ? strtotime($ph['end_date']) : strtotime('2099-12-31');
                    if ($ts >= $sTs && $ts <= $eTs) {
                        $day_salary = (int)$ph['base_salary'];
                        break;
                    }
                }
                $total_pro_rated_base += ($day_salary / $days_in_month);
            }
        }

        // 4. Chuẩn hóa sang hệ số 30 ngày cho Effective Days (Hiển thị)
        if ($actual_active_days >= $days_in_month) {
            $effective_days = 30.0;
        } else {
            $effective_days = ($actual_active_days / $days_in_month) * 30.0;
        }
        
        $effective_days = round($effective_days, 1);
        $pro_rated_base = (int)round($total_pro_rated_base);
        $total = $pro_rated_base + $allowance + $bonus - $deductions;
        
        return [
            'success' => true,
            'position_id' => $position_id,
            'base_salary' => $base_salary,
            'actual_active_days' => $actual_active_days,
            'effective_days' => $effective_days,
            'pro_rated_base' => $pro_rated_base,
            'allowance' => $allowance,
            'bonus' => $bonus,
            'deductions' => $deductions,
            'total_salary' => $total,
        ];
    }

    /**
     * Lưu bản ghi lương
     */
    public function saveSalary(int $account_id, int $month, int $year, array $data): array {
        // 1. Xác định trạng thái tự động dựa trên thời điểm
        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        
        $isPastMonth = ($year < $currentYear) || ($year == $currentYear && $month < $currentMonth);
        $newStatus = $isPastMonth ? 'finalized' : 'draft';

        // 2. Kiểm tra bản ghi hiện tại
        $existing = $this->salaryRepo->getByAccountAndMonth($account_id, $month, $year);
        
        if ($existing) {
            // Chặn chỉnh sửa nếu đã chốt (Finalized)
            if ($existing->status === 'finalized') {
                return ['success' => false, 'error' => 'Lương tháng này đã được chốt chính thức, không thể chỉnh sửa.'];
            }

            // Update
            $existing->allowance = (int)$data['allowance'];
            $existing->bonus = (int)$data['bonus'];
            $existing->deductions = (int)$data['deductions'];
            $existing->total_salary = $existing->base_salary + $existing->allowance + 
                                     $existing->bonus - $existing->deductions;
            $existing->notes = $data['notes'] ?? null;
            $existing->status = $newStatus; // Chuyển sang finalized nếu tháng đã kết thúc
            
            if ($this->salaryRepo->update($existing)) {
                $msg = ($newStatus === 'finalized') ? 'Chốt lương chính thức thành công' : 'Cập nhật lương tạm tính thành công';
                return ['success' => true, 'message' => $msg];
            }
        } else {
            // Create
            $calc = $this->calculateSalary($account_id, $month, $year, 
                                          (int)$data['allowance'], 
                                          (int)$data['bonus'], 
                                          (int)$data['deductions']);
            
            if (!$calc['success']) {
                return $calc;
            }
            
            $record = new SalaryRecord([
                'account_id' => $account_id,
                'position_id' => $calc['position_id'],
                'salary_month' => $month,
                'salary_year' => $year,
                'base_salary' => $calc['base_salary'],
                'allowance' => $calc['allowance'],
                'bonus' => $calc['bonus'],
                'deductions' => $calc['deductions'],
                'total_salary' => $calc['total_salary'],
                'notes' => $data['notes'] ?? null,
                'status' => $newStatus
            ]);
            
            if ($this->salaryRepo->create($record)) {
                $msg = ($newStatus === 'finalized') ? 'Chốt lương chính thức thành công' : 'Lưu lương tạm tính thành công';
                return ['success' => true, 'message' => $msg];
            }
        }
        
        return ['success' => false, 'error' => 'Lỗi lưu bản ghi lương'];
    }

    /**
     * Lấy bản ghi lương theo tháng/năm
     */
    public function getSalaryRecords(int $year, int $month): array {
        return $this->salaryRepo->getByYearMonth($year, $month);
    }

    /**
     * Thống kê lương theo tháng/năm
     */
    public function getSalaryStats(int $year, int $month): array {
        return $this->salaryRepo->getStatsByYearMonth($year, $month);
    }
}

/**
 * Leave Service - Quản lý nghỉ phép
 */
class LeaveService {
    private $leaveRepo;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->leaveRepo = new LeaveRepository($conn);
    }

    /**
     * Tạo yêu cầu nghỉ phép
     */
    public function requestLeave(int $account_id, array $data): array {
        $fromDate = trim((string)($data['from_date'] ?? ''));
        $toDate = trim((string)($data['to_date'] ?? ''));
        $reason = trim((string)($data['reason'] ?? ''));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $fromValid = \DateTime::createFromFormat('Y-m-d', $fromDate) && \DateTime::createFromFormat('Y-m-d', $fromDate)->format('Y-m-d') === $fromDate;
        $toValid = \DateTime::createFromFormat('Y-m-d', $toDate) && \DateTime::createFromFormat('Y-m-d', $toDate)->format('Y-m-d') === $toDate;

        if (!$fromValid || !$toValid) {
            return ['success' => false, 'error' => 'Ngày nghỉ không hợp lệ. Vui lòng chọn đúng định dạng ngày.'];
        }

        if ($fromDate < $tomorrow) {
            return ['success' => false, 'error' => 'Ngày bắt đầu nghỉ phép phải từ ngày mai trở đi.'];
        }

        if ($toDate < $fromDate) {
            return ['success' => false, 'error' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.'];
        }

        if ($reason === '') {
            return ['success' => false, 'error' => 'Lý do không được để trống.'];
        }

        $leave = new LeaveRequest([
            'account_id' => $account_id,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'leave_type' => $data['leave_type'] ?? 'phép',
            'reason' => $reason,
        ]);
        
        $request_id = $this->leaveRepo->create($leave);
        
        if ($request_id) {
            return [
                'success' => true,
                'request_id' => $request_id,
                'message' => 'Gửi yêu cầu nghỉ phép thành công',
                'days' => $leave->getDaysCount(),
            ];
        }
        
        return ['success' => false, 'error' => 'Lỗi tạo yêu cầu'];
    }

    /**
     * Duyệt yêu cầu nghỉ phép
     */
    public function approveLeave(int $request_id, int $approved_by): array {
        if ($this->leaveRepo->approve($request_id, $approved_by)) {
            return ['success' => true, 'message' => 'Chấp thuận yêu cầu nghỉ phép'];
        }
        return ['success' => false, 'error' => 'Lỗi duyệt yêu cầu'];
    }

    /**
     * Từ chối yêu cầu nghỉ phép
     */
    public function rejectLeave(int $request_id, int $approved_by, string $notes = ''): array {
        if ($this->leaveRepo->reject($request_id, $approved_by, $notes)) {
            return ['success' => true, 'message' => 'Từ chối yêu cầu nghỉ phép'];
        }
        return ['success' => false, 'error' => 'Lỗi từ chối yêu cầu'];
    }

    /**
     * Lấy danh sách yêu cầu chưa duyệt
     */
    public function getPendingLeaves(): array {
        return $this->leaveRepo->getPending();
    }

    /**
     * Lấy lịch sử nghỉ phép của nhân viên
     */
    public function getLeaveHistory(int $account_id): array {
        return $this->leaveRepo->getByAccountId($account_id);
    }
}

/**
 * Resignation Service - Quản lý nghỉ việc
 */
class ResignationService {
    private $resignRepo;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->resignRepo = new ResignationRepository($conn);
    }

    /**
     * Tạo yêu cầu nghỉ việc
     */
    public function requestResignation(int $account_id, array $data): array {
        $effectiveDate = trim((string)($data['effective_date'] ?? ''));
        $reason = trim((string)($data['reason'] ?? ''));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $effectiveValid = \DateTime::createFromFormat('Y-m-d', $effectiveDate)
            && \DateTime::createFromFormat('Y-m-d', $effectiveDate)->format('Y-m-d') === $effectiveDate;

        if (!$effectiveValid || $effectiveDate < $tomorrow) {
            return ['success' => false, 'error' => 'Ngày nghỉ việc phải từ ngày mai trở đi'];
        }

        if ($reason === '') {
            return ['success' => false, 'error' => 'Lý do không được để trống.'];
        }

        $resign = new ResignationRequest([
            'account_id' => $account_id,
            'notice_date' => date('Y-m-d'),
            'effective_date' => $effectiveDate,
            'reason' => $reason,
        ]);
        
        $request_id = $this->resignRepo->create($resign);
        
        if ($request_id) {
            return [
                'success' => true,
                'request_id' => $request_id,
                'message' => 'Gửi yêu cầu nghỉ việc thành công',
                'notice_period' => $resign->getNoticeperiodDays(),
            ];
        }
        
        return ['success' => false, 'error' => 'Lỗi tạo yêu cầu'];
    }

    /**
     * Duyệt yêu cầu nghỉ việc
     */
    public function approveResignation(int $request_id, int $approved_by): array {
        mysqli_begin_transaction($this->conn);
        try {
            // 1. Get request info
            $rid = (int)$request_id;
            $q = mysqli_query($this->conn, "SELECT account_id, effective_date FROM resignation_requests WHERE resignation_request_id = $rid");
            $request = mysqli_fetch_assoc($q);
            
            if (!$request) throw new \Exception("Không tìm thấy yêu cầu nghỉ việc.");
            
            $aid = (int)$request['account_id'];
            $effective_date = $request['effective_date'];
            $app_by = (int)$approved_by;

            // 2. Update request status
            $u1 = "UPDATE resignation_requests SET status = 'chấp thuận', 
                   approved_by = $app_by, approved_at = NOW() 
                   WHERE resignation_request_id = $rid";
            if (!mysqli_query($this->conn, $u1)) throw new \Exception("Lỗi cập nhật yêu cầu.");

            // 3. Update account status and offboarding info
            $u2 = "UPDATE accounts SET 
                   hr_status = 'resigned', 
                   system_status = 'locked', 
                   resignation_date = '$effective_date'
                   WHERE account_id = $aid";
            if (!mysqli_query($this->conn, $u2)) throw new \Exception("Lỗi cập nhật trạng thái nhân viên.");

            // 4. Đồng bộ lịch sử trạng thái (Đóng mốc cũ, mở mốc 'resigned')
            $empService = new EmployeeService($this->conn);
            $empService->syncStatusHistory($aid, $effective_date);

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Đã duyệt nghỉ việc và khóa tài khoản thành công'];

        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Từ chối yêu cầu nghỉ việc
     */
    public function rejectResignation(int $request_id, int $approved_by, string $notes = ''): array {
        if ($this->resignRepo->reject($request_id, $approved_by, $notes)) {
            return ['success' => true, 'message' => 'Từ chối yêu cầu nghỉ việc'];
        }
        return ['success' => false, 'error' => 'Lỗi từ chối yêu cầu'];
    }

    /**
     * Lấy danh sách yêu cầu chưa duyệt
     */
    public function getPendingResignations(): array {
        return $this->resignRepo->getPending();
    }
}

/**
 * HR Statistics Service - Thống kê nhân sự
 */
class HRStatisticsService {
    private $conn;
    private $salaryRepo;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->salaryRepo = new SalaryRepository($conn);
    }

    /**
     * Thống kê nhân sự theo tháng/năm
     */
    public function getMonthlyStats(int $year, int $month): array {
        // Total employees
        $query = "SELECT COUNT(*) as total FROM accounts WHERE status = 'active' AND role_id != 1";
        $result = mysqli_query($this->conn, $query);
        $total_employees = (int)mysqli_fetch_assoc($result)['total'];
        
        // Salary stats
        $salary_stats = $this->salaryRepo->getStatsByYearMonth($year, $month);
        
        // Employee by position
        $query = "SELECT p.position_name, COUNT(a.account_id) as count
                  FROM accounts a
                  LEFT JOIN positions p ON a.position_id = p.position_id
                  WHERE a.status = 'active' AND a.role_id != 1
                  GROUP BY a.position_id
                  ORDER BY count DESC";
        $result = mysqli_query($this->conn, $query);
        $by_position = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $by_position[] = $row;
        }
        
        return [
            'year' => $year,
            'month' => $month,
            'total_employees' => $total_employees,
            'salary_stats' => $salary_stats,
            'by_position' => $by_position,
        ];
    }

    /**
     * Thống kê biến động nhân sự
     */
    public function getEmployeeMovement(int $year): array {
        $query = "SELECT 
                    MONTH(eph.start_date) as month,
                    COUNT(*) as changes
                  FROM employee_positions_history eph
                  WHERE YEAR(eph.start_date) = $year
                  GROUP BY MONTH(eph.start_date)
                  ORDER BY month";
        
        $result = mysqli_query($this->conn, $query);
        $movement = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $movement[] = $row;
        }
        
        return [
            'year' => $year,
            'movement' => $movement,
        ];
    }
}
