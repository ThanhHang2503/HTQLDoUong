<?php

if(isset($_GET['id'])){
    $account_id = $_GET['id'];
    $sql = "SELECT * FROM accounts WHERE account_id = $account_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_array($result);
}

$rolesResult = mysqli_query($conn, "SELECT id, name FROM roles ORDER BY id ASC");
$roles = $rolesResult ? mysqli_fetch_all($rolesResult, MYSQLI_ASSOC) : [];
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
                                <label for="role_id">Quyền</label>
                                <select class="form-control" id="role_id" name="role_id">
                                    <?php foreach ($roles as $role) : ?>
                                        <option value="<?= $role['id'] ?>" <?php if ((int) $row['role_id'] === (int) $role['id']) echo 'selected'; ?>>
                                            <?= roleLabel($role['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mt-3">
                                <label for="status">Trạng thái</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php if (($row['status'] ?? 'active') === 'active') echo 'selected'; ?>>Hoạt động</option>
                                    <option value="inactive" <?php if (($row['status'] ?? 'active') === 'inactive') echo 'selected'; ?>>Ngừng hoạt động</option>
                                </select>
                            </div>
                
                            <button type="submit" class="btn mt-3 btn-danger">Cập nhật nhân sự</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>