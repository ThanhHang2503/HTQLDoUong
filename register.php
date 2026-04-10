<?php
session_start();

@include('config.php');
require_once __DIR__ . '/src/models/authorization.php';

$roleOptions = [
   AppRole::ADMIN,
   AppRole::MANAGER,
   AppRole::SALES,
   AppRole::WAREHOUSE,
];

if (isset($_POST['submit'])) {
   // Xử lý thêm dữ liệu vào db;
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   $cpass = md5($_POST['cpassword']);
   $role_name = $_POST['role_name'];

   if (!in_array($role_name, $roleOptions, true)) {
      $error[] = 'Role không hợp lệ';
   }

   $select = "SELECT account_id FROM accounts WHERE email = '$email'";

   $result = mysqli_query($conn, $select);

   if (mysqli_num_rows($result) > 0) {

      $error[] = 'Tài khoản đã tồn tại !';
   } else {
      if ($pass != $cpass) {
         $error[] = 'Mật khẩu không khớp';
      } else {
         $roleSql = "SELECT id FROM roles WHERE name = '$role_name' LIMIT 1";
         $roleResult = mysqli_query($conn, $roleSql);
         $roleRow = $roleResult ? mysqli_fetch_assoc($roleResult) : null;

         if (!$roleRow) {
            $error[] = 'Không tìm thấy role trong hệ thống';
         } else {
            $role_id = (int) $roleRow['id'];
            $insert = "INSERT INTO accounts(full_name, email, password, role_id, status) VALUES('$name','$email','$pass',$role_id,'active')";
            mysqli_query($conn, $insert);
            $_SESSION['dang-ky-thanh-cong'] = '<span class="bg-success p-1 error-msg"> Đăng ký thành công ! </span>';
            header('location:login_form.php');
            exit;
         }
      }
   }
};

?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>ĐĂNG KÝ</title>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>

<body>

   <div class="background">
      <div class="form-container">

         <form action="" method="post">
            <h1 class="fw-bolder ">ĐĂNG KÝ</h1>
            <?php
            if (isset($error)) {
               foreach ($error as $error) {
                  echo '<span class="error-msg">' . $error . '</span>';
               };
            };
            ?>
            <input type="text" name="name" required placeholder="Nhập vào tên của bạn">
            <input type="email" name="email" required placeholder="Nhập vào mail của bạn">
            <input type="password" name="password" required placeholder="Nhập mật khẩu">
            <input type="password" name="cpassword" required placeholder="Xác nhận mật khẩu">
            <select name="role_name">
               <option value="sales">Nhân viên bán hàng</option>
               <option value="warehouse">Nhân viên kho</option>
               <option value="manager">Quản lý</option>
               <option value="admin">Admin</option>
            </select>
            <input type="submit" name="submit" value="Đăng ký" class="form-btn">
            <p>Bạn đã có tài khoản? <a href="login_form.php">Đăng nhập ngay</a></p>
         </form>

      </div>
   </div>

</body>

</html>