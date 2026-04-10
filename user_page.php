<?php
session_start();
@include 'config.php';
require_once __DIR__ . '/src/models/functions.php';
require_once __DIR__ . '/src/models/authorization.php';

requireLogin();

if (currentRole() === AppRole::ADMIN) {
   header('location:admin/index.php');
   exit;
}

$user_id = currentUserId();

$sql = "SELECT a.full_name, r.name AS role_name
        FROM accounts a
        JOIN roles r ON r.id = a.role_id
        WHERE a.account_id = '$user_id' AND a.status = 'active'";
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

require_once __DIR__ . '/src/views/header.php';
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


if (isset($_GET['dashboard'])) {
   requirePermission(AppPermission::VIEW_DASHBOARD);
   switch ($_GET['dashboard']) {
         // case 'dashboard':
         //    require_once __DIR__. '/src/views/dashboard.php';
         //    break;
         // Các trường hợp xử lý với yêu cầu get
      default:
         require_once __DIR__ . '/src/views/dashboard.php';
         break;
   }
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
if (!isset($_POST['item_id']) && isset($_POST['item_name']) && isset($_POST['category_id']) && isset($_POST['description']) && isset($_POST['unit_price'])) {
   requirePermission(AppPermission::MANAGE_CATALOG);
   $item_name = $_POST['item_name'];
   $category_id = $_POST['category_id'];
   $description = $_POST['description'];
   $unit_price = $_POST['unit_price'];

   $insert = "INSERT INTO items (item_name, category_id, description, unit_price) values('$item_name','$category_id','$description', '$unit_price')";
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
// Them nhan su
if (!isset($_POST['account_id']) && isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['role_id'])) {
   requirePermission(AppPermission::MANAGE_STAFF);
   $full_name = htmlspecialchars($_POST['full_name']);
   $email = htmlspecialchars($_POST['email']);
   $password = md5($_POST['password']);
   $role_id = (int) $_POST['role_id'];
   $status = isset($_POST['status']) && $_POST['status'] === 'inactive' ? 'inactive' : 'active';

   $insert = "INSERT INTO accounts (full_name, email, password, role_id, status) values('$full_name','$email','$password', $role_id, '$status')";
   mysqli_query($conn, $insert);
   header('location:user_page.php?nhansu');
}
// Sửa nhân sự
if (isset($_POST['account_id']) &&  isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['role_id'])) {
   requirePermission(AppPermission::MANAGE_STAFF);
   $account_id =  htmlspecialchars($_POST['account_id']);
   $full_name = htmlspecialchars($_POST['full_name']);
   $email = $_POST['email'];
   $password = md5($_POST['password']);
   $role_id = (int) $_POST['role_id'];
   $status = isset($_POST['status']) && $_POST['status'] === 'inactive' ? 'inactive' : 'active';

   $update = "UPDATE accounts 
           SET full_name = '$full_name', email =  '$email', password = '$password', role_id = $role_id, status = '$status' 
           WHERE account_id = $account_id";

   mysqli_query($conn, $update);
   header('location:user_page.php?nhansu');
}

// Phan nhan su --> Dieu huong 
if (isset($_GET['nhansu'])) {
   requirePermission(AppPermission::MANAGE_STAFF);
   switch ($_GET['nhansu']) {
      case 'them':
         require_once __DIR__ . '/src/models/them_nhansu.php';
         break;
      case 'xoa':
         require_once __DIR__ . '/src/models/xoa_nhansu.php';
         break;
      case 'khoiphuc':
         require_once __DIR__ . '/src/models/xoa_nhansu.php';
         break;
      case 'sua':
         $id_item = $_GET['id'];
         require_once __DIR__ . '/src/models/sua_nhansu.php';
         break;
      case 'ten_tang_dan':

      default:
         require_once __DIR__ . '/src/views/nhansu.php';
         break;
   }
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
?>



<?php
require_once __DIR__ . '/src/views/footer.php';
?> 