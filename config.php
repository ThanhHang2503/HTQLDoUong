<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect('localhost', 'root', '', 'eldercoffee_db');
mysqli_set_charset($conn, 'utf8mb4');
