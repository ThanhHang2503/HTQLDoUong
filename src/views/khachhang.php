<?php

if (isset($ds_kh_sapxep)) {
    $ds_khachhang = array_values($ds_kh_sapxep);
} else
if (!isset($ds_kh_timkiem)) {
    $sql = "select * from customers; ";
    $ds_khachhang =  mysqli_query($conn, $sql);
    $ds_khachhang = mysqli_fetch_all($ds_khachhang);
} else {
    $ds_khachhang = $ds_kh_timkiem;
}
?>


<div class="dash_board px-2">
    <h1 class="head-name">KHÁCH HÀNG</h1>
    <?php if (isset($_GET['timkiem-khachhang'])) : ?>
        <h4 class="fw-bolder text-center text-success">Kết quả cho từ khóa '<?= $_GET['timkiem-khachhang'] ?? '' ?>'</h4>
    <?php endif; ?>
    <?php if (isset($_SESSION['them_kh_thanh_cong'])) : ?>
        <h4 class="fw-bolder text-center text-success"><?= $_SESSION['them_kh_thanh_cong']; ?></h4>
    <?php unset($_SESSION['them_kh_thanh_cong']);
    endif; ?>
    <div class="head-line"></div>
    <div class="container-fluid row justify-content-between">

        <!-- Thanh tìm kiếm-->

        <form method="GET" action="user_page.php?" class="d-flex col-6 my-2" role="search">
            <input class="form-control me-2" type="search" placeholder="Nhập tên khách hàng" name='timkiem-khachhang' aria-label="Search">
            <button class="btn btn-outline-success" type="submit">Tìm</button>
        </form>



        <div class="text-end col-4">
            <button type="button" class="my-2 btn btn-success fw-bolder" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="fa-solid fa-file-circle-plus"></i> Thêm
            </button>
        </div><!-- Modal Thêm Khách Hàng -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="addCustomerModalLabel"><i class="fa-solid fa-user-plus me-2"></i>Thêm khách hàng mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddCustomer" novalidate>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label fw-bold">Tên khách hàng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Ví dụ: Nguyễn Văn A">
                            <div class="invalid-feedback" id="err_customer_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone_number" class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" placeholder="Ví dụ: 090xxxxxxx">
                            <div class="invalid-feedback" id="err_phone_number"></div>
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label fw-bold">Email (Nếu có)</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Ví dụ: email@example.com">
                            <div class="invalid-feedback" id="err_email"></div>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label fw-bold">Địa chỉ</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Nhập địa chỉ của khách hàng..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnAddCustomer" class="btn btn-success px-5 fw-bold">Xác nhận thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>



        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th style="width: 120px;">
                        <div class="d-flex align-items-center justify-content-center">
                            <span>Mã số</span>
                            <div class="ms-2 d-flex flex-row">
                                <a href="user_page.php?khachhang=id_tang" class="text-white me-1"><i class="fa-solid fa-sort-up"></i></a>
                                <a href="user_page.php?khachhang=id_giam" class="text-white"><i class="fa-solid fa-sort-down"></i></a>
                            </div>
                        </div>
                    </th>
                    <th>Tên khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th style="min-width: 180px;">
                        <div class="d-flex align-items-center justify-content-center">
                            <span>Ngày tạo</span>
                            <div class="ms-2 d-flex flex-row">
                                <a href="user_page.php?khachhang=ngay_tang" class="text-white me-1"><i class="fa-solid fa-sort-up"></i></a>
                                <a href="user_page.php?khachhang=ngay_giam" class="text-white"><i class="fa-solid fa-sort-down"></i></a>
                            </div>
                        </div>
                    </th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ds_khachhang as $kh) :
                    // Chuyển thành indexed array để tương thích với code cũ nếu cần, 
                    // nhưng ta sẽ dùng key nếu có thể để an toàn hơn.
                    $kh_indexed = array_values($kh);
                    $cid = $kh_indexed[0];
                    $name = $kh_indexed[1];
                    $phone = $kh_indexed[2] ?? '-';
                    $email = $kh_indexed[3] ?? '-';
                    $address = $kh_indexed[4] ?? '-';
                    $created = isset($kh_indexed[5]) ? date('d/m/Y H:i', strtotime($kh_indexed[5])) : '-';
                ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= $cid ?></td>
                        <td class="text-start"><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($phone) ?></td>
                        <td><?= htmlspecialchars($email) ?></td>
                        <td class="text-start"><small><?= htmlspecialchars($address) ?></small></td>
                        <td><?= $created ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="user_page.php?khachhang=xem&id=<?= $cid ?>" class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="user_page.php?khachhang=sua&id=<?= $cid ?>" class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>
