<?php
if (isset($_GET['khachhang']) && $_GET['khachhang'] == 'sua' && isset($_GET['id'])) :
    $cid = (int)$_GET['id'];
    $q = mysqli_query($conn, "SELECT * FROM customers WHERE customer_id = $cid");
    $customer = mysqli_fetch_assoc($q);

    if (!$customer) {
        echo '<div class="alert alert-danger">Không tìm thấy khách hàng.</div>';
        return;
    }
?>
    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white">
                        <p class="h3 fw-bolder mb-0"><i class="fa-solid fa-user-pen me-2"></i>Chỉnh sửa khách hàng</p>
                    </div>
                    <div class="card-body">
                        <form id="formEditCustomer" novalidate>
                            <input type="hidden" name="customer_id" value="<?= $cid ?>">
                            
                            <div class="form-group pt-3">
                                <label for="customer_name" class="fw-bold">Tên khách hàng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?= htmlspecialchars($customer['customer_name']) ?>">
                                <div class="invalid-feedback" id="err_customer_name"></div>
                            </div>
                            
                            <div class="row pt-3">
                                <div class="col-md-6 form-group">
                                    <label for="phone_number" class="fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($customer['phone_number']) ?>">
                                    <div class="invalid-feedback" id="err_phone_number"></div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="email" class="fw-bold">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>">
                                    <div class="invalid-feedback" id="err_email"></div>
                                </div>
                            </div>

                            <div class="form-group pt-3">
                                <label for="address" class="fw-bold">Địa chỉ</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                            </div>
                
                            <div class="mt-4 pt-2 border-top d-flex justify-content-between">
                                <a href="user_page.php?khachhang" class="btn btn-secondary px-4">Quay lại</a>
                                <button type="submit" id="btnEditCustomer" class="btn btn-primary fw-bold px-5">Cập nhật thông tin</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

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
    const formEdit = document.getElementById('formEditCustomer');
    const btnEdit = document.getElementById('btnEditCustomer');

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
        return email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePhone(phone) {
        return /^[0-9]{9,11}$/.test(phone);
    }

    if (formEdit) {
        formEdit.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Reset validation
            formEdit.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

            const id = <?= $cid ?>;
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
                document.getElementById('err_phone_number').innerText = 'Số điện thoại phải từ 9-11 chữ số.';
                hasError = true;
            }

            if (email !== '' && !validateEmail(email)) {
                document.getElementById('email').classList.add('is-invalid');
                document.getElementById('err_email').innerText = 'Email không đúng định dạng.';
                hasError = true;
            }

            if (hasError) return;

            btnEdit.disabled = true;
            btnEdit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang lưu...';

            try {
                const response = await fetch('api/admin/customers.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: id,
                        customer_name: name,
                        phone_number: phone,
                        email: email,
                        address: address
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showResult(true, 'Thành công', result.message);
                    // Quay về trang danh sách sau 1.2s
                    setTimeout(() => location.href = 'user_page.php?khachhang', 1200);
                } else {
                    if (result.errors) {
                        for (const field in result.errors) {
                            const input = document.getElementById(field);
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
                btnEdit.disabled = false;
                btnEdit.innerHTML = 'Cập nhật thông tin';
            }
        });
    }
});
</script>
