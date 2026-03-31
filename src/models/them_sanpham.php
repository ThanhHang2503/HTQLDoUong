<?php

$sql = 'select * from category';
$result = mysqli_query($conn, $sql);
$list_of_categories = mysqli_fetch_all($result);
if (isset($_GET['sanpham']) && $_GET['sanpham'] == 'them') :
?>

    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <p class="h3 fw-bolder">Thêm sản phẩm</p>
                    </div>
                    <div class="card-body">
                        <form action="user_page.php?sanpham" method="POST">
                            <div class="form-group  pt-3">
                                <label for="item_name">Tên sản phẩm:</label>
                                <input required type="text" class="form-control" id="item_name" name="item_name" required>
                            </div>
                            <div class="form-group  pt-3">
                                <label for="category_id">Danh mục:</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <?php foreach ($list_of_categories as $loc) : ?>
                                        <option value="<?= $loc[0] ?>"><?= $loc[1] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group  pt-3">
                                <label for="description">Mô tả:</label>
                                <textarea required maxlength="100" class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="unit_price">Đơn giá:</label>
                                <input required type="number" class="form-control" id="unit_price" name="unit_price" required>
                            </div>
                            <button type="submit" class="mt-4 btn btn-success">Thêm sản phẩm</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php endif; ?>