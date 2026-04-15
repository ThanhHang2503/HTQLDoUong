<?php
session_start();
@include 'config.php';
require_once __DIR__ . '/src/models/functions.php';
require_once __DIR__ . '/src/models/authorization.php';

requireLogin();

// Admin được vào admin panel. Manager/Sales/Warehouse vào user_page
$current_role = currentRole();
if ($current_role === AppRole::ADMIN) {
   if (!isset($_GET['profile']) && !isset($_GET['nhansu']) && !isset($_GET['thongke'])
       && !isset($_GET['baocao_kinhdoanh']) && !isset($_GET['baocao_kho']) 
       && !isset($_GET['baocao_nhansu']) && !isset($_GET['luong_ca_nhan'])
       && !isset($_GET['chucvu']) && !isset($_GET['bangluong'])
       && !isset($_GET['dashboard'])) {
          header('location:admin/index.php');
          exit;
   }
}

$user_id = currentUserId();

$sql = "SELECT a.full_name, r.name AS role_name
        FROM accounts a
        JOIN roles r ON r.id = a.role_id
        WHERE a.account_id = '$user_id' AND a.system_status = 'active'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if (!$row) {
   session_unset();
   session_destroy();
   header('location:login_form.php');
   exit;
}

$user_name = $row['full_name'];
$_SESSION['role_name'] = $row['role_name'];

