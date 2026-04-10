<?php
$productsSql = "SELECT item_id, item_name, stock_quantity, unit_price
                FROM items
                WHERE item_status = 'active'
                ORDER BY item_name ASC";
$productsRs = mysqli_query($conn, $productsSql);
$products = mysqli_fetch_all($productsRs, MYSQLI_ASSOC);

$suppliersSql = "SELECT supplier_id, supplier_name
                 FROM suppliers
                 WHERE status = 'active'
                 ORDER BY supplier_name ASC";
$suppliersRs = mysqli_query($conn, $suppliersSql);
$suppliers = mysqli_fetch_all($suppliersRs, MYSQLI_ASSOC);

$editReceiptId = isset($_GET['edit_receipt_id']) ? (int)$_GET['edit_receipt_id'] : 0;
$editReceipt = null;
if ($editReceiptId > 0) {
    $editSql = "SELECT receipt_id, receipt_code, supplier_id, import_date, note
                FROM inventory_receipts
                WHERE receipt_id = {$editReceiptId}
                LIMIT 1";
    $editRs = mysqli_query($conn, $editSql);
    if ($editRs && mysqli_num_rows($editRs) > 0) {
        $editReceipt = mysqli_fetch_assoc($editRs);
        $editDetailSql = "SELECT d.item_id, d.quantity, d.import_price, i.unit_price
                  FROM inventory_receipt_items d
                  INNER JOIN items i ON i.item_id = d.item_id
                  WHERE d.receipt_id = {$editReceiptId}
                  ORDER BY d.receipt_item_id ASC";
        $editDetailRs = mysqli_query($conn, $editDetailSql);
        $editReceipt['items'] = mysqli_fetch_all($editDetailRs, MYSQLI_ASSOC);
    }
}

$receiptsSql = "SELECT r.receipt_id, r.receipt_code, r.import_date, r.total_value, s.supplier_name, a.full_name AS created_by_name
                FROM inventory_receipts r
                INNER JOIN suppliers s ON s.supplier_id = r.supplier_id
                INNER JOIN accounts a ON a.account_id = r.created_by
                ORDER BY r.receipt_id DESC
                LIMIT 20";
$receiptsRs = mysqli_query($conn, $receiptsSql);
$receipts = mysqli_fetch_all($receiptsRs, MYSQLI_ASSOC);
?>

