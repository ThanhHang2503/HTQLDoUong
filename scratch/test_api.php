<?php
// Mock GET parameters
$_GET['item_id'] = 1;
$_GET['year'] = 2026;
$_GET['month'] = 0;

include 'api/warehouse/product_import_details.php';
echo "\n";
