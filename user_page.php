<?php
session_start();
@include 'config.php';
@include 'function.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
   header('location:login_form.php');
}
?>
<?php

$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];

$sql = "select full_name from accounts where account_id = '$user_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);
$user_name = $row[0];

require_once __DIR__ . '/src/views/header.php';
?>

<?php

// Khu tim kiếm
if (isset($_GET['timkiem-donhang'])) {
   require_once __DIR__ . '/src/models/timkiem_donhang.php';
   require_once __DIR__ . '/src/views/donhang.php';
} 

if (isset($_GET['timkiem-khachhang'])) {
   require_once __DIR__ . '/src/models/timkiem_khachhang.php';
   require_once __DIR__ . '/src/views/khachhang.php';
} 

if (isset($_GET['timkiem-sanpham'])) {
   require_once __DIR__ . '/src/models/timkiem_sanpham.php';
   require_once __DIR__ . '/src/views/sanpham.php';
} 


if (isset($_GET['dashboard'])) {
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
   $customer_name = $_POST['customer_name'];
   $phone_number = $_POST['phone_number'];
   $sql = "INSERT INTO customers (customer_name, phone_number) VALUES ('" . $customer_name . "', '" . $phone_number . "')";
   mysqli_query($conn, $sql);
   $_SESSION['them_kh_thanh_cong'] = 'Bạn đã thêm 1 khách hàng thành công';
}

//Phần loại
//Thêm loai
if (!isset($_POST['category_id']) && isset($_POST['category_name'])) {
   $category_name = $_POST['category_name'];
   print_r($category_name);
   $insert = "INSERT INTO category (category_name) values ('$category_name')";
   mysqli_query($conn, $insert);
   header('location:user_page.php?loai');
}
//Sửa loại
if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
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
if (!isset($_POST['account_id']) && isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['type'])) {
   $full_name = htmlspecialchars($_POST['full_name']);
   $email = htmlspecialchars($_POST['email']);
   $password = md5($_POST['password']);
   $type = htmlspecialchars($_POST['type']);

   $insert = "INSERT INTO accounts (full_name, email, password, type) values('$full_name','$email','$password', '$type')";
   mysqli_query($conn, $insert);
   header('location:user_page.php?nhansu');
}
// Sửa nhân sự
if (isset($_POST['account_id']) &&  isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['type'])) {
   $account_id =  htmlspecialchars($_POST['account_id']);
   $full_name = htmlspecialchars($_POST['full_name']);
   $email = $_POST['email'];
   $password = md5($_POST['password']);
   $type = $_POST['type'];

   $update = "UPDATE accounts 
           SET full_name = '$full_name', email =  '$email', password = '$password', type = '$type' 
           WHERE account_id = $account_id";

   mysqli_query($conn, $update);
   header('location:user_page.php?nhansu');
}

// Phan nhan su --> Dieu huong 
if (isset($_GET['nhansu'])) {
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