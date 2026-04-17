<?php
// Mock GET
$_GET['item_id'] = 1;
$_GET['year'] = 2026;
$_GET['month'] = 0;

include 'api/business/product_sales_details.php';
echo "\n";
