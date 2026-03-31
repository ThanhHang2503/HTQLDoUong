<?php

if(isset($_GET['sanpham']) && $_GET['sanpham']=='xoa'){
    try{
    $id=$_GET['id'];
    $sql="delete from items where item_id=$id";
    $result=mysqli_query($conn,$sql);
    if($result){
        echo "<script>alert('Xóa thành công');</script>";
        echo "<script>window.location.href='user_page.php?sanpham';</script>";
    }
    }catch(Exception $e){
        echo "<script>confirm('Không thể xóa, sản phẩm đang có trong các hóa đơn. Vui lòng xóa sản phẩm khác')</script>";
        echo "<script>window.location.href='user_page.php?sanpham';</script>";
    }
    
}

?>
