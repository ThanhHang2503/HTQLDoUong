<?php
// admin_layout_start.php
$roleName = currentRole();
$baseUrl = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : './';

$currentTitle = 'Admin Dashboard';
if (isset($_GET['home']) || isset($_GET['dashboard'])) {
    $currentTitle = 'Admin Dashboard';
} elseif (isset($_GET['sanpham']) || isset($_GET['view']) && $_GET['view'] === 'products') {
    $currentTitle = 'Quản lý Sản phẩm';
} elseif (isset($_GET['nhacungcap']) || isset($_GET['view']) && $_GET['view'] === 'suppliers') {
    $currentTitle = 'Quản lý Nhà cung cấp';
} elseif (isset($_GET['nhansu'])) {
    $currentTitle = 'Quản lý User & Nhân sự';
} elseif (isset($_GET['baocao_kinhdoanh'])) {
    $currentTitle = 'Báo Cáo Kinh Doanh';
} elseif (isset($_GET['baocao_kho'])) {
    $currentTitle = 'Báo Cáo Kho';
} elseif (isset($_GET['baocao_nhansu'])) {
    $currentTitle = 'Báo Cáo Nhân Sự';
} elseif (isset($_GET['chucvu'])) {
    $currentTitle = 'Quản lý Chức vụ & Bổ nhiệm';
} elseif (isset($_GET['bangluong'])) {
    $currentTitle = 'Bảng lương Nhân viên';
} elseif (isset($_GET['luong_ca_nhan'])) {
    $currentTitle = 'Lương Của Tôi';
} elseif (isset($_GET['profile'])) {
    $currentTitle = 'Hồ Sơ Cá Nhân';
}

