-- Fix account full_name values if they were imported with the wrong client charset.
-- Run this only if names are showing as mojibake like: Trß║ºn V─ân ─Éß╗®c

USE eldercoffee_db;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE accounts
SET full_name = 'Nguyễn Thị Hương'
WHERE email = 'huongnt@example.com';

UPDATE accounts
SET full_name = 'Trần Văn Đức'
WHERE email = 'ductv@example.com';

UPDATE accounts
SET full_name = 'Lê Thị Lan'
WHERE email = 'lanlt@example.com';

UPDATE accounts
SET full_name = 'Admin hệ thống'
WHERE email = 'admin@gmail.com';

UPDATE accounts
SET full_name = 'Nhân viên bán hàng 1'
WHERE email = 'sales1@gmail.com';
