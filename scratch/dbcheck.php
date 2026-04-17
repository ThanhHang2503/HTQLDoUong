<?php
require_once __DIR__ . '/../config.php';
$r = mysqli_query($conn, 'DESCRIBE items');
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Key=' . $row['Key'] . ' | Extra=' . $row['Extra'] . PHP_EOL;
}
echo '---CHECK item_code---' . PHP_EOL;
$chk = mysqli_query($conn, "SHOW COLUMNS FROM items LIKE 'item_code'");
echo 'item_code exists: ' . (mysqli_num_rows($chk) > 0 ? 'YES' : 'NO') . PHP_EOL;
