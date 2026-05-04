<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Chỉ số 1: Số lượng sản phẩm
$countProducts = adminCountValue($conn, "SELECT COUNT(*) FROM items");

// Chỉ số 2: Số lượng loại sản phẩm
$countCategories = adminCountValue($conn, "SELECT COUNT(*) FROM category");

// Chỉ số 3: Số lượng nhân viên (Bỏ admin - role_id = 1)
$countEmployees = adminCountValue($conn, "SELECT COUNT(*) FROM accounts WHERE role_id != 1 AND hr_status = 'active'");

// Chỉ số 4: Số sản phẩm tồn kho thấp (< 10)
$lowStock = adminCountValue($conn, "SELECT COUNT(*) FROM items WHERE stock_quantity < 10");

// Chỉ số 5: Tổng doanh thu tháng này
$revenueMonthSql = "
    SELECT SUM(total) 
    FROM invoices 
    WHERE status = 'completed' 
      AND MONTH(creation_time) = MONTH(CURRENT_DATE()) 
      AND YEAR(creation_time) = YEAR(CURRENT_DATE())
";
$revenueThisMonth = adminCountValue($conn, $revenueMonthSql) ?: 0;

// Chỉ số 6: Biểu đồ doanh thu 7 ngày gần nhất - luôn đủ 7 ngày
$chartSql = "
    SELECT DATE(creation_time) as date, SUM(total) as daily_total 
    FROM invoices 
    WHERE status = 'completed' 
      AND creation_time >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY) 
    GROUP BY DATE(creation_time) 
    ORDER BY date ASC
";
$chartRes = mysqli_query($conn, $chartSql);
// Index các kết quả theo date key để lookup nhanh
$dbChartMap = [];
if ($chartRes) {
    while ($row = mysqli_fetch_assoc($chartRes)) {
        $dbChartMap[$row['date']] = (float)$row['daily_total'];
    }
}
// Luôn sinh đủ 7 ngày, ngày không có doanh thu = null (ẩn cột)
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = new DateTime("-$i days");
    $dateKey = $d->format('Y-m-d');
    $chartLabels[] = $d->format('d/m');
    // null = không có dữ liệu ngày đó (ẩn cột), 0 = có nhưng bằng 0
    $chartData[] = array_key_exists($dateKey, $dbChartMap) ? $dbChartMap[$dateKey] : null;
}

require_once __DIR__ . '/../src/views/layout.php';
renderAppLayoutStart($_SESSION['full_name'] ?? 'Admin', 'admin');
?>

<div class="px-2">
    <h1 class="head-name mb-1">DASHBOARD TỔNG QUAN</h1>
    <div class="head-line mb-4"></div>

    <!-- Hàng thống kê số liệu -->
    <div class="row g-3 mb-4">
        <!-- Thẻ 1 -->
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-3 opacity-50"><i class="fa-solid fa-box"></i></div>
                    <div>
                        <h6 class="card-title mb-1 text-uppercase fw-bold">Tổng sản phẩm</h6>
                        <h2 class="mb-0 fw-bolder"><?= number_format($countProducts) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thẻ 2 -->
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 bg-info text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-3 opacity-50"><i class="fa-solid fa-layer-group"></i></div>
                    <div>
                        <h6 class="card-title mb-1 text-uppercase fw-bold">Danh mục SP</h6>
                        <h2 class="mb-0 fw-bolder"><?= number_format($countCategories) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thẻ 3 -->
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 bg-success text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-3 opacity-50"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <h6 class="card-title mb-1 text-uppercase fw-bold">Nhân viên đang làm</h6>
                        <h2 class="mb-0 fw-bolder"><?= number_format($countEmployees) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thẻ 4 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-warning text-dark h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-3 opacity-50"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <h6 class="card-title mb-1 text-uppercase fw-bold">Sản phẩm sắp hết kho (< 10)</h6>
                        <h2 class="mb-0 fw-bolder"><?= number_format($lowStock) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thẻ 5 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-danger text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="fs-1 me-3 opacity-50"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div>
                        <h6 class="card-title mb-1 text-uppercase fw-bold">Doanh thu tháng <?= date('m/Y') ?></h6>
                        <h2 class="mb-0 fw-bolder"><?= number_format($revenueThisMonth, 0, ',', '.') ?> VNĐ</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ doanh thu 7 ngày -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fa-solid fa-chart-line text-primary me-2"></i> BIỂU ĐỒ DOANH THU 7 NGÀY QUA
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Dữ liệu từ PHP
    const labels = <?= json_encode($chartLabels) ?>;
    const dataVals = <?= json_encode($chartData) ?>;

    // Hàm tính màu cột theo ngưỡng doanh thu
    function getBarColor(value, alpha) {
        alpha = alpha || 0.8;
        if (value === null || value === 0)  return 'rgba(200, 200, 200, ' + alpha + ')'; // xám nhạt - không có dữ liệu
        if (value < 1000000)                return 'rgba(220, 53, 69, '  + alpha + ')'; // đỏ - thấp < 1 triệu
        if (value < 10000000)               return 'rgba(255, 193, 7, '  + alpha + ')'; // vàng - trung bình 1-10 triệu
        return                                     'rgba(25, 135, 84, '   + alpha + ')'; // xanh - cao >= 10 triệu
    }

    const bgColors  = dataVals.map(v => getBarColor(v, 0.75));
    const bdrColors = dataVals.map(v => getBarColor(v, 1));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: dataVals,
                backgroundColor: bgColors,
                borderColor: bdrColors,
                borderWidth: 1,
                borderRadius: 5,
                skipNull: true  // Bỏ qua cột null (không có dữ liệu)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false  // Ẩn legend vì màu đã nói lên ý nghĩa
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.parsed.y === null) return 'Không có dữ liệu';
                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + ' tr';
                            return new Intl.NumberFormat('vi-VN').format(value);
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Chú thích màu biểu đồ -->
<div class="px-2 mb-4">
    <div class="d-flex gap-3 flex-wrap mt-2">
        <span class="badge" style="background:rgba(220,53,69,0.85);font-size:0.8rem"><i class="fa-solid fa-square-full me-1"></i> Thấp (&lt; 1 triệu)</span>
        <span class="badge" style="background:rgba(255,193,7,0.95);color:#333;font-size:0.8rem"><i class="fa-solid fa-square-full me-1"></i> Trung bình (1 – 10 triệu)</span>
        <span class="badge" style="background:rgba(25,135,84,0.85);font-size:0.8rem"><i class="fa-solid fa-square-full me-1"></i> Tốt (&gt;= 10 triệu)</span>
        <span class="badge" style="background:rgba(200,200,200,0.9);color:#555;font-size:0.8rem"><i class="fa-solid fa-square-full me-1"></i> Không có dữ liệu</span>
    </div>
</div>

<?php
renderAppLayoutEnd('admin');
?>
