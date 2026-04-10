# HR Management System - API Documentation

## Overview
RESTful API cho hệ thống quản lý nhân sự (HR Management System)
- Base URL: `http://localhost/HTQLDoUong/api/hr/`
- Encoding: UTF-8
- Content-Type: application/json
- Authorization: Require MANAGER (role_id = 2) or ADMIN (role_id = 1)

---

## 1. EMPLOYEE MANAGEMENT (Quản lý nhân viên)

### 1.1 Get All Employees
```
GET /api/hr/employees
```

**Parameters:**
- `role_id` (optional): Filter by role

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "account_id": 4,
      "full_name": "Nhân viên bán hàng 1",
      "email": "sales1@gmail.com",
      "role_name": "sales",
      "position_name": "Nhân viên bán hàng",
      "base_salary": 8000000,
      "status": "active",
      "created_at": "2024-04-02 16:42:35"
    }
  ],
  "count": 1
}
```

---

### 1.2 Get Employee Details
```
GET /api/hr/employees/{id}
```

**Example:**
```
GET /api/hr/employees/4
```

**Response:**
```json
{
  "success": true,
  "data": {
    "account_id": 4,
    "full_name": "Nhân viên bán hàng 1",
    "email": "sales1@gmail.com",
    "role_name": "sales",
    "position_name": "Nhân viên bán hàng",
    "base_salary": 8000000,
    "status": "active"
  }
}
```

---

### 1.3 Create New Employee
```
POST /api/hr/employees
```

**Request Body:**
```json
{
  "full_name": "Nguyễn Văn A",
  "email": "vana@example.com",
  "password": "123456",
  "role_id": 3,
  "position_id": 2
}
```

**Response:**
```json
{
  "success": true,
  "account_id": 111,
  "message": "Tạo nhân viên thành công"
}
```

---

### 1.4 Update Employee
```
PUT /api/hr/employees/{id}
```

**Request Body:**
```json
{
  "full_name": "Nguyễn Văn A Updated",
  "email": "vana.new@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật nhân viên thành công"
}
```

---

### 1.5 Delete Employee (Soft Delete)
```
DELETE /api/hr/employees/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Xóa nhân viên thành công"
}
```

---

### 1.6 Change Employee Position (with History)
```
POST /api/hr/employees/{id}/position
```

**Request Body:**
```json
{
  "position_id": 3,
  "reason": "Thăng chức lên quản lý"
}
```

**Response:**
```json
{
  "success": true,
  "history_id": 25,
  "message": "Cập nhật chức vụ thành công"
}
```

---

### 1.7 Get Position History
```
GET /api/hr/employees/{id}/positions
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "history_id": 1,
      "account_id": 4,
      "position_id": 2,
      "start_date": "2024-01-01",
      "end_date": "2024-04-01",
      "reason": "Thăng chức",
      "created_at": "2024-01-01 00:00:00"
    }
  ]
}
```

---

## 2. SALARY MANAGEMENT (Quản lý lương)

### 2.1 Calculate Salary
```
POST /api/hr/salary/calculate
```

**Request Body:**
```json
{
  "account_id": 4,
  "month": 4,
  "year": 2024,
  "allowance": 500000,
  "bonus": 1000000,
  "deductions": 100000
}
```

**Response:**
```json
{
  "success": true,
  "position_id": 2,
  "base_salary": 8000000,
  "allowance": 500000,
  "bonus": 1000000,
  "deductions": 100000,
  "total_salary": 9400000
}
```

**Formula:** `total_salary = base_salary + allowance + bonus - deductions`

---

### 2.2 Save Salary Record
```
POST /api/hr/salary/save
```

**Request Body:**
```json
{
  "account_id": 4,
  "month": 4,
  "year": 2024,
  "allowance": 500000,
  "bonus": 1000000,
  "deductions": 100000,
  "notes": "Lương tháng 4/2024"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lưu bản ghi lương thành công"
}
```

---

### 2.3 Get Salary Records by Month/Year
```
GET /api/hr/salary/records?month=4&year=2024
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "salary_record_id": 1,
      "account_id": 4,
      "full_name": "Nhân viên bán hàng 1",
      "position_name": "Nhân viên bán hàng",
      "base_salary": 8000000,
      "allowance": 500000,
      "bonus": 1000000,
      "deductions": 100000,
      "total_salary": 9400000,
      "salary_month": 4,
      "salary_year": 2024
    }
  ],
  "month": 4,
  "year": 2024
}
```

---

### 2.4 Get Salary Statistics
```
GET /api/hr/salary/stats?month=4&year=2024
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_employees": 10,
    "total_salary": 94000000,
    "total_bonus": 10000000,
    "avg_salary": 9400000
  },
  "month": 4,
  "year": 2024
}
```

---

## 3. LEAVE MANAGEMENT (Quản lý nghỉ phép)

### 3.1 Request Leave
```
POST /api/hr/leave/request
```

**Request Body:**
```json
{
  "from_date": "2024-04-10",
  "to_date": "2024-04-15",
  "leave_type": "phép",
  "reason": "Nghỉ mát gia đình"
}
```

**Response:**
```json
{
  "success": true,
  "request_id": 1,
  "message": "Gửi yêu cầu nghỉ phép thành công",
  "days": 6
}
```

---

### 3.2 Get Leave History
```
GET /api/hr/leave/{id}/history
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "leave_request_id": 1,
      "account_id": 4,
      "from_date": "2024-04-10",
      "to_date": "2024-04-15",
      "leave_type": "phép",
      "reason": "Nghỉ mát gia đình",
      "status": "chờ duyệt",
      "created_at": "2024-04-02 10:00:00"
    }
  ]
}
```

---

### 3.3 Get Pending Leave Requests
```
GET /api/hr/leave/pending
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "leave_request_id": 1,
      "account_id": 4,
      "full_name": "Nhân viên bán hàng 1",
      "from_date": "2024-04-10",
      "to_date": "2024-04-15",
      "leave_type": "phép",
      "reason": "Nghỉ mát gia đình",
      "status": "chờ duyệt"
    }
  ],
  "count": 1
}
```

---

### 3.4 Approve Leave Request
```
POST /api/hr/leave/{id}/approve
```

**Response:**
```json
{
  "success": true,
  "message": "Chấp thuận yêu cầu nghỉ phép"
}
```

---

### 3.5 Reject Leave Request
```
POST /api/hr/leave/{id}/reject
```

**Request Body:**
```json
{
  "notes": "Lý do từ chối"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Từ chối yêu cầu nghỉ phép"
}
```

---

## 4. RESIGNATION MANAGEMENT (Quản lý nghỉ việc)

### 4.1 Request Resignation
```
POST /api/hr/resignation/request
```

**Request Body:**
```json
{
  "effective_date": "2024-05-02",
  "reason": "Chuyển việc"
}
```

**Response:**
```json
{
  "success": true,
  "request_id": 1,
  "message": "Gửi yêu cầu nghỉ việc thành công",
  "notice_period": 30
}
```

---

### 4.2 Get Pending Resignation Requests
```
GET /api/hr/resignation/pending
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "resignation_request_id": 1,
      "account_id": 4,
      "full_name": "Nhân viên bán hàng 1",
      "notice_date": "2024-04-02",
      "effective_date": "2024-05-02",
      "reason": "Chuyển việc",
      "status": "chờ duyệt"
    }
  ],
  "count": 1
}
```

---

### 4.3 Approve Resignation
```
POST /api/hr/resignation/{id}/approve
```

**Response:**
```json
{
  "success": true,
  "message": "Chấp thuận yêu cầu nghỉ việc"
}
```

---

### 4.4 Reject Resignation
```
POST /api/hr/resignation/{id}/reject
```

**Request Body:**
```json
{
  "notes": "Mong giữ lại nhân viên"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Từ chối yêu cầu nghỉ việc"
}
```

---

## 5. STATISTICS (Thống kê)

### 5.1 Monthly Employee Statistics
```
GET /api/hr/stats/monthly?month=4&year=2024
```

**Response:**
```json
{
  "success": true,
  "data": {
    "year": 2024,
    "month": 4,
    "total_employees": 10,
    "salary_stats": {
      "total_employees": 10,
      "total_salary": 94000000,
      "total_bonus": 10000000,
      "avg_salary": 9400000
    },
    "by_position": [
      {
        "position_name": "Nhân viên bán hàng",
        "count": 5
      },
      {
        "position_name": "Nhân viên kho",
        "count": 3
      }
    ]
  }
}
```

---

### 5.2 Employee Movement Statistics
```
GET /api/hr/stats/movement?year=2024
```

**Response:**
```json
{
  "success": true,
  "data": {
    "year": 2024,
    "movement": [
      {
        "month": 1,
        "changes": 2
      },
      {
        "month": 3,
        "changes": 1
      }
    ]
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Vui lòng đăng nhập"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Bạn không có quyền truy cập module nhân sự. Chỉ MANAGER hoặc ADMIN có thể truy cập."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Không tìm thấy tài nguyên"
}
```

### 400 Bad Request
```json
{
  "success": false,
  "message": "Thiếu tham số hoặc dữ liệu không hợp lệ"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Lỗi server",
  "error": "Error details"
}
```

---

## Authorization

All endpoints require:
- **Login Session**: User must be logged in
- **Role Check**: Only MANAGER (role_id = 2) or ADMIN (role_id = 1) can access HR module

Example session setup (handled by middleware):
```php
$_SESSION['account_id'] = 4;
$_SESSION['role_name'] = 'manager';  // or 'admin'
$_SESSION['full_name'] = 'Manager Name';
```

---

## Example cURL Commands

### Get all employees
```bash
curl -X GET http://localhost/HTQLDoUong/api/hr/employees \
  -H "Cookie: PHPSESSID=your_session_id"
```

### Create new employee
```bash
curl -X POST http://localhost/HTQLDoUong/api/hr/employees \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "full_name": "Nguyễn Văn A",
    "email": "vana@example.com",
    "password": "123456",
    "role_id": 3,
    "position_id": 2
  }'
```

### Change position
```bash
curl -X POST http://localhost/HTQLDoUong/api/hr/employees/4/position \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "position_id": 3,
    "reason": "Thăng chức"
  }'
```

### Calculate salary
```bash
curl -X POST http://localhost/HTQLDoUong/api/hr/salary/calculate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "account_id": 4,
    "month": 4,
    "year": 2024,
    "allowance": 500000,
    "bonus": 1000000,
    "deductions": 100000
  }'
```

### Request leave
```bash
curl -X POST http://localhost/HTQLDoUong/api/hr/leave/request \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "from_date": "2024-04-10",
    "to_date": "2024-04-15",
    "leave_type": "phép",
    "reason": "Nghỉ mát gia đình"
  }'
```

---

## Notes

1. **Date Format**: All dates use `YYYY-MM-DD` format
2. **Money Format**: All salary values are in VND (whole numbers, no decimal places)
3. **Authentication**: Must be logged in and have appropriate role
4. **Soft Delete**: Employees are soft-deleted (status = 'inactive'), not permanently removed
5. **Position History**: Each position change is automatically recorded with timestamp
6. **Salary Calculation**: Uses formula: `total = base + allowance + bonus - deductions`
