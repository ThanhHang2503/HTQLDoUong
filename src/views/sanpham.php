<?php
if (!isset($ds_sp_timkiem)) {
    $sql = "select i.*, c.category_name from items i, category c
    where i.category_id = c.category_id; ";
    $ds_sanpham =  mysqli_query($conn, $sql);
    $ds_sanpham = mysqli_fetch_all($ds_sanpham);
}else{
    $ds_sanpham = $ds_sp_timkiem;
}
?>


<div class="dash_board px-2">
    <h1 class="head-name">SẢN PHẨM</h1>
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
        <?php if (isset($_SESSION['admin_id'])) : ?>
            <div class="text-end col-4">
                <a href="user_page.php?sanpham=them" class="my-2 btn btn-success fw-bolder"><i class="fa-solid fa-file-circle-plus"></i> Thêm</a>
            </div>
        <?php endif; ?>


        <table id="myTable" class="table container-fluid text-center table-hover table-striped table-bordered">
            <tr>
                <th >Tên <i href="" class=" fw-bolder"></i></i></th>
                <th>Mô tả về sản phẩm</th>
                <th onclick="sortTable(2)">Giá <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <th onclick="sortTable(3)">Loại <i href="" class=" fw-bolder"><i class="p-0 btn fa-solid fa-sort"></i></th>
                <?php
                if (isset($_SESSION['admin_id']))
                    echo "<th>Thao tác</th>";
                else
                    echo "<th>Ngày tạo</th>";
                ?>

            </tr>
            <?php foreach ($ds_sanpham as $sp) : ?>
                <tr>
                    <td><?= $sp[1] ?></td>
                    <td><?= $sp[3] ?></td>
                    <td><?= intval($sp[4]) ?></td>
                    <td><?= $sp[6] ?></td>
                    <?php
                    if (isset($_SESSION['admin_id']))
                        echo '<td><a href="user_page.php?sanpham=sua&id=' . $sp[0] . '"><i class="btn btn-outline-success fa-solid fa-pen"></i> </a>
                        <a href="user_page.php?sanpham=xoa&id=' . $sp[0] . '"><i class="btn btn-outline-danger fa-solid fa-trash"></i></a></td>';
                    else
                        echo "<td>$sp[5]</td>";
                    ?>
                </tr>
            <?php endforeach; ?>
        </table>

    </div>
</div>
</div>

</div>
</div>