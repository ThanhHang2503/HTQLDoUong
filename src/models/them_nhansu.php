<?php
if (isset($_GET['nhansu']) && $_GET['nhansu'] == 'them') :
?>

    <div class="px-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <p class="h3 fw-bolder">Thêm nhân sự</p>
                    </div>
                    <div class="card-body">
                        <form action="user_page.php?nhansu" method="POST">
                            <div class="form-group  pt-3">
                                <label for="full_name">Tên nhân sự</label>
                                <input required type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="form-group  pt-3">
                                <label for="email">Email</label>
                                <input required type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group  pt-3">
                                <label for="item_name">Password</label>
                                <input required type="text" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group  pt-3">
                                <label for="category_id">Quyền</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="user">user</option>
                                    <option value="admin">admin</option>            
                                </select>
                            </div>                                               
                            <button type="submit" class="mt-4 btn btn-success">Thêm users</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php endif; ?>