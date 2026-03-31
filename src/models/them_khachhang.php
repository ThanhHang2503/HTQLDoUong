<?php

if (isset($_GET['khachhang']) && $_GET['khachhang'] == 'them') :
?>
    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <p class="h3 fw-bolder">Thêm khách hàng</p>
                    </div>
                    <div class="card-body">
                        <form action="user_page.php?khachhang" method="POST">
                            <div class="form-group  pt-3">
                                <label for="customer_name">Tên khách hàng</label>
                                <input required type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="form-group  pt-3">
                                <label for="phone_number">Số điện thoại</label>
                                <input required maxlength="100" class="form-control" id="phone_number" name="phone_number" rows="3"></input>
                            </div>
                
                            <button type="submit" class="mt-4 btn btn-success">Thêm khách hàng</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php endif; ?>