// API lịch sử chức vụ (JSON) - Phải xử lý TRƯỚC khi có bất kỳ HTML output nào
if (isset($_GET['chucvu_history_api'])) {
   requireLogin();
   $acc_id = (int)($_GET['account_id'] ?? 0);
   if ($acc_id > 0) {
       $hr = mysqli_query($conn, "SELECT eph.start_date, eph.end_date, p.position_name, eph.reason
           FROM employee_positions_history eph
           JOIN positions p ON p.position_id = eph.position_id
           WHERE eph.account_id = $acc_id ORDER BY eph.start_date DESC");
       $hist = $hr ? mysqli_fetch_all($hr, MYSQLI_ASSOC) : [];
   } else { $hist = []; }
   header('Content-Type: application/json; charset=utf-8');
   echo json_encode($hist);
   exit;
}

// Xử lý IN BẢNG LƯƠNG (Phải chạy TRƯỚC khi render layout để bản in sạch)
if (isset($_GET['bangluong']) && isset($_GET['print'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/bangluong.php';
   exit;
}

if (isset($_GET['luong_ca_nhan']) && isset($_GET['print'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/luong_ca_nhan.php';
   exit;
}

require_once __DIR__ . '/src/views/layout.php';
renderAppLayoutStart($user_name, $current_role);
?>

<?php

// Khu tim kiếm
if (isset($_GET['timkiem-donhang'])) {
   requirePermission(AppPermission::PROCESS_ORDERS);
   require_once __DIR__ . '/src/models/timkiem_donhang.php';
   require_once __DIR__ . '/src/views/donhang.php';
} 

if (isset($_GET['timkiem-khachhang'])) {
   requirePermission(AppPermission::MANAGE_CUSTOMERS);
   require_once __DIR__ . '/src/models/timkiem_khachhang.php';
   require_once __DIR__ . '/src/views/khachhang.php';
} 

if (isset($_GET['timkiem-sanpham'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   require_once __DIR__ . '/src/models/timkiem_sanpham.php';
   require_once __DIR__ . '/src/views/sanpham.php';
} 

if (empty($_GET)) {
   header('location:user_page.php?home');
   exit;
}

if (isset($_GET['home'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/home.php';
}


if (isset($_GET['dashboard'])) {
   header('location:user_page.php?home');
   exit;
}

// Thêm khách hàng
if (isset($_POST['customer_name']) && isset($_POST['phone_number'])) {
   requirePermission(AppPermission::MANAGE_CUSTOMERS);
   $customer_name = $_POST['customer_name'];
   $phone_number = $_POST['phone_number'];
   $sql = "INSERT INTO customers (customer_name, phone_number) VALUES ('" . $customer_name . "', '" . $phone_number . "')";
   mysqli_query($conn, $sql);
   $_SESSION['them_kh_thanh_cong'] = 'Bạn đã thêm 1 khách hàng thành công';
}

//Phần loại
//Thêm loai
if (!isset($_POST['category_id']) && isset($_POST['category_name'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $category_name = $_POST['category_name'];
   print_r($category_name);
   $insert = "INSERT INTO category (category_name) values ('$category_name')";
   mysqli_query($conn, $insert);
   header('location:user_page.php?loai');
}
//Sửa loại
if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $category_id = $_POST['category_id'];
   $category_name = $_POST['category_name'];
   $update = "UPDATE category SET category_name = '$category_name' WHERE category_id = '$category_id'";
   mysqli_query($conn, $update);
   header('location:user_page.php?loai');
}
//Hết phần loại
// phan san pham 

//Thêm sản phẩm
if (!isset($_POST['item_id']) && isset($_POST['item_name']) && isset($_POST['category_id']) && isset($_POST['description'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $item_name = trim((string)$_POST['item_name']);
   $category_id = (int)$_POST['category_id'];
   $description = trim((string)$_POST['description']);
   $purchase_price = 0;
   $unit_price = 0;
   $stock_quantity = 0;

   if ($item_name === '' || $description === '' || $category_id <= 0) {
      echo "<script>alert('Vui lòng nhập đầy đủ thông tin sản phẩm hợp lệ (tên, danh mục, mô tả).');</script>";
      echo "<script>window.location.href='user_page.php?sanpham=them';</script>";
      exit;
   }

   $safe_name = mysqli_real_escape_string($conn, $item_name);
   $safe_description = mysqli_real_escape_string($conn, $description);
   $insert = "INSERT INTO items (item_name, category_id, description, unit_price, purchase_price, stock_quantity, item_status) values('{$safe_name}', {$category_id}, '{$safe_description}', {$unit_price}, {$purchase_price}, {$stock_quantity}, 'active')";
   mysqli_query($conn, $insert);
   header('location:user_page.php?sanpham');
}

//Sửa sản phẩm
if (isset($_POST['item_id']) &&  isset($_POST['item_name']) && isset($_POST['category_id']) && isset($_POST['description']) && isset($_POST['unit_price'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $item_id = $_POST['item_id'];
   $item_name = $_POST['item_name'];
   $category_id = $_POST['category_id'];
   $description = $_POST['description'];
   $unit_price = $_POST['unit_price'];

   $update = "UPDATE items 
           SET item_name = '$item_name', category_id = $category_id, description = '$description', unit_price = $unit_price
           WHERE item_id = $item_id";

   mysqli_query($conn, $update);
   header('location:user_page.php?sanpham');
}

if (isset($_GET['sanpham'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   switch ($_GET['sanpham']) {
      case 'them':
         require_once __DIR__ . '/src/models/them_sanpham.php';
         break;
      case 'xoa':
         require_once __DIR__ . '/src/models/xoa_sanpham.php';
         break;
      case 'sua':
         $id_item = $_GET['id'];
         require_once __DIR__ . '/src/models/sua_sanpham.php';
         break;

      default:
         require_once __DIR__ . '/src/views/sanpham.php';
         break;
   }
}

if (isset($_POST['supplier_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   $supplier_name = trim((string)($_POST['supplier_name'] ?? ''));
   $contact_name = trim((string)($_POST['contact_name'] ?? ''));
   $phone_number = trim((string)($_POST['phone_number'] ?? ''));
   $email = trim((string)($_POST['email'] ?? ''));
   $address = trim((string)($_POST['address'] ?? ''));

   if ($supplier_name === '') {
      $_SESSION['supplier_error'] = 'Tên nhà cung cấp là bắt buộc.';
      header('location:user_page.php?nhacungcap');
      exit;
   }

   $supplier_code = 'SUP-' . date('YmdHis') . '-' . rand(100, 999);
   $safe_code = mysqli_real_escape_string($conn, $supplier_code);
   $safe_name = mysqli_real_escape_string($conn, $supplier_name);
   $safe_contact = mysqli_real_escape_string($conn, $contact_name);
   $safe_phone = mysqli_real_escape_string($conn, $phone_number);
   $safe_email = mysqli_real_escape_string($conn, $email);
   $safe_address = mysqli_real_escape_string($conn, $address);

   $insert = "INSERT INTO suppliers (supplier_code, supplier_name, contact_name, phone_number, email, address, status)
              VALUES ('{$safe_code}', '{$safe_name}', '{$safe_contact}', '{$safe_phone}', '{$safe_email}', '{$safe_address}', 'active')";

   try {
      mysqli_query($conn, $insert);
      $_SESSION['supplier_success'] = 'Thêm nhà cung cấp thành công.';
   } catch (\Throwable $e) {
      $_SESSION['supplier_error'] = 'Không thể thêm nhà cung cấp. Vui lòng kiểm tra dữ liệu trùng.';
   }

   header('location:user_page.php?nhacungcap');
   exit;
}

if (isset($_POST['supplier_update_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   $supplier_id = (int)($_POST['supplier_id'] ?? 0);
   $supplier_name = trim((string)($_POST['supplier_name'] ?? ''));
   $contact_name = trim((string)($_POST['contact_name'] ?? ''));
   $phone_number = trim((string)($_POST['phone_number'] ?? ''));
   $email = trim((string)($_POST['email'] ?? ''));
   $address = trim((string)($_POST['address'] ?? ''));

   if ($supplier_id <= 0 || $supplier_name === '') {
      $_SESSION['supplier_error'] = 'Dữ liệu cập nhật nhà cung cấp không hợp lệ.';
      header('location:user_page.php?nhacungcap');
      exit;
   }

   $safe_name = mysqli_real_escape_string($conn, $supplier_name);
   $safe_contact = mysqli_real_escape_string($conn, $contact_name);
   $safe_phone = mysqli_real_escape_string($conn, $phone_number);
   $safe_email = mysqli_real_escape_string($conn, $email);
   $safe_address = mysqli_real_escape_string($conn, $address);

   $update = "UPDATE suppliers
              SET supplier_name = '{$safe_name}',
                  contact_name = '{$safe_contact}',
                  phone_number = '{$safe_phone}',
                  email = '{$safe_email}',
                  address = '{$safe_address}'
              WHERE supplier_id = {$supplier_id}";

   try {
      mysqli_query($conn, $update);
      $_SESSION['supplier_success'] = 'Cập nhật nhà cung cấp thành công.';
   } catch (\Throwable $e) {
      $_SESSION['supplier_error'] = 'Không thể cập nhật nhà cung cấp.';
   }

   header('location:user_page.php?nhacungcap');
   exit;
}

if (isset($_POST['supplier_delete_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   $supplier_id = (int)($_POST['supplier_id'] ?? 0);
   if ($supplier_id <= 0) {
      $_SESSION['supplier_error'] = 'Nhà cung cấp không hợp lệ.';
      header('location:user_page.php?nhacungcap');
      exit;
   }

   $delete = "UPDATE suppliers SET status = 'inactive' WHERE supplier_id = {$supplier_id}";
   mysqli_query($conn, $delete);
   $_SESSION['supplier_success'] = 'Đã xóa (ẩn) nhà cung cấp thành công.';
   header('location:user_page.php?nhacungcap');
   exit;
}

if (isset($_GET['nhacungcap'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   require_once __DIR__ . '/src/views/nhacungcap.php';
}
// Phần đơn hàng 
if (isset($_GET['donhang'])) {
   requirePermission(AppPermission::PROCESS_ORDERS);
   switch ($_GET['donhang']) {
      case 'them':
         require_once __DIR__ . '/src/models/them_donhang.php';
         break;
      case 'xoa':
         require_once __DIR__ . '/src/models/xoa_donhang.php';
         break;
      // case 'sua':
      //    $id_item = $_GET['id'];
      //    require_once __DIR__ . '/src/models/sua_donhang.php';
      //    break;
      case 'in':
         $item_id = $_GET['id'];
         require_once __DIR__ . '/src/models/in_donhang.php';
         break;

      case 'luu':
         require_once __DIR__ . '/src/models/luu_donhang.php';
         break;
      default:
         require_once __DIR__ . '/src/views/donhang.php';
         break;
   }
}
if (isset($_GET['khachhang'])) {
   requirePermission(AppPermission::MANAGE_CUSTOMERS);
   switch ($_GET['khachhang']) {
      case 'tang_dan':
         require_once __DIR__ . '/src/models/tang_dan_khachhang.php';
         break;
      case 'giam_dan':
         require_once __DIR__ . '/src/models/giam_dan_khachhang.php';
         break;
      case 'them':
         require_once __DIR__ . '/src/models/them_khachhang.php';
         break;
      default:
         require_once __DIR__ . '/src/views/khachhang.php';
         break;
   }
}
// Phan nhan su --> Dieu huong 
if (isset($_GET['nhansu'])) {
   requirePermission(AppPermission::MANAGE_ACCOUNTS);
   require_once __DIR__ . '/src/views/nhansu.php';
}

//Phan Loai --> Dieu huong
if (isset($_GET['loai'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   switch ($_GET['loai']) {
      case 'them':
         require_once __DIR__ . '/src/models/them_loai.php';
         break;
      case 'xoa':
         require_once __DIR__ . '/src/models/xoa_loai.php';
         break;
      case 'sua':
         require_once __DIR__ . '/src/models/sua_loai.php';
         break;

      default:
         require_once __DIR__ . '/src/views/loai.php';
         break;
   }
}
if (isset($_GET['thongke'])) {
   requirePermission(AppPermission::VIEW_REPORTS);
   switch ($_GET['thongke']) {
         // case 'dashboard':
         //    require_once __DIR__. '/src/views/dashboard.php';
         //    break;
         // Các trường hợp xử lý với yêu cầu get
      default:
         require_once __DIR__ . '/src/views/thongke.php';
         break;
   }
}

if (isset($_POST['warehouse_receipt_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   require_once __DIR__ . '/src/warehouse/Models.php';
   require_once __DIR__ . '/src/warehouse/Repositories.php';
   require_once __DIR__ . '/src/warehouse/Services.php';

   $itemIds = $_POST['item_id'] ?? [];
   $quantities = $_POST['quantity'] ?? [];
   $importPrices = $_POST['import_price'] ?? [];
   $unitPrices = $_POST['unit_price'] ?? [];
   if (!is_array($itemIds)) {
      $itemIds = [$itemIds];
   }
   if (!is_array($quantities)) {
      $quantities = [$quantities];
   }
   if (!is_array($importPrices)) {
      $importPrices = [$importPrices];
   }
   if (!is_array($unitPrices)) {
      $unitPrices = [$unitPrices];
   }

   $items = [];
   foreach ($itemIds as $idx => $rawItemId) {
      $itemId = (int)$rawItemId;
      $qty = (int)($quantities[$idx] ?? 0);
      $importPrice = (float)($importPrices[$idx] ?? 0);
      $unitPrice = (float)($unitPrices[$idx] ?? 0);
      if ($itemId > 0 && $qty > 0 && $importPrice >= 0 && $unitPrice >= 0) {
         $items[] = [
            'item_id' => $itemId,
            'quantity' => $qty,
            'import_price' => $importPrice,
            'unit_price' => $unitPrice
         ];
      }
   }

   $supplierId = (int)($_POST['supplier_id'] ?? 0);
   $note = trim((string)($_POST['note'] ?? ''));
   $importDate = (string)($_POST['import_date'] ?? date('Y-m-d'));

   $receiptService = new \Warehouse\Services\GoodsReceiptService($conn);
   $result = $receiptService->createReceipt([
      'supplier_id' => $supplierId,
      'import_date' => $importDate,
      'note' => $note !== '' ? $note : null,
      'items' => $items
   ], currentUserId());

   if ($result['success']) {
      $_SESSION['warehouse_receipt_success'] = $result['message'] . ' - ' . ($result['receipt_code'] ?? '');
   } else {
      $_SESSION['warehouse_receipt_error'] = $result['error'] ?? ($result['message'] ?? 'Tạo phiếu nhập thất bại');
   }

   header('location:user_page.php?phieunhap');
   exit;
}

if (isset($_POST['warehouse_receipt_update_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   require_once __DIR__ . '/src/warehouse/Models.php';
   require_once __DIR__ . '/src/warehouse/Repositories.php';
   require_once __DIR__ . '/src/warehouse/Services.php';

   $receiptId = (int)($_POST['receipt_id'] ?? 0);
   $itemIds = $_POST['item_id'] ?? [];
   $quantities = $_POST['quantity'] ?? [];
   $importPrices = $_POST['import_price'] ?? [];
   $unitPrices = $_POST['unit_price'] ?? [];
   if (!is_array($itemIds)) {
      $itemIds = [$itemIds];
   }
   if (!is_array($quantities)) {
      $quantities = [$quantities];
   }
   if (!is_array($importPrices)) {
      $importPrices = [$importPrices];
   }
   if (!is_array($unitPrices)) {
      $unitPrices = [$unitPrices];
   }

   $items = [];
   foreach ($itemIds as $idx => $rawItemId) {
      $itemId = (int)$rawItemId;
      $qty = (int)($quantities[$idx] ?? 0);
      $importPrice = (float)($importPrices[$idx] ?? 0);
      $unitPrice = (float)($unitPrices[$idx] ?? 0);
      if ($itemId > 0 && $qty > 0 && $importPrice >= 0 && $unitPrice >= 0) {
         $items[] = [
            'item_id' => $itemId,
            'quantity' => $qty,
            'import_price' => $importPrice,
            'unit_price' => $unitPrice
         ];
      }
   }

   $supplierId = (int)($_POST['supplier_id'] ?? 0);
   $note = trim((string)($_POST['note'] ?? ''));
   $importDate = (string)($_POST['import_date'] ?? date('Y-m-d'));

   $receiptService = new \Warehouse\Services\GoodsReceiptService($conn);
   $result = $receiptService->updateReceipt($receiptId, [
      'supplier_id' => $supplierId,
      'import_date' => $importDate,
      'note' => $note !== '' ? $note : null,
      'items' => $items
   ], currentUserId());

   if ($result['success']) {
      $_SESSION['warehouse_receipt_success'] = $result['message'];
   } else {
      $_SESSION['warehouse_receipt_error'] = $result['error'] ?? ($result['message'] ?? 'Cập nhật phiếu nhập thất bại');
   }

   header('location:user_page.php?phieunhap');
   exit;
}

if (isset($_POST['warehouse_receipt_delete_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   require_once __DIR__ . '/src/warehouse/Models.php';
   require_once __DIR__ . '/src/warehouse/Repositories.php';
   require_once __DIR__ . '/src/warehouse/Services.php';

   $receiptId = (int)($_POST['receipt_id'] ?? 0);
   $receiptService = new \Warehouse\Services\GoodsReceiptService($conn);
   $result = $receiptService->deleteReceipt($receiptId, currentUserId());

   if ($result['success']) {
      $_SESSION['warehouse_receipt_success'] = $result['message'];
   } else {
      $_SESSION['warehouse_receipt_error'] = $result['error'] ?? ($result['message'] ?? 'Xóa phiếu nhập thất bại');
   }

   header('location:user_page.php?phieunhap');
   exit;
}

if (isset($_GET['phieunhap'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   require_once __DIR__ . '/src/views/phieunhap.php';
}

if (isset($_GET['phieuxuat'])) {
   requirePermission(AppPermission::MANAGE_WAREHOUSE);
   require_once __DIR__ . '/src/views/phieuxuat.php';
}

if (isset($_GET['kho_thongke'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   require_once __DIR__ . '/src/views/kho_thongke.php';
}

// ===== ROUTES MỚI =====

// Hồ sơ cá nhân
if (isset($_GET['profile'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/profile.php';
}

// Quản lý chức vụ
if (isset($_GET['chucvu'])) {
   requirePermission(AppPermission::MANAGE_STAFF);
   require_once __DIR__ . '/src/views/chucvu.php';
}


// Bảng lương (Manager quản lý)
if (isset($_GET['bangluong'])) {
   requirePermission(AppPermission::MANAGE_STAFF);
   require_once __DIR__ . '/src/views/bangluong.php';
}

// Lương cá nhân (mọi NV)
if (isset($_GET['luong_ca_nhan'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/luong_ca_nhan.php';
}

// Đơn nghỉ phép
if (isset($_GET['donnghi'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/donnghi.php';
}

// Đơn nghỉ việc
if (isset($_GET['donnghiviec'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/donnghiviec.php';
}

// Báo cáo kho
if (isset($_GET['baocao_kho'])) {
   if (!can(AppPermission::MANAGE_WAREHOUSE) && !can(AppPermission::VIEW_REPORTS)) {
      requirePermission(AppPermission::MANAGE_WAREHOUSE); // giữ redirect chuẩn forbidden
   }
   require_once __DIR__ . '/src/views/baocao_kho.php';
}

// Báo cáo kinh doanh
if (isset($_GET['baocao_kinhdoanh'])) {
   requirePermission(AppPermission::VIEW_REPORTS);
   require_once __DIR__ . '/src/views/baocao_kinhdoanh.php';
}

// Báo cáo nhân sự
if (isset($_GET['baocao_nhansu'])) {
   if (!can(AppPermission::MANAGE_STAFF) && !can(AppPermission::VIEW_REPORTS)) {
       requirePermission(AppPermission::MANAGE_STAFF); // will trigger standard forbidden redirect
   }
   require_once __DIR__ . '/src/views/baocao_nhansu.php';
}

// (Đã xóa tính năng backup)

renderAppLayoutEnd($current_role);
?>