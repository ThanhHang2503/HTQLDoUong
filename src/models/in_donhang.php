<?php

if (isset($_GET['donhang']) && isset($_GET['id'])) {
    $invoice_id = $_GET['id'];

    $sql = "select ct.customer_name, iv.invoice_id, iv.creation_time, iv.discount, iv.total, it.item_name, it.unit_price, id.quantity, ac.full_name, iv.notes  from invoices iv, invoice_details id, items it, customers ct, accounts ac
    where iv.invoice_id = id.invoice_id and iv.customer_id=ct.customer_id and iv.account_id = ac.account_id and id.item_id = it.item_id and iv.invoice_id = $invoice_id;";

    $result = mysqli_query($conn, $sql);
    $rows = mysqli_fetch_all($result);


    $customer_name = $rows[0][0];
    $invoice_id = $rows[0][1];
    $creation_time = $rows[0][2];
    $discount = $rows[0][3];
    $total = $rows[0][4];
    $staff_name = $rows[0][8];
    $notes = $rows[0][9];
    $item_list = array();

    $dateTime = new DateTime($creation_time);
    $creation_time = $dateTime->format('d-m-Y H:i:s');



    foreach ($rows as $row) {

        $details = array();
        $details['item_name'] = $row[5];
        $details['quantity'] = $row[7];
        $details['unit_price'] = $row[6];
        array_push($item_list, $details);
    }
}

?>



<div class="container row justify-content-center mt-5">
    <div class="d-flex row justify-content-between px-4">
        <a class="col-1 btn btn-primary" href="user_page.php?donhang">Quay lại</a>
        <button class="col-1 btn btn-warning" onclick="printToPDF()"><i class="fa-solid fa-print"></i></button>

    </div>

    <div id='bill' class="col-6 text-dark bg-light p-5 bill">
        <h2 class="text-center fw-bold">CỬA HÀNG COFFEE SHOP</h2>
        <div class=" row justify-content-center">
            <img style="border-radius: 50%;" src="img/logo.jpg" alt="Logo" class="col-3">
        </div>
        <p class="text-center">273 An Dương Vương, Phường Chợ Quán, Q5, TP HCM</p>
        <hr>
        <div class=" body-bill">
            <div class="row justify-content-between">
                <p class="col-6 text-start">Thời gian: <?= $creation_time ?? '' ?>
                </p>
                <p class="col-6 text-end">Mã hóa đơn: <?= $invoice_id ?? '' ?></p>
            </div>
            <p>Thu ngân: <?= $staff_name ?? '' ?></p>
            <p>Tên khách hàng: <?= $customer_name ?? '' ?></p>
            <table class="container-fluid table table-stripped">
                <tr>
                    <th>Món ăn</th>
                    <th>Số Lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
                <?php foreach ($item_list as $item) { ?>
                    <tr>
                        <td><?= $item['item_name'] ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format((int)$item['unit_price'], 0, ',', '.') ?> đ</td>
                        <td><?= number_format((int)($item['quantity'] * $item['unit_price']), 0, ',', '.') ?> đ</td>
                    </tr>
                    </tr>
                <?php } ?>
            </table>

            <p class="fw-bolder">Giảm giá: <?= $discount ?>%</p>
            <p class="fw-bolder">Ghi chú: <?= htmlspecialchars((string)$notes ?: 'Không có') ?></p>
            <h5 class="fw-bolder">Tổng tiền: <?= number_format((int)$total, 0, ',', '.') ?> VNĐ</h5>
            <hr>
            <p class="text-center py-0">Cảm ơn quý khách!</p>
            <p class="text-center ">PASS WIFI: hangdepgai</p>
        </div>





    </div>

</div>

<script>
    function printToPDF() {
        // Sử dụng html2canvas để chụp phần tử có id là "bill"
        html2canvas(document.getElementById('bill')).then(function(canvas) {

            var imgData = canvas.toDataURL('image/png');

            var pdf = new jsPDF('p', 'mm', 'a4');

            pdf.addImage(imgData, 'PNG', 0, 0);
            pdf.save('invoice.pdf');
            // Quay lại trang danh sách hóa đơn sau khi tải xong
            setTimeout(function() {
                window.location.href = "user_page.php?donhang";
            }, 1000);
        });
    }
</script>