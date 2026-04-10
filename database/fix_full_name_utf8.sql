-- Fix corrupted full_name in accounts table - Complete reimport
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Delete ALL corrupted data
DELETE FROM accounts;

-- Re-insert fresh correct data 
INSERT INTO `accounts` VALUES 
(4,'Nhân viên bán hàng 1','sales1@gmail.com','202cb962ac59075b964b07152d234b70',3,'active'),
(5,'Admin hệ thống','admin@gmail.com','202cb962ac59075b964b07152d234b70',1,'active'),
(101,'Nguyễn Thị Hương','huongnt@example.com','202cb962ac59075b964b07152d234b70',2,'active'),
(102,'Trần Văn Đức','ductv@example.com','202cb962ac59075b964b07152d234b70',3,'active'),
(103,'Lê Thị Lan','lanlt@example.com','202cb962ac59075b964b07152d234b70',4,'active'),
(104,'Phạm Tuấn Hưng','hungpt@example.com','202cb962ac59075b964b07152d234b70',2,'active'),
(105,'Đỗ Thúy Ngân','ngandodanh@example.com','202cb962ac59075b964b07152d234b70',3,'active'),
(106,'Hoàng Minh Châu','chauhm@example.com','202cb962ac59075b964b07152d234b70',4,'active'),
(107,'Vũ Thị Thanh Hương','huangvt@example.com','202cb962ac59075b964b07152d234b70',2,'active'),
(108,'Bùi Quốc Anh','anhbq@example.com','202cb962ac59075b964b07152d234b70',3,'active'),
(109,'Ngô Đức Trung','trungnd@example.com','202cb962ac59075b964b07152d234b70',4,'active'),
(110,'Đinh Văn Long','longdv@example.com','202cb962ac59075b964b07152d234b70',2,'active');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify the fix
SELECT account_id, full_name FROM accounts ORDER BY account_id;
