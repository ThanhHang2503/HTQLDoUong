<?php
session_start();
include('config.php');


if (isset($_POST['submit'])) {
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   //  $user_type = $_POST['type'];
   $select = " SELECT * FROM accounts WHERE email = '$email' && password = '$pass' ";

   $result = mysqli_query($conn, $select);
   if (mysqli_num_rows($result) > 0) {

      $row = mysqli_fetch_array($result);
      print_r($row);
      if ($row['type'] == 'admin') {

         $_SESSION['admin_id'] = $row['account_id'];
         header('location:user_page.php?dashboard');
      } elseif ($row['type'] == 'user') {

         $_SESSION['user_id'] = $row['account_id'];
         header('location:user_page.php?dashboard');
      }
   } else {
      $error[] = 'Sai email hoặc mật khẩu!';
   }
};


?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="shortcut icon" href="img/logo.jfif">
   <title>ÔNG GIÀ XIN CHÀO</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>

<body>

   <div class="background">
      <div class="form-container">
         <form action="" method="post">
            <h1 class="fw-bolder ">ĐĂNG NHẬP</h1>
            <img class="img-fluid" width="120px" style="border-radius:100%" src="img/logo.jfif" alt="">
            <?php
            if (isset($error)) {
               foreach ($error as $error) {
                  echo '<span class="bg-danger p-1 error-msg">' . $error . '</span>';
               };
            };

            if (isset($_SESSION['dang-ky-thanh-cong'])) {
               echo $_SESSION['dang-ky-thanh-cong'];
               unset($_SESSION['dang-ky-thanh-cong']);
            }
            ?>
            <input type="email" name="email" required placeholder="Nhập vào mail của bạn">
            <input type="password" name="password" required placeholder="Nhập vào password">
            <input type="submit" name="submit" value="Đăng nhập" class="form-btn btn">
            <p>Bạn chưa có tài khoản? <a href="register.php">Đăng kí ngay</a></p>
         </form>

      </div>
   </div>

</body>

</html>