<?php

if (isset($_GET['id'])) {
    $item_id = $_GET['id'];
    $sql = "select * from items where item_id = $item_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_array($result);
    $item_name = $row['item_name'];
    $unit_price = $row['unit_price'];
    $description = $row['description'];
}
$sql = 'select * from category';
$result = mysqli_query($conn, $sql);
$list_of_categories = mysqli_fetch_all($result);
?>

<div class="mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    CHỈNH SỬA SẢN PHẨM
                </div>

                <div class="card-body">
                    <form action="user_page.php" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                        <div class="form-group mt-3">
                            <label for="item_name">Tên sản phẩm:</label>
                            <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo $row['item_name']; ?>" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="category_id">Danh mục:</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <?php foreach ($list_of_categories as $loc) : ?>
                                    <option value="<?= $loc[0] ?>" <?php if ($row['category_id'] == $loc[0]) echo 'selected'; ?>> <?= $loc[1] ?></option>
                                <?php endforeach; ?>
                                <option value="1" <?php if ($row['category_id'] == 1) echo 'selected'; ?>>Bánh</option>
                                <option value="2" <?php if ($row['category_id'] == 2) echo 'selected'; ?>>Trà</option>
                                <option value="3" <?php if ($row['category_id'] == 3) echo 'selected'; ?>>Cà phê</option>
                                <option value="4" <?php if ($row['category_id'] == 4) echo 'selected'; ?>>Nước ngọt</option>
                                <option value="5" <?php if ($row['category_id'] == 5) echo 'selected'; ?>>Sinh tố</option>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label for="description">Mô tả</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $row['description']; ?></textarea>
                        </div>
                        <div class="form-group mt-3">
                            <label for="unit_price">Đơn giá:</label>
                            <input type="text" class="form-control" id="unit_price" name="unit_price" value="<?php echo $row['unit_price']; ?>" required>
                        </div>
                        <button type="submit" class="btn mt-3 btn-danger">Cập nhật sản phẩm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>