-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: eldercoffee_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `eldercoffee_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `eldercoffee_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `eldercoffee_db`;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 3,
  `position_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('nam','nữ','khác') DEFAULT NULL,
  `hr_status` enum('active','resigned','on_leave') NOT NULL DEFAULT 'active',
  `system_status` enum('active','locked','disabled','pending') NOT NULL DEFAULT 'pending',
  `hire_date` date DEFAULT NULL,
  `resignation_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uniq_email` (`email`),
  KEY `fk_acc_role` (`role_id`),
  KEY `fk_acc_pos` (`position_id`),
  CONSTRAINT `fk_acc_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`),
  CONSTRAINT `fk_acc_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` (`account_id`, `full_name`, `email`, `password`, `role_id`, `position_id`, `phone`, `address`, `birth_date`, `gender`, `hr_status`, `system_status`, `hire_date`, `resignation_date`, `created_at`, `updated_at`) VALUES (1,'Bùi Thị Thanh Hằng','thanhhang@eldercoffee.com','$2y$10$8Et/OI0i5Vja/aKmeWFO9uCCACFAH6vs0eiQT..y9Tq07zUdxn4aa',1,1,'0386601904','85b nguyễn trãi',NULL,'nữ','active','active','2026-01-01',NULL,'2026-04-14 06:50:38','2026-04-18 13:47:56'),(2,'Nguyễn Thị Minh Thư','minhthu@eldercoffee.com','$2y$10$Lwh40J0THfbkZHQyZM4oTuHOD9//kkWyRQkvidOskWYbPjEaQNcGG',2,2,'0386601904','85b nguyễn trãi',NULL,'nữ','active','active','2026-01-01',NULL,'2026-04-14 06:50:38','2026-04-18 13:49:06'),(3,'Lê Thị Ngọc Tuyền','ngoctuyen@eldercoffee.com','$2y$10$fkucYMsuaH/KEqP9R0bqNOvbsepgjxruovumQJK7ATdihZaLnJNtq',4,4,'0386601904','85b nguyễn trãi',NULL,'nữ','active','active','2026-01-01',NULL,'2026-04-14 06:50:38','2026-04-18 14:44:39'),(4,'Mai Thị Mỹ Duyên','myduyen@eldercoffee.com','$2y$10$pdyghmyoWMTSgMsqz7wczOQx.QdKsRRDVRm7CDeF5MD616XtmQvre',3,3,'0386601904','85b nguyễn trãi','2005-06-09','nữ','active','active','2026-01-01',NULL,'2026-04-14 06:50:38','2026-04-18 14:37:18'),(9,'Trần Văn Hùng','vanhung@eldercoffee.com','e10adc3949ba59abbe56e057f20f883e',2,2,'0912345678','123 C??ch M???ng Th??ng 8, Qu???n 10, TP.HCM','1985-05-20','nam','active','active','2026-01-01',NULL,'2026-04-14 10:05:29','2026-04-18 11:55:00'),(10,'Lê Thị Mai','thimai@eldercoffee.com','e10adc3949ba59abbe56e057f20f883e',3,3,'0987654321','6 nguyễn trãi','1998-11-12','nữ','active','active','2026-01-01',NULL,'2026-04-14 10:05:29','2026-04-18 11:21:24'),(11,'Hồ Hoàng Hải','hoanghai@eldercoffee.com','e10adc3949ba59abbe56e057f20f883e',2,2,'0909001122','789 Nguy???n Hu???, Qu???n 1, TP.HCM','1992-02-28','nam','active','active','2026-01-01',NULL,'2026-04-14 10:05:29','2026-04-18 11:13:57'),(12,'Phạm Minh Châu','minhchau@eldercoffee.com','e10adc3949ba59abbe56e057f20f883e',3,3,'0933445566','12 V?? V??n Ki???t, Qu???n 5, TP.HCM','2000-01-05','','resigned','locked','2026-01-01',NULL,'2026-04-14 10:05:29','2026-04-18 11:13:57'),(13,'Nguyễn Cẩm Tú','camtu@eldercoffee.com','$2y$10$GD9Py/JDV4RKSLzv5dVzD.HmD9fnpBJFGxvQlgMPLsGCIagkPK6IC',2,2,'0944556677','99 B??nh Th???nh, TP.HCM','1990-07-15','','resigned','locked','2026-01-01',NULL,'2026-04-14 10:05:29','2026-04-18 14:04:51');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` (`category_id`, `category_name`, `created_at`) VALUES (1,'Cà phê','2026-04-17 12:00:50'),(2,'Trà sữa','2026-04-17 12:00:51'),(3,'Trà trái cây','2026-04-17 12:00:51'),(4,'Đá xay','2026-04-17 12:00:51'),(5,'Bánh ngọt','2026-04-17 12:00:51'),(6,'nước ngọt các loại','2026-04-17 14:02:12');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(150) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `uniq_customer_phone` (`phone_number`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` (`customer_id`, `customer_name`, `phone_number`, `email`, `address`, `created_at`) VALUES (1,'Nguyễn Văn An','0381112223','an.nv@gmail.com','P12, Q.Gò Vấp, TP.HCM','2026-04-17 12:00:51'),(2,'Lê Thị Mai','0382223334','mai.lt@gmail.com','Q. Bình Thạnh, TP.HCM','2026-04-17 12:00:51'),(3,'Trần Quang Hải','0383334445','hai.tq@gmail.com','Q. Phú Nhuận, TP.HCM','2026-04-17 12:00:51'),(4,'Phạm Thu Thảo','0384445556','thao.pt@gmail.com','Q.3, TP.HCM','2026-04-17 12:00:51'),(5,'Đỗ Minh Đức','0385556667','duc.dm@gmail.com','Q. Tân Bình, TP.HCM','2026-04-17 12:00:51'),(6,'Ngô Gia Bảo','0386667778','bao.ng@gmail.com','Q.7, TP.HCM','2026-04-17 12:00:51'),(7,'Vũ Thanh Hằng','0387778889','hang.vt@gmail.com','Q.1, TP.HCM','2026-04-17 12:00:51'),(8,'Lý Tiểu Long','0388889990','long.ly@gmail.com','Q. Thủ Đức, TP.HCM','2026-04-17 12:00:51'),(9,'Phan Thị Ánh','0389990001','anh.pt@gmail.com','Q.2, TP.HCM','2026-04-17 12:00:51'),(10,'Bùi Tiến Dũng','0380001112','dung.bt@gmail.com','Q. Hóc Môn, TP.HCM','2026-04-17 12:00:51'),(13,'Hằng đẹp gái','0386601904','thanhhang250305@gmail.com','85b nguyễn trãi','2026-04-18 14:26:25');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_positions_history`
--

DROP TABLE IF EXISTS `employee_positions_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_positions_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `fk_eph_acc` (`account_id`),
  KEY `fk_eph_pos` (`position_id`),
  CONSTRAINT `fk_eph_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_eph_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_positions_history`
--

LOCK TABLES `employee_positions_history` WRITE;
/*!40000 ALTER TABLE `employee_positions_history` DISABLE KEYS */;
INSERT INTO `employee_positions_history` (`history_id`, `account_id`, `position_id`, `start_date`, `end_date`, `reason`, `created_by`, `created_at`) VALUES (1,1,1,'2026-01-01',NULL,'Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(2,2,2,'2026-01-01',NULL,'Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(3,3,4,'2026-01-01',NULL,'Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(4,4,3,'2026-01-01',NULL,'Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(6,10,3,'2026-01-01','2026-04-30','Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(7,11,2,'2026-01-01',NULL,'Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(8,12,3,'2026-01-01',NULL,'Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(9,13,2,'2026-01-01','2026-07-31','Ngày vào làm (Khởi tạo hệ thống)',NULL,'2026-04-18 11:39:33'),(27,13,3,'2026-08-01',NULL,'Test successful promotion',NULL,'2026-04-18 12:34:31'),(28,10,4,'2026-05-01',NULL,'',NULL,'2026-04-18 12:38:17');
/*!40000 ALTER TABLE `employee_positions_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_export_items`
--

DROP TABLE IF EXISTS `inventory_export_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_export_items` (
  `export_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `export_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  PRIMARY KEY (`export_item_id`),
  KEY `fk_ei_export` (`export_id`),
  KEY `fk_ei_item` (`item_id`),
  CONSTRAINT `fk_export_items_export` FOREIGN KEY (`export_id`) REFERENCES `inventory_exports` (`export_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_export_items_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_export_items`
--

LOCK TABLES `inventory_export_items` WRITE;
/*!40000 ALTER TABLE `inventory_export_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_export_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_exports`
--

DROP TABLE IF EXISTS `inventory_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_exports` (
  `export_id` int(11) NOT NULL AUTO_INCREMENT,
  `export_code` varchar(50) NOT NULL,
  `export_date` date NOT NULL,
  `reason` varchar(200) DEFAULT 'bán hàng',
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`export_id`),
  UNIQUE KEY `uniq_export_code` (`export_code`),
  KEY `idx_exports_date` (`export_date`),
  KEY `fk_exports_by` (`created_by`),
  CONSTRAINT `fk_exports_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_exports`
--

LOCK TABLES `inventory_exports` WRITE;
/*!40000 ALTER TABLE `inventory_exports` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_exports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_receipt_items`
--

DROP TABLE IF EXISTS `inventory_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_receipt_items` (
  `receipt_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `remaining_qty` int(11) NOT NULL DEFAULT 0,
  `import_price` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  PRIMARY KEY (`receipt_item_id`),
  KEY `fk_ri_receipt` (`receipt_id`),
  KEY `fk_ri_item` (`item_id`),
  CONSTRAINT `fk_ri_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_ri_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `inventory_receipts` (`receipt_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_receipt_items`
--

LOCK TABLES `inventory_receipt_items` WRITE;
/*!40000 ALTER TABLE `inventory_receipt_items` DISABLE KEYS */;
INSERT INTO `inventory_receipt_items` (`receipt_item_id`, `receipt_id`, `item_id`, `quantity`, `remaining_qty`, `import_price`, `unit_price`, `line_total`) VALUES (1,1,3,79,79,15000.00,35000.00,1185000.00),(2,1,2,85,85,7000.00,30000.00,595000.00),(3,1,5,69,69,15000.00,35000.00,1035000.00),(4,1,10,66,66,25000.00,55000.00,1650000.00),(5,2,9,89,89,20000.00,55000.00,1780000.00),(6,2,12,91,91,28000.00,60000.00,2548000.00),(7,2,2,99,99,7000.00,30000.00,693000.00),(8,2,4,60,60,16000.00,40000.00,960000.00),(9,3,12,54,54,28000.00,60000.00,1512000.00),(10,3,1,61,61,5000.00,25000.00,305000.00),(11,4,12,76,76,28000.00,60000.00,2128000.00),(12,4,5,77,77,15000.00,35000.00,1155000.00),(13,4,9,65,65,20000.00,55000.00,1300000.00),(14,5,5,55,55,15000.00,35000.00,825000.00),(15,5,7,62,62,12000.00,45000.00,744000.00),(16,6,2,59,59,7000.00,30000.00,413000.00),(17,6,8,93,93,18000.00,50000.00,1674000.00),(18,7,11,81,81,15000.00,30000.00,1215000.00),(19,7,3,97,97,15000.00,35000.00,1455000.00),(20,7,10,87,87,25000.00,55000.00,2175000.00),(21,7,12,70,70,28000.00,60000.00,1960000.00),(22,8,7,92,92,12000.00,45000.00,1104000.00),(23,8,12,95,95,28000.00,60000.00,2660000.00),(24,8,8,80,80,18000.00,50000.00,1440000.00),(25,9,5,54,54,15000.00,35000.00,810000.00),(26,9,12,50,50,28000.00,60000.00,1400000.00),(27,9,4,61,61,16000.00,40000.00,976000.00),(28,9,3,88,88,15000.00,35000.00,1320000.00),(29,10,6,80,80,12000.00,45000.00,960000.00),(30,10,10,54,54,25000.00,55000.00,1350000.00),(31,11,1,69,69,5000.00,25000.00,345000.00),(32,11,8,45,45,18000.00,50000.00,810000.00),(33,11,12,60,60,28000.00,60000.00,1680000.00),(34,11,4,33,33,16000.00,40000.00,528000.00),(35,11,9,71,71,20000.00,55000.00,1420000.00),(36,12,3,55,55,15000.00,35000.00,825000.00),(37,12,6,60,60,12000.00,45000.00,720000.00),(38,12,7,38,38,12000.00,45000.00,456000.00),(39,12,10,31,31,25000.00,55000.00,775000.00),(40,13,3,35,35,15000.00,35000.00,525000.00),(41,13,11,74,74,15000.00,30000.00,1110000.00),(42,13,8,65,65,18000.00,50000.00,1170000.00),(43,13,5,68,68,15000.00,35000.00,1020000.00),(44,14,7,76,76,12000.00,45000.00,912000.00),(45,14,10,40,40,25000.00,55000.00,1000000.00),(46,15,6,47,47,12000.00,45000.00,564000.00),(47,15,7,31,31,12000.00,45000.00,372000.00),(48,15,3,73,73,15000.00,35000.00,1095000.00),(49,15,10,61,61,25000.00,55000.00,1525000.00),(50,15,4,59,59,16000.00,40000.00,944000.00),(51,16,2,43,43,7000.00,30000.00,301000.00),(52,16,3,46,46,15000.00,35000.00,690000.00),(53,16,1,38,38,5000.00,25000.00,190000.00),(54,17,12,64,64,28000.00,60000.00,1792000.00),(55,17,1,72,72,5000.00,25000.00,360000.00),(56,17,8,32,32,18000.00,50000.00,576000.00),(57,17,6,74,74,12000.00,45000.00,888000.00),(58,18,9,72,72,20000.00,55000.00,1440000.00),(59,18,5,59,59,15000.00,35000.00,885000.00),(60,18,11,45,45,15000.00,30000.00,675000.00),(61,19,2,58,58,7000.00,30000.00,406000.00),(62,19,12,66,66,28000.00,60000.00,1848000.00),(63,20,3,78,78,15000.00,35000.00,1170000.00),(64,20,6,60,60,12000.00,45000.00,720000.00),(65,20,5,69,69,15000.00,35000.00,1035000.00),(66,20,8,33,33,18000.00,50000.00,594000.00),(67,20,4,45,45,16000.00,40000.00,720000.00),(68,21,5,33,33,15000.00,35000.00,495000.00),(69,21,12,70,70,28000.00,60000.00,1960000.00),(70,21,2,55,55,7000.00,30000.00,385000.00),(71,22,1,69,69,5000.00,25000.00,345000.00),(72,22,7,62,62,12000.00,45000.00,744000.00),(73,23,1,71,71,5000.00,25000.00,355000.00),(74,23,7,63,63,12000.00,45000.00,756000.00),(75,23,8,56,56,18000.00,50000.00,1008000.00),(76,24,13,51,0,10000.00,0.00,510000.00),(77,25,14,1,0,3000.00,0.00,3000.00),(78,26,16,10,0,10000.00,0.00,100000.00),(79,26,14,6,0,3000.00,0.00,18000.00);
/*!40000 ALTER TABLE `inventory_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_receipts`
--

DROP TABLE IF EXISTS `inventory_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_receipts` (
  `receipt_id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `import_date` date NOT NULL,
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `uniq_receipt_code` (`receipt_code`),
  KEY `idx_receipt_date` (`import_date`),
  KEY `fk_receipt_sup` (`supplier_id`),
  KEY `fk_receipt_by` (`created_by`),
  CONSTRAINT `fk_receipt_by` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_receipt_sup` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_receipts`
--

LOCK TABLES `inventory_receipts` WRITE;
/*!40000 ALTER TABLE `inventory_receipts` DISABLE KEYS */;
INSERT INTO `inventory_receipts` (`receipt_id`, `receipt_code`, `supplier_id`, `import_date`, `total_value`, `note`, `status`, `created_by`, `created_at`) VALUES (1,'PN2601001',1,'2026-01-19',4465000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(2,'PN2601002',5,'2026-01-18',5981000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(3,'PN2602001',3,'2026-02-27',1817000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(4,'PN2602002',2,'2026-02-24',4583000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(5,'PN2602003',5,'2026-02-13',1569000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(6,'PN2603001',5,'2026-03-23',2087000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(7,'PN2603002',4,'2026-03-08',6805000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(8,'PN2603003',1,'2026-03-15',5204000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(9,'PN2603004',4,'2026-03-26',4506000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(10,'PN2603005',2,'2026-03-13',2310000.00,NULL,'completed',1,'2026-04-17 12:00:51'),(11,'PN2601006',5,'2026-01-03',4783000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(12,'PN2601007',2,'2026-01-03',2776000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(13,'PN2601008',2,'2026-01-07',3825000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(14,'PN2601009',3,'2026-01-08',1912000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(15,'PN2601010',2,'2026-01-17',4500000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(16,'PN2604011',3,'2026-04-01',1181000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(17,'PN2604012',4,'2026-04-05',3616000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(18,'PN2604013',3,'2026-04-07',3000000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(19,'PN2604014',1,'2026-04-14',2254000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(20,'PN2604015',4,'2026-04-15',4239000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(21,'PN2604016',1,'2026-04-17',2840000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(22,'PN2604017',2,'2026-04-23',1089000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(23,'PN2604018',2,'2026-04-27',2119000.00,NULL,'completed',13,'2026-04-17 12:10:36'),(24,'PN-20260417-143204',6,'2026-04-17',510000.00,'','completed',3,'2026-04-17 12:32:04'),(25,'PN-20260417-162224',6,'2026-03-26',3000.00,'','completed',3,'2026-04-17 14:22:24'),(26,'PN-20260417-162416',6,'2026-04-17',118000.00,'','completed',3,'2026-04-17 14:24:16');
/*!40000 ALTER TABLE `inventory_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_details`
--

DROP TABLE IF EXISTS `invoice_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`detail_id`),
  KEY `fk_det_inv` (`invoice_id`),
  KEY `fk_det_item` (`item_id`),
  CONSTRAINT `fk_det_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_details`
--

LOCK TABLES `invoice_details` WRITE;
/*!40000 ALTER TABLE `invoice_details` DISABLE KEYS */;
INSERT INTO `invoice_details` (`detail_id`, `invoice_id`, `item_id`, `quantity`, `unit_price`) VALUES (1,1,7,4,45000),(2,2,9,3,55000),(3,3,1,1,25000),(4,4,12,2,60000),(5,5,11,4,30000),(6,5,12,2,60000),(7,6,7,2,45000),(8,7,2,4,30000),(9,7,8,5,50000),(10,8,4,2,40000),(11,8,12,4,60000),(12,9,4,2,40000),(13,10,9,5,55000),(14,11,2,5,30000),(15,11,12,5,60000),(16,11,11,4,30000),(17,12,3,2,35000),(18,12,7,2,45000),(19,12,10,5,55000),(20,13,1,1,25000),(21,13,2,1,30000),(22,14,6,2,45000),(23,14,10,1,55000),(24,14,7,4,45000),(25,15,4,1,40000),(26,15,7,3,45000),(27,15,1,3,25000),(28,16,10,2,55000),(29,16,11,2,30000),(30,16,1,3,25000),(31,17,4,3,40000),(32,17,2,2,30000),(33,17,3,2,35000),(34,18,12,3,60000),(35,18,10,3,55000),(36,18,11,1,30000),(37,19,8,3,50000),(38,19,2,2,30000),(39,19,11,1,30000),(40,20,4,4,40000),(41,20,10,2,55000),(42,21,2,2,30000),(43,21,10,4,55000),(44,21,4,2,40000),(45,21,5,4,35000),(46,22,4,5,40000),(47,22,6,1,45000),(48,23,10,4,55000),(49,23,11,1,30000),(50,23,2,5,30000),(51,23,1,1,25000),(52,24,1,4,25000),(53,24,6,5,45000),(54,24,5,3,35000),(55,25,10,3,55000),(56,25,6,4,45000),(57,25,8,3,50000),(58,26,1,3,25000),(59,26,12,1,60000),(60,26,7,4,45000),(61,27,4,3,40000),(62,28,6,2,45000),(63,28,5,4,35000),(64,29,1,5,25000),(65,29,4,5,40000),(66,29,12,3,60000),(67,29,3,4,35000),(68,30,12,3,60000),(69,30,8,1,50000),(70,30,3,5,35000),(71,31,1,2,25000),(72,31,9,5,55000),(73,32,4,5,40000),(74,32,5,3,35000),(75,32,1,2,25000),(76,32,10,5,55000),(77,33,4,1,40000),(78,33,9,1,55000),(79,33,6,1,45000),(80,33,5,1,35000),(81,33,3,2,35000),(82,33,7,1,45000),(83,33,10,1,55000),(84,33,1,200,25000),(85,33,8,1,50000),(86,34,6,1,45000),(87,34,7,1,45000),(88,34,10,1,55000),(89,34,5,8,35000),(90,34,3,1,35000),(91,34,12,1,60000),(92,34,8,1,50000),(93,34,2,4,30000),(94,34,1,8,25000),(95,35,12,14,60000),(96,35,3,13,35000),(97,35,5,10,35000),(98,35,7,8,45000),(99,35,11,4,30000),(100,35,1,4,25000),(101,35,13,2,25000),(102,35,4,5,40000),(103,36,3,519,35000),(104,37,6,8,45000),(105,37,7,329,45000),(106,37,8,1,50000),(107,37,9,2,55000),(108,37,11,2,30000),(109,37,10,2,55000),(110,37,12,1,60000);
/*!40000 ALTER TABLE `invoice_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `discount` decimal(10,0) NOT NULL DEFAULT 0,
  `total` decimal(12,0) NOT NULL DEFAULT 0,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `creation_time` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`invoice_id`),
  KEY `fk_inv_acc` (`account_id`),
  KEY `fk_inv_cust` (`customer_id`),
  KEY `idx_inv_time` (`creation_time`),
  CONSTRAINT `fk_inv_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_inv_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` (`invoice_id`, `account_id`, `customer_id`, `discount`, `total`, `status`, `notes`, `creation_time`) VALUES (1,1,9,0,180000,'completed',NULL,'2026-02-02 06:00:00'),(2,1,4,0,165000,'completed',NULL,'2026-02-04 14:52:00'),(3,1,3,0,25000,'completed',NULL,'2026-02-05 09:35:00'),(4,1,10,0,120000,'completed',NULL,'2026-02-10 09:53:00'),(5,1,7,0,240000,'completed',NULL,'2026-02-14 06:31:00'),(6,1,7,0,90000,'completed',NULL,'2026-02-20 12:04:00'),(7,1,5,0,370000,'completed',NULL,'2026-02-25 13:02:00'),(8,1,5,0,320000,'completed',NULL,'2026-02-27 10:38:00'),(9,1,1,0,80000,'completed',NULL,'2026-03-02 10:43:00'),(10,1,9,0,275000,'completed',NULL,'2026-03-11 02:54:00'),(11,1,7,0,570000,'completed',NULL,'2026-03-13 01:51:00'),(12,1,5,0,435000,'completed',NULL,'2026-03-15 14:34:00'),(13,1,8,0,55000,'completed',NULL,'2026-03-17 05:56:00'),(14,1,5,0,325000,'completed',NULL,'2026-03-19 13:04:00'),(15,1,9,0,250000,'completed',NULL,'2026-03-27 08:36:00'),(16,13,4,0,245000,'completed',NULL,'2026-01-09 11:14:00'),(17,13,10,0,250000,'completed',NULL,'2026-01-10 09:34:00'),(18,13,8,0,375000,'completed',NULL,'2026-01-20 04:01:00'),(19,13,6,0,240000,'completed',NULL,'2026-01-22 01:32:00'),(20,13,8,0,270000,'completed',NULL,'2026-01-23 10:13:00'),(21,13,3,0,500000,'completed',NULL,'2026-01-24 03:10:00'),(22,13,4,0,245000,'completed',NULL,'2026-01-24 12:51:00'),(23,13,4,0,425000,'completed',NULL,'2026-04-03 06:38:00'),(24,13,8,0,430000,'completed',NULL,'2026-04-04 03:58:00'),(25,13,8,0,495000,'completed',NULL,'2026-04-04 10:08:00'),(26,13,1,0,315000,'completed',NULL,'2026-04-06 09:28:00'),(27,13,8,0,120000,'completed',NULL,'2026-04-08 04:40:00'),(28,13,3,0,230000,'completed',NULL,'2026-04-19 05:55:00'),(29,13,8,0,645000,'completed',NULL,'2026-04-23 07:15:00'),(30,13,10,0,405000,'completed',NULL,'2026-04-24 01:36:00'),(31,13,10,0,325000,'completed',NULL,'2026-04-27 04:00:00'),(32,13,8,0,630000,'completed',NULL,'2026-04-27 11:37:00'),(33,4,3,0,5395000,'completed',NULL,'2026-04-17 12:53:56'),(34,4,5,0,890000,'completed',NULL,'2026-04-17 13:12:41'),(35,4,7,0,2475000,'completed',NULL,'2026-04-17 13:18:50'),(36,4,10,0,18165000,'completed',NULL,'2026-04-17 13:19:31'),(37,4,13,0,15555000,'completed',NULL,'2026-04-18 14:38:14');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(20) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit_price` decimal(10,0) NOT NULL DEFAULT 0,
  `purchase_price` decimal(10,0) NOT NULL DEFAULT 0,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `item_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sale_status` enum('selling','stopped') NOT NULL DEFAULT 'selling',
  `item_image` varchar(255) DEFAULT NULL,
  `added_date` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `uq_item_code` (`item_code`),
  KEY `fk_item_cat` (`category_id`),
  CONSTRAINT `fk_item_cat` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` (`item_id`, `item_code`, `item_name`, `category_id`, `description`, `unit_price`, `purchase_price`, `stock_quantity`, `item_status`, `sale_status`, `item_image`, `added_date`, `updated_at`) VALUES (1,'ITM-00001','Cà phê đen đá',1,'Cà phê rang xay nguyên chất, đậm đà.',25000,5000,143,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-17 13:18:50'),(2,'ITM-00002','Cà phê sữa đá',1,'Cà phê quyện cùng sữa đặc cao cấp.',30000,7000,374,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-17 13:12:41'),(3,'ITM-00003','Trà sữa truyền thống',2,'Vị trà đậm cùng sữa béo ngậy.',35000,15000,3,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-17 13:19:31'),(4,'ITM-00004','Trà sữa Thái xanh',2,'Trà Thái xanh mát lạnh kèm trân châu.',40000,16000,220,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-17 13:18:50'),(5,'ITM-00005','Hồng trà sữa',2,'Hồng trà thanh khiết pha cùng sữa.',35000,15000,451,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-17 13:18:50'),(6,'ITM-00006','Trà đào cam sả',3,'Đào miếng giòn rụm cùng hương cam sả.',45000,12000,297,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(7,'ITM-00007','Trà vải lài',3,'Hương vải thơm nồng cùng cốt trà lài.',45000,12000,66,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(8,'ITM-00008','Cookie đá xay',4,'Bánh oreo xay cùng sữa và kem béo.',50000,18000,389,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(9,'ITM-00009','Matcha đá xay',4,'Bột matcha Nhật Bản nguyên chất.',55000,20000,281,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(10,'ITM-00010','Bánh Tiramisu',5,'Cốt bánh mềm mịn, vị cà phê đắng nhẹ.',55000,25000,306,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(11,'ITM-00011','Bánh Croissant',5,'Bánh sừng bò ngàn lớp thơm mùi bơ.',30000,15000,181,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(12,'ITM-00012','Bánh Phô mai nướng',5,'Vị phô mai béo ngậy tan trong miệng.',60000,28000,657,'active','selling',NULL,'2026-04-17 12:00:51','2026-04-18 14:38:14'),(13,'ITM-00013','Hồng trà thái xanh',2,'Hồng trà thái xanh',25000,10000,49,'active','selling',NULL,'2026-04-17 12:30:28','2026-04-17 13:18:50'),(14,'ITM-00014','trà tắc',3,'trà tắc giải nhiệt',10000,3000,7,'active','selling','img/14.jpg','2026-04-17 13:59:28','2026-04-17 14:24:16'),(16,'ITM-00015','sting',6,'nước ngọt có gas',12000,10000,10,'active','selling','img/16.jpg','2026-04-17 14:23:20','2026-04-17 14:24:16');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_items_before_insert
BEFORE INSERT ON items
FOR EACH ROW
BEGIN
    DECLARE next_code VARCHAR(20);
    DECLARE max_num  INT DEFAULT 0;
    SELECT COALESCE(MAX(CAST(SUBSTRING(item_code, 5) AS UNSIGNED)), 0)
      INTO max_num
      FROM items
     WHERE item_code REGEXP '^ITM-[0-9]+$';
    SET next_code = CONCAT('ITM-', LPAD(max_num + 1, 5, '0'));
    SET NEW.item_code = next_code;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `leave_request_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `leave_type` enum('phép','bệnh','thai sản','không lương','khác') NOT NULL DEFAULT 'phép',
  `reason` text NOT NULL,
  `status` enum('chờ duyệt','chấp thuận','từ chối','hủy') NOT NULL DEFAULT 'chờ duyệt',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`leave_request_id`),
  KEY `fk_lr_acc` (`account_id`),
  KEY `fk_lr_appr` (`approved_by`),
  CONSTRAINT `fk_lr_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_lr_appr` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` (`leave_request_id`, `account_id`, `from_date`, `to_date`, `leave_type`, `reason`, `status`, `approved_by`, `approved_at`, `notes`, `created_at`, `updated_at`) VALUES (1,3,'2026-04-17','2026-04-17','phép','bệnh','chấp thuận',2,'2026-04-17 21:36:16','','2026-04-17 14:35:28','2026-04-17 14:36:16'),(2,3,'2026-04-17','2026-04-25','phép','bận','chấp thuận',2,'2026-04-17 22:43:22','','2026-04-17 15:43:12','2026-04-17 15:43:22'),(3,2,'2026-04-19','2026-04-19','phép','hằng đẹp gái','chấp thuận',2,'2026-04-18 21:13:12','','2026-04-18 14:13:06','2026-04-18 14:13:12'),(4,4,'2026-04-18','2026-04-18','phép','bận','hủy',NULL,NULL,NULL,'2026-04-18 14:15:33','2026-04-18 14:15:38'),(5,4,'2026-04-19','2026-04-19','phép','bận','chấp thuận',2,'2026-04-18 21:17:59','','2026-04-18 14:17:37','2026-04-18 14:17:59');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `position_name` varchar(100) NOT NULL,
  `base_salary` bigint(20) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` (`position_id`, `position_name`, `base_salary`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Quản trị viên',20000000,'Quản lý cấp cao hệ thống',1,'2026-04-15 14:54:56','2026-04-15 14:54:56'),(2,'Quản lý',15000000,'Quản lý toàn bộ hoạt động cửa hàng/nhân sự',1,'2026-04-15 14:54:56','2026-04-15 14:54:56'),(3,'Nhân viên bán hàng',8000000,'Tư vấn và phục vụ đồ uống cho khách hàng',1,'2026-04-15 14:54:56','2026-04-15 14:54:56'),(4,'Nhân viên kho',7500000,'Quản lý nhập xuất vật tư và kiểm kê hàng hóa',1,'2026-04-15 14:54:56','2026-04-15 14:54:56');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resignation_requests`
--

DROP TABLE IF EXISTS `resignation_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resignation_requests` (
  `resignation_request_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `notice_date` date NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('chờ duyệt','chấp thuận','từ chối','hủy') NOT NULL DEFAULT 'chờ duyệt',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`resignation_request_id`),
  KEY `fk_rr_acc` (`account_id`),
  KEY `fk_rr_appr` (`approved_by`),
  CONSTRAINT `fk_rr_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_rr_appr` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resignation_requests`
--

LOCK TABLES `resignation_requests` WRITE;
/*!40000 ALTER TABLE `resignation_requests` DISABLE KEYS */;
INSERT INTO `resignation_requests` (`resignation_request_id`, `account_id`, `notice_date`, `effective_date`, `reason`, `status`, `approved_by`, `approved_at`, `notes`, `created_at`, `updated_at`) VALUES (1,13,'2026-04-17','2026-04-17','nghỉ thai sản','chấp thuận',13,'2026-04-17 22:00:09','','2026-04-17 15:00:04','2026-04-17 15:00:09'),(3,13,'2026-04-18','2026-04-18','hằng đẹp gái nè','chấp thuận',13,'2026-04-18 21:04:51','','2026-04-18 14:04:41','2026-04-18 14:04:51');
/*!40000 ALTER TABLE `resignation_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_role_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `display_name`) VALUES (1,'admin','Quản trị viên'),(2,'manager','Quản lý'),(3,'sales','Nhân viên bán hàng'),(4,'warehouse','Nhân viên kho');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_records`
--

DROP TABLE IF EXISTS `salary_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_records` (
  `salary_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `salary_month` tinyint(4) NOT NULL,
  `salary_year` year(4) NOT NULL,
  `base_salary` bigint(20) NOT NULL DEFAULT 0,
  `allowance` bigint(20) NOT NULL DEFAULT 0,
  `bonus` bigint(20) NOT NULL DEFAULT 0,
  `deductions` bigint(20) NOT NULL DEFAULT 0,
  `total_salary` bigint(20) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`salary_record_id`),
  UNIQUE KEY `uniq_salary` (`account_id`,`salary_month`,`salary_year`),
  KEY `fk_sr_acc` (`account_id`),
  KEY `fk_sr_pos` (`position_id`),
  CONSTRAINT `fk_sr_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_sr_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_records`
--

LOCK TABLES `salary_records` WRITE;
/*!40000 ALTER TABLE `salary_records` DISABLE KEYS */;
INSERT INTO `salary_records` (`salary_record_id`, `account_id`, `position_id`, `salary_month`, `salary_year`, `base_salary`, `allowance`, `bonus`, `deductions`, `total_salary`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES (10,11,2,1,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 11:41:17','2026-04-18 11:41:17'),(11,10,3,1,2026,8000000,500000,100000,0,8600000,'',2,'2026-04-18 11:41:28','2026-04-18 11:41:28'),(12,3,4,1,2026,7500000,500000,0,0,8000000,'',2,'2026-04-18 11:41:36','2026-04-18 11:41:36'),(13,4,3,1,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 11:41:40','2026-04-18 11:41:40'),(14,2,2,1,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 11:41:45','2026-04-18 11:41:45'),(15,9,3,1,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 11:41:49','2026-04-18 11:41:49'),(16,11,2,2,2026,15000000,500000,200000,0,15700000,'',2,'2026-04-18 11:42:22','2026-04-18 11:42:22'),(17,10,3,2,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 11:42:28','2026-04-18 11:42:28'),(18,3,4,2,2026,7500000,500000,0,0,8000000,'',2,'2026-04-18 11:42:32','2026-04-18 11:42:32'),(19,2,2,2,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 11:42:36','2026-04-18 11:42:36'),(20,9,3,2,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 11:42:39','2026-04-18 11:42:39'),(21,4,3,2,2026,8000000,500000,100000,100000,8500000,'',2,'2026-04-18 11:42:55','2026-04-18 11:42:55'),(22,11,2,3,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 11:43:17','2026-04-18 11:43:17'),(23,10,3,3,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 11:43:22','2026-04-18 11:43:22'),(24,3,4,3,2026,7500000,500000,0,0,8000000,'',2,'2026-04-18 11:43:50','2026-04-18 11:43:50'),(25,2,2,3,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 11:43:54','2026-04-18 11:43:54'),(26,4,3,3,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 11:43:58','2026-04-18 11:43:58'),(27,9,3,3,2026,8000000,500000,200000,1000000,7700000,'',2,'2026-04-18 11:44:13','2026-04-18 11:44:13'),(28,11,2,4,2026,15000000,500000,300000,200000,15600000,'',2,'2026-04-18 14:02:09','2026-04-18 14:02:09'),(29,10,3,4,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 14:02:14','2026-04-18 14:02:14'),(30,9,2,4,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 14:02:18','2026-04-18 14:02:18'),(31,2,2,4,2026,15000000,500000,0,0,15500000,'',2,'2026-04-18 14:02:22','2026-04-18 14:02:22'),(32,4,3,4,2026,8000000,500000,0,0,8500000,'',2,'2026-04-18 14:02:26','2026-04-18 14:02:26'),(33,3,4,4,2026,7500000,500000,0,0,8000000,'',2,'2026-04-18 14:02:30','2026-04-18 14:02:30');
/*!40000 ALTER TABLE `salary_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `movement_type` enum('import','export','adjustment','receipt_update','receipt_delete','export_update','export_delete') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`movement_id`),
  KEY `fk_mv_item` (`item_id`),
  KEY `fk_mv_by` (`created_by`),
  CONSTRAINT `fk_mv_by` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_mv_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` (`movement_id`, `item_id`, `movement_type`, `quantity_change`, `stock_before`, `stock_after`, `unit_cost`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at`) VALUES (1,3,'import',79,0,79,15000.00,'inventory_receipts',1,NULL,1,'2026-04-17 12:00:51'),(2,2,'import',85,0,85,7000.00,'inventory_receipts',1,NULL,1,'2026-04-17 12:00:51'),(3,5,'import',69,0,69,15000.00,'inventory_receipts',1,NULL,1,'2026-04-17 12:00:51'),(4,10,'import',66,0,66,25000.00,'inventory_receipts',1,NULL,1,'2026-04-17 12:00:51'),(5,9,'import',89,0,89,20000.00,'inventory_receipts',2,NULL,1,'2026-04-17 12:00:51'),(6,12,'import',91,0,91,28000.00,'inventory_receipts',2,NULL,1,'2026-04-17 12:00:51'),(7,2,'import',99,85,184,7000.00,'inventory_receipts',2,NULL,1,'2026-04-17 12:00:51'),(8,4,'import',60,0,60,16000.00,'inventory_receipts',2,NULL,1,'2026-04-17 12:00:51'),(9,12,'import',54,91,145,28000.00,'inventory_receipts',3,NULL,1,'2026-04-17 12:00:51'),(10,1,'import',61,0,61,5000.00,'inventory_receipts',3,NULL,1,'2026-04-17 12:00:51'),(11,12,'import',76,145,221,28000.00,'inventory_receipts',4,NULL,1,'2026-04-17 12:00:51'),(12,5,'import',77,69,146,15000.00,'inventory_receipts',4,NULL,1,'2026-04-17 12:00:51'),(13,9,'import',65,89,154,20000.00,'inventory_receipts',4,NULL,1,'2026-04-17 12:00:51'),(14,5,'import',55,146,201,15000.00,'inventory_receipts',5,NULL,1,'2026-04-17 12:00:51'),(15,7,'import',62,0,62,12000.00,'inventory_receipts',5,NULL,1,'2026-04-17 12:00:51'),(16,2,'import',59,184,243,7000.00,'inventory_receipts',6,NULL,1,'2026-04-17 12:00:51'),(17,8,'import',93,0,93,18000.00,'inventory_receipts',6,NULL,1,'2026-04-17 12:00:51'),(18,11,'import',81,0,81,15000.00,'inventory_receipts',7,NULL,1,'2026-04-17 12:00:51'),(19,3,'import',97,79,176,15000.00,'inventory_receipts',7,NULL,1,'2026-04-17 12:00:51'),(20,10,'import',87,66,153,25000.00,'inventory_receipts',7,NULL,1,'2026-04-17 12:00:51'),(21,12,'import',70,221,291,28000.00,'inventory_receipts',7,NULL,1,'2026-04-17 12:00:51'),(22,7,'import',92,62,154,12000.00,'inventory_receipts',8,NULL,1,'2026-04-17 12:00:51'),(23,12,'import',95,291,386,28000.00,'inventory_receipts',8,NULL,1,'2026-04-17 12:00:51'),(24,8,'import',80,93,173,18000.00,'inventory_receipts',8,NULL,1,'2026-04-17 12:00:51'),(25,5,'import',54,201,255,15000.00,'inventory_receipts',9,NULL,1,'2026-04-17 12:00:51'),(26,12,'import',50,386,436,28000.00,'inventory_receipts',9,NULL,1,'2026-04-17 12:00:51'),(27,4,'import',61,60,121,16000.00,'inventory_receipts',9,NULL,1,'2026-04-17 12:00:51'),(28,3,'import',88,176,264,15000.00,'inventory_receipts',9,NULL,1,'2026-04-17 12:00:51'),(29,6,'import',80,0,80,12000.00,'inventory_receipts',10,NULL,1,'2026-04-17 12:00:51'),(30,10,'import',54,153,207,25000.00,'inventory_receipts',10,NULL,1,'2026-04-17 12:00:51'),(31,7,'export',-4,154,150,12000.00,'invoices',1,NULL,1,'2026-04-17 12:00:51'),(32,9,'export',-3,154,151,20000.00,'invoices',2,NULL,1,'2026-04-17 12:00:51'),(33,1,'export',-1,61,60,5000.00,'invoices',3,NULL,1,'2026-04-17 12:00:51'),(34,12,'export',-2,436,434,28000.00,'invoices',4,NULL,1,'2026-04-17 12:00:51'),(35,11,'export',-4,81,77,15000.00,'invoices',5,NULL,1,'2026-04-17 12:00:51'),(36,12,'export',-2,434,432,28000.00,'invoices',5,NULL,1,'2026-04-17 12:00:51'),(37,7,'export',-2,150,148,12000.00,'invoices',6,NULL,1,'2026-04-17 12:00:51'),(38,2,'export',-4,243,239,7000.00,'invoices',7,NULL,1,'2026-04-17 12:00:51'),(39,8,'export',-5,173,168,18000.00,'invoices',7,NULL,1,'2026-04-17 12:00:51'),(40,4,'export',-2,121,119,16000.00,'invoices',8,NULL,1,'2026-04-17 12:00:51'),(41,12,'export',-4,432,428,28000.00,'invoices',8,NULL,1,'2026-04-17 12:00:51'),(42,4,'export',-2,119,117,16000.00,'invoices',9,NULL,1,'2026-04-17 12:00:51'),(43,9,'export',-5,151,146,20000.00,'invoices',10,NULL,1,'2026-04-17 12:00:51'),(44,2,'export',-5,239,234,7000.00,'invoices',11,NULL,1,'2026-04-17 12:00:51'),(45,12,'export',-5,428,423,28000.00,'invoices',11,NULL,1,'2026-04-17 12:00:51'),(46,11,'export',-4,77,73,15000.00,'invoices',11,NULL,1,'2026-04-17 12:00:51'),(47,3,'export',-2,264,262,15000.00,'invoices',12,NULL,1,'2026-04-17 12:00:51'),(48,7,'export',-2,148,146,12000.00,'invoices',12,NULL,1,'2026-04-17 12:00:51'),(49,10,'export',-5,207,202,25000.00,'invoices',12,NULL,1,'2026-04-17 12:00:51'),(50,1,'export',-1,60,59,5000.00,'invoices',13,NULL,1,'2026-04-17 12:00:51'),(51,2,'export',-1,234,233,7000.00,'invoices',13,NULL,1,'2026-04-17 12:00:51'),(52,6,'export',-2,80,78,12000.00,'invoices',14,NULL,1,'2026-04-17 12:00:51'),(53,10,'export',-1,202,201,25000.00,'invoices',14,NULL,1,'2026-04-17 12:00:51'),(54,7,'export',-4,146,142,12000.00,'invoices',14,NULL,1,'2026-04-17 12:00:51'),(55,4,'export',-1,117,116,16000.00,'invoices',15,NULL,1,'2026-04-17 12:00:51'),(56,7,'export',-3,142,139,12000.00,'invoices',15,NULL,1,'2026-04-17 12:00:51'),(57,1,'export',-3,59,56,5000.00,'invoices',15,NULL,1,'2026-04-17 12:00:51'),(58,1,'import',69,56,125,5000.00,'inventory_receipts',11,NULL,13,'2026-04-17 12:10:36'),(59,8,'import',45,168,213,18000.00,'inventory_receipts',11,NULL,13,'2026-04-17 12:10:36'),(60,12,'import',60,423,483,28000.00,'inventory_receipts',11,NULL,13,'2026-04-17 12:10:36'),(61,4,'import',33,116,149,16000.00,'inventory_receipts',11,NULL,13,'2026-04-17 12:10:36'),(62,9,'import',71,146,217,20000.00,'inventory_receipts',11,NULL,13,'2026-04-17 12:10:36'),(63,3,'import',55,262,317,15000.00,'inventory_receipts',12,NULL,13,'2026-04-17 12:10:36'),(64,6,'import',60,78,138,12000.00,'inventory_receipts',12,NULL,13,'2026-04-17 12:10:36'),(65,7,'import',38,139,177,12000.00,'inventory_receipts',12,NULL,13,'2026-04-17 12:10:36'),(66,10,'import',31,201,232,25000.00,'inventory_receipts',12,NULL,13,'2026-04-17 12:10:36'),(67,3,'import',35,317,352,15000.00,'inventory_receipts',13,NULL,13,'2026-04-17 12:10:36'),(68,11,'import',74,73,147,15000.00,'inventory_receipts',13,NULL,13,'2026-04-17 12:10:36'),(69,8,'import',65,213,278,18000.00,'inventory_receipts',13,NULL,13,'2026-04-17 12:10:36'),(70,5,'import',68,255,323,15000.00,'inventory_receipts',13,NULL,13,'2026-04-17 12:10:36'),(71,7,'import',76,177,253,12000.00,'inventory_receipts',14,NULL,13,'2026-04-17 12:10:36'),(72,10,'import',40,232,272,25000.00,'inventory_receipts',14,NULL,13,'2026-04-17 12:10:36'),(73,6,'import',47,138,185,12000.00,'inventory_receipts',15,NULL,13,'2026-04-17 12:10:36'),(74,7,'import',31,253,284,12000.00,'inventory_receipts',15,NULL,13,'2026-04-17 12:10:36'),(75,3,'import',73,352,425,15000.00,'inventory_receipts',15,NULL,13,'2026-04-17 12:10:36'),(76,10,'import',61,272,333,25000.00,'inventory_receipts',15,NULL,13,'2026-04-17 12:10:36'),(77,4,'import',59,149,208,16000.00,'inventory_receipts',15,NULL,13,'2026-04-17 12:10:36'),(78,10,'export',-2,333,331,25000.00,'invoices',16,NULL,13,'2026-04-17 12:10:36'),(79,11,'export',-2,147,145,15000.00,'invoices',16,NULL,13,'2026-04-17 12:10:36'),(80,1,'export',-3,125,122,5000.00,'invoices',16,NULL,13,'2026-04-17 12:10:36'),(81,4,'export',-3,208,205,16000.00,'invoices',17,NULL,13,'2026-04-17 12:10:36'),(82,2,'export',-2,233,231,7000.00,'invoices',17,NULL,13,'2026-04-17 12:10:36'),(83,3,'export',-2,425,423,15000.00,'invoices',17,NULL,13,'2026-04-17 12:10:36'),(84,12,'export',-3,483,480,28000.00,'invoices',18,NULL,13,'2026-04-17 12:10:36'),(85,10,'export',-3,331,328,25000.00,'invoices',18,NULL,13,'2026-04-17 12:10:36'),(86,11,'export',-1,145,144,15000.00,'invoices',18,NULL,13,'2026-04-17 12:10:36'),(87,8,'export',-3,278,275,18000.00,'invoices',19,NULL,13,'2026-04-17 12:10:36'),(88,2,'export',-2,231,229,7000.00,'invoices',19,NULL,13,'2026-04-17 12:10:36'),(89,11,'export',-1,144,143,15000.00,'invoices',19,NULL,13,'2026-04-17 12:10:36'),(90,4,'export',-4,205,201,16000.00,'invoices',20,NULL,13,'2026-04-17 12:10:36'),(91,10,'export',-2,328,326,25000.00,'invoices',20,NULL,13,'2026-04-17 12:10:36'),(92,2,'export',-2,229,227,7000.00,'invoices',21,NULL,13,'2026-04-17 12:10:36'),(93,10,'export',-4,326,322,25000.00,'invoices',21,NULL,13,'2026-04-17 12:10:36'),(94,4,'export',-2,201,199,16000.00,'invoices',21,NULL,13,'2026-04-17 12:10:36'),(95,5,'export',-4,323,319,15000.00,'invoices',21,NULL,13,'2026-04-17 12:10:36'),(96,4,'export',-5,199,194,16000.00,'invoices',22,NULL,13,'2026-04-17 12:10:36'),(97,6,'export',-1,185,184,12000.00,'invoices',22,NULL,13,'2026-04-17 12:10:36'),(98,2,'import',43,227,270,7000.00,'inventory_receipts',16,NULL,13,'2026-04-17 12:10:36'),(99,3,'import',46,423,469,15000.00,'inventory_receipts',16,NULL,13,'2026-04-17 12:10:36'),(100,1,'import',38,122,160,5000.00,'inventory_receipts',16,NULL,13,'2026-04-17 12:10:36'),(101,12,'import',64,480,544,28000.00,'inventory_receipts',17,NULL,13,'2026-04-17 12:10:36'),(102,1,'import',72,160,232,5000.00,'inventory_receipts',17,NULL,13,'2026-04-17 12:10:36'),(103,8,'import',32,275,307,18000.00,'inventory_receipts',17,NULL,13,'2026-04-17 12:10:36'),(104,6,'import',74,184,258,12000.00,'inventory_receipts',17,NULL,13,'2026-04-17 12:10:36'),(105,9,'import',72,217,289,20000.00,'inventory_receipts',18,NULL,13,'2026-04-17 12:10:36'),(106,5,'import',59,319,378,15000.00,'inventory_receipts',18,NULL,13,'2026-04-17 12:10:36'),(107,11,'import',45,143,188,15000.00,'inventory_receipts',18,NULL,13,'2026-04-17 12:10:36'),(108,2,'import',58,270,328,7000.00,'inventory_receipts',19,NULL,13,'2026-04-17 12:10:36'),(109,12,'import',66,544,610,28000.00,'inventory_receipts',19,NULL,13,'2026-04-17 12:10:36'),(110,3,'import',78,469,547,15000.00,'inventory_receipts',20,NULL,13,'2026-04-17 12:10:36'),(111,6,'import',60,258,318,12000.00,'inventory_receipts',20,NULL,13,'2026-04-17 12:10:36'),(112,5,'import',69,378,447,15000.00,'inventory_receipts',20,NULL,13,'2026-04-17 12:10:36'),(113,8,'import',33,307,340,18000.00,'inventory_receipts',20,NULL,13,'2026-04-17 12:10:36'),(114,4,'import',45,194,239,16000.00,'inventory_receipts',20,NULL,13,'2026-04-17 12:10:36'),(115,5,'import',33,447,480,15000.00,'inventory_receipts',21,NULL,13,'2026-04-17 12:10:36'),(116,12,'import',70,610,680,28000.00,'inventory_receipts',21,NULL,13,'2026-04-17 12:10:36'),(117,2,'import',55,328,383,7000.00,'inventory_receipts',21,NULL,13,'2026-04-17 12:10:36'),(118,1,'import',69,232,301,5000.00,'inventory_receipts',22,NULL,13,'2026-04-17 12:10:36'),(119,7,'import',62,284,346,12000.00,'inventory_receipts',22,NULL,13,'2026-04-17 12:10:36'),(120,1,'import',71,301,372,5000.00,'inventory_receipts',23,NULL,13,'2026-04-17 12:10:36'),(121,7,'import',63,346,409,12000.00,'inventory_receipts',23,NULL,13,'2026-04-17 12:10:36'),(122,8,'import',56,340,396,18000.00,'inventory_receipts',23,NULL,13,'2026-04-17 12:10:36'),(123,10,'export',-4,322,318,25000.00,'invoices',23,NULL,13,'2026-04-17 12:10:36'),(124,11,'export',-1,188,187,15000.00,'invoices',23,NULL,13,'2026-04-17 12:10:36'),(125,2,'export',-5,383,378,7000.00,'invoices',23,NULL,13,'2026-04-17 12:10:36'),(126,1,'export',-1,372,371,5000.00,'invoices',23,NULL,13,'2026-04-17 12:10:36'),(127,1,'export',-4,371,367,5000.00,'invoices',24,NULL,13,'2026-04-17 12:10:36'),(128,6,'export',-5,318,313,12000.00,'invoices',24,NULL,13,'2026-04-17 12:10:36'),(129,5,'export',-3,480,477,15000.00,'invoices',24,NULL,13,'2026-04-17 12:10:36'),(130,10,'export',-3,318,315,25000.00,'invoices',25,NULL,13,'2026-04-17 12:10:36'),(131,6,'export',-4,313,309,12000.00,'invoices',25,NULL,13,'2026-04-17 12:10:36'),(132,8,'export',-3,396,393,18000.00,'invoices',25,NULL,13,'2026-04-17 12:10:36'),(133,1,'export',-3,367,364,5000.00,'invoices',26,NULL,13,'2026-04-17 12:10:36'),(134,12,'export',-1,680,679,28000.00,'invoices',26,NULL,13,'2026-04-17 12:10:36'),(135,7,'export',-4,409,405,12000.00,'invoices',26,NULL,13,'2026-04-17 12:10:36'),(136,4,'export',-3,239,236,16000.00,'invoices',27,NULL,13,'2026-04-17 12:10:36'),(137,6,'export',-2,309,307,12000.00,'invoices',28,NULL,13,'2026-04-17 12:10:36'),(138,5,'export',-4,477,473,15000.00,'invoices',28,NULL,13,'2026-04-17 12:10:36'),(139,1,'export',-5,364,359,5000.00,'invoices',29,NULL,13,'2026-04-17 12:10:36'),(140,4,'export',-5,236,231,16000.00,'invoices',29,NULL,13,'2026-04-17 12:10:36'),(141,12,'export',-3,679,676,28000.00,'invoices',29,NULL,13,'2026-04-17 12:10:36'),(142,3,'export',-4,547,543,15000.00,'invoices',29,NULL,13,'2026-04-17 12:10:36'),(143,12,'export',-3,676,673,28000.00,'invoices',30,NULL,13,'2026-04-17 12:10:36'),(144,8,'export',-1,393,392,18000.00,'invoices',30,NULL,13,'2026-04-17 12:10:36'),(145,3,'export',-5,543,538,15000.00,'invoices',30,NULL,13,'2026-04-17 12:10:36'),(146,1,'export',-2,359,357,5000.00,'invoices',31,NULL,13,'2026-04-17 12:10:36'),(147,9,'export',-5,289,284,20000.00,'invoices',31,NULL,13,'2026-04-17 12:10:36'),(148,4,'export',-5,231,226,16000.00,'invoices',32,NULL,13,'2026-04-17 12:10:37'),(149,5,'export',-3,473,470,15000.00,'invoices',32,NULL,13,'2026-04-17 12:10:37'),(150,1,'export',-2,357,355,5000.00,'invoices',32,NULL,13,'2026-04-17 12:10:37'),(151,10,'export',-5,315,310,25000.00,'invoices',32,NULL,13,'2026-04-17 12:10:37'),(152,13,'import',51,0,51,10000.00,'receipt',24,NULL,3,'2026-04-17 12:32:04'),(153,4,'export',-1,226,225,16000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(154,9,'export',-1,284,283,20000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(155,6,'export',-1,307,306,12000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(156,5,'export',-1,470,469,15000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(157,3,'export',-2,538,536,15000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(158,7,'export',-1,405,404,12000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(159,10,'export',-1,310,309,25000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(160,1,'export',-200,355,155,5000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(161,8,'export',-1,392,391,18000.00,'invoice',33,'Bán hàng (Số HĐ: 33)',4,'2026-04-17 12:53:56'),(162,6,'export',-1,306,305,12000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(163,7,'export',-1,404,403,12000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(164,10,'export',-1,309,308,25000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(165,5,'export',-8,469,461,15000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(166,3,'export',-1,536,535,15000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(167,12,'export',-1,673,672,28000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(168,8,'export',-1,391,390,18000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(169,2,'export',-4,378,374,7000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(170,1,'export',-8,155,147,5000.00,'invoice',34,'Bán hàng (Số HĐ: 34)',4,'2026-04-17 13:12:41'),(171,12,'export',-14,672,658,28000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(172,3,'export',-13,535,522,15000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(173,5,'export',-10,461,451,15000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(174,7,'export',-8,403,395,12000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(175,11,'export',-4,187,183,15000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(176,1,'export',-4,147,143,5000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(177,13,'export',-2,51,49,10000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(178,4,'export',-5,225,220,16000.00,'invoice',35,'Bán hàng (Số HĐ: 35)',4,'2026-04-17 13:18:50'),(179,3,'export',-519,522,3,15000.00,'invoice',36,'Bán hàng (Số HĐ: 36)',4,'2026-04-17 13:19:31'),(180,14,'import',1,0,1,3000.00,'receipt',25,NULL,3,'2026-04-17 14:22:24'),(181,16,'import',10,0,10,10000.00,'receipt',26,NULL,3,'2026-04-17 14:24:16'),(182,14,'import',6,1,7,3000.00,'receipt',26,NULL,3,'2026-04-17 14:24:16'),(183,6,'export',-8,305,297,12000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14'),(184,7,'export',-329,395,66,12000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14'),(185,8,'export',-1,390,389,18000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14'),(186,9,'export',-2,283,281,20000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14'),(187,11,'export',-2,183,181,15000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14'),(188,10,'export',-2,308,306,25000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14'),(189,12,'export',-1,658,657,28000.00,'invoice',37,'Bán hàng (Số HĐ: 37)',4,'2026-04-18 14:38:14');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `uniq_sup_code` (`supplier_code`),
  UNIQUE KEY `uniq_sup_phone` (`phone_number`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_name`, `phone_number`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES (1,'RN001','Tổng kho Nguyên liệu Sài Gòn','Anh Tú','0901234567','saigon.nguyenlieu@gmail.com','123 Đường số 7, Q. Bình Tân, TP.HCM','active','2026-04-17 12:00:51','2026-04-17 12:00:51'),(2,'RN002','Đại lý Cà phê Buôn Ma Thuột','Chị Lan','0912345678','bm.coffee@yahoo.com','45 Phan Bội Châu, TP. Buôn Ma Thuột','active','2026-04-17 12:00:51','2026-04-17 12:00:51'),(3,'RN003','Cung ứng Sữa Vinamilk Chi nhánh 2','Anh Hùng','0987654321','vnm.cn2@vinamilk.com.vn','10 Tân Trào, Q.7, TP.HCM','active','2026-04-17 12:00:51','2026-04-17 12:00:51'),(4,'RN004','Thế giới Trà Thái & Topping','Anh Minh','0933445566','trathai.sg@gmail.com','78 Cách Mạng Tháng 8, Q.3, TP.HCM','active','2026-04-17 12:00:51','2026-04-17 12:00:51'),(5,'RN005','Bao bì & Dụng cụ quầy bar','Chị Hoa','0944556677','baobi.coffee@gmail.com','22 Hòa Bình, Q. Tân Phú, TP.HCM','active','2026-04-17 12:00:51','2026-04-17 12:00:51'),(6,'SUP-20260417143058-436','Hằng đẹp gái','Thanh Hằng','0386601904','thanhhang250305@gmail.com','85b nguyễn trãi','active','2026-04-17 12:30:58','2026-04-17 12:30:58'),(7,'SUP-20260418164452-898','Hằng đẹp gái','Thanh Hằng',NULL,'thanhhang250305@gmail.com','85b nguyễn trãi','inactive','2026-04-18 14:44:52','2026-04-18 14:48:25'),(8,'SUP-20260418165001-699','Hằng đẹp gái','','','','','inactive','2026-04-18 14:50:01','2026-04-18 14:50:07');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_monthly_revenue`
--

DROP TABLE IF EXISTS `v_monthly_revenue`;
/*!50001 DROP VIEW IF EXISTS `v_monthly_revenue`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_monthly_revenue` AS SELECT
 1 AS `nam`,
  1 AS `thang`,
  1 AS `so_hoa_don`,
  1 AS `doanh_thu`,
  1 AS `tong_giam_gia` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_salary_report`
--

DROP TABLE IF EXISTS `v_salary_report`;
/*!50001 DROP VIEW IF EXISTS `v_salary_report`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_salary_report` AS SELECT
 1 AS `salary_year`,
  1 AS `salary_month`,
  1 AS `account_id`,
  1 AS `full_name`,
  1 AS `role_name`,
  1 AS `position_name`,
  1 AS `base_salary`,
  1 AS `allowance`,
  1 AS `bonus`,
  1 AS `deductions`,
  1 AS `total_salary`,
  1 AS `notes` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_stock_summary`
--

DROP TABLE IF EXISTS `v_stock_summary`;
/*!50001 DROP VIEW IF EXISTS `v_stock_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_stock_summary` AS SELECT
 1 AS `item_id`,
  1 AS `item_name`,
  1 AS `category_name`,
  1 AS `stock_quantity`,
  1 AS `purchase_price`,
  1 AS `unit_price`,
  1 AS `stock_cost_value`,
  1 AS `stock_sell_value`,
  1 AS `item_status` */;
SET character_set_client = @saved_cs_client;

--
-- Current Database: `eldercoffee_db`
--

USE `eldercoffee_db`;

--
-- Final view structure for view `v_monthly_revenue`
--

/*!50001 DROP VIEW IF EXISTS `v_monthly_revenue`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_monthly_revenue` AS select year(`invoices`.`creation_time`) AS `nam`,month(`invoices`.`creation_time`) AS `thang`,count(0) AS `so_hoa_don`,sum(`invoices`.`total`) AS `doanh_thu`,sum(`invoices`.`discount`) AS `tong_giam_gia` from `invoices` where `invoices`.`status` <> 'cancelled' group by year(`invoices`.`creation_time`),month(`invoices`.`creation_time`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_salary_report`
--

/*!50001 DROP VIEW IF EXISTS `v_salary_report`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_salary_report` AS select `sr`.`salary_year` AS `salary_year`,`sr`.`salary_month` AS `salary_month`,`a`.`account_id` AS `account_id`,`a`.`full_name` AS `full_name`,`r`.`display_name` AS `role_name`,`p`.`position_name` AS `position_name`,`sr`.`base_salary` AS `base_salary`,`sr`.`allowance` AS `allowance`,`sr`.`bonus` AS `bonus`,`sr`.`deductions` AS `deductions`,`sr`.`total_salary` AS `total_salary`,`sr`.`notes` AS `notes` from (((`salary_records` `sr` join `accounts` `a` on(`a`.`account_id` = `sr`.`account_id`)) join `roles` `r` on(`r`.`id` = `a`.`role_id`)) join `positions` `p` on(`p`.`position_id` = `sr`.`position_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_stock_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_stock_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_stock_summary` AS select `i`.`item_id` AS `item_id`,`i`.`item_name` AS `item_name`,`c`.`category_name` AS `category_name`,`i`.`stock_quantity` AS `stock_quantity`,`i`.`purchase_price` AS `purchase_price`,`i`.`unit_price` AS `unit_price`,`i`.`stock_quantity` * `i`.`purchase_price` AS `stock_cost_value`,`i`.`stock_quantity` * `i`.`unit_price` AS `stock_sell_value`,`i`.`item_status` AS `item_status` from (`items` `i` left join `category` `c` on(`c`.`category_id` = `i`.`category_id`)) where `i`.`item_status` = 'active' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-18 22:02:07