<div class="dash_board px-2">
    <h1 class="head-name">PHIEU NHAP KHO</h1>
    <div class="head-line"></div>

    <div class="container-fluid my-3">
        <?php if (isset($_SESSION['warehouse_receipt_success'])): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($_SESSION['warehouse_receipt_success']) ?>
            </div>
            <?php unset($_SESSION['warehouse_receipt_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['warehouse_receipt_error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($_SESSION['warehouse_receipt_error']) ?>
            </div>
            <?php unset($_SESSION['warehouse_receipt_error']); ?>
        <?php endif; ?>
    </div>

    <div class="container-fluid row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-bold">Tao phieu nhap moi</div>
                <div class="card-body">
                    <form method="POST" action="user_page.php?phieunhap" id="warehouse-create-receipt-form">
                        <input type="hidden" name="warehouse_receipt_submit" value="1">

                        <div class="mb-3">
                            <label class="form-label">Nha cung cap</label>
                            <select class="form-select" name="supplier_id" required>
                                <option value="">-- Chon nha cung cap --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= (int)$s['supplier_id'] ?>">
                                        <?= htmlspecialchars($s['supplier_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ngay nhap</label>
                            <input class="form-control" type="date" name="import_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div id="create-receipt-lines">
                            <div class="row g-2 align-items-end receipt-line-row mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">San pham</label>
                                    <select class="form-select" name="item_id[]" required>
                                        <option value="">-- Chon san pham --</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= (int)$p['item_id'] ?>">
                                                <?= htmlspecialchars($p['item_name']) ?> (Ton: <?= (int)$p['stock_quantity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">So luong</label>
                                    <input class="form-control" type="number" min="1" name="quantity[]" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Gia nhap</label>
                                    <input class="form-control" type="number" min="0" step="1000" name="import_price[]" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gia ban</label>
                                    <input class="form-control" type="number" min="0" step="1000" name="unit_price[]" required>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn btn-outline-danger w-100 remove-receipt-line" type="button">X</button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary" id="add-create-receipt-line">
                                <i class="fa-solid fa-plus"></i> Them dong san pham
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chu</label>
                            <textarea class="form-control" name="note" rows="2" placeholder="Ghi chu phieu nhap"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fa-solid fa-file-import"></i> Tao phieu nhap
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header fw-bold">Sua phieu nhap</div>
                <div class="card-body">
                    <?php if (!$editReceipt): ?>
                        <p class="mb-0">Chon mot phieu trong danh sach ben duoi de chinh sua.</p>
                    <?php else: ?>
                        <form method="POST" action="user_page.php?phieunhap" id="warehouse-edit-receipt-form">
                            <input type="hidden" name="warehouse_receipt_update_submit" value="1">
                            <input type="hidden" name="receipt_id" value="<?= (int)$editReceipt['receipt_id'] ?>">

                            <div class="mb-2"><strong>Ma phieu:</strong> <?= htmlspecialchars($editReceipt['receipt_code']) ?></div>

                            <div class="mb-3">
                                <label class="form-label">Nha cung cap</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">-- Chon nha cung cap --</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= (int)$s['supplier_id'] ?>" <?= ((int)$s['supplier_id'] === (int)$editReceipt['supplier_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['supplier_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ngay nhap</label>
                                <input class="form-control" type="date" name="import_date" value="<?= htmlspecialchars($editReceipt['import_date']) ?>" required>
                            </div>

                            <div id="edit-receipt-lines">
                                <?php foreach (($editReceipt['items'] ?? []) as $line): ?>
                                    <div class="row g-2 align-items-end receipt-line-row mb-2">
                                        <div class="col-md-4">
                                            <label class="form-label">San pham</label>
                                            <select class="form-select" name="item_id[]" required>
                                                <option value="">-- Chon san pham --</option>
                                                <?php foreach ($products as $p): ?>
                                                    <option value="<?= (int)$p['item_id'] ?>" <?= ((int)$p['item_id'] === (int)$line['item_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($p['item_name']) ?> (Ton: <?= (int)$p['stock_quantity'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">So luong</label>
                                            <input class="form-control" type="number" min="1" name="quantity[]" value="<?= (int)$line['quantity'] ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Gia nhap</label>
                                            <input class="form-control" type="number" min="0" step="1000" name="import_price[]" value="<?= (float)$line['import_price'] ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Gia ban</label>
                                            <input class="form-control" type="number" min="0" step="1000" name="unit_price[]" value="<?= (float)($line['unit_price'] ?? 0) ?>" required>
                                        </div>
                                        <div class="col-md-1">
                                            <button class="btn btn-outline-danger w-100 remove-receipt-line" type="button">X</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary" id="add-edit-receipt-line">
                                    <i class="fa-solid fa-plus"></i> Them dong san pham
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chu</label>
                                <textarea class="form-control" name="note" rows="2" placeholder="Ghi chu phieu nhap"><?= htmlspecialchars((string)($editReceipt['note'] ?? '')) ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning fw-bold">
                                <i class="fa-solid fa-pen"></i> Cap nhat phieu nhap
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-bold">Lich su phieu nhap gan day</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0 text-center">
                        <thead>
                            <tr>
                                <th>Ma phieu</th>
                                <th>Ngay nhap</th>
                                <th>Nha cung cap</th>
                                <th>Tong gia tri</th>
                                <th>Nguoi tao</th>
                                <th>Thao tac</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receipts as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['receipt_code']) ?></td>
                                    <td><?= htmlspecialchars($r['import_date']) ?></td>
                                    <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                                    <td><?= number_format((float)$r['total_value'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($r['created_by_name']) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="user_page.php?phieunhap&edit_receipt_id=<?= (int)$r['receipt_id'] ?>">
                                            Sua
                                        </a>
                                        <form method="POST" action="user_page.php?phieunhap" class="d-inline" onsubmit="return confirm('Ban co chac muon xoa phieu nhap nay?');">
                                            <input type="hidden" name="warehouse_receipt_delete_submit" value="1">
                                            <input type="hidden" name="receipt_id" value="<?= (int)$r['receipt_id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Xoa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="receipt-line-template">
    <div class="row g-2 align-items-end receipt-line-row mb-2">
        <div class="col-md-4">
            <label class="form-label">San pham</label>
            <select class="form-select" name="item_id[]" required>
                <option value="">-- Chon san pham --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['item_id'] ?>">
                        <?= htmlspecialchars($p['item_name']) ?> (Ton: <?= (int)$p['stock_quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">So luong</label>
            <input class="form-control" type="number" min="1" name="quantity[]" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Gia nhap</label>
            <input class="form-control" type="number" min="0" step="1000" name="import_price[]" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Gia ban</label>
            <input class="form-control" type="number" min="0" step="1000" name="unit_price[]" required>
        </div>
        <div class="col-md-1">
            <button class="btn btn-outline-danger w-100 remove-receipt-line" type="button">X</button>
        </div>
    </div>
</template>

<script>
    (function () {
        const template = document.getElementById('receipt-line-template');
        const addCreateBtn = document.getElementById('add-create-receipt-line');
        const addEditBtn = document.getElementById('add-edit-receipt-line');
        const createContainer = document.getElementById('create-receipt-lines');
        const editContainer = document.getElementById('edit-receipt-lines');

        function addLine(container) {
            if (!container || !template) return;
            const node = template.content.cloneNode(true);
            container.appendChild(node);
        }

        function bindRemove(container) {
            if (!container) return;
            container.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                if (!target.classList.contains('remove-receipt-line')) return;

                const row = target.closest('.receipt-line-row');
                if (!row) return;
                const rows = container.querySelectorAll('.receipt-line-row');
                if (rows.length <= 1) {
                    return;
                }
                row.remove();
            });
        }

        if (addCreateBtn) {
            addCreateBtn.addEventListener('click', function () {
                addLine(createContainer);
            });
        }

        if (addEditBtn) {
            addEditBtn.addEventListener('click', function () {
                addLine(editContainer);
            });
        }

        bindRemove(createContainer);
        bindRemove(editContainer);
    })();
</script>
