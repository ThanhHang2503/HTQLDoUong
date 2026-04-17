<?php
require('./src/models/thongke.php');
?>



<div class="dash_board px-2">
    <h1 class="head-name">THỐNG KÊ</h1>
    <div class="head-line"></div>
    <div class="container-fluid">
        <div class="row px-2">
            <form method="POST" action="" class=" mt-5 col-3  ">
                <div class="py-3 px-2 rounded-1 bg-light">
                    <div class="row justify-content-start">
                        <h3 class="fw-bold text-center">CHỌN</h3>
                        <div class="">
                            <label class="fw-bold" for="start_date">Ngày bắt đầu:</label>
                            <input type="date" id="start_date" class="form-control" name="start_date" value="<?= $start_date ?>" required>
                        </div>
                        <div class="">
                            <label class="fw-bold" for="end_date">Ngày kết thúc:</label>
                            <input type="date" id="end_date" class="form-control" name="end_date" value="<?= $end_date_string ?>" required>
                        </div>

                        <div>
                            <hr>
                            <button type="submit" class="btn btn-success mt-2 mr-2">Thống kê</button>
                            <a href="user_page.php?thongke" class="btn btn-danger mt-2 mr-2">Hủy</a>
                        </div>

                    </div>
                </div>
                <!-- Thống kê theo tiêu chí    -->
            </form>
            <div class="container-fluid col-9">
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom mb-3">
                    <h1 class="fw-bold mb-0">BÁO CÁO KINH DOANH</h1>
                    <?php if (!empty($ds_hoadon)): ?>
                        <button onclick="printStatisticsReport()" class="btn btn-outline-primary fw-bold">
                            <i class="fa-solid fa-print me-2"></i>In Báo Cáo
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($start_date) && !empty($end_date_string)) :
                    $new_start_date = date('d/m/Y', strtotime($start_date));
                    $new_end_date = date('d/m/Y', strtotime($end_date_string));
                ?>
                    <h3>Thống kê từ ngày <b><?= $new_start_date ?></b> đến ngày <b><?= $new_end_date ?></b></h3>
                <?php endif; ?>
                <hr>

                <!-- BẢNG 1: DANH SÁCH HÓA ĐƠN -->
                <div class="mb-5">
                    <h5 class="fw-bold"><i class="fa-solid fa-list-check me-2"></i>Chi tiết danh sách hóa đơn</h5>
                    <table class="table table-striped table-hover text-center table-bordered shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Ngày tạo</th>
                                <th>Khách hàng</th>
                                <th>Người tạo</th>
                                <th>Tổng tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ds_hoadon)) : ?>
                                <?php foreach ($ds_hoadon as $hd) : ?>
                                    <tr>
                                        <td><?= $hd['invoice_id'] ?? 0 ?></td>
                                        <td><?= $hd['creation_time'] ?? 0 ?></td>
                                        <td><?= htmlspecialchars($hd['customer_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($hd['full_name'] ?? '-') ?></td>
                                        <td class="fw-bold text-success"><?= number_format($hd['total'], 0, ',', '.') ?> VNĐ</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-muted">Chưa có dữ liệu trong khoảng thời gian này.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BẢNG 2: TỔNG HỢP CHỈ SỐ -->
                <div class="mb-5">
                    <h5 class="fw-bold"><i class="fa-solid fa-chart-line me-2"></i>Tổng hợp chỉ số kinh doanh</h5>
                    <table class="table table-striped table-hover text-center table-bordered shadow-sm">
                        <thead class="table-secondary">
                            <tr>
                                <th scope="col" style="width: 40%">Thông tin</th>
                                <th scope="col">Số liệu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_revenue > 0) : ?>
                                <tr>
                                    <td class='fw-bold'>Tổng doanh thu</td>
                                    <td class="text-success fw-bold h5"><?= number_format($total_revenue, 0, ',', '.') ?> VNĐ</td>
                                </tr>
                                <?php if (!empty($customer_sales_rows)) : ?>
                                    <tr>
                                        <td class='fw-bold'>Khách hàng giao dịch nhiều nhất</td>
                                        <td>
                                            <?php foreach ($customer_sales_rows as $name) echo "<span class='badge bg-primary me-1'>".htmlspecialchars($name['customer_name'])."</span>"; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($mon_chay_rows)) : ?>
                                    <tr>
                                        <td class='fw-bold'>Món bán chạy nhất</td>
                                        <td>
                                            <?php foreach ($mon_chay_rows as $name) echo "<span class='badge bg-warning text-dark me-1'>".htmlspecialchars($name['best_selling_item'])."</span>"; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($name_best_staff_rows)) : ?>
                                    <tr>
                                        <td class='fw-bold'>Nhân viên tích cực nhất</td>
                                        <td>
                                            <?php foreach ($name_best_staff_rows as $name) echo "<span class='badge bg-info text-dark me-1'>".htmlspecialchars($name['full_name'])."</span>"; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-muted italic">Vui lòng chọn thời gian và nhấn Thống kê để xem chỉ số.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BẢNG 3: TOP 5 KHÁCH HÀNG MUA NHIỀU NHẤT -->
                <div class="mb-5" id="top-customers-section">
                    <h5 class="fw-bold text-primary"><i class="fa-solid fa-crown me-2 text-warning"></i>Top 5 khách hàng chi tiêu cao nhất</h5>
                    <table class="table table-bordered table-hover shadow-sm bg-white">
                        <thead class="table-primary text-center">
                            <tr>
                                <th style="width: 80px;">Hạng</th>
                                <th>Tên khách hàng</th>
                                <th>Liên hệ</th>
                                <th style="width: 100px;">Số đơn</th>
                                <th class="text-end" style="width: 220px;">Tổng tiền mua</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($top_customers_data)): 
                                $rank = 1; 
                                foreach ($top_customers_data as $c): 
                            ?>
                                <tr>
                                    <td class="text-center fw-bold">#<?= $rank++ ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($c['customer_name'] ?? 'N/A') ?></td>
                                    <td class="small">
                                        <i class="fa-solid fa-phone me-1 text-muted"></i> <?= htmlspecialchars($c['phone_number'] ?: '-') ?><br>
                                        <i class="fa-solid fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($c['email'] ?: '-') ?>
                                    </td>
                                    <td class="text-center"><?= $c['total_orders'] ?></td>
                                    <td class="text-end fw-bold text-success border-start"><?= number_format($c['total_spent'], 0, ',', '.') ?> VNĐ</td>
                                </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted border-bottom">
                                        <i class="fa-solid fa-circle-info me-2"></i> Không có dữ liệu
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printStatisticsReport() {
    const reportTitle = document.querySelector('.dash_board .head-name').innerText;
    const dateRange = document.querySelector('.dash_board .col-9 h3')?.innerText || '';
    const tables = document.querySelectorAll('.dash_board .container-fluid.col-9 table');
    let tablesHtml = '';
    
    tables.forEach((table, index) => {
        let sectionTitle = '';
        if (index === 0) sectionTitle = 'DANH SÁCH HÓA ĐƠN';
        else if (index === 1) sectionTitle = 'TỔNG HỢP CHỈ SỐ';
        else if (index === 2) sectionTitle = 'TOP 5 KHÁCH HÀNG MUA NHIỀU NHẤT';

        tablesHtml += `
            <div class="print-section">
                <h3 class="print-section-title">${sectionTitle}</h3>
                <table class="print-table">${table.innerHTML}</table>
            </div>
        `;
    });

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Báo cáo - ${new Date().toLocaleDateString()}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: 'Times New Roman', serif; padding: 40px; color: #333; }
                    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                    .header h1 { margin: 0; text-transform: uppercase; font-weight: bold; }
                    .header p { margin: 5px 0 0; font-size: 1.1em; color: #666; }
                    .print-section { margin-bottom: 40px; page-break-inside: avoid; }
                    .print-section-title { font-size: 1.4em; font-weight: bold; margin-bottom: 15px; color: #000; border-left: 5px solid #000; padding-left: 10px; }
                    .print-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    .print-table th, .print-table td { border: 1px solid #ddd; padding: 12px 8px; font-size: 11pt; }
                    .print-table th { background-color: #f2f2f2 !important; font-weight: bold; text-align: center; }
                    .text-end { text-align: right !important; }
                    .text-center { text-align: center !important; }
                    .text-success { color: #198754 !important; font-weight: bold; }
                    @media print { @page { margin: 20mm; } body { padding: 0; } }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>${reportTitle}</h1>
                    <p>${dateRange}</p>
                    <p style="font-style: italic;">Ngày xuất báo cáo: ${new Date().toLocaleString('vi-VN')}</p>
                </div>
                ${tablesHtml}
                <div style="margin-top: 50px; display: flex; justify-content: space-between;">
                    <div style="text-align: center; width: 200px;"><p>Người lập biểu</p><p style="margin-top: 60px;">(Ký và ghi rõ họ tên)</p></div>
                    <div style="text-align: center; width: 250px;"><p>Ngày ...... tháng ...... năm ......</p><p style="font-weight: bold;">Xác nhận của quản lý</p><p style="margin-top: 60px;">(Ký và đóng dấu)</p></div>
                </div>
            </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
}
</script>