<?php
if (isset($_GET['donhang']) && $_GET['donhang'] == 'them') :
?>
    <style>
        #sales-panel {
            height: 70vh;
        }

        #panel-left,
        #item-list {
            background: rgb(255 255 255 / 17%);
        }

        #item-list {
            height: 60%;
        }
    </style>
    <div class="px-2 mt-2 py-3">
        <div class="container-fluid">
            <div class="card shadow blur">
                <div class="card-header bg-success text-white">
                    <p class="h3 fw-bolder">Tạo hóa đơn mới </p>
                </div>

                <div class="card-body">
                    <div class="container-fluid">
                        <form action="" method="" id="">
                            <div class="row">
                                <div class="form-group pt-3">
                                    <label for="customer_name">Tên khách hàng:</label>
                                    <input required type="text" class="form-control" id="customer_name" name="customer_name">
                                </div>
                                <div class="form-group pt-3 mb-3">
                                    <label for="phone_number">Số điện thoại</label>
                                    <input required maxlength="100" class="form-control" id="phone_number" name="phone_number">
                                </div>
                            </div>

                            <div class="border rounded-0 shadow bg-gradient-navy px-1 py-1" id="sale-panel">
                                <div class="d-flex h-100 w-100">
                                    <div class="col-7 px-1 h-100" id="panel-left">
                                        <div class="card card-primary bg-transparent border-0 h-100 card-tabs round-1">
                                            <div class="card-header bg-gradient-dark p-0 p-1">
                                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                                    <?php
                                                    $has_active = false;
                                                    $category = $conn->query("SELECT * FROM `category` order by category_name asc");
                                                    $product = $conn->query("SELECT * FROM `items` order by `item_name` asc");
                                                    $prod_arr = [];
                                                    while ($row = $product->fetch_array()) {
                                                        $prod_arr[$row['category_id']][] = $row;
                                                    }
                                                    $cat_arr = array_column($category->fetch_all(MYSQLI_ASSOC), 'category_name', 'category_id');
                                                    foreach ($cat_arr as $k => $v) :
                                                    ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link <?= (!$has_active) ? 'active' : '' ?>" id="custom-tabs-one-home-tab" data-toggle="pill" href="#cat-tab-<?= $k ?>" role="tab" aria-controls="cat-tab-<?= $k ?>" aria-selected="<?= (!$has_active) ? 'true' : 'false' ?>"><?= $v ?></a>
                                                        </li>
                                                    <?php
                                                        $has_active = true;
                                                    endforeach;
                                                    ?>
                                                </ul>
                                            </div>

                                            <div class="card-body">
                                                <div class="tab-content" id="custom-tabs-one-tabContent">
                                                    <?php
                                                    $has_active = false;
                                                    foreach ($cat_arr as $k => $v) :
                                                    ?>
                                                        <div class="tab-pane fade <?= (!$has_active) ? 'show active' : '' ?>" id="cat-tab-<?= $k ?>" role="tabpanel" aria-labelledby="cat-tab-<?= $k ?>-tab">
                                                            <div class="row">
                                                                <?php if (isset($prod_arr[$k])) : ?>
                                                                    <?php foreach ($prod_arr[$k] as $row) : ?>
                                                                        <div class="col-lg-3 col-md-4 col-sm-12 col-xs-12 px-2 py-3">
                                                                            <div class="rounded-pill text-dark">
                                                                                <div class="card py-2 text-center prod-item text-truncate cursor-pointer" data-price="<?= $row['unit_price'] ?>" data-id="<?= $row['item_id'] ?>">
                                                                                    <?= $row['item_name'] ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php
                                                        $has_active = true;
                                                    endforeach;
                                                    ?>
                                                </div>
                                            </div>
                                            <!-- hết card  -->
                                        </div>

                                    </div>
                                    <div class="col-5 h-100">
                                        <table id="product-list" class="table table-bordered table-striped mb-0 container-fluid">
                                            <thead>
                                                <tr class="text-truncate bg-gradient-navy-dark">
                                                    <th class="text-center">Số lượng</th>
                                                    <th class="text-center">Tên món</th>
                                                    <th class="text-center">Đơn giá</th>
                                                    <th class="text-center">Thành tiền</th>
                                                    <th class="text-center">Xóa</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-truncate">
                                                <!-- Cần thêm các sản phẩm vô đây -->
                                            </tbody>
                                        </table>

                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


                <form id="last-bill-form" action="user_page.php?donhang=luu" method="POST" class="d-none">
                    <input name="account_id" type="hidden" value="<?= $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?>">
                    <input name="customer_name" type="hidden">
                    <input name="phone_number" type="hidden">
                    <input type="hidden" name="product_details[]">
                    <input type="hidden" name="total">
                    <input type="hiden" name="discount2" value="0">
                </form>

                <div class="card-footer py-2 text-right">
                    <h3><input class="p-2" type="text" placeholder="Giảm giá" name="discount">%</h3>
                    <div class="fw-bold h3">Tổng tiền: <span id="total">0</span> VNĐ</div>

                    <button class="btn btn-success rounded-2" type="submit" form="last-bill-form">Tạo</button>
                    <?php if (!isset($id)) : ?>
                        <a class="btn btn-outline-success border rounded-2" href="./user_page.php?donhang">Hủy</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    $(function() {

        // Lắng nghe sự kiện khi có sự thay đổi trong ô input "Tên khách hàng"
        $("#customer_name").on("input", function() {
            // Lấy giá trị của ô input "Tên khách hàng"
            var customerName = $(this).val();
            // Cập nhật giá trị của input tương ứng trong form "last-bill-form"
            $("#last-bill-form input[name='customer_name']").val(customerName);
        });

        // Lắng nghe sự kiện khi có sự thay đổi trong ô input "Số điện thoại"
        $("#phone_number").on("input", function() {
            // Lấy giá trị của ô input "Số điện thoại"
            var phoneNumber = $(this).val();
            // Cập nhật giá trị của input tương ứng trong form "last-bill-form"
            $("#last-bill-form input[name='phone_number']").val(phoneNumber);
        });


        $('.prod-item').on('click', function() {
            var productName = $(this).text().trim();
            var productPrice = $(this).closest('.card').attr('data-price');
            var productId = $(this).closest('.card').attr('data-id');

            // Tạo một hàng mới trong bảng
            var newRow = $('<tr>');

            // Thêm cột số lượng vào hàng mới
            var quantityCell = $('<td>').addClass('text-center').html('<input style="width:100px" type="number" class="form-control product-quantity" name="product_quantity[]" min="1" value="1">');
            newRow.append(quantityCell);

            // Thêm cột tên sản phẩm vào hàng mới
            var nameCell = $('<td>').addClass('text-center').text(productName);
            newRow.append(nameCell);

            // Thêm cột đơn giá vào hàng mới
            var priceCell = $('<td>').addClass('text-center product-price').text(parseFloat(productPrice).toLocaleString());
            newRow.append(priceCell);

            // Thêm cột thành tiền vào hàng mới
            var totalCell = $('<td>').addClass('text-center product-total').text(parseFloat(productPrice).toLocaleString());
            newRow.append(totalCell);

            // Thêm nút xóa vào hàng mới
            var deleteCell = $('<td>').addClass('text-center').html('<a type="button" class="btn btn-danger btn-sm remove-product"><i class="fa-solid fa-trash"></i></a>');
            newRow.append(deleteCell);

            // Thêm hàng mới vào bảng
            $('#product-list tbody').append(newRow);

            // Ẩn sản phẩm đã được thêm vào bảng khỏi danh sách prod-item
            $(this).addClass('d-none');


            var productId = $(this).closest('.card').attr('data-id');
            var quantity = 1;
            var productDetails = productId + ':' + quantity; // Format: id:số_lượng
            $('#last-bill-form input[name="product_details[]"]').val(function(index, currentValue) {
                if (currentValue === '') {
                    return productDetails;
                } else {
                    return currentValue + ',' + productDetails;
                }
            });

            updateTotal();
        });

        // Xóa sản phẩm khi nhấn vào nút Xóa
        $('#product-list').on('click', '.remove-product', function() {
            var productName = $(this).closest('tr').find('td:eq(1)').text().trim(); // Lấy tên sản phẩm đã xóa

            // Hiển thị lại sản phẩm trong danh sách prod-item
            $('.prod-item').each(function() {
                if ($(this).text().trim() === productName) {
                    $(this).removeClass('d-none');
                    return false; // Dừng vòng lặp khi đã tìm thấy sản phẩm
                }
            });

            var productId = $(this).closest('tr').find('.product-id').val();
            $('#last-bill-form input[name="product_details[]"]').val(function(index, currentValue) {
                var productArray = currentValue.split(',');
                var newProductArray = productArray.filter(function(item) {
                    return item.split(':')[0] !== productId;
                });
                return newProductArray.join(',');
            });

            // Xóa hàng trong bảng
            $(this).closest('tr').remove();

            // Cập nhật tổng tiền
            updateTotal();
        });

        // Cập nhật tổng tiền khi thay đổi số lượng sản phẩm
        $('#product-list').on('change', '.product-quantity', function() {
            updateTotal();
        });

        $("input[name='discount']").on("input", function() {
            var discountValue = $(this).val();
            $('#last-bill-form input[name="discount2"]').val(discountValue);
            updateTotal(); // Cập nhật tổng tiền khi có thay đổi
        });

        // Hàm cập nhật tổng tiền
        function updateTotal() {
            var total = 0;
            $('.product-total').each(function() {
                // Cập nhật tổng tiền dựa trên giá trị sản phẩm
                var quantity = parseInt($(this).closest('tr').find('.product-quantity').val());
                var price = parseFloat($(this).closest('tr').find('.product-price').text().replace(/[^0-9.-]+/g, "")); // Lấy giá trị số từ chuỗi
                var subtotal = quantity * price;
                total += subtotal;
                $(this).text(subtotal.toLocaleString());
            });

            // Lấy giá trị giảm giá
            var discount = parseFloat($("input[name='discount']").val()) || 0;
            // Tính tổng tiền sau khi giảm giá
            var discountedTotal = total - (total * discount / 100);
            // Hiển thị tổng tiền sau khi giảm giá
            $('#total').text(discountedTotal.toLocaleString());

            $('#last-bill-form input[name="total"]').val(discountedTotal);
        }

        $('#last-bill-form').submit(function(event) {
            var customerName = $('input[name="customer_name"]').val().trim();
            var phoneNumber = $('input[name="phone_number"]').val().trim();
            var productDetails = $('input[name="product_details[]"]').val().trim();

            if (customerName === '' || phoneNumber === '') {
                alert("Vui lòng điền đầy đủ thông tin khách hàng.");
                event.preventDefault(); // Ngăn chặn form được gửi đi
            }
            if (productDetails === '') {
                alert("Bạn chưa chọn món cho khách hàng.");
                event.preventDefault();
            }
        });

    });
</script>