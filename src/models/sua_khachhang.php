<?php
if (isset($_GET['khachhang']) && $_GET['khachhang'] == 'sua' && isset($_GET['id'])) :
    $cid = (int)$_GET['id'];
    $q = mysqli_query($conn, "SELECT * FROM customers WHERE customer_id = $cid");
    $customer = mysqli_fetch_assoc($q);

    if (!$customer) {
        echo '<div class="alert alert-danger">Không tìm thấy khách hàng.</div>';
        return;
    }
?>
    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white">
                        <p class="h3 fw-bolder mb-0"><i class="fa-solid fa-user-pen me-2"></i>Chỉnh sửa khách hàng</p>
                    </div>
                    <div class="card-body">
                        <form action="user_page.php?khachhang=sua&id=<?= $cid ?>" method="POST">
                            <input type="hidden" name="customer_id" value="<?= $cid ?>">
                            
                            <div class="form-group pt-3">
                                <label for="customer_name" class="fw-bold">Tên khách hàng</label>
                                <input required type="text" class="form-control" id="customer_name" name="customer_name" value="<?= htmlspecialchars($customer['customer_name']) ?>">
                            </div>
                            
                            <div class="row pt-3">
                                <div class="col-md-6 form-group">
                                    <label for="phone_number" class="fw-bold">Số điện thoại</label>
                                    <input required type="text" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($customer['phone_number']) ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="email" class="fw-bold">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>">
                                </div>
                            </div>

                            <div class="form-group pt-3">
                                <label for="address" class="fw-bold">Địa chỉ</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                            </div>
                
                            <div class="mt-4 pt-2 border-top d-flex justify-content-between">
                                <a href="user_page.php?khachhang" class="btn btn-secondary px-4">Quay lại</a>
                                <button type="submit" name="update_customer_submit" class="btn btn-primary fw-bold px-5">Cập nhật thông tin</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
