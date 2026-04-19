<?php
/**
 * HR Repository - Database access layer
 */

namespace HR\Repositories;

use HR\Models\{Position, PositionHistory, SalaryRecord, LeaveRequest, ResignationRequest};

/**
 * Position Repository
 */
class PositionRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll(): array {
        $query = "SELECT * FROM positions ORDER BY position_id";
        $result = mysqli_query($this->conn, $query);
        $positions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $positions[] = new Position($row);
        }
        return $positions;
    }

    public function getById(int $position_id): ?Position {
        $query = "SELECT * FROM positions WHERE position_id = $position_id";
        $result = mysqli_query($this->conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            return new Position($row);
        }
        return null;
    }

    public function getActive(): array {
        $query = "SELECT * FROM positions WHERE is_active = 1 ORDER BY position_name";
        $result = mysqli_query($this->conn, $query);
        $positions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $positions[] = new Position($row);
        }
        return $positions;
    }

    public function create(Position $position): int {
        $name = mysqli_real_escape_string($this->conn, $position->position_name);
        $salary = (int)$position->base_salary;
        $desc = mysqli_real_escape_string($this->conn, $position->description ?? '');
        
        $query = "INSERT INTO positions (position_name, base_salary, description) 
                  VALUES ('$name', $salary, '$desc')";
        
        if (mysqli_query($this->conn, $query)) {
            return (int)mysqli_insert_id($this->conn);
        }
        return 0;
    }

    public function update(Position $position): bool {
        $name = mysqli_real_escape_string($this->conn, $position->position_name);
        $salary = (int)$position->base_salary;
        $desc = mysqli_real_escape_string($this->conn, $position->description ?? '');
        
        $query = "UPDATE positions SET position_name = '$name', base_salary = $salary, 
                  description = '$desc' WHERE position_id = {$position->position_id}";
        
        return (bool)mysqli_query($this->conn, $query);
    }

    public function delete(int $position_id): bool {
        $query = "UPDATE positions SET is_active = 0 WHERE position_id = $position_id";
        return (bool)mysqli_query($this->conn, $query);
    }
}

/**
 * Position History Repository
 */
class PositionHistoryRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByAccountId(int $account_id, string $order = 'DESC'): array {
        $query = "SELECT * FROM employee_positions_history WHERE account_id = $account_id 
                  ORDER BY start_date $order";
        $result = mysqli_query($this->conn, $query);
        $histories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $histories[] = new PositionHistory($row);
        }
        return $histories;
    }

    public function getCurrentPosition(int $account_id): ?PositionHistory {
        $query = "SELECT * FROM employee_positions_history WHERE account_id = $account_id 
                  AND (end_date IS NULL OR end_date >= CURRENT_DATE()) 
                  ORDER BY start_date DESC LIMIT 1";
        $result = mysqli_query($this->conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            return new PositionHistory($row);
        }
        return null;
    }

    /**
     * Lấy bản ghi đang có hiệu lực tại một ngày cụ thể
     */
    public function getByDate(int $account_id, string $date): ?PositionHistory {
        $date = mysqli_real_escape_string($this->conn, $date);
        $query = "SELECT * FROM employee_positions_history 
                  WHERE account_id = $account_id 
                  AND start_date <= '$date' 
                  AND (end_date IS NULL OR end_date >= '$date')
                  LIMIT 1";
        $result = mysqli_query($this->conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            return new PositionHistory($row);
        }
        return null;
    }

    /**
     * Lấy bản ghi bắt đầu ngay sau hoặc tại ngày cụ thể
     */
    public function getFollowing(int $account_id, string $date): ?PositionHistory {
        $date = mysqli_real_escape_string($this->conn, $date);
        $query = "SELECT * FROM employee_positions_history 
                  WHERE account_id = $account_id AND start_date > '$date'
                  ORDER BY start_date ASC LIMIT 1";
        $result = mysqli_query($this->conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            return new PositionHistory($row);
        }
        return null;
    }

    public function create(PositionHistory $history): int {
        $aid = (int)$history->account_id;
        $pid = (int)$history->position_id;
        $start = mysqli_real_escape_string($this->conn, $history->start_date);
        $end = $history->end_date ? "'" . mysqli_real_escape_string($this->conn, $history->end_date) . "'" : "NULL";
        $reason = mysqli_real_escape_string($this->conn, $history->reason ?? '');
        
        $query = "INSERT INTO employee_positions_history (account_id, position_id, start_date, end_date, reason) 
                  VALUES ($aid, $pid, '$start', $end, '$reason')";
        
        if (mysqli_query($this->conn, $query)) {
            return (int)mysqli_insert_id($this->conn);
        }
        return 0;
    }

    public function update(PositionHistory $history): bool {
        $hid = (int)$history->history_id;
        $pid = (int)$history->position_id;
        $start = mysqli_real_escape_string($this->conn, $history->start_date);
        $end = $history->end_date ? "'" . mysqli_real_escape_string($this->conn, $history->end_date) . "'" : "NULL";
        $reason = mysqli_real_escape_string($this->conn, $history->reason ?? '');
        
        $query = "UPDATE employee_positions_history SET 
                  position_id = $pid, start_date = '$start', end_date = $end, reason = '$reason' 
                  WHERE history_id = $hid";
        return (bool)mysqli_query($this->conn, $query);
    }

    public function delete(int $history_id): bool {
        $query = "DELETE FROM employee_positions_history WHERE history_id = $history_id";
        return (bool)mysqli_query($this->conn, $query);
    }

    public function endCurrentPosition(int $account_id, string $end_date): bool {
        $end_date = mysqli_real_escape_string($this->conn, $end_date);
        $query = "UPDATE employee_positions_history SET end_date = '$end_date' 
                  WHERE account_id = $account_id AND end_date IS NULL";
        return (bool)mysqli_query($this->conn, $query);
    }
}

