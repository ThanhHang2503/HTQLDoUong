<?php

if(isset($_GET['id'])){
    $account_id = $_GET['id'];
    $sql = "select * from accounts where account_id = $account_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_array($result);
    $full_name = $row['full_name'];
    $email = $row['email'];
    $password = $row['password'];
    $type = $row['type'];
}
?>

<div class="mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        CHỈNH SỬA NHÂN SỰ
                    </div>
                    
                    <div class="card-body">
                        <form action="user_page.php" method="POST">
                            <input type="hidden" name="account_id" value="<?php echo $row['account_id']; ?>">
                            <div class="form-group mt-3">
                                <label for="full_name">Họ tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $row['full_name']; ?>" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $row['email']; ?>" required> 
                            </div>
                            <div class="form-group mt-3">
                                <label for="password">Đặt lại mật khẩu</label>
                                <input type="text" class="form-control" id="password" name="password" value="" placeholder="Nhập mật khẩu mới" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="type">Quyền</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="user" <?php if ($row['type'] == 'user') echo 'selected'; ?>>User</option>
                                    <option value="admin" <?php if ($row['type'] == 'admin') echo 'selected'; ?>>Admin</option>                           
                                </select>
                            </div>                     
                
                            <button type="submit" class="btn mt-3 btn-danger">Cập nhật nhân sự</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>