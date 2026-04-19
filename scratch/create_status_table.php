<?php
require 'config.php';
global $conn;

$sql = "CREATE TABLE IF NOT EXISTS employee_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    status ENUM('active', 'locked', 'on_leave', 'resigned') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_acc_date (account_id, start_date),
    CONSTRAINT fk_esh_acc FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "Table 'employee_status_history' created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
