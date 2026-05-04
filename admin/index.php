<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Các view có thể bắt qua $_GET (nếu cần tương thích ngược)
$view = $_GET['view'] ?? null;
$handled_views = ['products', 'suppliers'];
$is_home = (empty($_GET) && empty($_POST)) || isset($_GET['home']) || isset($_GET['dashboard']);
$is_handled_view = $view && in_array($view, $handled_views, true);

// Proxy Router cho các trang trong user_page.php (Nhân sự, Báo cáo...)
if (!$is_home && !$is_handled_view) {
    define('IS_ADMIN_ROUTER', true);
    require_once __DIR__ . '/../user_page.php';
    exit;
}

// Redirect tương thích ngược nếu query string là ?view=products hoặc ?view=suppliers
if ($view === 'products') {
    header('Location: products.php');
    exit;
} elseif ($view === 'suppliers') {
    header('Location: suppliers.php');
    exit;
}

// Mặc định load Dashboard
require_once __DIR__ . '/dashboard.php';
