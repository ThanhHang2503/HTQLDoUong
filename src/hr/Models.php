<?php
/**
 * HR Models - Data objects representing HR entities
 */

namespace HR\Models;

/**
 * Position (Chức vụ)
 */
class Position {
    public int $position_id;
    public string $position_name;
    public int $base_salary;
    public ?string $description;
    public int $is_active;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = []) {
        $this->position_id = $data['position_id'] ?? 0;
        $this->position_name = $data['position_name'] ?? '';
        $this->base_salary = (int)($data['base_salary'] ?? 0);
        $this->description = $data['description'] ?? null;
        $this->is_active = (int)($data['is_active'] ?? 1);
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function isActive(): bool {
        return (bool)$this->is_active;
    }

    public function toArray(): array {
        return [
            'position_id' => $this->position_id,
            'position_name' => $this->position_name,
            'base_salary' => $this->base_salary,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

/**
 * Employee Position History (Lịch sử chức vụ)
 */
class PositionHistory {
    public int $history_id;
    public int $account_id;
    public int $position_id;
    public string $start_date;
    public ?string $end_date;
    public ?string $reason;
    public string $created_at;

    public function __construct(array $data = []) {
        $this->history_id = $data['history_id'] ?? 0;
        $this->account_id = $data['account_id'] ?? 0;
        $this->position_id = $data['position_id'] ?? 0;
        $this->start_date = $data['start_date'] ?? date('Y-m-d');
        $this->end_date = $data['end_date'] ?? null;
        $this->reason = $data['reason'] ?? null;
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    public function isActive(): bool {
        return is_null($this->end_date);
    }

    public function toArray(): array {
        return [
            'history_id' => $this->history_id,
            'account_id' => $this->account_id,
            'position_id' => $this->position_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}

/**
 * Salary Record (Bản ghi lương)
 */
class SalaryRecord {
    public int $salary_record_id;
    public int $account_id;
    public int $position_id;
    public int $salary_month;
    public int $salary_year;
    public int $base_salary;
    public int $allowance;
    public int $bonus;
    public int $deductions;
    public int $total_salary;
    public ?string $notes;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = []) {
        $this->salary_record_id = $data['salary_record_id'] ?? 0;
        $this->account_id = $data['account_id'] ?? 0;
        $this->position_id = $data['position_id'] ?? 0;
        $this->salary_month = (int)($data['salary_month'] ?? 1);
        $this->salary_year = (int)($data['salary_year'] ?? date('Y'));
        $this->base_salary = (int)($data['base_salary'] ?? 0);
        $this->allowance = (int)($data['allowance'] ?? 0);
        $this->bonus = (int)($data['bonus'] ?? 0);
        $this->deductions = (int)($data['deductions'] ?? 0);
        $this->total_salary = (int)($data['total_salary'] ?? 0);
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function calculateTotalSalary(): int {
        return $this->base_salary + $this->allowance + $this->bonus - $this->deductions;
    }

    public function toArray(): array {
        return [
            'salary_record_id' => $this->salary_record_id,
            'account_id' => $this->account_id,
            'position_id' => $this->position_id,
            'salary_month' => $this->salary_month,
            'salary_year' => $this->salary_year,
            'base_salary' => $this->base_salary,
            'allowance' => $this->allowance,
            'bonus' => $this->bonus,
            'deductions' => $this->deductions,
            'total_salary' => $this->total_salary,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

/**
 * Leave Request (Yêu cầu nghỉ phép)
 */
class LeaveRequest {
    public int $leave_request_id;
    public int $account_id;
    public string $from_date;
    public string $to_date;
    public string $leave_type; // 'phép', 'bệnh', 'không lương'
    public string $reason;
    public string $status; // 'chờ duyệt', 'chấp thuận', 'từ chối', 'hủy'
    public ?int $approved_by;
    public ?string $approved_at;
    public ?string $notes;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = []) {
        $this->leave_request_id = $data['leave_request_id'] ?? 0;
        $this->account_id = $data['account_id'] ?? 0;
        $this->from_date = $data['from_date'] ?? date('Y-m-d');
        $this->to_date = $data['to_date'] ?? date('Y-m-d');
        $this->leave_type = $data['leave_type'] ?? 'phép';
        $this->reason = $data['reason'] ?? '';
        $this->status = $data['status'] ?? 'chờ duyệt';
        $this->approved_by = $data['approved_by'] ?? null;
        $this->approved_at = $data['approved_at'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function getDaysCount(): int {
        $from = new \DateTime($this->from_date);
        $to = new \DateTime($this->to_date);
        return (int)$from->diff($to)->format('%a') + 1;
    }

    public function isPending(): bool {
        return $this->status === 'chờ duyệt';
    }

    public function toArray(): array {
        return [
            'leave_request_id' => $this->leave_request_id,
            'account_id' => $this->account_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'leave_type' => $this->leave_type,
            'reason' => $this->reason,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

/**
 * Resignation Request (Yêu cầu nghỉ việc)
 */
class ResignationRequest {
    public int $resignation_request_id;
    public int $account_id;
    public string $notice_date;
    public string $effective_date;
    public string $reason;
    public string $status; // 'chờ duyệt', 'chấp thuận', 'từ chối', 'hủy'
    public ?int $approved_by;
    public ?string $approved_at;
    public ?string $notes;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = []) {
        $this->resignation_request_id = $data['resignation_request_id'] ?? 0;
        $this->account_id = $data['account_id'] ?? 0;
        $this->notice_date = $data['notice_date'] ?? date('Y-m-d');
        $this->effective_date = $data['effective_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $this->reason = $data['reason'] ?? '';
        $this->status = $data['status'] ?? 'chờ duyệt';
        $this->approved_by = $data['approved_by'] ?? null;
        $this->approved_at = $data['approved_at'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function getNoticeperiodDays(): int {
        $notice = new \DateTime($this->notice_date);
        $effective = new \DateTime($this->effective_date);
        return (int)$notice->diff($effective)->format('%a');
    }

    public function isPending(): bool {
        return $this->status === 'chờ duyệt';
    }

    public function toArray(): array {
        return [
            'resignation_request_id' => $this->resignation_request_id,
            'account_id' => $this->account_id,
            'notice_date' => $this->notice_date,
            'effective_date' => $this->effective_date,
            'reason' => $this->reason,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
