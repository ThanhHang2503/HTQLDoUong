<?php
/**
 * Warehouse services
 */

namespace Warehouse\Services;

use Warehouse\Models\{Item, Supplier, InventoryReceipt, InventoryReceiptItem, StockMovement};
use Warehouse\Repositories\{ItemRepository, SupplierRepository, InventoryReceiptRepository, InventoryExportRepository, StockMovementRepository, ReportRepository};

class ProductService {
    private ItemRepository $itemRepo;

    public function __construct(\mysqli $conn) {
        $this->itemRepo = new ItemRepository($conn);
    }

    public function getProducts(?string $keyword = null): array {
        return $this->itemRepo->getAll($keyword);
    }

    public function getProduct(int $item_id): ?array {
        $item = $this->itemRepo->getById($item_id);
        return $item ? $item->toArray() : null;
    }

    public function createProduct(array $data): array {
        if (empty($data['item_name']) || !isset($data['unit_price'])) {
            return ['success' => false, 'message' => 'Thiếu item_name hoặc unit_price'];
        }

        $item = new Item([
            'item_name' => $data['item_name'],
            'category_id' => $data['category_id'] ?? null,
            'description' => $data['description'] ?? null,
            'unit_price' => (float)$data['unit_price'],
            'purchase_price' => (float)($data['purchase_price'] ?? 0),
            'stock_quantity' => (int)($data['stock_quantity'] ?? 0)
        ]);

        $newId = $this->itemRepo->create($item);
        return ['success' => true, 'item_id' => $newId, 'message' => 'Thêm sản phẩm thành công'];
    }

    public function updateProduct(int $item_id, array $data): array {
        $existing = $this->itemRepo->getById($item_id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }

        $item = new Item([
            'item_name' => $data['item_name'] ?? $existing->item_name,
            'category_id' => $data['category_id'] ?? $existing->category_id,
            'description' => $data['description'] ?? $existing->description,
            'unit_price' => isset($data['unit_price']) ? (float)$data['unit_price'] : $existing->unit_price,
            'purchase_price' => isset($data['purchase_price']) ? (float)$data['purchase_price'] : $existing->purchase_price
        ]);

        $ok = $this->itemRepo->update($item_id, $item);
        return ['success' => $ok, 'message' => $ok ? 'Cập nhật sản phẩm thành công' : 'Cập nhật sản phẩm thất bại'];
    }

    public function deleteProduct(int $item_id): array {
        $ok = $this->itemRepo->softDelete($item_id);
        return ['success' => $ok, 'message' => $ok ? 'Xóa sản phẩm thành công' : 'Xóa sản phẩm thất bại'];
    }
}

class SupplierService {
    private SupplierRepository $supplierRepo;

    public function __construct(\mysqli $conn) {
        $this->supplierRepo = new SupplierRepository($conn);
    }

    public function getSuppliers(?string $keyword = null): array {
        return $this->supplierRepo->getAll($keyword);
    }

    public function createSupplier(array $data): array {
        if (empty($data['supplier_code']) || empty($data['supplier_name'])) {
            return ['success' => false, 'message' => 'Thiếu supplier_code hoặc supplier_name'];
        }

        $supplier = new Supplier([
            'supplier_code' => $data['supplier_code'],
            'supplier_name' => $data['supplier_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null
        ]);

        $newId = $this->supplierRepo->create($supplier);
        return ['success' => true, 'supplier_id' => $newId, 'message' => 'Thêm nhà cung cấp thành công'];
    }

    public function updateSupplier(int $supplier_id, array $data): array {
        $existing = $this->supplierRepo->getById($supplier_id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Nhà cung cấp không tồn tại'];
        }

        $supplier = new Supplier([
            'supplier_code' => $data['supplier_code'] ?? $existing->supplier_code,
            'supplier_name' => $data['supplier_name'] ?? $existing->supplier_name,
            'contact_name' => $data['contact_name'] ?? $existing->contact_name,
            'phone_number' => $data['phone_number'] ?? $existing->phone_number,
            'email' => $data['email'] ?? $existing->email,
            'address' => $data['address'] ?? $existing->address
        ]);

        $ok = $this->supplierRepo->update($supplier_id, $supplier);
        return ['success' => $ok, 'message' => $ok ? 'Cập nhật nhà cung cấp thành công' : 'Cập nhật nhà cung cấp thất bại'];
    }

    public function deleteSupplier(int $supplier_id): array {
        $ok = $this->supplierRepo->softDelete($supplier_id);
        return ['success' => $ok, 'message' => $ok ? 'Xóa nhà cung cấp thành công' : 'Xóa nhà cung cấp thất bại'];
    }
}

class InventoryService {
    private \mysqli $conn;
    private ItemRepository $itemRepo;
    private StockMovementRepository $movementRepo;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
        $this->itemRepo = new ItemRepository($conn);
        $this->movementRepo = new StockMovementRepository($conn);
    }

