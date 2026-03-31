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
                <div>
                    <h1 class="py-3 fw-bold text-center">HÓA ĐƠN</h3>

                        <?php
                        if (!empty($start_date) && !empty($end_date_string)) :
                            $new_start_date = date('d/m/Y', strtotime($start_date));
                            $new_end_date = date('d/m/Y', strtotime($end_date_string));
                        ?>
                            <h3>Thống kê từ ngày <b><?= $new_start_date ?></b> đến ngày <b><?= $new_end_date ?></b></h3>

                        <?php endif; ?>
                        <hr>
                        <table class="table table-striped table-hover text-center">
                            <thead>
                                <tr>
                                    <th>Mã hóa đơn</th>
                                    <th>Ngày tạo</th>
                                    <th>Khách hàng</th>
                                    <th>Người tạo</th>
                                    <th>Tổng tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                if (!empty($ds_hoadon)) {
                                    foreach ($ds_hoadon as $hd) : ?>
                                        <tr>
                                            <td><?= $hd['invoice_id'] ?? 0 ?></td>
                                            <td><?= $hd['creation_time'] ?? 0 ?></td>
                                            <td><?= $hd['customer_name'] ?? 0 ?></td>
                                            <td><?= $hd['full_name'] ?? 0 ?></td>
                                            <td><?= intval($hd['total']) ?? 0 ?> VNĐ</td>
                                        </tr>
                                <?php endforeach;
                                }
                                ?>


                            </tbody>
                </div>


            </div>


        </div>
        <div class="row px-2">
            <table class="table table-striped table-hover text-center">
                <tr>
                    <th scope="col">Thông tin</th>
                    <th scope="col">Số liệu</th>
                </tr>
                <?php
                if (!empty($tong_doanhthu)) {
                    echo "<tr>
                    <td class='fw-bolder'>Tổng doanh thu</td>
                    <td>$total_revenue VNĐ</td>
                    </tr>                                      
                    ";
                    if (!empty($customer_sales_rows)) {
                    }

                    if (!empty($customer_sales_rows)) {
                        $number = $customer_sales_rows->num_rows;
                        echo " <tr>
                            <td class='fw-bolder'>Khách hàng có nhiều hóa đơn nhất</td><td>";
                        foreach ($customer_sales_rows as $name) {
                            echo "[" . $name['customer_name'] . "] ";
                        }
                        echo "</td> </tr>";
                    }

                    if (!empty($mon_chay_rows)) {
                        $number = $mon_chay_rows->num_rows;
                        echo " <tr>
                            <td class='fw-bolder'>Món bán chạy</td><td>";
                        foreach ($mon_chay_rows as $name) {
                            echo "[" . $name['best_selling_item'] . "] ";
                        }
                        echo "</td> </tr>";
                    }



                    if (!empty($name_best_staff_rows)) {
                        $number = $name_best_staff_rows->num_rows;  
                        echo " <tr >
                            <td class='fw-bolder'>Nhân viên tích cực nhất</td><td>";
                        foreach ($name_best_staff_rows as $name) {
                            echo "[" . $name['full_name'] . "] ";
                        }
                        echo "</td> </tr>";
                    }
                }
                ?>
            </table>
        </div>
    </div>
</div>



</div>
</div>

</div>
</div>

<!-- Link tới các tập tin JavaScript của Bootstrap và DatePicker -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>