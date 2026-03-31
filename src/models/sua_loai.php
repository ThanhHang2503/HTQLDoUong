<?php
if(isset($_GET["loai"]))
{
   $category_id=$_GET['id'];
   $sql ="SELECT * FROM category WHERE category_id = $category_id";
   $result = mysqli_query($conn, $sql);
   $row = mysqli_fetch_array($result);
   $category_name=$row['category_name'];
}
?>

<div class="mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        CHỈNH SỬA LOẠI
                    </div>
                    
                    <div class="card-body">
                        <form action="user_page.php" method="POST">
                            <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                            <div class="form-group mt-3">
                                <label for="category_name">Tên loại</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo $category_name ?>" required>
                            </div>
                            <button type="submit" class="btn mt-3 btn-danger">Cập nhật loại</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>