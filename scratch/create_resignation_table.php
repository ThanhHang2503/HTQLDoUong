<?php
require "config.php";
global $conn;

echo "Creating table resignation_requests...\n";

$sql = "
CREATE TABLE IF NOT EXISTS `resignation_requests` (
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
  CONSTRAINT `fk_rr_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_rr_appr` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($conn, $sql)) {
    echo "Table 'resignation_requests' created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
