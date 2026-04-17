<?php

$sql = 'select * from category';
$result = mysqli_query($conn, $sql);
$list_of_categories = mysqli_fetch_all($result);
if (isset($_GET['sanpham']) && $_GET['sanpham'] == 'them') :
?>

<style>
    .upload-zone {
        border: 2px dashed #198754;
        border-radius: 12px;
        background: #f0fff4;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .upload-zone:hover {
        background: #d1fae5;
        border-color: #0f6438;
    }
    .upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .upload-preview {
        display: none;
        margin-top: 12px;
        text-align: center;
    }
    .upload-preview img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 3px solid #198754;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }
    .upload-preview .remove-img {
        display: block;
        margin-top: 6px;
        font-size: 0.8rem;
        color: #dc3545;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
    }
</style>

<div class="px-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <p class="h3 fw-bolder mb-0">
                        <i class="fa-solid fa-box me-2"></i>Thêm sản phẩm
                    </p>
                </div>
                <div class="card-body">
                    <form action="user_page.php?sanpham" method="POST" enctype="multipart/form-data">

                        <div class="form-group pt-3">
                            <label for="item_name" class="fw-bold">Tên sản phẩm:</label>
                            <input required type="text" class="form-control" id="item_name" name="item_name">
                        </div>

                        <div class="form-group pt-3">
                            <label for="category_id" class="fw-bold">Danh mục:</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <?php foreach ($list_of_categories as $loc) : ?>
                                    <option value="<?= $loc[0] ?>"><?= $loc[1] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group pt-3">
                            <label for="description" class="fw-bold">Mô tả:</label>
                            <textarea required maxlength="100" class="form-control" id="description"
                                      name="description" rows="3"></textarea>
                        </div>

                        <!-- Upload ảnh sản phẩm -->
                        <div class="form-group pt-3">
                            <label class="fw-bold d-block mb-2">
                                <i class="fa-solid fa-image me-1 text-success"></i>Hình ảnh sản phẩm:
                            </label>

                            <div class="upload-zone" id="upload-zone">
                                <input required type="file" name="item_image_file" id="item_image_file"
                                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                       onchange="previewUpload(this)">
                                <i class="fa-solid fa-cloud-arrow-up fa-2x text-success mb-2 d-block"></i>
                                <div class="fw-bold text-success">Nhấn để chọn ảnh *</div>
                                <div class="text-muted small mt-1">Hỗ trợ: JPG, JPEG, PNG — Tối đa 5MB</div>
                                <div class="text-muted small">Ảnh là bắt buộc và sẽ được đặt tên theo ID sản phẩm</div>
                            </div>

                            <div class="upload-preview" id="upload-preview">
                                <img id="preview-img" src="#" alt="Xem trước ảnh">
                                <div class="small text-muted mt-1" id="preview-filename"></div>
                                <button type="button" class="remove-img" onclick="removePreview()">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>Xóa ảnh đã chọn
                                </button>
                            </div>

                            <div class="form-text text-danger mt-1 fw-bold">
                                <i class="fa-solid fa-circle-exclamation me-1"></i> Bắt buộc phải có ảnh để thêm sản phẩm.
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success fw-bold">
                                <i class="fa-solid fa-plus me-1"></i>Thêm sản phẩm
                            </button>
                            <a href="user_page.php?sanpham" class="btn btn-outline-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewUpload(input) {
    var file = input.files[0];
    if (!file) return;

    // Validate type
    var allowed = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowed.includes(file.type)) {
        alert('Chỉ chấp nhận file ảnh JPG, JPEG hoặc PNG!');
        input.value = '';
        return;
    }
    // Validate size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('File ảnh quá lớn! Vui lòng chọn file dưới 5MB.');
        input.value = '';
        return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('preview-filename').textContent = 'Đã chọn: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('upload-preview').style.display = 'block';
        document.getElementById('upload-zone').style.borderColor = '#0f6438';
        document.getElementById('upload-zone').style.background = '#d1fae5';
    };
    reader.readAsDataURL(file);
}

function removePreview() {
    document.getElementById('item_image_file').value = '';
    document.getElementById('preview-img').src = '#';
    document.getElementById('upload-preview').style.display = 'none';
    document.getElementById('upload-zone').style.borderColor = '#198754';
    document.getElementById('upload-zone').style.background = '#f0fff4';
}
</script>

<?php endif; ?>