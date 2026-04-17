<?php
if (isset($_GET['donhang']) && $_GET['donhang'] == 'them') :
    // Fetch all active items - Priority to higher stock, out of stock to bottom
    $product = $conn->query("SELECT * FROM `items` WHERE `item_status` = 'active' ORDER BY CASE WHEN `stock_quantity` <= 0 THEN 1 ELSE 0 END ASC, `stock_quantity` DESC, `item_name` asc");
    $prod_arr = [];
    $all_prods = [];
    while ($row = $product->fetch_array()) {
        $prod_arr[$row['category_id']][] = $row;
        $all_prods[] = $row;
    }

    // Fetch categories
    $category = $conn->query("SELECT * FROM `category` order by category_name asc");
    $cat_arr = array_column($category->fetch_all(MYSQLI_ASSOC), 'category_name', 'category_id');

    // Fetch customers for selection
    $customers_res = $conn->query("SELECT * FROM `customers` ORDER BY `customer_name` ASC");
    $customers = $customers_res->fetch_all(MYSQLI_ASSOC);
?>
    <style>
        #sale-panel {
            height: 70vh;
        }

        #panel-left,
        #item-list {
            background: rgb(255 255 255 / 17%);
        }

        #item-list {
            height: 60%;
        }

        /* Custom Scrollable Tabs */
        .nav-pills-scrollable {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 5px;
        }
        .nav-pills-scrollable::-webkit-scrollbar {
            height: 4px;
        }
        .nav-pills-scrollable::-webkit-scrollbar-thumb {
            background: #6c757d;
            border-radius: 10px;
        }
        .nav-pills-scrollable .nav-item {
            flex: 0 0 auto;
        }
        .nav-pills-scrollable .nav-link {
            white-space: nowrap;
            margin: 0 2px;
            padding: 5px 15px;
            border-radius: 20px;
        }
    </style>
    <div class="px-2 mt-2 py-3">
        <div class="container-fluid">
            <div class="card shadow blur border-0">
                <div class="card-header bg-success text-white">
                    <p class="h3 fw-bolder mb-0"><i class="fa-solid fa-cart-plus me-2"></i>Tạo hóa đơn mới</p>
                </div>

                <div class="card-body">
                    <div class="container-fluid">
                        <form action="" method="" id="">
                            <div class="row bg-light p-3 rounded shadow-sm mb-4">
                                <div class="col-md-4 form-group">
                                    <label class="fw-bold mb-1"><i class="fa-solid fa-user me-2"></i>Tên khách hàng:</label>
                                    <input required type="text" class="form-control" id="customer_name" name="customer_name" list="customerList" placeholder="Chọn hoặc nhập tên...">
                                    <datalist id="customerList">
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= htmlspecialchars($c['customer_name']) ?>" 
                                                    data-phone="<?= htmlspecialchars($c['phone_number']) ?>"
                                                    data-email="<?= htmlspecialchars($c['email']) ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="fw-bold mb-1"><i class="fa-solid fa-phone me-2"></i>Số điện thoại:</label>
                                    <input required maxlength="100" class="form-control" id="phone_number" name="phone_number" placeholder="Nhập số điện thoại...">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="fw-bold mb-1"><i class="fa-solid fa-envelope me-2"></i>Email (Nếu có):</label>
                                    <input type="email" maxlength="150" class="form-control" id="email" name="email" placeholder="Nhập địa chỉ email...">
                                </div>
                            </div>

                            <div class="border rounded shadow-sm overflow-hidden" id="sale-panel" style="background: #f8f9fa;">
                                <div class="row g-0 h-100">
                                    <div class="col-lg-7 border-end bg-white" id="panel-left">
                                        <div class="card border-0 h-100">
                                            <div class="card-header bg-dark p-1">
                                                <ul class="nav nav-pills nav-pills-scrollable" id="custom-tabs-one-tab" role="tablist">
                                                    <li class="nav-item">
                                                        <a class="nav-link active" id="cat-tab-all-tab" data-toggle="pill" data-bs-toggle="pill" href="#cat-tab-all" role="tab">Tất cả</a>
                                                    </li>
                                                    <?php foreach ($cat_arr as $k => $v) : ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link" id="cat-tab-<?= $k ?>-tab" data-toggle="pill" data-bs-toggle="pill" href="#cat-tab-<?= $k ?>" role="tab"><?= $v ?></a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>

                                            <div class="card-body p-0 overflow-auto" style="height: 500px;">
                                                <!-- Search Bar -->
                                                <div class="sticky-top bg-white p-2 border-bottom shadow-sm">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                                        <input type="text" id="prod-search" class="form-control border-start-0" placeholder="Tìm kiếm tên sản phẩm...">
                                                    </div>
                                                </div>

                                                <div class="tab-content" id="custom-tabs-one-tabContent">
                                                    <!-- All Products -->
                                                    <div class="tab-pane fade show active" id="cat-tab-all" role="tabpanel">
                                                        <div class="row g-3 p-3" style="padding-bottom: 120px !important;">
                                                            <?php foreach ($all_prods as $row) : ?>
                                                                 <div class="col-lg-3 col-md-4 col-sm-6 prod-card" data-name="<?= strtolower(htmlspecialchars($row['item_name'])) ?>">
                                                                    <div class="card h-100 prod-item shadow-sm border <?= $row['stock_quantity'] <= 0 ? 'opacity-50' : '' ?>" 
                                                                         style="cursor:pointer; transition: all 0.2s;"
                                                                         data-price="<?= $row['unit_price'] ?>" 
                                                                         data-id="<?= $row['item_id'] ?>"
                                                                         data-stock="<?= $row['stock_quantity'] ?>">
                                                                        <div class="card-body text-center p-2 d-flex flex-column justify-content-center" style="min-height: 100px;">
                                                                            <div class="fw-bold mb-1 text-dark prod-name" style="font-size: 0.85rem; line-height: 1.2;"><?= $row['item_name'] ?></div>
                                                                            <div class="text-success small fw-bold mt-auto"><?= number_format($row['unit_price'], 0, ',', '.') ?>đ</div>
                                                                            <?php if ($row['stock_quantity'] <= 0): ?>
                                                                                <div class="badge bg-danger text-white mt-1" style="font-size: 0.7rem;">Hết hàng</div>
                                                                            <?php else: ?>
                                                                                <div class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;">Kho: <?= $row['stock_quantity'] ?></div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                            <!-- Extra space -->
                                                            <div class="col-12 py-5"></div>
                                                        </div>
                                                    </div>

                                                    <?php foreach ($cat_arr as $k => $v) : ?>
                                                        <div class="tab-pane fade" id="cat-tab-<?= $k ?>" role="tabpanel">
                                                            <div class="row g-3 p-3" style="padding-bottom: 120px !important;">
                                                                <?php if (isset($prod_arr[$k])) : ?>
                                                                    <?php foreach ($prod_arr[$k] as $row) : ?>
                                                                         <div class="col-lg-3 col-md-4 col-sm-6 prod-card" data-name="<?= strtolower(htmlspecialchars($row['item_name'])) ?>">
                                                                            <div class="card h-100 prod-item shadow-sm border <?= $row['stock_quantity'] <= 0 ? 'opacity-50' : '' ?>" 
                                                                                 style="cursor:pointer; transition: all 0.2s;"
                                                                                 data-price="<?= $row['unit_price'] ?>" 
                                                                                 data-id="<?= $row['item_id'] ?>"
                                                                                 data-stock="<?= $row['stock_quantity'] ?>">
                                                                                <div class="card-body text-center p-2 d-flex flex-column justify-content-center" style="min-height: 100px;">
                                                                                    <div class="fw-bold mb-1 text-dark prod-name" style="font-size: 0.85rem; line-height: 1.2;"><?= $row['item_name'] ?></div>
                                                                                    <div class="text-success small fw-bold mt-auto"><?= number_format($row['unit_price'], 0, ',', '.') ?>đ</div>
                                                                                    <?php if ($row['stock_quantity'] <= 0): ?>
                                                                                        <div class="badge bg-danger text-white mt-1" style="font-size: 0.7rem;">Hết hàng</div>
                                                                                    <?php else: ?>
                                                                                        <div class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;">Kho: <?= $row['stock_quantity'] ?></div>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                                <!-- Extra space -->
                                                                <div class="col-12 py-5"></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 bg-white position-relative" id="panel-right" style="height: 100%;">
                                        <!-- Header (Approx 40px high) -->
                                        <div class="p-2 bg-secondary text-white fw-bold" id="panel-right-header">
                                            <i class="fa-solid fa-cart-shopping me-2"></i>SẢN PHẨM ĐÃ CHỌN
                                        </div>
                                        
                                        <!-- Scrollable Table Container -->
                                        <div style="height: calc(100% - 170px); overflow-y: auto; overflow-x: hidden;">
                                            <table id="product-list" class="table table-sm table-hover mb-0">
                                                <thead class="bg-light sticky-top">
                                                    <tr class="small text-uppercase">
                                                        <th class="ps-3">Sản phẩm</th>
                                                        <th class="text-center" style="width: 80px;">SL</th>
                                                        <th class="text-end">Đơn giá</th>
                                                        <th class="text-end pe-3">Thành tiền</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Items added here -->
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Fixed Footer (Approx 130px high) -->
                                        <div class="p-3 border-top bg-white shadow-lg" style="position: absolute; bottom: 0; left: 0; right: 0; height: 130px; z-index: 10;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold text-dark">TỔNG TIỀN:</span>
                                                <div class="h4 mb-0 fw-bolder text-danger"><span id="total">0</span>đ</div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <a class="btn btn-outline-danger w-100 fw-bold py-2" href="./user_page.php?donhang">
                                                        <i class="fa-solid fa-xmark me-1"></i> HỦY BỎ
                                                    </a>
                                                </div>
                                                <div class="col-6">
                                                    <button class="btn btn-success w-100 fw-bold py-2" type="button" id="btn-submit-order">
                                                        <i class="fa-solid fa-check me-1"></i> ĐỒNG Ý
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>


                <form id="last-bill-form" action="user_page.php?donhang=luu" method="POST" class="d-none">
                    <input name="account_id" type="hidden" value="<?= currentUserId() ?>">
                    <input name="customer_name" type="hidden">
                    <input name="phone_number" type="hidden">
                    <input name="email" type="hidden">
                    <input type="hidden" name="product_details[]">
                    <input type="hidden" name="total">
                    <input type="hidden" name="discount2" value="0">
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Alert Modal -->
    <div class="modal fade" id="stockAlertModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark py-2">
                    <h5 class="modal-title fw-bold" style="font-size: 1rem;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Thông báo kho</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-1">Sản phẩm <strong id="alert-item-name" class="text-primary"></strong> không đủ tồn kho.</p>
                    <div class="alert alert-light border small py-2 mb-0">
                        Số lượng đã được điều chỉnh về mức tối đa: <strong id="alert-max-stock" class="text-danger"></strong>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm fw-bold px-4 mt-3" data-bs-dismiss="modal">Đã hiểu</button>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(function() {
        // --- 1. Customer Selection Logic ---
        const customers = <?= json_encode($customers) ?>;
        
        $("#customer_name").on("input", function() {
            const name = $(this).val();
            const option = $(`#customerList option[value="${name}"]`);
            
            if (option.length > 0) {
                const phone = option.data('phone');
                const email = option.data('email');
                
                $("#phone_number").val(phone);
                $("#email").val(email);
                
                $("#last-bill-form input[name='phone_number']").val(phone);
                $("#last-bill-form input[name='email']").val(email);
            }
            $("#last-bill-form input[name='customer_name']").val(name);
        });

        $("#phone_number").on("input", function() {
            $("#last-bill-form input[name='phone_number']").val($(this).val());
        });

        $("#email").on("input", function() {
            $("#last-bill-form input[name='email']").val($(this).val());
        });

        // --- 2. Product Search & Tab Filtering Logic ---
        function showStockAlert(name, max) {
            $('#alert-item-name').text(name);
            $('#alert-max-stock').text(max);
            const modal = new bootstrap.Modal(document.getElementById('stockAlertModal'));
            modal.show();
        }

        // Manual Tab Switcher (Fallback for Bootstrap conflict)
        $('.nav-link[data-toggle="pill"], .nav-link[data-bs-toggle="pill"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            
            // UI Update
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
            
            $('.tab-pane').removeClass('show active');
            $(target).addClass('show active');
        });

        $("#prod-search").on("input", function() {
            const kw = $(this).val().toLowerCase().trim();
            $(".prod-card").each(function() {
                const name = $(this).data('name');
                $(this).toggle(name.includes(kw));
            });
        });

        // --- 3. Add to Cart with Stock Validation ---
        $('.prod-item').on('click', function() {
            const card = $(this);
            const id = card.data('id');
            const name = card.find('.prod-name').text();
            const price = card.data('price');
            const stock = parseInt(card.data('stock'));

            // Check if already in cart
            let existingRow = $(`#product-list tbody tr[data-id="${id}"]`);
            if (existingRow.length > 0) {
                const qtyInput = existingRow.find('.product-quantity');
                const newQty = parseInt(qtyInput.val()) + 1;
                if (newQty > stock) {
                    showStockAlert(name, stock);
                    qtyInput.val(stock).trigger('change');
                    return;
                }
                qtyInput.val(newQty).trigger('change');
            } else {
                if (stock < 1) {
                    showStockAlert(name, 0);
                    return;
                }

                const html = `
                    <tr data-id="${id}">
                        <td class="ps-3 py-2">
                            <div class="fw-bold cart-item-name">${name}</div>
                            <small class="text-muted small">ID: ${id}</small>
                        </td>
                        <td class="text-center align-middle">
                            <input type="number" class="form-control form-control-sm product-quantity text-center mx-auto" 
                                   value="1" min="1" max="${stock}" style="width: 60px;">
                        </td>
                        <td class="text-end align-middle product-price" data-raw="${price}">
                            ${price.toLocaleString()}đ
                        </td>
                        <td class="text-end align-middle fw-bold pe-3 product-total">
                            ${price.toLocaleString()}đ
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-product"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>`;
                $('#product-list tbody').append(html);
            }
            updateTotal();
        });

        // --- 4. Cart Modifiers ---
        $('#product-list').on('change', '.product-quantity', function() {
            const row = $(this).closest('tr');
            const name = row.find('.cart-item-name').text();
            const max = parseInt($(this).attr('max'));
            let val = parseInt($(this).val());
            
            if (val > max) {
                showStockAlert(name, max);
                $(this).val(max);
                val = max;
            }
            if (val < 1 || isNaN(val)) {
                $(this).val(1);
                val = 1;
            }
            updateTotal();
        });

        $('#product-list').on('click', '.remove-product', function() {
            $(this).closest('tr').remove();
            updateTotal();
        });

        // --- 5. Calculation & Submission ---
        function updateTotal() {
            let grandTotal = 0;
            let productDetails = [];

            $('#product-list tbody tr').each(function() {
                const id = $(this).data('id');
                const qty = parseInt($(this).find('.product-quantity').val());
                const price = $(this).find('.product-price').data('raw');
                const subtotal = qty * price;
                
                $(this).find('.product-total').text(subtotal.toLocaleString() + 'đ');
                grandTotal += subtotal;
                productDetails.push(`${id}:${qty}`);
            });

            $('#total').text(grandTotal.toLocaleString());
            $('#last-bill-form input[name="total"]').val(grandTotal);
            $('#last-bill-form input[name="product_details[]"]').val(productDetails.join(','));
        }

        $('#btn-submit-order').on('click', function() {
            const name = $('input[name="customer_name"]').val().trim();
            const phone = $('input[name="phone_number"]').val().trim();
            const details = $('#last-bill-form input[name="product_details[]"]').val().trim();

            if (!name || !phone) {
                alert("Vui lòng nhập thông tin khách hàng!");
                return;
            }
            if (!details) {
                alert("Bạn chưa chọn sản phẩm nào!");
                return;
            }

            if (confirm("Xác nhận tạo hóa đơn và thanh toán đơn hàng này?")) {
                $('#last-bill-form').submit();
            }
        });
    });
</script>