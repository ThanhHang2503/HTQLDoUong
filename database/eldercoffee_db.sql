-- =============================================
-- ELDERCOFFEE_DB - TẤT CẢ LÀ ĐỒ UỐNG (ĐÃ SỬA LỖI)
-- =============================================

DROP DATABASE IF EXISTS eldercoffee_db;
CREATE DATABASE eldercoffee_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eldercoffee_db;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Table structure for table `roles`
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `roles` WRITE;
INSERT INTO `roles` (`id`, `name`) VALUES
(1,'admin'),
(2,'manager'),
(3,'sales'),
(4,'warehouse');
UNLOCK TABLES;

-- Table structure for table `accounts`
DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
    `role_id` int NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`account_id`),
    UNIQUE KEY `uniq_account_email` (`email`),
    KEY `idx_accounts_role` (`role_id`),
    CONSTRAINT `fk_accounts_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `accounts`
-- Mật khẩu mặc định của tất cả tài khoản là: 123
LOCK TABLES `accounts` WRITE;
INSERT INTO `accounts` VALUES 
(4,'Nhân viên bán hàng 1','sales1@gmail.com','202cb962ac59075b964b07152d234b70',3,'active'),
(5,'Admin hệ thống','admin@gmail.com','202cb962ac59075b964b07152d234b70',1,'active'),
(101,'Nguyễn Thị Hương','huongnt@example.com','202cb962ac59075b964b07152d234b70',2,'active'),
(102,'Trần Văn Đức','ductv@example.com','202cb962ac59075b964b07152d234b70',3,'active'),
(103,'Lê Thị Lan','lanlt@example.com','202cb962ac59075b964b07152d234b70',4,'active')
;
UNLOCK TABLES;

-- Table structure for table `suppliers`
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
    `supplier_id` int NOT NULL AUTO_INCREMENT,
    `supplier_code` varchar(50) NOT NULL,
    `supplier_name` varchar(255) NOT NULL,
    `contact_name` varchar(255) DEFAULT NULL,
    `phone_number` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `address` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`supplier_id`),
    UNIQUE KEY `uniq_supplier_code` (`supplier_code`),
    UNIQUE KEY `uniq_supplier_name` (`supplier_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `suppliers` WRITE;
INSERT INTO `suppliers` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_name`, `phone_number`, `email`, `address`, `status`, `created_at`) VALUES
(1,'SUP-001','Công ty CP Nguyên Liệu Sạch','Nguyễn Văn A','0909123456','contact@nguyenlieusach.vn','Cần Thơ','active','2024-04-02 08:00:00'),
(2,'SUP-002','Tân Phát Beverage','Trần Thị B','0911222333','sales@tanphatbev.vn','TP.HCM','active','2024-04-02 08:00:00'),
(3,'SUP-003','Hương Trà Việt','Lê Văn C','0988777666','info@huongtraviet.vn','Đà Nẵng','inactive','2024-04-02 08:00:00');
UNLOCK TABLES;

-- Table structure for table `category`
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `category` WRITE;
INSERT INTO `category` VALUES 
(1,'Trà Sữa'),(2,'Trà'),(3,'Cà phê'),(4,'Nước ngọt'),
(5,'Sinh tố'),(6,'Nước ép'),(7,'Sữa chua'),(8,'Rượu'),
(9,'Chè'),(10,'Nước Đặc Biệt'),(11,'Đồ Uống Truyền Thống');
UNLOCK TABLES;

-- Table structure for table `customers`
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `customers` WRITE;
INSERT INTO `customers` VALUES 
(99,'Nguyễn Văn A','0123456789'),(100,'Trần Thị B','0987654321'),
(101,'Lê Văn C','036987123'),(102,'Phạm Thị D','0321654987'),
(103,'Hoàng Văn E','0111222333'),(104,'Vũ Thị F','0444555666'),
(105,'Ngô Văn G','0777888999'),(106,'Bùi Thị H','0999888777'),
(107,'Đặng Văn I','0456123123'),(108,'Đỗ Thị K','0987456321'),
(109,'Dương Văn L','0987123456'),(110,'Lý Thị M','0978456321'),
(128,'Trần Đức Hữu','0777866788');
UNLOCK TABLES;

-- Table structure for table `items`
DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category_id` int DEFAULT NULL,
  `description` text,
  `unit_price` decimal(10,2) DEFAULT NULL,
    `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `item_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`item_id`),
  KEY `fk_category_id` (`category_id`),
  CONSTRAINT `fk_category_id` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `items` WRITE;