    public function adjustStock(int $item_id, int $new_stock_quantity, int $created_by, ?string $note = null): array {
        $item = $this->itemRepo->getById($item_id);
        if (!$item) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }

        if ($new_stock_quantity < 0) {
            return ['success' => false, 'message' => 'Số lượng tồn không hợp lệ'];
        }

        $before = (int)$item->stock_quantity;
        $delta = $new_stock_quantity - $before;

        mysqli_begin_transaction($this->conn);
        try {
            $ok = $this->itemRepo->updateStock($item_id, $new_stock_quantity);
            if (!$ok) {
                throw new \Exception('Không thể cập nhật tồn kho');
            }

            $movement = new StockMovement([
                'item_id' => $item_id,
                'movement_type' => 'adjustment',
                'quantity_change' => $delta,
                'stock_before' => $before,
                'stock_after' => $new_stock_quantity,
                'unit_cost' => (float)$item->purchase_price,
                'reference_type' => 'manual',
                'reference_id' => null,
                'note' => $note,
                'created_by' => $created_by
            ]);
            $this->movementRepo->create($movement);

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Cập nhật tồn kho thành công', 'stock_before' => $before, 'stock_after' => $new_stock_quantity];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Cập nhật tồn kho thất bại', 'error' => $e->getMessage()];
        }
    }

    public function getMovements(?int $item_id = null, ?int $year = null, ?int $month = null): array {
        return $this->movementRepo->getMovements($item_id, $year, $month);
    }
}

