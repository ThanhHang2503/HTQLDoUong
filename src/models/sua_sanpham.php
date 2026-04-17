<?php
if (!isset($_GET['id'])) {
    redirect('user_page.php?sanpham');
    exit;
}

$item_id = (int)$_GET['id'];
$sql     = "SELECT i.*, c.category_name FROM items i LEFT JOIN category c ON c.category_id = i.category_id WHERE i.item_id = $item_id LIMIT 1";
$result  = mysqli_query($conn, $sql);
$row     = $result ? mysqli_fetch_assoc($result) : null;

if (!$row) {
    $_SESSION['product_delete_error'] = 'Sản phẩm không tồn tại.';
    redirect('user_page.php?sanpham');
    exit;
}

// Kiểm tra sản phẩm đã phát sinh dữ liệu (có trong phiếu nhập hoặc tồn kho > 0)
$hasReceiptData = false;
$checkReceiptSql = "SELECT COUNT(*) AS cnt FROM inventory_receipt_items WHERE item_id = {$item_id}";
$checkReceiptRs = mysqli_query($conn, $checkReceiptSql);
$checkReceiptRow = $checkReceiptRs ? mysqli_fetch_assoc($checkReceiptRs) : null;
if (($checkReceiptRow && (int)$checkReceiptRow['cnt'] > 0) || (int)$row['stock_quantity'] > 0) {
    $hasReceiptData = true;
}

$cat_sql = 'SELECT * FROM category ORDER BY category_name ASC';
$cat_rs  = mysqli_query($conn, $cat_sql);
$list_of_categories = $cat_rs ? mysqli_fetch_all($cat_rs, MYSQLI_ASSOC) : [];
?>

<div class="mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <?php if (isset($_SESSION['sanpham_success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['sanpham_success']) ?></div>
                <?php unset($_SESSION['sanpham_success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['sanpham_error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['sanpham_error']) ?></div>
                <?php unset($_SESSION['sanpham_error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-danger text-white">
                    CHỈNH SỬA SẢN PHẨM
                    <span class="float-end badge bg-light text-dark font-monospace"><?= htmlspecialchars($row['item_code'] ?? '') ?></span>
                </div>
                <div class="card-body">
                    <form action="user_page.php?sanpham=sua" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="item_id" value="<?= (int)$row['item_id'] ?>">

                        <!-- Hiển thị ảnh hiện tại -->
                        <div class="text-center mb-3">
                            <label class="fw-bold d-block mb-2">Ảnh hiện tại:</label>
                            <?php 
                            $id_img = 'img/' . (int)$row['item_id'] . '.jpg';
                            $img_src = file_exists(__DIR__ . '/../../' . $id_img) ? $id_img : ($row['item_image'] ?: 'img/1.jpg');
                            ?>
                            <img src="<?= htmlspecialchars($img_src) ?>" 
                                 width="60" height="60" 
                                 class="rounded shadow-sm border" 
                                 style="object-fit: cover;"
                                 onerror="this.src='img/1.jpg'">
                        </div>

                        <!-- Input sửa ảnh -->
                        <div class="form-group mt-3">
                            <label for="item_image_file" class="fw-bold">
                                <i class="fa-solid fa-image me-1 text-danger"></i>Thay đổi ảnh mới (tùy chọn):
                            </label>
                            <input type="file" class="form-control" id="item_image_file" name="item_image_file"
                                   accept=".jpg,.jpeg,.png">
                            <small class="text-muted">Để trống nếu muốn giữ nguyên ảnh cũ. Chỉ nhận JPG/PNG.</small>
                        </div>

                        <div class="form-group mt-3">
                            <label for="item_name">Tên sản phẩm:</label>
                            <input type="text" class="form-control" id="item_name" name="item_name"
                                   value="<?= htmlspecialchars($row['item_name']) ?>" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="category_id">Danh mục:</label>
                            <?php if ($hasReceiptData): ?>
                                <input type="hidden" name="category_id" value="<?= (int)$row['category_id'] ?>">
                            <?php endif; ?>
                            <select class="form-control" id="category_id" <?= $hasReceiptData ? '' : 'name="category_id"' ?> <?= $hasReceiptData ? 'disabled' : '' ?>>
                                <?php foreach ($list_of_categories as $cat): ?>
                                    <option value="<?= (int)$cat['category_id'] ?>"
                                        <?= ((int)$row['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($hasReceiptData): ?>
                                <small class="text-warning">⚠️ Sản phẩm đã phát sinh dữ liệu (phiếu nhập/tồn kho), không thể thay đổi danh mục.</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mt-3">
                            <label for="description">Mô tả:</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars((string)$row['description']) ?></textarea>
                        </div>

                        <div class="form-group mt-3">
                            <label for="unit_price">Đơn giá (VND):</label>
                            <input type="number" class="form-control" id="unit_price" name="unit_price"
                                   min="0" step="1000" value="<?= (int)$row['unit_price'] ?>" required>
                        </div>

                        <div class="form-group mt-3">
                            <label for="sale_status">Trạng thái bán:</label>
                            <select class="form-control" id="sale_status" name="sale_status">
                                <option value="selling" <?= ($row['sale_status'] ?? 'selling') === 'selling' ? 'selected' : '' ?>>
                                    🟢 Đang bán
                                </option>
                                <option value="stopped" <?= ($row['sale_status'] ?? '') === 'stopped' ? 'selected' : '' ?>>
                                    🔴 Ngừng bán
                                </option>
                            </select>
                            <?php if ((int)$row['stock_quantity'] === 0): ?>
                                <small class="text-danger">⚠️ Tồn kho = 0, trạng thái tự động là Ngừng bán.</small>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn mt-3 btn-danger w-100">
                            <i class="fa-solid fa-pen me-1"></i>Cập nhật sản phẩm
                        </button>
                        <a href="user_page.php?sanpham" class="btn mt-2 btn-outline-secondary w-100">Thoát</a>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>