<?php
/**
 * Báo cáo kho - Bản thống nhất (Unified Warehouse Report)
 * Hỗ trợ bộ lọc Tháng/Quý/Năm cho cả Admin và Nhân viên.
 */
if (!can(AppPermission::MANAGE_WAREHOUSE) && !can(AppPermission::VIEW_REPORTS)) {
    requirePermission(AppPermission::MANAGE_WAREHOUSE);
}

global $conn;
$roleName = currentRole();
$isAdmin = ($roleName === AppRole::ADMIN);

// 1. Phân tích tham số lọc (Shared Filters)
$sel_year    = (int)($_GET['year']    ?? date('Y'));
$sel_month   = (int)($_GET['month']   ?? 0);

// Xây dựng điều kiện thời gian cho Phiếu Nhập
$where_receipt = "ir.status='completed' AND YEAR(ir.import_date) = $sel_year";
if ($sel_month > 0) {
    $where_receipt .= " AND MONTH(ir.import_date) = $sel_month";
}

// 2. Tính toán các chỉ số chung (Shared Metrics)

// 2.1 Tổng số phiếu nhập và giá trị trong kỳ lọc (Tính từ chi tiết sản phẩm)
$imp_summary_sql = "SELECT COUNT(DISTINCT ir.receipt_id) AS total_receipts, SUM(ri.line_total) AS total_import_value
                    FROM inventory_receipts ir 
                    LEFT JOIN inventory_receipt_items ri ON ri.receipt_id = ir.receipt_id
                    WHERE $where_receipt";
$imp_r = mysqli_query($conn, $imp_summary_sql);
$imp_summary = $imp_r ? mysqli_fetch_assoc($imp_r) : [];

// 2.2 Tổng số lượng nhập trong kỳ lọc
$imp_qty_sql = "SELECT SUM(ri.quantity) AS total_import_qty
                FROM inventory_receipt_items ri
                JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                WHERE $where_receipt";
$imp_qty_r = mysqli_query($conn, $imp_qty_sql);
$imp_qty_summary = $imp_qty_r ? mysqli_fetch_assoc($imp_qty_r) : [];

// 2.3 Top sản phẩm nhập nhiều nhất trong kỳ lọc
$top_import_sql = "SELECT ri.item_id, i.item_name, SUM(ri.quantity) AS total_qty, SUM(ri.line_total) AS total_value
                   FROM inventory_receipt_items ri
                   JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                   JOIN items i ON i.item_id = ri.item_id
                   WHERE $where_receipt
                   GROUP BY ri.item_id ORDER BY total_qty DESC LIMIT 10";
$ti_r = mysqli_query($conn, $top_import_sql);
$top_import_items = $ti_r ? mysqli_fetch_all($ti_r, MYSQLI_ASSOC) : [];

// 2.4 Cảnh báo tồn kho thấp (Real-time)
$low_stock_sql = "SELECT i.item_code, i.item_name, i.stock_quantity, c.category_name
                  FROM items i LEFT JOIN category c ON c.category_id = i.category_id
                  WHERE i.item_status = 'active' AND i.stock_quantity < 10
                  ORDER BY i.stock_quantity ASC";
$ls_r = mysqli_query($conn, $low_stock_sql);
$low_stock_items = $ls_r ? mysqli_fetch_all($ls_r, MYSQLI_ASSOC) : [];

// 2.5 Tổng lượng nhập theo năm đang lọc
$yearly_qty_sql = "SELECT SUM(ri.quantity) AS yearly_qty
                   FROM inventory_receipt_items ri
                   JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                   WHERE ir.status='completed' AND YEAR(ir.import_date) = $sel_year";
$yq_r = mysqli_query($conn, $yearly_qty_sql);
$yearly_qty = ($yq_r ? mysqli_fetch_assoc($yq_r)['yearly_qty'] : 0) ?: 0;
?>

