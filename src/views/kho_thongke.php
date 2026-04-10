<?php
$keyword = trim((string)($_GET['kw'] ?? ''));
$categoryFilter = (int)($_GET['category_id'] ?? 0);
$onlyLowStock = isset($_GET['only_low']) && $_GET['only_low'] === '1';
$lowStockThreshold = (int)($_GET['low_threshold'] ?? 10);
if ($lowStockThreshold < 1) {
    $lowStockThreshold = 10;
}

$categorySql = "SELECT category_id, category_name FROM category ORDER BY category_name ASC";
$categoryRs = mysqli_query($conn, $categorySql);
$categories = mysqli_fetch_all($categoryRs, MYSQLI_ASSOC);

$conditions = ["i.item_status = 'active'"];
if ($keyword !== '') {
    $safeKeyword = mysqli_real_escape_string($conn, $keyword);
    $conditions[] = "i.item_name LIKE '%{$safeKeyword}%'";
}
if ($categoryFilter > 0) {
    $conditions[] = "i.category_id = {$categoryFilter}";
}
if ($onlyLowStock) {
    $conditions[] = "i.stock_quantity <= {$lowStockThreshold}";
}

$whereClause = implode(' AND ', $conditions);
$sql = "SELECT i.item_id, i.item_name, c.category_name, i.purchase_price, i.unit_price, i.stock_quantity,
               (i.stock_quantity * i.purchase_price) AS stock_value,
               (i.stock_quantity * i.unit_price) AS projected_revenue
        FROM items i
        LEFT JOIN category c ON c.category_id = i.category_id
        WHERE {$whereClause}
        ORDER BY i.stock_quantity DESC, i.item_id DESC";
$result = mysqli_query($conn, $sql);
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

$total_qty = 0;
$total_value = 0;
$total_projected_revenue = 0;
$in_stock_count = 0;
$out_of_stock_count = 0;
$low_stock_count = 0;

foreach ($rows as $row) {
    $qty = (int)($row['stock_quantity'] ?? 0);
    $value = (float)($row['stock_value'] ?? 0);
    $projectedRevenue = (float)($row['projected_revenue'] ?? 0);
    $total_qty += $qty;
    $total_value += $value;
    $total_projected_revenue += $projectedRevenue;

    if ($qty > 0) {
        $in_stock_count++;
    }
    if ($qty <= 0) {
        $out_of_stock_count++;
    }
    if ($qty > 0 && $qty <= $lowStockThreshold) {
        $low_stock_count++;
    }
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">THỐNG KÊ SẢN PHẨM CÒN TRONG KHO</h1>
    <div class="head-line"></div>

    <div class="container-fluid my-3">
        <form class="row g-2 align-items-end" method="GET" action="user_page.php">
            <input type="hidden" name="kho_thongke" value="1">
            <div class="col-lg-4">
                <label class="form-label">Tìm theo tên sản phẩm</label>
                <input type="text" class="form-control" name="kw" value="<?= htmlspecialchars($keyword) ?>" placeholder="Nhập tên sản phẩm">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Lọc theo danh mục</label>
                <select class="form-select" name="category_id">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= $categoryFilter === (int)$c['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Ngưỡng tồn thấp</label>
                <input type="number" min="1" class="form-control" name="low_threshold" value="<?= (int)$lowStockThreshold ?>">
            </div>
            <div class="col-lg-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="only_low" value="1" id="onlyLowStock" <?= $onlyLowStock ? 'checked' : '' ?>>
                    <label class="form-check-label" for="onlyLowStock">Chỉ hiện tồn thấp</label>
                </div>
            </div>
            <div class="col-lg-1 d-grid">
                <button class="btn btn-primary" type="submit">Lọc</button>
            </div>
        </form>
    </div>

    <div class="container-fluid row my-3 g-3">
        <div class="col-md-3">
            <div class="card text-bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">Sản phẩm còn tồn</h6>
                    <h3 class="mb-0"><?= $in_stock_count ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">Tổng số lượng tồn</h6>
                    <h3 class="mb-0"><?= $total_qty ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">Tổng giá trị tồn (giá nhập)</h6>
                    <h3 class="mb-0"><?= number_format($total_value, 0, ',', '.') ?> VND</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">Sản phẩm tồn thấp</h6>
                    <h3 class="mb-0"><?= $low_stock_count ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid row my-3 g-3">
        <div class="col-md-6">
            <div class="card text-bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">Sản phẩm hết hàng</h6>
                    <h3 class="mb-0"><?= $out_of_stock_count ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-bg-light h-100">
                <div class="card-body">
                    <h6 class="card-title">Doanh thu tiềm năng (theo giá bán hiện tại)</h6>
                    <h3 class="mb-0"><?= number_format($total_projected_revenue, 0, ',', '.') ?> VND</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <table class="table table-hover table-striped table-bordered text-center">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên sản phẩm</th>
                    <th>Loại</th>
                    <th>Tồn kho</th>
                    <th>Giá nhập</th>
                    <th>Giá bán</th>
                    <th>Giá trị tồn</th>
                    <th>Doanh thu tiềm năng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $qty = (int)($row['stock_quantity'] ?? 0); ?>
                    <tr>
                        <td><?= (int)$row['item_id'] ?></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= htmlspecialchars((string)$row['category_name']) ?></td>
                        <td>
                            <strong class="<?= $qty <= 0 ? 'text-danger' : ($qty <= $lowStockThreshold ? 'text-warning' : '') ?>">
                                <?= $qty ?>
                            </strong>
                        </td>
                        <td><?= number_format((float)$row['purchase_price'], 0, ',', '.') ?></td>
                        <td><?= number_format((float)$row['unit_price'], 0, ',', '.') ?></td>
                        <td><?= number_format((float)$row['stock_value'], 0, ',', '.') ?></td>
                        <td><?= number_format((float)$row['projected_revenue'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
