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
     */
    public function createEmployee(array $data): array {
        $name = mysqli_real_escape_string($this->conn, $data['full_name']);
        $email = mysqli_real_escape_string($this->conn, $data['email']);
        $pass = md5($data['password'] ?? '123');
        $role_id = (int)($data['role_id'] ?? 3); // sales by default
        $position_id = (int)($data['position_id'] ?? 2);
        
        $query = "INSERT INTO accounts (full_name, email, password, role_id, position_id, status) 
                  VALUES ('$name', '$email', '$pass', $role_id, $position_id, 'active')";
        
        if (mysqli_query($this->conn, $query)) {
            $account_id = (int)mysqli_insert_id($this->conn);
            
            // Create initial position history
            $hist = new PositionHistory([
                'account_id' => $account_id,
                'position_id' => $position_id,
                'start_date' => date('Y-m-d'),
            ]);
            $this->posHistRepo->create($hist);
            
            return ['success' => true, 'account_id' => $account_id];
        }
        
        return ['success' => false, 'error' => 'Không thể tạo nhân viên'];
    }

    /**
     * Cập nhật thông tin nhân viên
     */
    public function updateEmployee(int $account_id, array $data): array {
        $aid = (int)$account_id;
        $name = mysqli_real_escape_string($this->conn, $data['full_name']);
        $email = mysqli_real_escape_string($this->conn, $data['email']);
        
        $query = "UPDATE accounts SET full_name = '$name', email = '$email' WHERE account_id = $aid";
        
        if (mysqli_query($this->conn, $query)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Lỗi cập nhật'];
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
     * Đổi chức vụ nhân viên (với lưu lịch sử)
     */
    public function changePosition(int $account_id, int $new_position_id, string $reason = ''): array {
        $aid = (int)$account_id;
        $new_pos = (int)$new_position_id;
        
        // End current position
        $this->posHistRepo->endCurrentPosition($aid, date('Y-m-d'));
        
        // Create new position history
        $hist = new PositionHistory([
            'account_id' => $aid,
            'position_id' => $new_pos,
            'start_date' => date('Y-m-d'),
            'reason' => $reason,
        ]);
        
        $hist_id = $this->posHistRepo->create($hist);
        
        // Update accounts table
        $query = "UPDATE accounts SET position_id = $new_pos WHERE account_id = $aid";
        mysqli_query($this->conn, $query);
        
        return [
            'success' => true,
            'history_id' => $hist_id,
            'message' => 'Cập nhật chức vụ thành công'
        ];
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
     * Formula: base_salary + allowance + bonus - deductions
     */
    public function calculateSalary(int $account_id, int $month, int $year, 
                                    int $allowance = 0, int $bonus = 0, int $deductions = 0): array {
        // Get current position
        $query = "SELECT position_id FROM accounts WHERE account_id = $account_id";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        if (!$row) {
            return ['success' => false, 'error' => 'Không tìm thấy nhân viên'];
        }
        
        $position_id = (int)$row['position_id'];
        $position = $this->posRepo->getById($position_id);
        
        if (!$position) {
            return ['success' => false, 'error' => 'Không tìm thấy chức vụ'];
        }
        
        $base_salary = $position->base_salary;
        $total = $base_salary + $allowance + $bonus - $deductions;
        
        return [
            'success' => true,
            'position_id' => $position_id,
            'base_salary' => $base_salary,
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
        // Check if already exists
        $existing = $this->salaryRepo->getByAccountAndMonth($account_id, $month, $year);
        
        if ($existing) {
            // Update
            $existing->allowance = (int)$data['allowance'];
            $existing->bonus = (int)$data['bonus'];
            $existing->deductions = (int)$data['deductions'];
            $existing->total_salary = $existing->base_salary + $existing->allowance + 
                                     $existing->bonus - $existing->deductions;
            $existing->notes = $data['notes'] ?? null;
            
            if ($this->salaryRepo->update($existing)) {
                return ['success' => true, 'message' => 'Cập nhật bản ghi lương thành công'];
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
            ]);
            
            if ($this->salaryRepo->create($record)) {
                return ['success' => true, 'message' => 'Lưu bản ghi lương thành công'];
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
        $leave = new LeaveRequest([
            'account_id' => $account_id,
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'leave_type' => $data['leave_type'] ?? 'phép',
            'reason' => $data['reason'],
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
        $resign = new ResignationRequest([
            'account_id' => $account_id,
            'notice_date' => date('Y-m-d'),
            'effective_date' => $data['effective_date'],
            'reason' => $data['reason'],
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
        if ($this->resignRepo->approve($request_id, $approved_by)) {
            return ['success' => true, 'message' => 'Chấp thuận yêu cầu nghỉ việc'];
        }
        return ['success' => false, 'error' => 'Lỗi duyệt yêu cầu'];
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
