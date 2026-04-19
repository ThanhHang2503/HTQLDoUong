<?php
if(isset($_GET["loai"]) && $_GET['loai'] === 'sua' && isset($_GET['id']))
{
   $category_id = (int)$_GET['id'];
   $sql = "SELECT * FROM category WHERE category_id = $category_id";
   $result = mysqli_query($conn, $sql);
   $row = mysqli_fetch_assoc($result);
   
   if (!$row) {
       echo '<div class="alert alert-danger mt-5">Không tìm thấy loại sản phẩm.</div>';
       return;
   }
   $category_name = $row['category_name'];
} else {
    return;
}
?>

<div class="mt-5 px-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-danger text-white">
                    <p class="h3 fw-bolder mb-0"><i class="fa-solid fa-pen-to-square me-2"></i>CHỈNH SỬA LOẠI</p>
                </div>
                
                <div class="card-body p-4">
                    <form action="user_page.php?loai=sua&id=<?php echo $category_id; ?>" method="POST">
                        <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                        <div class="form-group">
                            <label for="category_name" class="fw-bold mb-2">Tên loại sản phẩm</label>
                            <input type="text" class="form-control form-control-lg" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name) ?>" required>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <a href="user_page.php?loai" class="btn btn-secondary px-4">
                                <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
                            </a>
                            <button type="submit" class="btn btn-danger fw-bold px-5">
                                <i class="fa-solid fa-save me-2"></i>Cập nhật loại
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>