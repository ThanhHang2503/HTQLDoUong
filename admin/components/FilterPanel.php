<?php

function renderAdminFilterPanel(string $view, array $filters, array $categories = []): void
{
    $currentSort = $filters['sort'] ?? 'date';
    $currentDirection = $filters['direction'] ?? 'desc';
    ?>
    <form method="GET" class="admin-filter-panel card shadow-sm border-0 mb-4">
        <input type="hidden" name="view" value="<?= adminText($view) ?>">
        <?php if (!empty($filters['q'])) : ?>
            <input type="hidden" name="q" value="<?= adminText((string) $filters['q']) ?>">
        <?php endif; ?>
        <div class="card-header bg-white border-0 pb-0">
            <h5 class="mb-0 fw-bold">Bộ lọc nâng cao</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($view === 'products') : ?>
                    <div class="col-md-3">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="0">Tất cả</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?= (int) $category['category_id'] ?>" <?= ((int) ($filters['category_id'] ?? 0) === (int) $category['category_id']) ? 'selected' : '' ?>>
                                    <?= adminText($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="active" <?= (($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>Hoạt động</option>
                        <option value="inactive" <?= (($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Ngừng hoạt động</option>
                    </select>
                </div>
                <?php if ($view === 'products') : ?>
                    <div class="col-md-3">
                        <label class="form-label">Giá từ</label>
                        <input type="number" min="0" name="price_min" class="form-control" value="<?= adminText((string) ($filters['price_min'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Giá đến</label>
                        <input type="number" min="0" name="price_max" class="form-control" value="<?= adminText((string) ($filters['price_max'] ?? '')) ?>">
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label"><?= $view === 'suppliers' ? 'Ngày tạo từ' : 'Ngày tạo từ' ?></label>
                    <input type="date" name="date_from" class="form-control" value="<?= adminText((string) ($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày tạo đến</label>
                    <input type="date" name="date_to" class="form-control" value="<?= adminText((string) ($filters['date_to'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sắp xếp theo</label>
                    <select name="sort" class="form-select">
                        <?php if ($view === 'products') : ?>
                            <option value="date" <?= $currentSort === 'date' ? 'selected' : '' ?>>Ngày tạo</option>
                            <option value="name" <?= $currentSort === 'name' ? 'selected' : '' ?>>Tên</option>
                            <option value="price" <?= $currentSort === 'price' ? 'selected' : '' ?>>Giá</option>
                            <option value="code" <?= $currentSort === 'code' ? 'selected' : '' ?>>Mã</option>
                        <?php else : ?>
                            <option value="date" <?= $currentSort === 'date' ? 'selected' : '' ?>>Ngày tạo</option>
                            <option value="name" <?= $currentSort === 'name' ? 'selected' : '' ?>>Tên</option>
                            <option value="code" <?= $currentSort === 'code' ? 'selected' : '' ?>>Mã</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Thứ tự</label>
                    <select name="direction" class="form-select">
                        <option value="desc" <?= strtolower((string) $currentDirection) === 'desc' ? 'selected' : '' ?>>Giảm dần</option>
                        <option value="asc" <?= strtolower((string) $currentDirection) === 'asc' ? 'selected' : '' ?>>Tăng dần</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 pt-0 pb-3">
            <button type="submit" class="btn btn-primary">Áp dụng</button>
        </div>
    </form>
    <?php
}
