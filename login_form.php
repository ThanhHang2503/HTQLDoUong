<?php
session_start();
include('config.php');
require_once __DIR__ . '/src/models/authorization.php';


if (isset($_POST['submit'])) {
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $password_input = $_POST['password'];

   $select = "SELECT a.account_id, a.full_name, a.password as stored_hash, r.name AS role_name, a.system_status
              FROM accounts a
              JOIN roles r ON r.id = a.role_id
              WHERE a.email = '$email'";

   $result = mysqli_query($conn, $select);
   if ($row = mysqli_fetch_assoc($result)) {
      $stored_hash = $row['stored_hash'];
      $authenticated = false;
      $needs_upgrade = false;

      // 1. Kiểm tra theo chuẩn bảo mật mới (password_hash)
      if (password_verify($password_input, $stored_hash)) {
         $authenticated = true;
      } 
      // 2. Fallback cho tài khoản cũ (MD5) - Tự động nâng cấp hash
      else if (md5($password_input) === $stored_hash) {
         $authenticated = true;
         $needs_upgrade = true;
      }

      if ($authenticated) {
         if ($row['system_status'] === 'pending') {
            $error[] = 'Tài khoản chưa được kích hoạt. Vui lòng chờ Admin phê duyệt!';
         } else if ($row['system_status'] !== 'active') {
            $error[] = 'Tài khoản của bạn đã bị khóa hoặc vô hiệu hóa!';
         } else {
            // Nâng cấp mật khẩu lên hash mới nếu là MD5
            if ($needs_upgrade) {
               $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
               $update_sql = "UPDATE accounts SET password = '$new_hash' WHERE account_id = " . $row['account_id'];
               mysqli_query($conn, $update_sql);
            }

            session_unset();
            $_SESSION['account_id'] = (int) $row['account_id'];
            $_SESSION['role_name'] = $row['role_name'];
            $_SESSION['full_name'] = $row['full_name'];
            
            if ($row['role_name'] === AppRole::ADMIN) {
               header('location:admin/index.php');
            } else {
               header('location:user_page.php?home');
            }
            exit;
         }
      } else {
         $error[] = 'Sai email hoặc mật khẩu!';
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
   <link rel="shortcut icon" href="img/logo.jpg">
   <title>Quản Lý Đồ Uống - Đăng Nhập</title>
   
   <!-- Google Fonts -->
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

   <style>
      body {
         font-family: 'Inter', sans-serif;
         background-color: #f8f9fa;
         margin: 0;
         padding: 0;
      }
      .split-layout {
         min-height: 100vh;
      }
      .bg-image {
         background-image: url('img/login_bg.png');
         background-size: cover;
         background-position: center;
         background-repeat: no-repeat;
         min-height: 250px; /* For mobile */
      }
      @media (min-width: 992px) {
         .bg-image {
            min-height: 100vh;
         }
      }
      .form-wrapper {
         width: 100%;
         max-width: 450px;
         padding: 2.5rem;
      }
      .form-title {
         font-weight: 700;
         color: #212529;
         margin-bottom: 0.5rem;
         font-size: 1.75rem;
      }
      .form-subtitle {
         color: #6c757d;
         font-size: 0.95rem;
         margin-bottom: 2rem;
      }
      .input-group-custom {
         margin-bottom: 1.5rem;
      }
      .input-group-custom label {
         font-weight: 500;
         color: #495057;
         margin-bottom: 0.5rem;
         display: block;
      }
      .input-group-custom input {
         width: 100%;
         padding: 0.8rem 1rem;
         border: 1px solid #dee2e6;
         border-radius: 8px;
         font-size: 1rem;
         transition: all 0.3s ease;
      }
      .input-group-custom input:focus {
         border-color: #0d6efd;
         box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
         outline: none;
      }
      .btn-login {
         width: 100%;
         padding: 0.8rem;
         font-weight: 600;
         font-size: 1rem;
         border-radius: 8px;
         background-color: #0d6efd;
         border: none;
         color: white;
         transition: all 0.3s ease;
      }
      .btn-login:hover {
         background-color: #0b5ed7;
         transform: translateY(-2px);
         box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
      }
      .error-alert {
         background-color: #f8d7da;
         color: #842029;
         padding: 0.75rem 1rem;
         border-radius: 8px;
         margin-bottom: 1.5rem;
         font-size: 0.9rem;
         border: 1px solid #f5c2c7;
      }
      .logo-img {
         width: 80px;
         height: 80px;
         border-radius: 50%;
         object-fit: cover;
         margin-bottom: 1.5rem;
         box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      }
   </style>
</head>

<body>

   <div class="container-fluid p-0">
      <div class="row g-0 split-layout">
         <!-- Image Side -->
         <div class="col-12 col-lg-6 bg-image order-1 order-lg-1"></div>
         
         <!-- Form Side -->
         <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center order-2 order-lg-2 bg-white p-3 p-md-0">
            <div class="form-wrapper">
               <div class="text-center">
                  <img src="img/logo.jpg" alt="Logo" class="logo-img">
                  <h2 class="form-title">ĐĂNG NHẬP</h2>
                  <p class="form-subtitle">Vui lòng đăng nhập để tiếp tục</p>
               </div>

               <?php
               if (isset($error)) {
                  foreach ($error as $err) {
                     echo '<div class="error-alert">' . htmlspecialchars($err) . '</div>';
                  };
               };
               ?>

               <form action="" method="post">
                  <div class="input-group-custom">
                     <label for="email">Địa chỉ Email</label>
                     <input type="email" id="email" name="email" required placeholder="Nhập vào mail của bạn">
                  </div>
                  
                  <div class="input-group-custom">
                     <label for="password">Mật khẩu</label>
                     <input type="password" id="password" name="password" required placeholder="Nhập vào password">
                  </div>

                  <button type="submit" name="submit" value="Đăng nhập" class="btn-login mt-2">
                     Đăng nhập
                  </button>
               </form>
            </div>
         </div>
      </div>
   </div>

</body>

</html>