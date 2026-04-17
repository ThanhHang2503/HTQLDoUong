<?php
// Lấy danh sách danh mục để vào dropdown
$cat_sql = "SELECT category_id, category_name FROM category ORDER BY category_name ASC";
$cat_rs = mysqli_query($conn, $cat_sql);
$categories = $cat_rs ? mysqli_fetch_all($cat_rs, MYSQLI_ASSOC) : [];

// Xử lý nút sort toggle
if (isset($_GET['sort_btn'])) {
    if (strpos($_GET['sort_btn'], 'code_') === 0) {
        $_GET['sort_col'] = 'item_code';
        $_GET['sort_dir'] = substr($_GET['sort_btn'], 5);
    } elseif (strpos($_GET['sort_btn'], 'price_') === 0) {
        $_GET['sort_col'] = 'unit_price';
        $_GET['sort_dir'] = substr($_GET['sort_btn'], 6);
    }
}

// Xây dựng câu SQL thống nhất
$where_clauses = ["i.item_status = 'active'"];

// Tìm kiếm theo tên
if (isset($_GET['timkiem-sanpham']) && trim($_GET['timkiem-sanpham']) !== '') {
    $search_term = mysqli_real_escape_string($conn, trim($_GET['timkiem-sanpham']));
    $where_clauses[] = "i.item_name LIKE '%$search_term%'";
}

// Lọc theo danh mục
if (isset($_GET['filter_cat']) && trim($_GET['filter_cat']) !== '') {
    $cat_id = (int)$_GET['filter_cat'];
    $where_clauses[] = "i.category_id = $cat_id";
}

// Sắp xếp
$order_by = "CASE WHEN i.stock_quantity <= 0 THEN 1 ELSE 0 END ASC, CAST(i.stock_quantity AS UNSIGNED) DESC, i.item_id DESC";
if (isset($_GET['sort_col']) && in_array($_GET['sort_col'], ['item_code', 'unit_price'])) {
    $sort_dir = (isset($_GET['sort_dir']) && strtoupper($_GET['sort_dir']) === 'ASC') ? 'ASC' : 'DESC';
    $order_by = "i." . $_GET['sort_col'] . " $sort_dir";
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT i.*, c.category_name 
        FROM items i 
        LEFT JOIN category c ON i.category_id = c.category_id 
        WHERE $where_sql 
        ORDER BY $order_by";

$ds_sanpham_rs = mysqli_query($conn, $sql);
$ds_sanpham    = $ds_sanpham_rs ? mysqli_fetch_all($ds_sanpham_rs, MYSQLI_ASSOC) : [];
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

        <!-- Thanh tìm kiếm và bộ lọc sản phẩm -->
        <form method="GET" action="user_page.php" class="col-8 my-2" role="search">
            <input type="hidden" name="sanpham" value="">
            
            <?php 
            $sc = $_GET['sort_col'] ?? '';
            $sd = $_GET['sort_dir'] ?? '';
            ?>
            <input type="hidden" name="sort_col" id="sort_col" value="<?= htmlspecialchars($sc) ?>">
            <input type="hidden" name="sort_dir" id="sort_dir" value="<?= htmlspecialchars($sd) ?>">
            
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input class="form-control" type="search" placeholder="Nhập tên SP..." name='timkiem-sanpham' value="<?= htmlspecialchars($_GET['timkiem-sanpham'] ?? '') ?>" aria-label="Search">
                </div>
                
                <div class="col-md-3">
                    <select class="form-select" name="filter_cat" onchange="this.form.submit()">
                        <option value="">-- Danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= (isset($_GET['filter_cat']) && $_GET['filter_cat'] == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 d-flex gap-2">
                    <button class="btn btn-success" type="submit" onclick="document.getElementById('sort_col').value=''; document.getElementById('sort_dir').value='';">Tìm</button>
                    
                    <?php 
                    $next_code_dir = ($sc == 'item_code' && $sd == 'ASC') ? 'DESC' : 'ASC';
                    $next_price_dir = ($sc == 'unit_price' && $sd == 'ASC') ? 'DESC' : 'ASC';
                    
                    $code_icon = ($sc == 'item_code') ? ($sd == 'ASC' ? '↑' : '↓') : '';
                    $price_icon = ($sc == 'unit_price') ? ($sd == 'ASC' ? '↑' : '↓') : '';
                    ?>
                    
                    <button type="submit" name="sort_btn" title="Sắp xếp theo Mã sản phẩm" value="code_<?= $next_code_dir ?>" class="btn <?= ($sc == 'item_code') ? 'btn-primary' : 'btn-outline-secondary' ?> px-2 text-nowrap">
                        Mã SP <?= $code_icon ?>
                    </button>
                    <button type="submit" name="sort_btn" title="Sắp xếp theo Giá bán" value="price_<?= $next_price_dir ?>" class="btn <?= ($sc == 'unit_price') ? 'btn-primary' : 'btn-outline-secondary' ?> px-2 text-nowrap">
                        Giá bán <?= $price_icon ?>
                    </button>
                    
                    <a href="user_page.php?sanpham" class="btn btn-outline-danger ms-auto text-nowrap" title="Xóa toàn bộ bộ lọc và sắp xếp">Hủy lọc</a>
                </div>
            </div>
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
                        <th>Ảnh</th>
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
                            <td class="align-middle text-center">
                                <?php
                                // Ưu tiên: ảnh upload riêng theo ID → item_image DB → fallback item_id % 13
                                $id_img   = 'img/' . (int)$sp['item_id'] . '.jpg';
                                $db_img   = trim((string)($sp['item_image'] ?? ''));
                                $fallback = 'img/' . (((int)$sp['item_id'] % 13) + 1) . '.jpg';
                                // Kiểm tra file thật trên server (img/ nằm ở root, src/views/ nằm sâu 2 cấp)
                                if (file_exists(__DIR__ . '/../../' . $id_img)) {
                                    $img_src = $id_img;
                                } elseif ($db_img !== '' && file_exists(__DIR__ . '/../../' . $db_img)) {
                                    $img_src = $db_img;
                                } else {
                                    $img_src = $fallback;
                                }
                                ?>
                                <img src="<?= htmlspecialchars($img_src) ?>"
                                     alt="Ảnh SP"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"
                                     class="shadow-sm"
                                     onerror="this.src='<?= htmlspecialchars($fallback) ?>'">
                            </td>
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