<?php
session_start();

@include('config.php');

if (isset($_POST['submit'])) {
   // Xử lý thêm dữ liệu vào db;
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   $cpass = md5($_POST['cpassword']);
   $user_type = $_POST['user_type'];

   $select = " SELECT * FROM accounts WHERE email = '$email' && password = '$pass' ";

   $result = mysqli_query($conn, $select);

   if (mysqli_num_rows($result) > 0) {

      $error[] = 'Tài khoản đã tồn tại !';
   } else {
      if ($pass != $cpass) {
         $error[] = 'Mật khẩu không khớp';
      } else {
         $insert = "INSERT INTO accounts(full_name, email, password, type) VALUES('$name','$email','$pass','$user_type')";
         mysqli_query($conn, $insert);
         $_SESSION['dang-ky-thanh-cong'] = '<span class="bg-success p-1 error-msg"> Đăng ký thành công ! </span>';
         header('location:login_form.php');
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
            <select name="user_type">
               <option value="user">Người dùng</option>
               <option value="admin">Quản trị</option>
            </select>
            <input type="submit" name="submit" value="Đăng ký" class="form-btn">
            <p>Bạn đã có tài khoản? <a href="login_form.php">Đăng nhập ngay</a></p>
         </form>

      </div>
   </div>

</body>

</html>