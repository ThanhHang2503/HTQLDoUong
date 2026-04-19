<?php
require 'config.php';
$q = mysqli_query($conn, "SELECT position_id, position_name FROM positions");
$positions = [];
while ($row = mysqli_fetch_assoc($q)) {
    $positions[] = $row;
}
echo json_encode($positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