class GoodsReceiptService {
    private \mysqli $conn;
    private ItemRepository $itemRepo;
    private SupplierRepository $supplierRepo;
    private InventoryReceiptRepository $receiptRepo;
    private StockMovementRepository $movementRepo;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
        $this->itemRepo = new ItemRepository($conn);
        $this->supplierRepo = new SupplierRepository($conn);
        $this->receiptRepo = new InventoryReceiptRepository($conn);
        $this->movementRepo = new StockMovementRepository($conn);
    }

    public function createReceipt(array $data, int $created_by): array {
        if (empty($data['supplier_id']) || empty($data['items']) || !is_array($data['items'])) {
            return ['success' => false, 'message' => 'Thiếu supplier_id hoặc danh sách items'];
        }

        $supplier = $this->supplierRepo->getById((int)$data['supplier_id']);
        if (!$supplier || $supplier->status !== 'active') {
            return ['success' => false, 'message' => 'Nhà cung cấp không hợp lệ'];
        }

        $importDate = $data['import_date'] ?? date('Y-m-d');
        $receiptCode = $data['receipt_code'] ?? ('PN-' . date('Ymd-His'));
        $note = $data['note'] ?? null;

        mysqli_begin_transaction($this->conn);
        try {
            $totalValue = 0.0;
            $normalizedItems = [];

            foreach ($data['items'] as $line) {
                $item_id = (int)($line['item_id'] ?? 0);
                $quantity = (int)($line['quantity'] ?? 0);
                $import_price = (float)($line['import_price'] ?? 0);
                $unit_price = (float)($line['unit_price'] ?? 0);

                if ($item_id <= 0 || $quantity <= 0 || $import_price < 0 || $unit_price < 0) {
                    throw new \Exception('Dữ liệu dòng phiếu nhập không hợp lệ');
                }

                $item = $this->itemRepo->getById($item_id);
                if (!$item || $item->item_status !== 'active') {
                    throw new \Exception('Sản phẩm không tồn tại hoặc đã ngưng hoạt động: ' . $item_id);
                }

                $lineTotal = $quantity * $import_price;
                $totalValue += $lineTotal;

                $normalizedItems[] = [
                    'item' => $item,
                    'item_id' => $item_id,
                    'quantity' => $quantity,
                    'import_price' => $import_price,
                    'unit_price' => $unit_price,
                    'line_total' => $lineTotal
                ];
            }

            $receipt = new InventoryReceipt([
                'receipt_code' => $receiptCode,
                'supplier_id' => (int)$data['supplier_id'],
                'import_date' => $importDate,
                'total_value' => $totalValue,
                'note' => $note,
                'status' => 'completed',
                'created_by' => $created_by
            ]);

            $receiptId = $this->receiptRepo->createReceipt($receipt);

            foreach ($normalizedItems as $line) {
                $receiptItem = new InventoryReceiptItem([
                    'receipt_id' => $receiptId,
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'import_price' => $line['import_price'],
                    'line_total' => $line['line_total']
                ]);
                $this->receiptRepo->createReceiptItem($receiptItem);

                $stockBefore = (int)$line['item']->stock_quantity;
                $stockAfter = $stockBefore + $line['quantity'];

                if (!$this->itemRepo->updateStock($line['item_id'], $stockAfter)) {
                    throw new \Exception('Không thể cập nhật tồn kho cho sản phẩm ' . $line['item_id']);
                }
                if (!$this->itemRepo->updatePurchasePrice($line['item_id'], $line['import_price'])) {
                    throw new \Exception('Không thể cập nhật giá nhập cho sản phẩm ' . $line['item_id']);
                }
                if (!$this->itemRepo->updateUnitPrice($line['item_id'], $line['unit_price'])) {
                    throw new \Exception('Không thể cập nhật giá bán cho sản phẩm ' . $line['item_id']);
                }

                $movement = new StockMovement([
                    'item_id' => $line['item_id'],
                    'movement_type' => 'import',
                    'quantity_change' => $line['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $line['import_price'],
                    'reference_type' => 'receipt',
                    'reference_id' => $receiptId,
                    'note' => $note,
                    'created_by' => $created_by
                ]);
                $this->movementRepo->create($movement);
            }

            mysqli_commit($this->conn);
            return [
                'success' => true,
                'message' => 'Lập phiếu nhập thành công',
                'receipt_id' => $receiptId,
                'receipt_code' => $receiptCode,
                'total_value' => $totalValue
            ];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return [
                'success' => false,
                'message' => 'Lập phiếu nhập thất bại',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getReceipts(?int $year = null, ?int $month = null): array {
        return $this->receiptRepo->getReceipts($year, $month);
    }

    public function getReceiptById(int $receipt_id): ?array {
        return $this->receiptRepo->getReceiptById($receipt_id);
    }

    public function updateReceipt(int $receipt_id, array $data, int $updated_by): array {
        $existing = $this->receiptRepo->getReceiptById($receipt_id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Phiếu nhập không tồn tại'];
        }

        if (empty($data['supplier_id']) || empty($data['items']) || !is_array($data['items'])) {
            return ['success' => false, 'message' => 'Thiếu supplier_id hoặc danh sách items'];
        }

        $supplier = $this->supplierRepo->getById((int)$data['supplier_id']);
        if (!$supplier || $supplier->status !== 'active') {
            return ['success' => false, 'message' => 'Nhà cung cấp không hợp lệ'];
        }

        $oldQtyByItem = [];
        foreach ($existing['items'] as $line) {
            $itemId = (int)$line['item_id'];
            $oldQtyByItem[$itemId] = ($oldQtyByItem[$itemId] ?? 0) + (int)$line['quantity'];
        }

        $newQtyByItem = [];
        $normalizedItems = [];
        $totalValue = 0.0;

        foreach ($data['items'] as $line) {
            $item_id = (int)($line['item_id'] ?? 0);
            $quantity = (int)($line['quantity'] ?? 0);
            $import_price = (float)($line['import_price'] ?? 0);
            $unit_price = (float)($line['unit_price'] ?? 0);

            if ($item_id <= 0 || $quantity <= 0 || $import_price < 0 || $unit_price < 0) {
                return ['success' => false, 'message' => 'Dữ liệu dòng phiếu nhập không hợp lệ'];
            }

            $item = $this->itemRepo->getById($item_id);
            if (!$item || $item->item_status !== 'active') {
                return ['success' => false, 'message' => 'Sản phẩm không hợp lệ: ' . $item_id];
            }

            $lineTotal = $quantity * $import_price;
            $totalValue += $lineTotal;
            $newQtyByItem[$item_id] = ($newQtyByItem[$item_id] ?? 0) + $quantity;

            $normalizedItems[] = [
                'item_id' => $item_id,
                'quantity' => $quantity,
                'import_price' => $import_price,
                'unit_price' => $unit_price,
                'line_total' => $lineTotal
            ];
        }

        $allItemIds = array_unique(array_merge(array_keys($oldQtyByItem), array_keys($newQtyByItem)));
        foreach ($allItemIds as $itemId) {
            $oldQty = (int)($oldQtyByItem[$itemId] ?? 0);
            $newQty = (int)($newQtyByItem[$itemId] ?? 0);
            $delta = $newQty - $oldQty;

            if ($delta === 0) {
                continue;
            }

            $currentStock = $this->itemRepo->getCurrentStock((int)$itemId);
            $nextStock = $currentStock + $delta;
            if ($nextStock < 0) {
                return [
                    'success' => false,
                    'message' => 'Không đủ tồn kho khi sửa phiếu nhập cho sản phẩm ' . $itemId,
                    'item_id' => (int)$itemId,
                    'current_stock' => $currentStock,
                    'delta' => $delta
                ];
            }
        }

        mysqli_begin_transaction($this->conn);
        try {
            $importDate = $data['import_date'] ?? $existing['import_date'];
            $note = $data['note'] ?? ($existing['note'] ?? null);

            if (!$this->receiptRepo->updateReceiptHeader($receipt_id, (int)$data['supplier_id'], $importDate, $totalValue, $note)) {
                throw new \Exception('Không thể cập nhật phiếu nhập');
            }

            if (!$this->receiptRepo->deleteReceiptItems($receipt_id)) {
                throw new \Exception('Không thể làm mới chi tiết phiếu nhập');
            }

            foreach ($normalizedItems as $line) {
                $receiptItem = new InventoryReceiptItem([
                    'receipt_id' => $receipt_id,
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'import_price' => $line['import_price'],
                    'line_total' => $line['line_total']
                ]);
                $this->receiptRepo->createReceiptItem($receiptItem);
                $this->itemRepo->updatePurchasePrice($line['item_id'], $line['import_price']);
                $this->itemRepo->updateUnitPrice($line['item_id'], $line['unit_price']);
            }

            foreach ($allItemIds as $itemId) {
                $oldQty = (int)($oldQtyByItem[$itemId] ?? 0);
                $newQty = (int)($newQtyByItem[$itemId] ?? 0);
                $delta = $newQty - $oldQty;
                if ($delta === 0) {
                    continue;
                }

                $stockBefore = $this->itemRepo->getCurrentStock((int)$itemId);
                $stockAfter = $stockBefore + $delta;
                if ($stockAfter < 0) {
                    throw new \Exception('Tồn kho âm không hợp lệ cho sản phẩm ' . $itemId);
                }

                if (!$this->itemRepo->updateStock((int)$itemId, $stockAfter)) {
                    throw new \Exception('Không thể cập nhật tồn kho cho sản phẩm ' . $itemId);
                }

                $movement = new StockMovement([
                    'item_id' => (int)$itemId,
                    'movement_type' => 'receipt_update',
                    'quantity_change' => $delta,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => 0,
                    'reference_type' => 'receipt',
                    'reference_id' => $receipt_id,
                    'note' => 'Cap nhat phieu nhap',
                    'created_by' => $updated_by
                ]);
                $this->movementRepo->create($movement);
            }

            mysqli_commit($this->conn);
            return [
                'success' => true,
                'message' => 'Cập nhật phiếu nhập thành công',
                'receipt_id' => $receipt_id,
                'total_value' => $totalValue
            ];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Cập nhật phiếu nhập thất bại', 'error' => $e->getMessage()];
        }
    }

    public function deleteReceipt(int $receipt_id, int $deleted_by): array {
        $existing = $this->receiptRepo->getReceiptById($receipt_id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Phiếu nhập không tồn tại'];
        }

        foreach ($existing['items'] as $line) {
            $itemId = (int)$line['item_id'];
            $qty = (int)$line['quantity'];
            $currentStock = $this->itemRepo->getCurrentStock($itemId);
            if (($currentStock - $qty) < 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa phiếu nhập vì sẽ làm tồn kho âm',
                    'item_id' => $itemId,
                    'current_stock' => $currentStock,
                    'decrease' => $qty
                ];
            }
        }

        mysqli_begin_transaction($this->conn);
        try {
            foreach ($existing['items'] as $line) {
                $itemId = (int)$line['item_id'];
                $qty = (int)$line['quantity'];
                $stockBefore = $this->itemRepo->getCurrentStock($itemId);
                $stockAfter = $stockBefore - $qty;

                if (!$this->itemRepo->updateStock($itemId, $stockAfter)) {
                    throw new \Exception('Không thể cập nhật tồn kho sản phẩm ' . $itemId);
                }

                $movement = new StockMovement([
                    'item_id' => $itemId,
                    'movement_type' => 'receipt_delete',
                    'quantity_change' => -$qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => (float)($line['import_price'] ?? 0),
                    'reference_type' => 'receipt',
                    'reference_id' => $receipt_id,
                    'note' => 'Xoa phieu nhap',
                    'created_by' => $deleted_by
                ]);
                $this->movementRepo->create($movement);
            }

            if (!$this->receiptRepo->deleteReceipt($receipt_id)) {
                throw new \Exception('Không thể xóa phiếu nhập');
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Xóa phiếu nhập thành công', 'receipt_id' => $receipt_id];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Xóa phiếu nhập thất bại', 'error' => $e->getMessage()];
        }
    }
}

class WarehouseReportService {
    private ReportRepository $reportRepo;

    public function __construct(\mysqli $conn) {
        $this->reportRepo = new ReportRepository($conn);
    }

    public function getSummary(int $year, ?int $month = null): array {
        return $this->reportRepo->getImportSummary($year, $month);
    }

    public function exportCsv(int $year, ?int $month = null): string {
        $rows = $this->reportRepo->getImportRows($year, $month);

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['Ma phieu', 'Ngay nhap', 'Nha cung cap', 'San pham', 'So luong', 'Gia nhap', 'Thanh tien']);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['receipt_code'],
                $row['import_date'],
                $row['supplier_name'],
                $row['item_name'],
                $row['quantity'],
                $row['import_price'],
                $row['line_total']
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv ?: '';
    }

    public function getInStockProducts(?string $keyword = null): array {
        return $this->reportRepo->getInStockProducts($keyword);
    }
}

class GoodsExportService {
    private \mysqli $conn;
    private ItemRepository $itemRepo;
    private InventoryExportRepository $exportRepo;
    private StockMovementRepository $movementRepo;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
        $this->itemRepo = new ItemRepository($conn);
        $this->exportRepo = new InventoryExportRepository($conn);
        $this->movementRepo = new StockMovementRepository($conn);
    }

    public function createExport(array $data, int $created_by): array {
        if (empty($data['items']) || !is_array($data['items'])) {
            return ['success' => false, 'message' => 'Thiếu danh sách sản phẩm xuất'];
        }

        $exportDate = $data['export_date'] ?? date('Y-m-d');
        $exportCode = $data['export_code'] ?? ('PX-' . date('Ymd-His'));
        $note = $data['note'] ?? null;

        $normalized = [];
        $totalValue = 0.0;

        foreach ($data['items'] as $line) {
            $item_id = (int)($line['item_id'] ?? 0);
            $quantity = (int)($line['quantity'] ?? 0);

            if ($item_id <= 0 || $quantity <= 0) {
                return ['success' => false, 'message' => 'Dữ liệu dòng phiếu xuất không hợp lệ'];
            }

            $item = $this->itemRepo->getById($item_id);
            if (!$item || $item->item_status !== 'active') {
                return ['success' => false, 'message' => 'Sản phẩm không hợp lệ: ' . $item_id];
            }

            $currentStock = (int)$item->stock_quantity;
            if ($currentStock < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Không đủ tồn kho để xuất',
                    'item_id' => $item_id,
                    'current_stock' => $currentStock,
                    'request_quantity' => $quantity
                ];
            }

            $unitPrice = (float)$item->unit_price;
            $lineTotal = $unitPrice * $quantity;
            $totalValue += $lineTotal;

            $normalized[] = [
                'item' => $item,
                'item_id' => $item_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal
            ];
        }

        mysqli_begin_transaction($this->conn);
        try {
            $exportId = $this->exportRepo->createExport([
                'export_code' => $exportCode,
                'export_date' => $exportDate,
                'total_value' => $totalValue,
                'note' => $note,
                'created_by' => $created_by
            ]);

            foreach ($normalized as $line) {
                $this->exportRepo->createExportItem(
                    $exportId,
                    $line['item_id'],
                    $line['quantity'],
                    $line['unit_price'],
                    $line['line_total']
                );

                $stockBefore = (int)$line['item']->stock_quantity;
                $stockAfter = $stockBefore - $line['quantity'];
                if ($stockAfter < 0) {
                    throw new \Exception('Tồn kho âm không hợp lệ cho sản phẩm ' . $line['item_id']);
                }

                if (!$this->itemRepo->updateStock($line['item_id'], $stockAfter)) {
                    throw new \Exception('Không thể cập nhật tồn kho cho sản phẩm ' . $line['item_id']);
                }

                $movement = new StockMovement([
                    'item_id' => $line['item_id'],
                    'movement_type' => 'export',
                    'quantity_change' => -$line['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => (float)$line['item']->purchase_price,
                    'reference_type' => 'export',
                    'reference_id' => $exportId,
                    'note' => $note,
                    'created_by' => $created_by
                ]);
                $this->movementRepo->create($movement);
            }

            mysqli_commit($this->conn);
            return [
                'success' => true,
                'message' => 'Tạo phiếu xuất thành công',
                'export_id' => $exportId,
                'export_code' => $exportCode,
                'total_value' => $totalValue
            ];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Tạo phiếu xuất thất bại', 'error' => $e->getMessage()];
        }
    }

    public function getExports(?int $year = null, ?int $month = null): array {
        return $this->exportRepo->getExports($year, $month);
    }

    public function getExportById(int $export_id): ?array {
        return $this->exportRepo->getExportById($export_id);
    }

    public function updateExport(int $export_id, array $data, int $updated_by): array {
        $existing = $this->exportRepo->getExportById($export_id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Phiếu xuất không tồn tại'];
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            return ['success' => false, 'message' => 'Thiếu danh sách sản phẩm xuất'];
        }

        $exportDate = $data['export_date'] ?? ($existing['export_date'] ?? date('Y-m-d'));
        $note = $data['note'] ?? ($existing['note'] ?? null);

        $oldQtyByItem = [];
        foreach ($existing['items'] as $line) {
            $itemId = (int)$line['item_id'];
            $oldQtyByItem[$itemId] = ($oldQtyByItem[$itemId] ?? 0) + (int)$line['quantity'];
        }

        $newQtyByItem = [];
        $normalized = [];
        $totalValue = 0.0;

        foreach ($data['items'] as $line) {
            $item_id = (int)($line['item_id'] ?? 0);
            $quantity = (int)($line['quantity'] ?? 0);

            if ($item_id <= 0 || $quantity <= 0) {
                return ['success' => false, 'message' => 'Dữ liệu dòng phiếu xuất không hợp lệ'];
            }

            $item = $this->itemRepo->getById($item_id);
            if (!$item || $item->item_status !== 'active') {
                return ['success' => false, 'message' => 'Sản phẩm không hợp lệ: ' . $item_id];
            }

            $unitPrice = (float)$item->unit_price;
            $lineTotal = $unitPrice * $quantity;
            $totalValue += $lineTotal;
            $newQtyByItem[$item_id] = ($newQtyByItem[$item_id] ?? 0) + $quantity;

            $normalized[] = [
                'item_id' => $item_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'purchase_price' => (float)$item->purchase_price
            ];
        }

        $allItemIds = array_unique(array_merge(array_keys($oldQtyByItem), array_keys($newQtyByItem)));
        foreach ($allItemIds as $itemId) {
            $currentStock = $this->itemRepo->getCurrentStock((int)$itemId);
            $oldQty = (int)($oldQtyByItem[$itemId] ?? 0);
            $newQty = (int)($newQtyByItem[$itemId] ?? 0);
            $nextStock = $currentStock + $oldQty - $newQty;

            if ($nextStock < 0) {
                return [
                    'success' => false,
                    'message' => 'Không đủ tồn kho để cập nhật phiếu xuất',
                    'item_id' => (int)$itemId,
                    'current_stock' => $currentStock,
                    'old_quantity' => $oldQty,
                    'new_quantity' => $newQty
                ];
            }
        }

        mysqli_begin_transaction($this->conn);
        try {
            if (!$this->exportRepo->updateExportHeader($export_id, $exportDate, $totalValue, $note)) {
                throw new \Exception('Không thể cập nhật thông tin phiếu xuất');
            }

            if (!$this->exportRepo->deleteExportItems($export_id)) {
                throw new \Exception('Không thể làm mới chi tiết phiếu xuất');
            }

            foreach ($normalized as $line) {
                $this->exportRepo->createExportItem(
                    $export_id,
                    $line['item_id'],
                    $line['quantity'],
                    $line['unit_price'],
                    $line['line_total']
                );
            }

            foreach ($allItemIds as $itemId) {
                $oldQty = (int)($oldQtyByItem[$itemId] ?? 0);
                $newQty = (int)($newQtyByItem[$itemId] ?? 0);
                $stockCursor = $this->itemRepo->getCurrentStock((int)$itemId);

                if ($oldQty > 0) {
                    $afterRollback = $stockCursor + $oldQty;
                    $rollbackMovement = new StockMovement([
                        'item_id' => (int)$itemId,
                        'movement_type' => 'export',
                        'quantity_change' => $oldQty,
                        'stock_before' => $stockCursor,
                        'stock_after' => $afterRollback,
                        'unit_cost' => 0,
                        'reference_type' => 'export',
                        'reference_id' => $export_id,
                        'note' => 'Rollback khi cap nhat phieu xuat',
                        'created_by' => $updated_by
                    ]);
                    $this->movementRepo->create($rollbackMovement);
                    $stockCursor = $afterRollback;
                }

                if ($newQty > 0) {
                    $afterApply = $stockCursor - $newQty;
                    if ($afterApply < 0) {
                        throw new \Exception('Tồn kho âm không hợp lệ khi cập nhật phiếu xuất');
                    }

                    $applyMovement = new StockMovement([
                        'item_id' => (int)$itemId,
                        'movement_type' => 'export',
                        'quantity_change' => -$newQty,
                        'stock_before' => $stockCursor,
                        'stock_after' => $afterApply,
                        'unit_cost' => 0,
                        'reference_type' => 'export',
                        'reference_id' => $export_id,
                        'note' => 'Ap dung cap nhat phieu xuat',
                        'created_by' => $updated_by
                    ]);
                    $this->movementRepo->create($applyMovement);
                    $stockCursor = $afterApply;
                }

                if (!$this->itemRepo->updateStock((int)$itemId, $stockCursor)) {
                    throw new \Exception('Không thể cập nhật tồn kho cho sản phẩm ' . $itemId);
                }
            }

            mysqli_commit($this->conn);
            return [
                'success' => true,
                'message' => 'Cập nhật phiếu xuất thành công',
                'export_id' => $export_id,
                'total_value' => $totalValue
            ];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Cập nhật phiếu xuất thất bại', 'error' => $e->getMessage()];
        }
    }

    public function deleteExport(int $export_id, int $deleted_by): array {
        $existing = $this->exportRepo->getExportById($export_id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Phiếu xuất không tồn tại'];
        }

        $oldQtyByItem = [];
        foreach ($existing['items'] as $line) {
            $itemId = (int)$line['item_id'];
            $oldQtyByItem[$itemId] = ($oldQtyByItem[$itemId] ?? 0) + (int)$line['quantity'];
        }

        mysqli_begin_transaction($this->conn);
        try {
            foreach ($oldQtyByItem as $itemId => $qty) {
                $stockBefore = $this->itemRepo->getCurrentStock((int)$itemId);
                $stockAfter = $stockBefore + (int)$qty;

                if (!$this->itemRepo->updateStock((int)$itemId, $stockAfter)) {
                    throw new \Exception('Không thể hoàn tồn kho cho sản phẩm ' . $itemId);
                }

                $movement = new StockMovement([
                    'item_id' => (int)$itemId,
                    'movement_type' => 'export',
                    'quantity_change' => (int)$qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => 0,
                    'reference_type' => 'export',
                    'reference_id' => $export_id,
                    'note' => 'Xoa phieu xuat, hoan ton kho',
                    'created_by' => $deleted_by
                ]);
                $this->movementRepo->create($movement);
            }

            if (!$this->exportRepo->deleteExport($export_id)) {
                throw new \Exception('Không thể xóa phiếu xuất');
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Xóa phiếu xuất thành công', 'export_id' => $export_id];
        } catch (\Throwable $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Xóa phiếu xuất thất bại', 'error' => $e->getMessage()];
        }
    }
}
