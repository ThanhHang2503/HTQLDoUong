<?php

function renderAdminSearchBox(string $view, string $searchValue): void
{
    $placeholder = $view === 'suppliers' ? 'Tìm theo mã, tên nhà cung cấp' : 'Tìm theo tên hoặc mã sản phẩm';
    $label = $view === 'suppliers' ? 'Nhà cung cấp' : 'Sản phẩm';
    ?>
    <form method="GET" class="admin-search-box card shadow-sm border-0 mb-3">
        <input type="hidden" name="view" value="<?= adminText($view) ?>">
        <div class="card-body d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
            <div class="flex-grow-1">
                <label class="form-label fw-semibold mb-1"><?= $label ?> cơ bản</label>
                <input type="search" name="q" class="form-control form-control-lg" value="<?= adminText($searchValue) ?>" placeholder="<?= adminText($placeholder) ?>">
            </div>
            <div class="d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-dark btn-lg px-4">Tìm kiếm</button>
                <a href="?view=<?= adminText($view) ?>" class="btn btn-outline-secondary btn-lg px-4">Xóa lọc</a>
            </div>
        </div>
    </form>
    <?php
}
