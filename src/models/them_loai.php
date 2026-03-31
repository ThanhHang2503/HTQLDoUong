<?php
if (isset($_GET['loai']) && $_GET['loai'] == 'them') :
?>

    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <p class="h3 fw-bolder">Thêm loại</p>
                    </div>
                    <div class="card-body">
                        <form action="user_page.php?loai" method="POST">
                            <div class="form-group  pt-3">
                                <label for="category_name">Tên loại</label>
                                <input required type="text" class="form-control" id="category_name" name="category_name" required>
                            </div>                                              
                            <button type="submit" class="mt-4 btn btn-success">Thêm loại</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php endif; ?>