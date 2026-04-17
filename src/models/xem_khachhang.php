<?php
if (isset($_GET['khachhang']) && $_GET['khachhang'] == 'xem' && isset($_GET['id'])) :
    $cid = (int)$_GET['id'];
    
    // Lấy thông tin khách hàng
    $q = mysqli_query($conn, "SELECT * FROM customers WHERE customer_id = $cid");
    $customer = mysqli_fetch_assoc($q);

    if (!$customer) {
        echo '<div class="alert alert-danger">Không tìm thấy khách hàng.</div>';
        return;
    }

    // Lấy lịch sử hóa đơn
    $q_orders = mysqli_query($conn, "SELECT invoice_id, creation_time, total, status FROM invoices WHERE customer_id = $cid ORDER BY creation_time DESC LIMIT 10");
    $orders = mysqli_fetch_all($q_orders, MYSQLI_ASSOC);
?>
    <div class="px-5 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-address-card me-2"></i>Chi tiết khách hàng</h2>
            <a href="user_page.php?khachhang" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại</a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body text-center py-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fa-solid fa-user-tie fa-3x text-secondary"></i>
                        </div>
                        <h4 class="fw-bold"><?= htmlspecialchars($customer['customer_name']) ?></h4>
                        <p class="text-muted small mb-0">Khách hàng ID: #<?= $cid ?></p>
                        <hr>
                        <div class="text-start">
                            <p class="mb-2"><strong><i class="fa-solid fa-phone me-2"></i>SĐT:</strong> <?= htmlspecialchars($customer['phone_number'] ?: 'N/A') ?></p>
                            <p class="mb-2"><strong><i class="fa-solid fa-envelope me-2"></i>Email:</strong> <?= htmlspecialchars($customer['email'] ?: 'N/A') ?></p>
                            <p class="mb-0"><strong><i class="fa-solid fa-location-dot me-2"></i>Đ/C:</strong> <?= htmlspecialchars($customer['address'] ?: 'N/A') ?></p>
                        </div>
                        <div class="mt-4">
                            <a href="user_page.php?khachhang=sua&id=<?= $cid ?>" class="btn btn-primary w-100 fw-bold">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Chỉnh sửa hồ sơ
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-history me-2"></i>Lịch sử mua hàng gần đây</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Mã HĐ</th>
                                        <th>Ngày tạo</th>
                                        <th class="text-end">Tổng tiền</th>
                                        <th class="text-center">Trạng thái</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($orders) > 0): ?>
                                        <?php foreach ($orders as $o): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold">#<?= $o['invoice_id'] ?></td>
                                                <td class="small"><?= date('d/m/Y H:i', strtotime($o['creation_time'])) ?></td>
                                                <td class="text-end text-success fw-bold"><?= number_format($o['total'], 0, ',', '.') ?>đ</td>
                                                <td class="text-center">
                                                    <span class="badge bg-success small">Hoàn thành</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="user_page.php?donhang=in&id=<?= $o['invoice_id'] ?>" class="btn btn-sm btn-light border">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Chưa có lịch sử giao dịch.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