/**
 * Salary Record Repository
 */
class SalaryRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByAccountAndMonth(int $account_id, int $month, int $year): ?SalaryRecord {
        $aid = (int)$account_id;
        $m = (int)$month;
        $y = (int)$year;
        
        $query = "SELECT * FROM salary_records WHERE account_id = $aid 
                  AND salary_month = $m AND salary_year = $y";
        
        $result = mysqli_query($this->conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            return new SalaryRecord($row);
        }
        return null;
    }

    public function getByYearMonth(int $year, int $month): array {
        $y = (int)$year;
        $m = (int)$month;
        
        $query = "SELECT sr.*, a.full_name, p.position_name FROM salary_records sr
                  JOIN accounts a ON sr.account_id = a.account_id
                  JOIN positions p ON sr.position_id = p.position_id
                  WHERE sr.salary_year = $y AND sr.salary_month = $m
                  ORDER BY a.full_name";
        
        $result = mysqli_query($this->conn, $query);
        $records = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }
        return $records;
    }

    public function create(SalaryRecord $record): int {
        $aid = (int)$record->account_id;
        $pid = (int)$record->position_id;
        $m = (int)$record->salary_month;
        $y = (int)$record->salary_year;
        $base = (int)$record->base_salary;
        $allow = (int)$record->allowance;
        $bonus = (int)$record->bonus;
        $deduct = (int)$record->deductions;
        $total = (int)$record->total_salary;
        $notes = mysqli_real_escape_string($this->conn, $record->notes ?? '');
        $status = mysqli_real_escape_string($this->conn, $record->status ?? 'draft');
        
        $query = "INSERT INTO salary_records 
                  (account_id, position_id, salary_month, salary_year, base_salary, allowance, bonus, deductions, total_salary, notes, status) 
                  VALUES ($aid, $pid, $m, $y, $base, $allow, $bonus, $deduct, $total, '$notes', '$status')";
        
        if (mysqli_query($this->conn, $query)) {
            return (int)mysqli_insert_id($this->conn);
        }
        return 0;
    }

    public function update(SalaryRecord $record): bool {
        $allow = (int)$record->allowance;
        $bonus = (int)$record->bonus;
        $deduct = (int)$record->deductions;
        $total = (int)$record->total_salary;
        $notes = mysqli_real_escape_string($this->conn, $record->notes ?? '');
        $status = mysqli_real_escape_string($this->conn, $record->status ?? 'draft');
        
        $query = "UPDATE salary_records SET allowance = $allow, bonus = $bonus, 
                  deductions = $deduct, total_salary = $total, notes = '$notes', status = '$status' 
                  WHERE salary_record_id = {$record->salary_record_id}";
        
        return (bool)mysqli_query($this->conn, $query);
    }

    public function getStatsByYearMonth(int $year, int $month): array {
        $y = (int)$year;
        $m = (int)$month;
        
        $query = "SELECT 
                    COUNT(DISTINCT account_id) as total_employees,
                    SUM(total_salary) as total_salary,
                    SUM(bonus) as total_bonus,
                    AVG(total_salary) as avg_salary
                  FROM salary_records 
                  WHERE salary_year = $y AND salary_month = $m";
        
        $result = mysqli_query($this->conn, $query);
        return mysqli_fetch_assoc($result) ?? [];
    }
}