INSERT INTO `items` VALUES 
(73,'Trà Sữa Trân Châu Đường Nâu',1,'Trà sữa trân châu đường nâu thơm ngon, bán chạy nhất.',45000.00,'2024-04-02 16:42:35','active'),
(74,'Trà Sữa Kem Cheese',1,'Trà sữa kem phô mai béo ngậy.',50000.00,'2024-04-02 16:42:35','active'),
(75,'Trà Gừng Mật Ong',2,'Trà gừng ấm nóng, tốt cho sức khỏe.',35000.00,'2024-04-02 16:42:35','active'),
(76,'Trà Đào Cam Sả',2,'Trà đào cam sả chua ngọt thanh mát.',40000.00,'2024-04-02 16:42:35','active'),
(77,'Cà Phê Sữa Đá',3,'Cà phê sữa đá đậm đà truyền thống.',35000.00,'2024-04-02 16:42:35','active'),
(78,'Cà Phê Đen Nóng',3,'Cà phê đen nguyên chất, đắng mạnh.',40000.00,'2024-04-02 16:42:35','active'),
(79,'Nước Ngọt Coca Cola',4,'Coca Cola có ga lạnh.',12000.00,'2024-04-02 16:42:35','active'),
(95,'Nước Ngọt Có Ga Mix',4,'Nước ngọt có ga các loại (Coca, Pepsi, Sprite).',15000.00,'2024-04-02 16:42:35','active'),
(96,'Sinh Tố Bơ',5,'Sinh tố bơ tươi ngon, béo ngậy.',45000.00,'2024-04-02 16:42:35','active'),
(115,'Sinh Tố Dâu Tây',5,'Sinh tố dâu tây chua ngọt.',40000.00,'2024-04-02 16:42:35','active'),
(116,'Nước Ép Ổi',6,'Nước ép ổi tươi nguyên chất.',30000.00,'2024-04-02 16:42:35','active');
UNLOCK TABLES;

-- Table structure for table `invoices`
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `invoice_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `creation_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `discount` int DEFAULT '0',
  `total` int DEFAULT '0',
  PRIMARY KEY (`invoice_id`),
  KEY `account_id` (`account_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `invoices` WRITE;
INSERT INTO `invoices` VALUES 
(109,101,99,'2024-01-01 01:30:00',0,550000),
(110,102,100,'2024-01-02 02:15:00',0,240000),
(139,5,128,'2024-04-02 16:59:54',12,114400);
UNLOCK TABLES;

-- Table structure for table `invoice_details`
DROP TABLE IF EXISTS `invoice_details`;
CREATE TABLE `invoice_details` (
  `detail_id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int DEFAULT NULL,
  `item_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `invoice_details_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`),
  CONSTRAINT `invoice_details_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `invoice_details` WRITE;
INSERT INTO `invoice_details` VALUES 
(161,139,96,1),(162,139,95,1);
UNLOCK TABLES;

-- Function
DELIMITER ;;
CREATE FUNCTION `count_items_func`() RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE item_count INT;
    SELECT COUNT(*) INTO item_count FROM items;
    RETURN item_count;
END ;;
DELIMITER ;

DELIMITER ;;
CREATE FUNCTION `count_users_func`() RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE user_count INT;
    SELECT COUNT(*) INTO user_count
    FROM accounts a
    JOIN roles r ON r.id = a.role_id
    WHERE r.name IN ('sales', 'warehouse') AND a.status = 'active';
    RETURN user_count;
END ;;
DELIMITER ;

DELIMITER ;;
CREATE FUNCTION `count_customers_func`() RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE customer_count INT;
    SELECT COUNT(*) INTO customer_count FROM customers;
    RETURN customer_count;
END ;;
DELIMITER ;

DELIMITER ;;
CREATE FUNCTION `count_categories_func`() RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE category_count INT;
    SELECT COUNT(*) INTO category_count FROM category;
    RETURN category_count;
END ;;
DELIMITER ;

DELIMITER ;;
CREATE FUNCTION `count_admins_func`() RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE admin_count INT;
    SELECT COUNT(*) INTO admin_count
    FROM accounts a
    JOIN roles r ON r.id = a.role_id
    WHERE r.name = 'admin' AND a.status = 'active';
    RETURN admin_count;
END ;;
DELIMITER ;

DELIMITER ;;
CREATE FUNCTION `count_invoices_func`() RETURNS int
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE invoice_count INT;
    SELECT COUNT(*) INTO invoice_count FROM invoices;
    RETURN invoice_count;
END ;;
DELIMITER ;

DELIMITER ;;
CREATE FUNCTION `sum_money_func`() RETURNS decimal(10,2)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE total1 DECIMAL(10, 2);
    SELECT SUM(total) INTO total1 FROM invoices;
    RETURN total1;
END ;;
DELIMITER ;