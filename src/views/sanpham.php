<?php
if (!isset($ds_sp_timkiem)) {
    $sql = "select i.*, c.category_name from items i, category c
    where i.category_id = c.category_id and i.stock_quantity <> 0; ";
    $ds_sanpham =  mysqli_query($conn, $sql);
    $ds_sanpham = mysqli_fetch_all($ds_sanpham, MYSQLI_ASSOC);
}else{
    $ds_sanpham = array_values(array_filter($ds_sp_timkiem, function ($sp) {
        return ((int)($sp['stock_quantity'] ?? 0)) !== 0;
    }));
}
?>

<div class="dash_board px-2">
    <h1 class="head-name">SẢN PHẨM</h1>
    <?php if (isset($_SESSION['product_delete_success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['product_delete_success']) ?>
        </div>
        <?php unset($_SESSION['product_delete_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['product_delete_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_SESSION['product_delete_error']) ?>
        </div>
        <?php unset($_SESSION['product_delete_error']); ?>
    <?php endif; ?>
    <?php if( isset($_GET['timkiem-sanpham'])):?>
        <h4 class="fw-bolder text-center text-success">Kết quả cho từ khóa '<?=$_GET['timkiem-sanpham']??''?>'</h4>
    <?php endif;?>
    <div class="head-line"></div>
    <div class="container-fluid row justify-content-between">

        <!-- Thanh tìm kiếm sản phẩm -->

        <form method="GET" action="user_page.php?" class="d-flex col-6 my-2" role="search">
            <input class="form-control me-2" type="search" placeholder="Nhập tên sản phẩm" name='timkiem-sanpham' aria-label="Search">
            <button class="btn btn-outline-success" type="submit">Tìm</button>
        </form>

        <!-- là admin thì được sửa  -->
        <?php if (can(AppPermission::MANAGE_CATALOG)) : ?>
            <div class="text-end col-4">
                <a href="user_page.php?sanpham=them" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-file-circle-plus"></i> Thêm</a>
            </div>
        <?php endif; ?>


        <div class="product-table-scroll w-100">
        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <tr>
                <th >Tên <i href="" class=" fw-bolder"></i></i></th>
                <th>Mô tả về sản phẩm</th>
                <th onclick="sortTable(2)">Giá bán <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <th>Loại</th>
                <th onclick="sortTable(4)">Tồn kho <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <?php
                if (can(AppPermission::MANAGE_CATALOG))
                    echo "<th>Thao tác</th>";
                else
                    echo "<th>Ngày tạo</th>";
                ?>

            </tr>
            <?php foreach ($ds_sanpham as $sp) : ?>
                <tr>
                    <td><?= htmlspecialchars($sp['item_name']) ?></td>
                    <td><?= htmlspecialchars((string)$sp['description']) ?></td>
                    <td><?= intval($sp['unit_price']) ?></td>
                    <td><?= htmlspecialchars($sp['category_name']) ?></td>
                    <td><?= intval($sp['stock_quantity'] ?? 0) ?></td>
                    <?php
                    if (can(AppPermission::MANAGE_CATALOG))
                        echo '<td><a href="user_page.php?sanpham=sua&id=' . $sp['item_id'] . '"><i class="btn btn-outline-success fa-solid fa-pen"></i> </a>
                        <a href="user_page.php?sanpham=xoa&id=' . $sp['item_id'] . '" onclick="return confirm(\'Bạn có chắc chắn muốn xóa sản phẩm này không?\');"><i class="btn btn-outline-danger fa-solid fa-trash"></i></a></td>';
                    else
                        echo "<td>{$sp['added_date']}</td>";
                    ?>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>

    </div>
</div>
</div>

</div>
</div>