<?php
if (!isset($ds_sp_timkiem)) {
    $sql = "SELECT i.*, c.category_name
            FROM items i
            LEFT JOIN category c ON i.category_id = c.category_id
            WHERE i.item_status = 'active'
            ORDER BY CASE WHEN i.stock_quantity <= 0 THEN 1 ELSE 0 END ASC, CAST(i.stock_quantity AS UNSIGNED) DESC, i.item_id DESC";
    $ds_sanpham_rs = mysqli_query($conn, $sql);
    $ds_sanpham    = $ds_sanpham_rs ? mysqli_fetch_all($ds_sanpham_rs, MYSQLI_ASSOC) : [];
} else {
    $ds_sanpham = $ds_sp_timkiem;
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">SẢN PHẨM</h1>

    <?php if (isset($_SESSION['product_delete_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['product_delete_error']) ?>
        </div>
        <?php unset($_SESSION['product_delete_error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['product_delete_success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['product_delete_success']) ?>
        </div>
        <?php unset($_SESSION['product_delete_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['sanpham_success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['sanpham_success']) ?>
        </div>
        <?php unset($_SESSION['sanpham_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['sanpham_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['sanpham_error']) ?>
        </div>
        <?php unset($_SESSION['sanpham_error']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['timkiem-sanpham'])): ?>
        <h4 class="fw-bolder text-center text-success">Kết quả cho từ khóa '<?= htmlspecialchars($_GET['timkiem-sanpham'] ?? '') ?>'</h4>
    <?php endif; ?>

    <div class="head-line"></div>
    <div class="container-fluid row justify-content-between">

        <!-- Thanh tìm kiếm sản phẩm -->
        <form method="GET" action="user_page.php?" class="d-flex col-6 my-2" role="search">
            <input class="form-control me-2" type="search" placeholder="Nhập tên sản phẩm" name='timkiem-sanpham' aria-label="Search">
            <button class="btn btn-outline-success" type="submit">Tìm</button>
        </form>

        <?php if (can(AppPermission::MANAGE_CATALOG)) : ?>
            <div class="text-end col-4">
                <a href="user_page.php?sanpham=them" class="my-2 btn btn-success fw-bolder">
                    <i class="fa-solid fa-file-circle-plus"></i> Thêm
                </a>
            </div>
        <?php endif; ?>

        <div class="product-table-scroll w-100">
            <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Mã SP</th>
                        <th>Tên</th>
                        <th>Mô tả về sản phẩm</th>
                        <th>Giá bán</th>
                        <th>Loại</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <?php if (can(AppPermission::MANAGE_CATALOG)): ?>
                            <th>Thao tác</th>
                        <?php else: ?>
                            <th>Ngày tạo</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ds_sanpham as $sp): ?>
                        <tr>
                            <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($sp['item_code'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($sp['item_name']) ?></td>
                            <td><?= htmlspecialchars((string)$sp['description']) ?></td>
                            <td><?= number_format((int)$sp['unit_price'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string)($sp['category_name'] ?? '')) ?></td>
                            <td><?= (int)($sp['stock_quantity'] ?? 0) ?></td>
                            <td>
                                <?php if (($sp['sale_status'] ?? 'selling') === 'selling'): ?>
                                    <span class="badge bg-success">🟢 Đang bán</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">🔴 Ngừng bán</span>
                                <?php endif; ?>
                            </td>
                            <?php if (can(AppPermission::MANAGE_CATALOG)): ?>
                                <td>
                                    <a href="user_page.php?sanpham=xem&id=<?= (int)$sp['item_id'] ?>"
                                       class="btn btn-sm btn-outline-info me-1" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="user_page.php?sanpham=sua&id=<?= (int)$sp['item_id'] ?>"
                                       class="btn btn-sm btn-outline-success" title="Chỉnh sửa">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                </td>
                            <?php else: ?>
                                <td>
                                    <a href="user_page.php?sanpham=xem&id=<?= (int)$sp['item_id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</div>

</div>