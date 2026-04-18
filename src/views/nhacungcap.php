<?php
$keyword = trim((string)($_GET['timkiem-ncc'] ?? ''));
$where = "WHERE status = 'active'";
if ($keyword !== '') {
    $safeKeyword = mysqli_real_escape_string($conn, $keyword);
    $where .= " AND (supplier_name LIKE '%{$safeKeyword}%' OR supplier_code LIKE '%{$safeKeyword}%' OR contact_name LIKE '%{$safeKeyword}%' OR phone_number LIKE '%{$safeKeyword}%')";
}

$listSql = "SELECT supplier_id, supplier_code, supplier_name, contact_name, phone_number, email, address
            FROM suppliers
            {$where}
            ORDER BY supplier_id DESC";
$listRs = mysqli_query($conn, $listSql);
$suppliers = mysqli_fetch_all($listRs, MYSQLI_ASSOC);

$editSupplierId = (int)($_GET['edit_supplier_id'] ?? 0);
$editSupplier = null;
if ($editSupplierId > 0) {
    $editSql = "SELECT supplier_id, supplier_code, supplier_name, contact_name, phone_number, email, address
                FROM suppliers
                WHERE supplier_id = {$editSupplierId}
                LIMIT 1";
    $editRs = mysqli_query($conn, $editSql);
    if ($editRs && mysqli_num_rows($editRs) > 0) {
        $editSupplier = mysqli_fetch_assoc($editRs);
    }
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">QUẢN LÝ NHÀ CUNG CẤP</h1>
    <div class="head-line"></div>

    <div class="container-fluid my-3">
        <?php if (isset($_SESSION['supplier_success'])): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($_SESSION['supplier_success']) ?>
            </div>
            <?php unset($_SESSION['supplier_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['supplier_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($_SESSION['supplier_error']) ?>
            </div>
            <?php unset($_SESSION['supplier_error']); ?>
        <?php endif; ?>
    </div>

    <div class="container-fluid row g-3">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header fw-bold">Thêm nhà cung cấp</div>
                <div class="card-body">
                    <form method="POST" action="user_page.php?nhacungcap">
                        <input type="hidden" name="supplier_submit" value="1">

                        <div class="mb-2">
                            <label class="form-label">Tên nhà cung cấp</label>
                            <input class="form-control" type="text" name="supplier_name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Người liên hệ</label>
                            <input class="form-control" type="text" name="contact_name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Số điện thoại</label>
                            <input class="form-control" type="text" name="phone_number" inputmode="numeric" pattern="[0-9]{10,11}" minlength="10" maxlength="11" required title="Số điện thoại chỉ gồm 10 hoặc 11 chữ số">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="address" rows="2" required></textarea>
                        </div>

                        <button class="btn btn-success" type="submit">Thêm nhà cung cấp</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-bold">Tìm kiếm nhà cung cấp</div>
                <div class="card-body">
                    <form method="GET" action="user_page.php" class="row g-2">
                        <input type="hidden" name="nhacungcap" value="1">
                        <div class="col-9">
                            <input class="form-control" type="text" name="timkiem-ncc" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tên, mã, liên hệ, số điện thoại...">
                        </div>
                        <div class="col-3 d-grid">
                            <button class="btn btn-primary" type="submit">Tìm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header fw-bold">Sửa nhà cung cấp</div>
                <div class="card-body">
                    <?php if (!$editSupplier): ?>
                        <p class="mb-0">Chọn nhà cung cấp ở danh sách bên dưới để chỉnh sửa.</p>
                    <?php else: ?>
                        <form method="POST" action="user_page.php?nhacungcap">
                            <input type="hidden" name="supplier_update_submit" value="1">
                            <input type="hidden" name="supplier_id" value="<?= (int)$editSupplier['supplier_id'] ?>">

                            <div class="mb-2">
                                <label class="form-label">Mã nhà cung cấp</label>
                                <input class="form-control" value="<?= htmlspecialchars($editSupplier['supplier_code']) ?>" disabled>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Tên nhà cung cấp</label>
                                <input class="form-control" type="text" name="supplier_name" value="<?= htmlspecialchars($editSupplier['supplier_name']) ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Người liên hệ</label>
                                <input class="form-control" type="text" name="contact_name" value="<?= htmlspecialchars((string)$editSupplier['contact_name']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Số điện thoại</label>
                                <input class="form-control" type="text" name="phone_number" value="<?= htmlspecialchars((string)$editSupplier['phone_number']) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="email" value="<?= htmlspecialchars((string)$editSupplier['email']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars((string)$editSupplier['address']) ?></textarea>
                            </div>

                            <button class="btn btn-warning" type="submit">Cập nhật nhà cung cấp</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-bold">Danh sách nhà cung cấp</div>
                <div class="card-body p-0">
                    <div class="product-table-scroll w-100">
                        <table class="table table-striped table-hover text-center mb-0">
                            <thead>
                                <tr>
                                    <th>Mã</th>
                                    <th>Tên nhà cung cấp</th>
                                    <th>Liên hệ</th>
                                    <th>SĐT</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suppliers as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['supplier_code']) ?></td>
                                        <td><?= htmlspecialchars($s['supplier_name']) ?></td>
                                        <td><?= htmlspecialchars((string)$s['contact_name']) ?></td>
                                        <td><?= htmlspecialchars((string)$s['phone_number']) ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="user_page.php?nhacungcap&edit_supplier_id=<?= (int)$s['supplier_id'] ?>">Sửa</a>
                                            <form method="POST" action="user_page.php?nhacungcap" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhà cung cấp này không?');">
                                                <input type="hidden" name="supplier_delete_submit" value="1">
                                                <input type="hidden" name="supplier_id" value="<?= (int)$s['supplier_id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
