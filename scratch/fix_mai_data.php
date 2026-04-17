<?php
require_once dirname(__DIR__) . '/config.php';
$aid = 10; // Lê Thị Mai

// 1. Update Hire Date
mysqli_query($conn, "UPDATE accounts SET hire_date = '2026-01-01' WHERE account_id = $aid");

// 2. Clean up duplicate history for April 9
mysqli_query($conn, "DELETE FROM employee_positions_history WHERE account_id = $aid AND start_date = '2026-04-09'");

// 3. Add initial position (ID 3 - Sales) from Jan 1st to April 8th
mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, end_date) 
                     VALUES ($aid, 3, '2026-01-01', '2026-04-08')");

// 4. Add current position (ID 2 - Manager) from April 9th to Present
mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, end_date) 
                     VALUES ($aid, 2, '2026-04-09', NULL)");

echo "Fixed data for Lê Thị Mai (ID 10).\n";
