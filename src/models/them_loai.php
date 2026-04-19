<?php
if (isset($_GET['loai']) && $_GET['loai'] == 'them') :
?>

    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-success text-white">
                        <p class="h3 fw-bolder mb-0"><i class="fa-solid fa-file-circle-plus me-2"></i>THÊM LOẠI MỚI</p>
                    </div>
                    <div class="card-body p-4">
                        <form action="user_page.php?loai=them" method="POST">
                            <div class="form-group">
                                <label for="category_name" class="fw-bold mb-2">Tên loại sản phẩm</label>
                                <input required type="text" class="form-control form-control-lg" id="category_name" name="category_name" placeholder="Nhập tên loại sản phẩm...">
                            </div>                                              
                            
                            <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                                <a href="user_page.php?loai" class="btn btn-secondary px-4">
                                    <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
                                </a>
                                <button type="submit" class="btn btn-success fw-bold px-5">
                                    <i class="fa-solid fa-plus me-2"></i>Thêm loại
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>