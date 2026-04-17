<?php
require_once __DIR__ . '/config.php';
$r = mysqli_query($conn, 'DESCRIBE items');
while ($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Key=' . $row['Key'] . ' | Extra=' . $row['Extra'] . PHP_EOL;
}