<div class="dash_board px-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="head-name mb-0">BÁO CÁO KHO</h1>
        <div class="d-flex gap-2 align-items-center">
            <?php if (!$isAdmin): ?>
            
            <?php endif; ?>
            <span class="badge bg-primary px-3 py-2"><?= $isAdmin ? 'Quyền Hạn: Quản trị viên' : 'Quyền Hạn: Nhân viên kho' ?></span>
        </div>
    </div>
    <div class="head-line mb-3"></div>

    <!-- 3. Bộ lọc chung (Unified Filter) -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <form class="row g-2 align-items-end" method="GET" action="user_page.php">
                <input type="hidden" name="baocao_kho" value="1">
                <div class="col-md-4">
                    <label class="form-label small fw-bold"><i class="fa-solid fa-calendar me-1 text-primary"></i> Năm báo cáo</label>
                    <input type="number" class="form-control form-control-sm" name="year" value="<?= $sel_year ?>" min="2020" max="2030">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold"><i class="fa-solid fa-calendar-day me-1 text-primary"></i> Lọc theo Tháng</label>
                    <select class="form-select form-select-sm" name="month">
                        <option value="0">-- Tất cả các tháng --</option>
                        <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?=$m?>" <?=$m==$sel_month?'selected':''?>>Tháng <?=$m?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-dark btn-sm w-100 fw-bold" type="submit"><i class="fa-solid fa-filter me-2"></i>ÁP DỤNG BỘ LỌC</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Thẻ KPI chung (Shared KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3 h-100" style="border-left: 5px solid #0d6efd !important;">
                <div class="mb-2 text-primary"><i class="fa-solid fa-file-invoice-dollar fa-2x"></i></div>
                <div class="text-muted small text-uppercase fw-bold">Số phiếu nhập</div>
                <div class="fs-3 fw-bold"><?= number_format($imp_summary['total_receipts']??0) ?></div>
                <div class="small text-primary">Trong kỳ được chọn</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3 h-100" style="border-left: 5px solid #198754 !important;">
                <div class="mb-2 text-success"><i class="fa-solid fa-boxes-stacked fa-2x"></i></div>
                <div class="text-muted small text-uppercase fw-bold">Tổng SL nhập (Kỳ)</div>
                <div class="fs-3 fw-bold"><?= number_format($imp_qty_summary['total_import_qty']??0) ?></div>
                <div class="small text-success">Đơn vị sản phẩm</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3 h-100" style="border-left: 5px solid #dc3545 !important;">
                <div class="mb-2 text-danger"><i class="fa-solid fa-battery-quarter fa-2x"></i></div>
                <div class="text-muted small text-uppercase fw-bold">Tồn kho thấp</div>
                <div class="fs-3 fw-bold text-danger"><?= count($low_stock_items) ?></div>
                <div class="small text-danger">Cần nhập thêm hàng</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3 h-100" style="border-left: 5px solid #ffc107 !important;">
                <div class="mb-2 text-warning"><i class="fa-solid fa-calendar-check fa-2x"></i></div>
                <div class="text-muted small text-uppercase fw-bold">Tổng nhập năm <?= $sel_year ?></div>
                <div class="fs-3 fw-bold"><?= number_format($yearly_qty) ?></div>
                <div class="small text-warning">Dữ liệu cả năm</div>
            </div>
        </div>
    </div>

    <!-- 5. Khối thông tin bổ sung (Shared Info Blocks) -->
    <div class="row g-3 mb-4">
        <!-- Top sản phẩm -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100" id="printSectionTopProducts">
                <div class="card-header bg-white fw-bold border-0 py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-trophy text-warning me-2"></i>Top Sản Phẩm Nhập Nhiều Nhất (Kỳ)</span>
                    <?php if (!$isAdmin): ?>
                    <button onclick="printDiv('printSectionTopProducts', 'BÁO CÁO TOP SẢN PHẨM NHẬP')" class="btn btn-sm btn-link text-dark no-print p-0">
                        <i class="fa-solid fa-print"></i> In
                    </button>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="bg-light"><tr><th class="ps-3"><i class="fa-solid fa-box me-1"></i> Sản phẩm</th><th class="text-center"><i class="fa-solid fa-list-ol me-1"></i> Số lượng</th><th class="text-end pe-3"><i class="fa-solid fa-money-bill-wave me-1"></i> Giá trị</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_import_items as $ti): ?>
                            <tr>
                                <td class="ps-3 fw-bold small"><?= htmlspecialchars($ti['item_name']) ?></td>
                                <td class="text-center"><?= number_format($ti['total_qty']) ?></td>
                                <td class="text-end pe-3">
                                    <?= number_format($ti['total_value'],0,',','.') ?>đ
                                    <button class="btn btn-sm btn-link p-0 ms-1 text-primary no-print" 
                                            onclick="showImportDetails(<?= $ti['item_id'] ?>, '<?= addslashes($ti['item_name']) ?>')" 
                                            title="Xem chi tiết các phiếu nhập">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_import_items)): ?><tr><td colspan="3" class="text-center py-3 text-muted">Không có dữ liệu</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Cảnh báo tồn thấp -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold border-0 py-3 text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>Cảnh Báo Tồn Kho Thấp (< 10)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="bg-light"><tr><th class="ps-3"><i class="fa-solid fa-box me-1"></i> Sản phẩm</th><th class="text-center"><i class="fa-solid fa-cubes-stacked me-1"></i> Số tồn</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($low_stock_items, 0, 8) as $ls): ?>
                            <tr class="table-danger border-white">
                                <td class="ps-3 small"><?= htmlspecialchars($ls['item_name']) ?></td>
                                <td class="text-center fw-bold"><?= number_format($ls['stock_quantity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($low_stock_items)): ?><tr><td colspan="2" class="text-center py-3 text-success">Mọi SP đều đủ tồn kho</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4 opacity-10">

    <!-- BIỂU ĐỒ VÀ THỐNG KÊ NHẬP KHO (Admin + Staff) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold border-0 py-3"><i class="fa-solid fa-chart-column text-primary me-2"></i>Biểu đồ Tiền Nhập Kho Theo Tháng (Năm <?= $sel_year ?>)</div>
                <div class="card-body">
                    <?php
                    // Thống kê 12 tháng cho biểu đồ
                    $imp_chart_sql = "SELECT MONTH(ir.import_date) as thang, SUM(ri.quantity * ri.import_price) as val 
                                      FROM inventory_receipts ir
                                      JOIN inventory_receipt_items ri ON ri.receipt_id = ir.receipt_id
                                      WHERE ir.status='completed' AND YEAR(ir.import_date)=$sel_year 
                                      GROUP BY MONTH(ir.import_date)";
                    $ic_r = mysqli_query($conn, $imp_chart_sql);
                    $imp_data = array_fill(1, 12, 0);
                    while($r = mysqli_fetch_assoc($ic_r)) $imp_data[(int)$r['thang']] = (float)$r['val'];
                    ?>
                    <canvas id="unifiedChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    new Chart(document.getElementById('unifiedChart'), {
        type: 'bar',
        data: {
            labels: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
            datasets: [
                { label: 'Tiền nhập hàng (VND)', data: <?= json_encode(array_values($imp_data)) ?>, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
    </script>

    <!-- 6. Phân nhánh Giao diện (Role-based Logic) -->
    <?php if ($isAdmin): ?>
    <!-- ADMIN: KHO THÔNG TIN BỔ SUNG NẾU CÓ -->
    <?php else: ?>
    <!-- STAFF: DANH SÁCH SẢN PHẨM VÀ TÌM KIẾM CHI TIẾT -->
    <div class="card shadow-sm border-0" id="printSectionInventoryList">
        <div class="card-header bg-white fw-bold border-0 py-3 d-flex justify-content-between align-items-center">
            <span>Danh Sách Tra Cứu Sản Phẩm & Tồn Kho</span>
            <button onclick="printDiv('printSectionInventoryList', 'DANH SÁCH TRA CỨU SẢN PHẨM & TỒN KHO')" class="btn btn-sm btn-link text-dark no-print p-0">
                <i class="fa-solid fa-print"></i> In bảng này
            </button>
        </div>
        <div class="card-body p-3 border-top">
            <form class="row g-2 mb-3 align-items-center" method="GET" action="user_page.php">
                <input type="hidden" name="baocao_kho" value="1">
                <input type="hidden" name="year" value="<?= $sel_year ?>">
                <input type="hidden" name="month" value="<?= $sel_month ?>">
                <div class="col-md-9">
                    <input type="text" name="search_item" class="form-control" placeholder="Tìm kiếm theo mã, tên, loại hoặc nhà cung cấp..." value="<?= htmlspecialchars($_GET['search_item']??'') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary w-100 fw-bold"><i class="fa-solid fa-magnifying-glass me-2"></i>TÌM KIẾM CHI TIẾT</button>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort ?? 'item_id') ?>">
                    <input type="hidden" name="dir" value="<?= htmlspecialchars($dir ?? 'DESC') ?>">
                </div>
            </form>
            <div class="table-responsive">
                <?php
                $kw = trim((string)($_GET['search_item'] ?? ''));
                
                // Xử lý sắp xếp - Mặc định ưu tiên sản phẩm còn hàng và số lượng nhiều nhất
                $sort = $_GET['sort'] ?? 'availability';
                $dir = $_GET['dir'] ?? 'DESC';
                $allowed_sort = ['item_id', 'item_code', 'item_name', 'category_name', 'stock_quantity', 'purchase_price', 'unit_price', 'availability'];
                if (!in_array($sort, $allowed_sort)) $sort = 'availability';
                $dir = (strtoupper($dir) === 'ASC') ? 'ASC' : 'DESC';

                // Xác định chuỗi ORDER BY
                if ($sort === 'availability') {
                    $order_by = "CASE WHEN i.stock_quantity <= 0 THEN 1 ELSE 0 END ASC, i.stock_quantity DESC, i.item_name ASC";
                } else {
                    $order_by = "$sort $dir";
                }

                $conds = ["i.item_status = 'active'"];
                if($kw !== '') {
                    $sk = mysqli_real_escape_string($conn, $sk);
                    $conds[] = "(i.item_code LIKE '%$sk%' OR i.item_name LIKE '%$sk%' OR c.category_name LIKE '%$sk%' OR s.supplier_name LIKE '%$sk%')";
                }
                $whereStaff = implode(" AND ", $conds);
                $staff_sql = "SELECT i.item_id, i.item_code, i.item_name, c.category_name, i.stock_quantity, i.purchase_price, i.unit_price,
                                     GROUP_CONCAT(DISTINCT s.supplier_name SEPARATOR ', ') AS suppliers
                              FROM items i 
                              LEFT JOIN category c ON c.category_id = i.category_id
                              LEFT JOIN inventory_receipt_items ri ON ri.item_id = i.item_id
                              LEFT JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
                              LEFT JOIN suppliers s ON s.supplier_id = ir.supplier_id
                              WHERE $whereStaff 
                              GROUP BY i.item_id
                              ORDER BY $order_by";
                $staff_rs = mysqli_query($conn, $staff_sql);
                $staff_items = mysqli_fetch_all($staff_rs, MYSQLI_ASSOC);
                ?>
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <?php 
                            $base_url = "user_page.php?baocao_kho=1&year=$sel_year&month=$sel_month&search_item=".urlencode($kw);
                            $toggle_dir = ($dir == 'ASC') ? 'DESC' : 'ASC';
                            ?>
                            <th class="ps-3"><a href="<?= $base_url ?>&sort=item_code&dir=<?= ($sort=='item_code')?$toggle_dir:'ASC' ?>" class="text-decoration-none text-secondary">Mã SP <i class="fa-solid fa-sort<?= ($sort=='item_code')?($dir=='ASC'?'-up':'-down'):'' ?> small"></i></a></th>
                            <th><a href="<?= $base_url ?>&sort=item_name&dir=<?= ($sort=='item_name')?$toggle_dir:'ASC' ?>" class="text-decoration-none text-secondary">Tên sản phẩm <i class="fa-solid fa-sort<?= ($sort=='item_name')?($dir=='ASC'?'-up':'-down'):'' ?> small"></i></a></th>
                            <th>Loại</th>
                            <th>Nhà cung cấp</th>
                            <th class="text-center"><a href="<?= $base_url ?>&sort=stock_quantity&dir=<?= ($sort=='stock_quantity')?$toggle_dir:'ASC' ?>" class="text-decoration-none text-secondary">Tồn kho <i class="fa-solid fa-sort<?= ($sort=='stock_quantity')?($dir=='ASC'?'-up':'-down'):'' ?> small"></i></a></th>
                            <th class="text-end">Giá nhập</th>
                            <th class="text-end pe-3">Giá bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_items as $si): ?>
                        <tr>
                            <td class="ps-3"><code><?= htmlspecialchars($si['item_code']) ?></code></td>
                            <td class="fw-bold"><?= htmlspecialchars($si['item_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($si['category_name']??'N/A') ?></span></td>
                            <td class="small text-muted"><?= htmlspecialchars($si['suppliers'] ?? 'Chưa nhập hàng') ?></td>
                            <td class="text-center fw-bold <?= $si['stock_quantity']<10?'text-danger':'' ?>"><?= number_format($si['stock_quantity']) ?></td>
                            <td class="text-end"><?= number_format($si['purchase_price'],0,',','.') ?>đ</td>
                            <td class="text-end pe-3 text-primary fw-bold"><?= number_format($si['unit_price'],0,',','.') ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Chi Tiết Nhập Hàng -->
<div class="modal fade" id="modalImportDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="importDetailsTitle">Chi tiết nhập hàng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã Phiếu</th>
                                <th>Nhà cung cấp</th>
                                <th>Giá nhập</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="importDetailsBody">
                            <!-- Rendered by JS -->
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="4" class="text-end pe-3">Tổng cộng:</td>
                                <td id="importDetailsTotal" class="text-primary fs-5">0đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function showImportDetails(itemId, itemName) {
    const modal = new bootstrap.Modal(document.getElementById('modalImportDetails'));
    document.getElementById('importDetailsTitle').innerText = 'Chi tiết nhập hàng: ' + itemName;
    const tbody = document.getElementById('importDetailsBody');
    const totalEl = document.getElementById('importDetailsTotal');
    
    tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải dữ liệu...</td></tr>';
    totalEl.innerText = '0đ';
    modal.show();

    try {
        const year = <?= $sel_year ?>;
        const month = <?= $sel_month ?>;
        const res = await fetch(`api/warehouse/product_import_details.php?item_id=${itemId}&year=${year}&month=${month}`);
        const result = await res.json();

        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-muted">Không có dữ liệu chi tiết</td></tr>';
                return;
            }

            let html = '';
            let total = 0;
            result.data.forEach(row => {
                total += row.line_total;
                html += `<tr>
                    <td><code>${row.receipt_code}</code></td>
                    <td class="text-start">${row.supplier_name}</td>
                    <td>${row.import_price.toLocaleString('vi-VN')}đ</td>
                    <td>${row.quantity}</td>
                    <td class="fw-bold">${row.line_total.toLocaleString('vi-VN')}đ</td>
                </tr>`;
            });
            tbody.innerHTML = html;
            totalEl.innerText = total.toLocaleString('vi-VN') + 'đ';
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-danger">Lỗi: ${result.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-danger">Lỗi kết nối mạng: ${err}</td></tr>`;
    }
}
</script>

<style>
    .head-name { color: #212529; font-weight: 800; border-left: 5px solid #000; padding-left: 15px; }
    .card { border-radius: 10px; }
    .table thead th { font-size: 0.75rem; text-transform: uppercase; color: #666; font-weight: 700; padding: 12px 8px; }
    .table tbody td { font-size: 0.9rem; padding: 10px 8px; }
    .badge { font-weight: 600; }
    code { font-size: 0.8rem; }
    .bg-light { background-color: #fcfcfc !important; }

    @media print {
        .no-print, .nav-side, .app-topbar, .card-footer, .btn, form, .modal { display: none !important; }
        .dash_board { padding: 0 !important; margin: 0 !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; margin-bottom: 20px !important; break-inside: avoid; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border: 1px solid #ddd !important; padding: 8px !important; }
        .head-name { border-left: none !important; text-align: center; font-size: 24pt !important; margin-top: 10mm; }
        .app-right { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        body { background: white !important; }
    }
</style>

<script>
function printDiv(divId, title) {
    const el = document.getElementById(divId);
    const content = el.outerHTML;
    const win = window.open('', '', 'height=700,width=1000');
    win.document.write('<html><head><title>' + title + '</title>');
    win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
    win.document.write('<style>');
    win.document.write('body{padding:40px; font-family: sans-serif;}');
    win.document.write('.no-print{display:none !important;}');
    win.document.write('.card{border:none !important; box-shadow:none !important;}');
    win.document.write('.card-header{font-size: 18pt; font-weight: bold; text-align: center; border-bottom: 2px solid #000 !important; margin-bottom: 20px;}');
    win.document.write('table{width:100% !important; border-collapse: collapse; margin-top: 10px;}');
    win.document.write('th, td{border: 1px solid #ccc !important; padding: 10px !important; text-align: left;}');
    win.document.write('th{background-color: #f8f9fa !important; font-weight: bold;}');
    win.document.write('.text-end{text-align: right !important;} .text-center{text-align: center !important;}');
    win.document.write('.badge{border: 1px solid #ccc; padding: 2px 5px;}');
    win.document.write('</style></head><body>');
    win.document.write('<h1 style="text-align:center; margin-bottom: 10px;">BÁO CÁO CỬA HÀNG COFFEE</h1>');
    win.document.write(content);
    win.document.write('<div style="margin-top: 30px; text-align: right;"><i>Ngày in: ' + new Date().toLocaleDateString("vi-VN") + '</i></div>');
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 700);
}
</script>
