-- =============================================================
-- MIGRATION: Đồng bộ role_id ↔ position_id (Phương án 2)
-- Ngày: 2026-04-15
-- Mục tiêu: Đảm bảo position_id = role_id cho tất cả accounts
-- Lý do: Hai bảng roles và positions có ID khớp 1:1:
--   role_id=1 (admin)     ↔ position_id=1 (Quản trị viên)
--   role_id=2 (manager)   ↔ position_id=2 (Quản lý)
--   role_id=3 (sales)     ↔ position_id=3 (Nhân viên bán hàng)
--   role_id=4 (warehouse) ↔ position_id=4 (Nhân viên kho)
-- =============================================================

-- BƯỚC 0: BACKUP trước khi migration
CREATE TABLE IF NOT EXISTS accounts_backup_20260415
    SELECT * FROM accounts;

-- =============================================================
-- BƯỚC 1: Kiểm tra tình trạng hiện tại (chạy để xem, không sửa)
-- =============================================================
SELECT
    account_id,
    full_name,
    role_id,
    position_id,
    CASE WHEN role_id != position_id THEN 'LỆCH ⚠️' ELSE 'OK ✅' END AS trang_thai,
    CASE WHEN position_id IS NULL THEN 'NULL ⚠️' ELSE '' END AS position_null
FROM accounts
WHERE role_id != position_id OR position_id IS NULL
ORDER BY account_id;

-- =============================================================
-- BƯỚC 2: Đồng bộ – accounts có position_id NULL → gán = role_id
-- =============================================================
UPDATE accounts
SET position_id = role_id
WHERE position_id IS NULL;

-- =============================================================
-- BƯỚC 3: Đồng bộ – accounts có position_id != role_id
-- Ưu tiên role_id là chuẩn (quyền hệ thống), cập nhật position_id theo
-- =============================================================
UPDATE accounts
SET position_id = role_id
WHERE position_id != role_id;

-- =============================================================
-- BƯỚC 4: Kiểm tra sau migration (phải trả về 0 row)
-- =============================================================
SELECT COUNT(*) AS so_luong_lech
FROM accounts
WHERE role_id != position_id OR position_id IS NULL;

-- =============================================================
-- BƯỚC 5: Xác nhận tổng hợp
-- =============================================================
SELECT
    a.account_id,
    a.full_name,
    r.name AS role_name,
    r.display_name AS role_display,
    p.position_name,
    p.base_salary,
    a.hr_status
FROM accounts a
JOIN roles r ON r.id = a.role_id
LEFT JOIN positions p ON p.position_id = a.position_id
ORDER BY a.role_id, a.full_name;

-- =============================================================
-- GHI CHÚ:
-- Sau khi chạy script này, hệ thống CODE đã được cập nhật để:
-- 1. chucvu.php   → Khi đổi chức vụ NV: UPDATE cả position_id VÀ role_id
-- 2. accounts.php → positions dropdown loại bỏ position_id=1 (Admin)
-- 3. chucvu.php   → positions list loại bỏ position_id=1 (Admin)
-- 4. chucvu.php   → Guard: chặn gán position_id=1 từ form HR
-- =============================================================