/**
 * Leave Request Repository
 */
class LeaveRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByAccountId(int $account_id, $status = null): array {
        $aid = (int)$account_id;
        $query = "SELECT * FROM leave_requests WHERE account_id = $aid";
        
        if ($status) {
            $s = mysqli_real_escape_string($this->conn, $status);
            $query .= " AND status = '$s'";
        }
        
        $query .= " ORDER BY from_date DESC";
        
        $result = mysqli_query($this->conn, $query);
        $requests = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $requests[] = new LeaveRequest($row);
        }
        return $requests;
    }

    public function getPending(): array {
        $query = "SELECT * FROM leave_requests WHERE status = 'chờ duyệt' 
                  ORDER BY created_at DESC";
        $result = mysqli_query($this->conn, $query);
        $requests = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $requests[] = $row;
        }
        return $requests;
    }

    public function create(LeaveRequest $request): int {
        $aid = (int)$request->account_id;
        $from = $request->from_date;
        $to = $request->to_date;
        $type = mysqli_real_escape_string($this->conn, $request->leave_type);
        $reason = mysqli_real_escape_string($this->conn, $request->reason);
        
        $query = "INSERT INTO leave_requests (account_id, from_date, to_date, leave_type, reason) 
                  VALUES ($aid, '$from', '$to', '$type', '$reason')";
        
        if (mysqli_query($this->conn, $query)) {
            return (int)mysqli_insert_id($this->conn);
        }
        return 0;
    }

    public function approve(int $request_id, int $approved_by): bool {
        $approved_by = (int)$approved_by;
        
        $query = "UPDATE leave_requests SET status = 'chấp thuận', 
                  approved_by = $approved_by, approved_at = NOW() 
                  WHERE leave_request_id = $request_id";
        
        return (bool)mysqli_query($this->conn, $query);
    }

    public function reject(int $request_id, int $approved_by, string $notes = ''): bool {
        $approved_by = (int)$approved_by;
        $notes = mysqli_real_escape_string($this->conn, $notes);
        
        $query = "UPDATE leave_requests SET status = 'từ chối', 
                  approved_by = $approved_by, approved_at = NOW(), notes = '$notes' 
                  WHERE leave_request_id = $request_id";
        
        return (bool)mysqli_query($this->conn, $query);
    }
}

/**
 * Resignation Request Repository
 */
class ResignationRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByAccountId(int $account_id): array {
        $aid = (int)$account_id;
        $query = "SELECT * FROM resignation_requests WHERE account_id = $aid 
                  ORDER BY notice_date DESC";
        
        $result = mysqli_query($this->conn, $query);
        $requests = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $requests[] = new ResignationRequest($row);
        }
        return $requests;
    }

    public function getPending(): array {
        $query = "SELECT * FROM resignation_requests WHERE status = 'chờ duyệt' 
                  ORDER BY created_at DESC";
        
        $result = mysqli_query($this->conn, $query);
        $requests = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $requests[] = $row;
        }
        return $requests;
    }

    public function create(ResignationRequest $request): int {
        $aid = (int)$request->account_id;
        $notice = $request->notice_date;
        $effective = $request->effective_date;
        $reason = mysqli_real_escape_string($this->conn, $request->reason);
        
        $query = "INSERT INTO resignation_requests (account_id, notice_date, effective_date, reason) 
                  VALUES ($aid, '$notice', '$effective', '$reason')";
        
        if (mysqli_query($this->conn, $query)) {
            return (int)mysqli_insert_id($this->conn);
        }
        return 0;
    }

    public function approve(int $request_id, int $approved_by): bool {
        $approved_by = (int)$approved_by;
        
        $query = "UPDATE resignation_requests SET status = 'chấp thuận', 
                  approved_by = $approved_by, approved_at = NOW() 
                  WHERE resignation_request_id = $request_id";
        
        return (bool)mysqli_query($this->conn, $query);
    }

    public function reject(int $request_id, int $approved_by, string $notes = ''): bool {
        $approved_by = (int)$approved_by;
        $notes = mysqli_real_escape_string($this->conn, $notes);
        
        $query = "UPDATE resignation_requests SET status = 'từ chối', 
                  approved_by = $approved_by, approved_at = NOW(), notes = '$notes' 
                  WHERE resignation_request_id = $request_id";
        
        return (bool)mysqli_query($this->conn, $query);
    }
}