$user_name = $GLOBALS['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentTitle) ?> | ElderCoffee Admin</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/admin.css"> <!-- Used for admin/index.php styles if present -->
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-text: #ecf0f1;
            --admin-bg-light: #f8f9fa;
        }
        
        body.admin-mode {
            background-color: var(--admin-bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .admin-sidebar-new {
            width: 260px;
            background: var(--admin-primary);
            color: var(--admin-text);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
        }

        .admin-sidebar-new.collapsed {
            width: 70px;
        }

        .admin-brand {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--admin-secondary);
            text-decoration: none;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .admin-brand-icon {
            font-size: 1.5rem;
            color: var(--admin-accent);
            min-width: 30px;
            text-align: center;
        }

        .admin-brand-text {
            font-weight: 700;
            font-size: 1.2rem;
            white-space: nowrap;
            transition: opacity 0.3s;
        }
        
        .admin-sidebar-new.collapsed .admin-brand-text {
            opacity: 0;
            display: none;
        }

        .admin-menu {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px 0;
            list-style: none;
            margin: 0;
        }
        
        .admin-menu::-webkit-scrollbar {
            width: 5px;
        }
        .admin-menu::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }

        .admin-menu-header {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            padding: 10px 20px;
            margin-top: 10px;
            white-space: nowrap;
        }
        
        .admin-sidebar-new.collapsed .admin-menu-header {
            text-align: center;
            padding: 10px 5px;
        }
        .admin-sidebar-new.collapsed .admin-menu-header span {
            display: none;
        }
        .admin-sidebar-new.collapsed .admin-menu-header::after {
            content: "•••";
        }

        .admin-menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.2s ease;
            gap: 15px;
            white-space: nowrap;
        }

        .admin-menu-item:hover, .admin-menu-item.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left: 4px solid var(--admin-accent);
        }

        .admin-menu-icon {
            font-size: 1.1rem;
            min-width: 25px;
            text-align: center;
        }

        .admin-menu-text {
            transition: opacity 0.3s;
        }
        
        .admin-sidebar-new.collapsed .admin-menu-text {
            opacity: 0;
            display: none;
        }

        .admin-user-info {
            padding: 15px 20px;
            background: rgba(0,0,0,0.15);
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            white-space: nowrap;
        }
        
        .admin-user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--admin-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            min-width: 35px;
        }

        .admin-sidebar-new.collapsed .admin-user-details {
            display: none;
        }

        /* Main Content Styling */
        .admin-content {
            flex-grow: 1;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .admin-content.expanded {
            margin-left: 70px;
        }

        .admin-header {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .admin-header-title {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #7f8c8d;
            padding: 5px;
        }

        .toggle-btn:hover {
            color: var(--admin-accent);
        }

        .admin-main-body {
            padding: 25px;
            flex-grow: 1;
        }

        /* Fixed height for table containers to allow scrolling */
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Sticky header for tables */
        .table-responsive thead th {
            position: sticky;
            top: 0;
            background-color: var(--admin-primary);
            color: white;
            z-index: 10;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .admin-sidebar-new {
                transform: translateX(-100%);
            }
            .admin-sidebar-new.mobile-show {
                transform: translateX(0);
                width: 260px;
            }
            .admin-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="admin-mode">

    <div class="admin-wrapper">
        
        <!-- SIDEBAR -->
        <aside class="admin-sidebar-new" id="adminSidebar">
            <a href="<?= $baseUrl ?>admin/index.php" class="admin-brand">
                <i class="fa-solid fa-mug-hot admin-brand-icon"></i>
                <span class="admin-brand-text">Admin Panel</span>
            </a>

            <div class="admin-menu">
                <div class="admin-menu-header"><span>Quản trị Catalog</span></div>
                <a href="<?= $baseUrl ?>admin/index.php?view=products" class="admin-menu-item <?= (isset($_GET['view']) && $_GET['view']==='products') || (!isset($_GET['view']) && strpos($_SERVER['REQUEST_URI'], 'admin/index.php') !== false) ? 'active' : '' ?>">
                    <i class="fa-solid fa-box admin-menu-icon"></i>
                    <span class="admin-menu-text">Sản phẩm</span>
                </a>
                <a href="<?= $baseUrl ?>admin/index.php?view=suppliers" class="admin-menu-item <?= isset($_GET['view']) && $_GET['view']==='suppliers' ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck-fast admin-menu-icon"></i>
                    <span class="admin-menu-text">Nhà cung cấp</span>
                </a>

                <div class="admin-menu-header"><span>Quản trị Hệ thống</span></div>
                <a href="<?= $baseUrl ?>user_page.php?nhansu" class="admin-menu-item <?= isset($_GET['nhansu']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear admin-menu-icon"></i>
                    <span class="admin-menu-text">Người dùng (Tài khoản)</span>
                </a>

                <div class="admin-menu-header"><span>Báo Cáo - Thống Kê</span></div>
                <a href="<?= $baseUrl ?>user_page.php?baocao_kinhdoanh" class="admin-menu-item <?= isset($_GET['baocao_kinhdoanh']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line admin-menu-icon"></i>
                    <span class="admin-menu-text">Báo cáo Kinh doanh</span>
                </a>
                <a href="<?= $baseUrl ?>user_page.php?baocao_kho" class="admin-menu-item <?= isset($_GET['baocao_kho']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-warehouse admin-menu-icon"></i>
                    <span class="admin-menu-text">Báo cáo Kho</span>
                </a>
                <a href="<?= $baseUrl ?>user_page.php?baocao_nhansu" class="admin-menu-item <?= isset($_GET['baocao_nhansu']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie admin-menu-icon"></i>
                    <span class="admin-menu-text">Báo cáo Nhân sự</span>
                </a>

                <div class="admin-menu-header"><span>Cá Nhân</span></div>
                <a href="<?= $baseUrl ?>user_page.php?profile" class="admin-menu-item <?= isset($_GET['profile']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-id-card admin-menu-icon"></i>
                    <span class="admin-menu-text">Hồ sơ cá nhân</span>
                </a>
            </div>

            <div class="admin-user-info">
                <div class="admin-user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="admin-user-details">
                    <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($user_name) ?></div>
                    <div style="font-size: 0.75rem; color: #aaa;">Administrator</div>
                </div>
                <a href="<?= $baseUrl ?>logout.php" style="margin-left: auto; color: #e74c3c; cursor: pointer;">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <!-- MAIN LAYOUT -->
        <main class="admin-content" id="adminContent">
            <header class="admin-header">
                <div class="d-flex align-items-center gap-3">
                    <button class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1 class="admin-header-title"><?= htmlspecialchars($currentTitle) ?></h1>
                </div>
            </header>
            
            <div class="admin-main-body">

            <script>
                function toggleSidebar() {
                    const sidebar = document.getElementById('adminSidebar');
                    const content = document.getElementById('adminContent');
                    
                    if (window.innerWidth <= 768) {
                        sidebar.classList.toggle('mobile-show');
                    } else {
                        sidebar.classList.toggle('collapsed');
                        content.classList.toggle('expanded');
                    }
                }
            </script>