</div>

</div>

<script>
// Chế độ xem chi tiết khách hàng
});
</script>

<!-- Modal Thông báo kết quả -->
<div class="modal fade" id="modalResult" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" id="resultHeader">
                <h5 class="modal-title fw-bold" id="resultTitle">Thông báo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="fs-1 mb-3" id="resultIcon"></div>
                <h5 id="resultMsg" class="fw-bold mb-2"></h5>
                <p id="resultDetails" class="text-muted small mb-0"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalResult = new bootstrap.Modal(document.getElementById('modalResult'));
    const formAdd = document.getElementById('formAddCustomer');
    const btnAdd = document.getElementById('btnAddCustomer');

    function showResult(success, title, msg) {
        const header = document.getElementById('resultHeader');
        const titleEl = document.getElementById('resultTitle');
        const msgEl = document.getElementById('resultMsg');
        const iconEl = document.getElementById('resultIcon');

        if (success) {
            header.className = "modal-header text-bg-success border-0";
            titleEl.innerText = title;
            msgEl.innerText = msg;
            iconEl.innerHTML = '<i class="fa-solid fa-circle-check text-success"></i>';
        } else {
            header.className = "modal-header text-bg-danger border-0";
            titleEl.innerText = title;
            msgEl.innerText = msg;
            iconEl.innerHTML = '<i class="fa-solid fa-circle-xmark text-danger"></i>';
        }
        modalResult.show();
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePhone(phone) {
        return /^[0-9]{9,11}$/.test(phone);
    }

    if (formAdd) {
        formAdd.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Reset validation state
            const inputs = formAdd.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
            });

            const name = document.getElementById('customer_name').value.trim();
            const phone = document.getElementById('phone_number').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();

            let hasError = false;

            if (name === '') {
                document.getElementById('customer_name').classList.add('is-invalid');
                document.getElementById('err_customer_name').innerText = 'Tên khách hàng không được để trống.';
                hasError = true;
            }

            if (phone === '') {
                document.getElementById('phone_number').classList.add('is-invalid');
                document.getElementById('err_phone_number').innerText = 'Số điện thoại không được để trống.';
                hasError = true;
            } else if (!validatePhone(phone)) {
                document.getElementById('phone_number').classList.add('is-invalid');
                document.getElementById('err_phone_number').innerText = 'Số điện thoại phải là số và có độ dài 9-11 chữ số.';
                hasError = true;
            }

            if (email !== '' && !validateEmail(email)) {
                document.getElementById('email').classList.add('is-invalid');
                document.getElementById('err_email').innerText = 'Email không đúng định dạng.';
                hasError = true;
            }

            if (hasError) return;

            // Submit via Fetch
            btnAdd.disabled = true;
            btnAdd.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang lưu...';

            try {
                const response = await fetch('api/admin/customers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_name: name,
                        phone_number: phone,
                        email: email,
                        address: address
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Thành công
                    const modalAddElement = document.getElementById('addCustomerModal');
                    const modalAdd = bootstrap.Modal.getInstance(modalAddElement) || new bootstrap.Modal(modalAddElement);
                    modalAdd.hide();
                    
                    showResult(true, 'Thành công', result.message);
                    
                    // Refresh trang sau 1.2s để thấy dữ liệu mới
                    setTimeout(() => location.reload(), 1200);
                } else {
                    // Lỗi từ backend
                    if (result.errors) {
                        for (const field in result.errors) {
                            const input = formAdd.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const errDiv = document.getElementById('err_' + field);
                                if (errDiv) errDiv.innerText = result.errors[field];
                            }
                        }
                    } else {
                        showResult(false, 'Lỗi', result.message || 'Có lỗi xảy ra.');
                    }
                }
            } catch (error) {
                showResult(false, 'Lỗi kết nối', 'Không thể kết nối tới máy chủ.');
            } finally {
                btnAdd.disabled = false;
                btnAdd.innerHTML = 'Xác nhận thêm';
            }
        });
    }
</script>