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

// API tạo sản phẩm mới inline (dùng trong form phiếu nhập)
if (isset($_POST['create_item_api'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   header('Content-Type: application/json; charset=utf-8');

   $item_name      = trim((string)($_POST['item_name'] ?? ''));
   $category_id    = (int)($_POST['category_id'] ?? 0);
   $description    = trim((string)($_POST['description'] ?? ''));
   $unit_price     = (float)($_POST['unit_price'] ?? 0);
   $purchase_price = (float)($_POST['purchase_price'] ?? 0);

   if ($item_name === '') {
      echo json_encode(['success' => false, 'message' => 'Tên sản phẩm không được để trống.']);
      exit;
   }
   if ($unit_price <= 0) {
      echo json_encode(['success' => false, 'message' => 'Giá bán phải lớn hơn 0.']);
      exit;
   }
   if ($purchase_price < 0) {
      echo json_encode(['success' => false, 'message' => 'Giá nhập không hợp lệ.']);
      exit;
   }
   if ($description === '') {
      echo json_encode(['success' => false, 'message' => 'Mô tả sản phẩm không được để trống.']);
      exit;
   }

   // Kiểm tra trùng tên
   $safe_name = mysqli_real_escape_string($conn, $item_name);
   $dup_r = mysqli_query($conn, "SELECT item_id FROM items WHERE item_name = '$safe_name' AND item_status = 'active' LIMIT 1");
   if ($dup_r && mysqli_num_rows($dup_r) > 0) {
      $dup_row = mysqli_fetch_assoc($dup_r);
      echo json_encode(['success' => false, 'message' => 'Sản phẩm "' . $item_name . '" đã tồn tại.', 'existing_id' => (int)$dup_row['item_id']]);
      exit;
   }

   $safe_desc = mysqli_real_escape_string($conn, $description);
   $cat_part  = $category_id > 0 ? $category_id : 'NULL';

   $insert_sql = "INSERT INTO items (item_name, category_id, description, unit_price, purchase_price, stock_quantity, item_status)
                  VALUES ('{$safe_name}', {$cat_part}, '{$safe_desc}', {$unit_price}, {$purchase_price}, 0, 'active')";
   $ok = mysqli_query($conn, $insert_sql);

   if ($ok) {
      $new_id = (int)mysqli_insert_id($conn);
      // Fetch item_code do trigger MySQL tự sinh
      $code_r = mysqli_query($conn, "SELECT item_code FROM items WHERE item_id = $new_id LIMIT 1");
      $item_code = $code_r ? (mysqli_fetch_assoc($code_r)['item_code'] ?? '') : '';
      echo json_encode([
         'success'   => true,
         'item_id'   => $new_id,
         'item_code' => $item_code,
         'item_name' => $item_name,
         'message'   => 'Đã thêm sản phẩm "' . $item_name . '" (Mã: ' . $item_code . ') thành công.'
      ]);
   } else {
      echo json_encode(['success' => false, 'message' => 'Không thể thêm sản phẩm. Vui lòng thử lại.']);
   }
   exit;
}

// API lấy thông tin chi tiết phiếu nhập (dùng cho modal Sửa/Xem)
if (isset($_GET['get_receipt_api'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   header('Content-Type: application/json; charset=utf-8');
   $receipt_id = (int)($_GET['receipt_id'] ?? 0);
   if ($receipt_id > 0) {
      require_once __DIR__ . '/src/warehouse/Models.php';
      require_once __DIR__ . '/src/warehouse/Repositories.php';
      $repo = new \Warehouse\Repositories\InventoryReceiptRepository($conn);
      $receipt = $repo->getReceiptById($receipt_id);
      if ($receipt) {
         echo json_encode(['success' => true, 'data' => $receipt]);
         exit;
      }
   }
   echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu nhập.']);
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

// =====================================================================
// XỬ LÝ TẤT CẢ POST / REDIRECT TRƯỚC KHI RENDER LAYOUT
// (tránh lỗi "headers already sent")
// =====================================================================

if (empty($_GET)) {
   header('location:user_page.php?home');
   exit;
}

if (isset($_GET['dashboard'])) {
   header('location:user_page.php?home');
   exit;
}

// Thêm khách hàng
if (isset($_POST['add_customer_submit'])) {
   requirePermission(AppPermission::MANAGE_CUSTOMERS);
   $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
   $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
   $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
   $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');

   $sql = "INSERT INTO customers (customer_name, phone_number, email, address) 
           VALUES ('$customer_name', '$phone_number', '$email', '$address')";
   
   if (mysqli_query($conn, $sql)) {
       setNotify('success', 'Bạn đã thêm 1 khách hàng thành công');
   } else {
       setNotify('error', 'Lỗi hệ thống khi thêm khách hàng.');
   }
   header("location:user_page.php?khachhang");
   exit;
}

// Sửa khách hàng
if (isset($_POST['update_customer_submit'])) {
   requirePermission(AppPermission::MANAGE_CUSTOMERS);
   $customer_id = (int)$_POST['customer_id'];
   $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
   $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $address = mysqli_real_escape_string($conn, $_POST['address']);

   // Kiểm tra trùng SĐT hoặc Email ngoại trừ bản thân
   $check = mysqli_query($conn, "SELECT 1 FROM customers WHERE (phone_number = '$phone_number' OR (email != '' AND email = '$email')) AND customer_id != $customer_id LIMIT 1");
   if ($check && mysqli_num_rows($check) > 0) {
       setNotify('error', 'Số điện thoại hoặc Email đã tồn tại cho một khách hàng khác.', 'Lỗi trùng lặp');
   } else {
       $sql = "UPDATE customers SET 
               customer_name = '$customer_name', 
               phone_number = '$phone_number', 
               email = '$email', 
               address = '$address' 
               WHERE customer_id = $customer_id";
       if (mysqli_query($conn, $sql)) {
           setNotify('success', 'Cập nhật thông tin khách hàng thành công.', 'Hoàn thành');
       } else {
           setNotify('error', 'Lỗi hệ thống khi cập nhật khách hàng.', 'Lỗi');
       }
   }
   header("location:user_page.php?khachhang=sua&id=$customer_id");
   exit;
}

//Phần loại
//Thêm loai
if (!isset($_POST['category_id']) && isset($_POST['category_name'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $category_name = $_POST['category_name'];
   $insert = "INSERT INTO category (category_name) values ('$category_name')";
   mysqli_query($conn, $insert);
   header('location:user_page.php?loai');
   exit;
}
//Sửa loại
if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $category_id = $_POST['category_id'];
   $category_name = $_POST['category_name'];
   $update = "UPDATE category SET category_name = '$category_name' WHERE category_id = '$category_id'";
   mysqli_query($conn, $update);
   header('location:user_page.php?loai');
   exit;
}
//Hết phần loại

// Thêm sản phẩm
if (!isset($_POST['item_id']) && isset($_POST['item_name']) && isset($_POST['category_id']) && isset($_POST['description'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $item_name = trim((string)$_POST['item_name']);
   $category_id = (int)$_POST['category_id'];
   $description = trim((string)$_POST['description']);
   $purchase_price = 0;
   $unit_price = 0;
   $stock_quantity = 0;

   // 0. Kiểm tra ảnh bắt buộc (Yêu cầu mới)
   $upload_file = $_FILES['item_image_file'] ?? null;
   if (!$upload_file || $upload_file['error'] !== UPLOAD_ERR_OK) {
       $_SESSION['sanpham_error'] = 'Bắt buộc phải tải lên ảnh sản phẩm.';
       header('location:user_page.php?sanpham=them');
       exit;
   }

   // Kiểm tra định dạng ảnh (JPG, PNG)
   $allowed_mime   = ['image/jpeg', 'image/jpg', 'image/png'];
   $max_size_bytes = 5 * 1024 * 1024; // 5MB

   $finfo = finfo_open(FILEINFO_MIME_TYPE);
   $mime  = finfo_file($finfo, $upload_file['tmp_name']);
   finfo_close($finfo);

   if (!in_array($mime, $allowed_mime)) {
       $_SESSION['sanpham_error'] = 'Ảnh không hợp lệ! Chỉ chấp nhận JPG, JPEG hoặc PNG.';
       header('location:user_page.php?sanpham=them');
       exit;
   }

   if ($item_name === '' || $description === '' || $category_id <= 0) {
      $_SESSION['sanpham_error'] = 'Vui lòng nhập đầy đủ thông tin sản phẩm hợp lệ (tên, danh mục, mô tả).';
      header('location:user_page.php?sanpham=them');
      exit;
   }

   $safe_name        = mysqli_real_escape_string($conn, $item_name);
   $safe_description = mysqli_real_escape_string($conn, $description);

   // 1. INSERT sản phẩm vào DB trước để lấy item_id
   $insert = "INSERT INTO items (item_name, category_id, description, unit_price, purchase_price, stock_quantity, item_status)
              VALUES ('{$safe_name}', {$category_id}, '{$safe_description}', {$unit_price}, {$purchase_price}, {$stock_quantity}, 'active')";
   
   if (!mysqli_query($conn, $insert)) {
       $_SESSION['sanpham_error'] = 'Lỗi hệ thống khi thêm sản phẩm: ' . mysqli_error($conn);
       header('location:user_page.php?sanpham=them');
       exit;
   }
   
   $new_item_id = (int)mysqli_insert_id($conn);

   // 2. Xử lý lưu ảnh theo item_id
   $dest_filename = $new_item_id . '.jpg';
   $dest_path     = __DIR__ . '/img/' . $dest_filename;

   if (move_uploaded_file($upload_file['tmp_name'], $dest_path)) {
      $item_image_path = 'img/' . $dest_filename;
      $esc_img = mysqli_real_escape_string($conn, $item_image_path);
      mysqli_query($conn, "UPDATE items SET item_image = '$esc_img' WHERE item_id = $new_item_id");
      $_SESSION['sanpham_success'] = 'Thêm sản phẩm và ảnh thành công.';
   } else {
      $_SESSION['sanpham_error'] = 'Thêm sản phẩm thành công nhưng không thể lưu ảnh.';
   }

   header('location:user_page.php?sanpham');
   exit;
}

// Sửa sản phẩm
if (isset($_GET['sanpham']) && $_GET['sanpham'] === 'sua' && isset($_POST['item_id']) && isset($_POST['item_name'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   $item_id     = (int)$_POST['item_id'];
   $item_name   = trim((string)($_POST['item_name'] ?? ''));
   $category_id = (int)($_POST['category_id'] ?? 0);
   $description = trim((string)($_POST['description'] ?? ''));
   $unit_price  = (float)($_POST['unit_price'] ?? 0);
   $sale_status = ($_POST['sale_status'] ?? 'selling') === 'stopped' ? 'stopped' : 'selling';

   if ($item_id <= 0 || $item_name === '' || $category_id <= 0 || $unit_price < 0) {
      $_SESSION['sanpham_error'] = 'Vui lòng nhập đầy đủ thông tin hợp lệ.';
      header('location:user_page.php?sanpham=sua&id=' . ($item_id ?: 0));
      exit;
   }

   // Xử lý upload ảnh mới (nếu có)
   $upload_file = $_FILES['item_image_file'] ?? null;
   if ($upload_file && $upload_file['error'] === UPLOAD_ERR_OK) {
       $allowed_mime   = ['image/jpeg', 'image/jpg', 'image/png'];
       $max_size_bytes = 5 * 1024 * 1024; // 5MB

       $finfo = finfo_open(FILEINFO_MIME_TYPE);
       $mime  = finfo_file($finfo, $upload_file['tmp_name']);
       finfo_close($finfo);

       if (in_array($mime, $allowed_mime) && $upload_file['size'] <= $max_size_bytes) {
           $dest_filename = $item_id . '.jpg';
           $dest_path     = __DIR__ . '/img/' . $dest_filename;
           if (move_uploaded_file($upload_file['tmp_name'], $dest_path)) {
               $esc_img = mysqli_real_escape_string($conn, 'img/' . $dest_filename);
               mysqli_query($conn, "UPDATE items SET item_image = '$esc_img' WHERE item_id = $item_id");
           }
       } else {
           $_SESSION['sanpham_error'] = 'Ảnh không hợp lệ hoặc quá lớn (tối đa 5MB).';
           header('location:user_page.php?sanpham=sua&id=' . $item_id);
           exit;
       }
   }

   // Nếu tồn kho = 0 → bắt buộc ngừng bán
   $stock_r = mysqli_query($conn, "SELECT stock_quantity, category_id FROM items WHERE item_id = $item_id LIMIT 1");
   $stock_row = $stock_r ? mysqli_fetch_assoc($stock_r) : null;
   if ($stock_row && (int)$stock_row['stock_quantity'] === 0) {
      $sale_status = 'stopped';
   }

   // Chặn thay đổi danh mục nếu sản phẩm đã phát sinh dữ liệu (phiếu nhập/tồn kho)
   if ($stock_row) {
      $currentCatId = (int)$stock_row['category_id'];
      $hasData = (int)$stock_row['stock_quantity'] > 0;
      if (!$hasData) {
         $chkReceipt = mysqli_query($conn, "SELECT 1 FROM inventory_receipt_items WHERE item_id = $item_id LIMIT 1");
         $hasData = $chkReceipt && mysqli_num_rows($chkReceipt) > 0;
      }
      if ($hasData && $category_id !== $currentCatId) {
         $_SESSION['sanpham_error'] = 'Không thể thay đổi danh mục vì sản phẩm đã phát sinh dữ liệu (phiếu nhập/tồn kho).';
         header('location:user_page.php?sanpham=sua&id=' . $item_id);
         exit;
      }
   }

   $safe_name = mysqli_real_escape_string($conn, $item_name);
   $safe_desc = mysqli_real_escape_string($conn, $description);

   $update = "UPDATE items
              SET item_name   = '{$safe_name}',
                  category_id = {$category_id},
                  description = '{$safe_desc}',
                  unit_price  = {$unit_price},
                  sale_status = '{$sale_status}'
              WHERE item_id = {$item_id}";

   if (mysqli_query($conn, $update)) {
      $_SESSION['sanpham_success'] = 'Cập nhật sản phẩm thành công.';
   } else {
      $_SESSION['sanpham_error'] = 'Cập nhật thất bại: ' . mysqli_error($conn);
   }
   header('location:user_page.php?sanpham=sua&id=' . $item_id);
   exit;
}

// Thêm nhà cung cấp
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

// Sửa nhà cung cấp
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

// Xóa nhà cung cấp (kiểm tra ràng buộc phiếu nhập trước khi xóa)
if (isset($_POST['supplier_delete_submit'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);

   $supplier_id = (int)($_POST['supplier_id'] ?? 0);
   if ($supplier_id <= 0) {
      $_SESSION['supplier_error'] = 'Nhà cung cấp không hợp lệ.';
      header('location:user_page.php?nhacungcap');
      exit;
   }

   // Kiểm tra xem nhà cung cấp có tồn tại trong phiếu nhập không
   $check_sql = "SELECT COUNT(*) AS cnt FROM inventory_receipts WHERE supplier_id = {$supplier_id}";
   $check_rs  = mysqli_query($conn, $check_sql);
   $check_row = $check_rs ? mysqli_fetch_assoc($check_rs) : null;
   $used_count = (int)($check_row['cnt'] ?? 0);

   if ($used_count > 0) {
      $_SESSION['supplier_error'] = 'Không thể xóa nhà cung cấp vì đã tồn tại trong phiếu nhập.';
      header('location:user_page.php?nhacungcap');
      exit;
   }

   $delete = "UPDATE suppliers SET status = 'inactive' WHERE supplier_id = {$supplier_id}";
   mysqli_query($conn, $delete);
   $_SESSION['supplier_success'] = 'Đã xóa (ẩn) nhà cung cấp thành công.';
   header('location:user_page.php?nhacungcap');
   exit;
}

// Xử lý phiếu nhập kho
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
   $supplierId = (int)($_POST['supplier_id'] ?? 0);
   $note = trim((string)($_POST['note'] ?? ''));
   $importDate = (string)($_POST['import_date'] ?? date('Y-m-d'));

   $receiptService = new \Warehouse\Services\GoodsReceiptService($conn);
   $result = $receiptService->updateReceiptHeaderOnly($receiptId, [
      'supplier_id' => $supplierId,
      'import_date' => $importDate,
      'note' => $note !== '' ? $note : null
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

   $_SESSION['warehouse_receipt_error'] = 'Hệ thống không cho phép xóa phiếu nhập để đảm bảo toàn vẹn dữ liệu tồn kho.';

   header('location:user_page.php?phieunhap');
   exit;
}

// =====================================================================
// QUẢN LÝ CHỨC VỤ - POST HANDLING
// =====================================================================
if (isset($_POST['add_position'])) {
    requirePermission(AppPermission::MANAGE_STAFF);
    $name    = trim(mysqli_real_escape_string($conn, $_POST['position_name'] ?? ''));
    $salary  = (int)($_POST['base_salary'] ?? 0);
    $desc    = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));
    if ($name === '' || $salary <= 0) {
        setNotify('error', 'Tên chức vụ và lương cơ bản là bắt buộc.');
    } else {
        mysqli_query($conn, "INSERT INTO positions (position_name, base_salary, description) VALUES ('$name', $salary, '$desc')");
        setNotify('success', "Đã thêm chức vụ '$name'.");
    }
    header('Location: user_page.php?chucvu'); exit;
}

if (isset($_POST['edit_position'])) {
    requirePermission(AppPermission::MANAGE_STAFF);
    $pid    = (int)($_POST['position_id'] ?? 0);
    $name   = trim(mysqli_real_escape_string($conn, $_POST['position_name'] ?? ''));
    $salary = (int)($_POST['base_salary'] ?? 0);
    $desc   = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($pid > 0 && $name !== '' && $salary > 0) {
        mysqli_query($conn, "UPDATE positions SET position_name='$name', base_salary=$salary,
                              description='$desc', is_active=$active WHERE position_id=$pid");
        setNotify('success', 'Đã cập nhật chức vụ.');
    } else {
        setNotify('error', 'Dữ liệu chức vụ không hợp lệ.');
    }
    header('Location: user_page.php?chucvu'); exit;
}

if (isset($_POST['change_employee_position'])) {
    requirePermission(AppPermission::MANAGE_STAFF);
    $acc_id  = (int)($_POST['account_id'] ?? 0);
    $new_pos = (int)($_POST['new_position_id'] ?? 0);
    $reason  = trim(mysqli_real_escape_string($conn, $_POST['change_reason'] ?? ''));
    $date    = trim($_POST['change_date'] ?? date('Y-m-d'));
    // Chặn gán chức vụ Quản trị viên (position_id=1) từ giao diện HR
    if ($acc_id > 0 && $new_pos > 0 && $new_pos != 1) {
        mysqli_query($conn, "UPDATE employee_positions_history SET end_date='$date'
                              WHERE account_id=$acc_id AND end_date IS NULL");
        mysqli_query($conn, "INSERT INTO employee_positions_history (account_id, position_id, start_date, reason, created_by)
                              VALUES ($acc_id, $new_pos, '$date', '$reason', " . currentUserId() . ")");
        mysqli_query($conn, "UPDATE accounts SET position_id=$new_pos, role_id=$new_pos WHERE account_id=$acc_id");
        setNotify('success', 'Đã đổi chức vụ nhân viên thành công.');
    } elseif ($new_pos == 1) {
        setNotify('error', 'Không thể gán chức vụ Quản trị viên từ giao diện nhân sự.');
    }
    header('Location: user_page.php?chucvu'); exit;
}

// =====================================================================
// RENDER LAYOUT - chỉ chạy sau khi toàn bộ POST/redirect đã xong
// =====================================================================

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

if (isset($_GET['home'])) {
   requireLogin();
   require_once __DIR__ . '/src/views/home.php';
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
         require_once __DIR__ . '/src/models/sua_sanpham.php';
         break;
      case 'xem':
         require_once __DIR__ . '/src/models/xem_sanpham.php';
         break;

      default:
         require_once __DIR__ . '/src/views/sanpham.php';
         break;
   }
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
      case 'giam_dan':
      case 'id_tang':
      case 'id_giam':
      case 'ngay_tang':
      case 'ngay_giam':
         require_once __DIR__ . '/src/models/tang_dan_khachhang.php';
         break;
      case 'sua':
         require_once __DIR__ . '/src/models/sua_khachhang.php';
         break;
      case 'xem':
         require_once __DIR__ . '/src/models/xem_khachhang.php';
         break;
      case 'xoa':
         require_once __DIR__ . '/src/models/xoa_khachhang.php';
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

if (isset($_GET['phieunhap'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   require_once __DIR__ . '/src/views/phieunhap.php';
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
    if (!can(AppPermission::VIEW_REPORTS) && !can(AppPermission::PROCESS_ORDERS)) {
        requirePermission(AppPermission::VIEW_REPORTS);
    }
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