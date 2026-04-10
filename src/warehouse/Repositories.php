<?php
/**
 * Warehouse repositories
 */

namespace Warehouse\Repositories;

use Warehouse\Models\{Item, Supplier, InventoryReceipt, InventoryReceiptItem, StockMovement};

class ItemRepository {
    private \mysqli $conn;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
    }

    public function getAll(?string $keyword = null): array {
        $sql = "SELECT i.item_id, i.item_name, i.category_id, c.category_name, i.description, i.unit_price, i.purchase_price, i.stock_quantity, i.item_status
                FROM items i
                LEFT JOIN category c ON c.category_id = i.category_id
                WHERE i.item_status = 'active'";

        if ($keyword !== null && $keyword !== '') {
            $kw = mysqli_real_escape_string($this->conn, $keyword);
            $sql .= " AND (i.item_name LIKE '%{$kw}%' OR i.description LIKE '%{$kw}%')";
        }

        $sql .= " ORDER BY i.item_id DESC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = (new Item($row))->toArray();
        }
        return $rows;
    }

    public function getById(int $item_id): ?Item {
        $sql = "SELECT i.item_id, i.item_name, i.category_id, c.category_name, i.description, i.unit_price, i.purchase_price, i.stock_quantity, i.item_status
                FROM items i
                LEFT JOIN category c ON c.category_id = i.category_id
                WHERE i.item_id = {$item_id} LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        if (!$result || mysqli_num_rows($result) === 0) {
            return null;
        }
        return new Item(mysqli_fetch_assoc($result));
    }

    public function create(Item $item): int {
        $item_name = mysqli_real_escape_string($this->conn, $item->item_name);
        $description = mysqli_real_escape_string($this->conn, (string)$item->description);
        $category_id = $item->category_id === null ? 'NULL' : (string)$item->category_id;
        $unit_price = (float)$item->unit_price;
        $purchase_price = (float)$item->purchase_price;
        $stock_quantity = (int)$item->stock_quantity;

        $sql = "INSERT INTO items (item_name, category_id, description, unit_price, purchase_price, stock_quantity, item_status)
                VALUES ('{$item_name}', {$category_id}, '{$description}', {$unit_price}, {$purchase_price}, {$stock_quantity}, 'active')";

        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function update(int $item_id, Item $item): bool {
        $item_name = mysqli_real_escape_string($this->conn, $item->item_name);
        $description = mysqli_real_escape_string($this->conn, (string)$item->description);
        $category_id = $item->category_id === null ? 'NULL' : (string)$item->category_id;
        $unit_price = (float)$item->unit_price;
        $purchase_price = (float)$item->purchase_price;

        $sql = "UPDATE items
                SET item_name = '{$item_name}',
                    category_id = {$category_id},
                    description = '{$description}',
                    unit_price = {$unit_price},
                    purchase_price = {$purchase_price}
                WHERE item_id = {$item_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function softDelete(int $item_id): bool {
        $sql = "UPDATE items SET item_status = 'inactive' WHERE item_id = {$item_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function updateStock(int $item_id, int $new_stock_quantity): bool {
        $sql = "UPDATE items SET stock_quantity = {$new_stock_quantity} WHERE item_id = {$item_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function updatePurchasePrice(int $item_id, float $purchase_price): bool {
        $sql = "UPDATE items SET purchase_price = {$purchase_price} WHERE item_id = {$item_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function updateUnitPrice(int $item_id, float $unit_price): bool {
        $sql = "UPDATE items SET unit_price = {$unit_price} WHERE item_id = {$item_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function getCurrentStock(int $item_id): int {
        $sql = "SELECT stock_quantity FROM items WHERE item_id = {$item_id} LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        if (!$result || mysqli_num_rows($result) === 0) {
            return 0;
        }
        $row = mysqli_fetch_assoc($result);
        return (int)$row['stock_quantity'];
    }
}

class SupplierRepository {
    private \mysqli $conn;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
    }

    public function getAll(?string $keyword = null): array {
        $sql = "SELECT * FROM suppliers WHERE status = 'active'";

        if ($keyword !== null && $keyword !== '') {
            $kw = mysqli_real_escape_string($this->conn, $keyword);
            $sql .= " AND (
                supplier_code LIKE '%{$kw}%'
                OR supplier_name LIKE '%{$kw}%'
                OR contact_name LIKE '%{$kw}%'
                OR phone_number LIKE '%{$kw}%'
            )";
        }

        $sql .= " ORDER BY supplier_id DESC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = (new Supplier($row))->toArray();
        }
        return $rows;
    }

    public function getById(int $supplier_id): ?Supplier {
        $sql = "SELECT * FROM suppliers WHERE supplier_id = {$supplier_id} LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        if (!$result || mysqli_num_rows($result) === 0) {
            return null;
        }
        return new Supplier(mysqli_fetch_assoc($result));
    }

    public function create(Supplier $supplier): int {
        $supplier_code = mysqli_real_escape_string($this->conn, $supplier->supplier_code);
        $supplier_name = mysqli_real_escape_string($this->conn, $supplier->supplier_name);
        $contact_name = mysqli_real_escape_string($this->conn, (string)$supplier->contact_name);
        $phone_number = mysqli_real_escape_string($this->conn, (string)$supplier->phone_number);
        $email = mysqli_real_escape_string($this->conn, (string)$supplier->email);
        $address = mysqli_real_escape_string($this->conn, (string)$supplier->address);

        $sql = "INSERT INTO suppliers (supplier_code, supplier_name, contact_name, phone_number, email, address, status)
                VALUES ('{$supplier_code}', '{$supplier_name}', '{$contact_name}', '{$phone_number}', '{$email}', '{$address}', 'active')";

        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function update(int $supplier_id, Supplier $supplier): bool {
        $supplier_code = mysqli_real_escape_string($this->conn, $supplier->supplier_code);
        $supplier_name = mysqli_real_escape_string($this->conn, $supplier->supplier_name);
        $contact_name = mysqli_real_escape_string($this->conn, (string)$supplier->contact_name);
        $phone_number = mysqli_real_escape_string($this->conn, (string)$supplier->phone_number);
        $email = mysqli_real_escape_string($this->conn, (string)$supplier->email);
        $address = mysqli_real_escape_string($this->conn, (string)$supplier->address);

        $sql = "UPDATE suppliers
                SET supplier_code = '{$supplier_code}',
                    supplier_name = '{$supplier_name}',
                    contact_name = '{$contact_name}',
                    phone_number = '{$phone_number}',
                    email = '{$email}',
                    address = '{$address}'
                WHERE supplier_id = {$supplier_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function softDelete(int $supplier_id): bool {
        $sql = "UPDATE suppliers SET status = 'inactive' WHERE supplier_id = {$supplier_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }
}

class InventoryReceiptRepository {
    private \mysqli $conn;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
    }

    public function createReceipt(InventoryReceipt $receipt): int {
        $receipt_code = mysqli_real_escape_string($this->conn, $receipt->receipt_code);
        $import_date = mysqli_real_escape_string($this->conn, $receipt->import_date);
        $note = mysqli_real_escape_string($this->conn, (string)$receipt->note);
        $sql = "INSERT INTO inventory_receipts (receipt_code, supplier_id, import_date, total_value, note, status, created_by)
                VALUES ('{$receipt_code}', {$receipt->supplier_id}, '{$import_date}', {$receipt->total_value}, '{$note}', '{$receipt->status}', {$receipt->created_by})";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function createReceiptItem(InventoryReceiptItem $item): int {
        $sql = "INSERT INTO inventory_receipt_items (receipt_id, item_id, quantity, import_price, line_total)
                VALUES ({$item->receipt_id}, {$item->item_id}, {$item->quantity}, {$item->import_price}, {$item->line_total})";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function updateReceiptHeader(int $receipt_id, int $supplier_id, string $import_date, float $total_value, ?string $note): bool {
        $safeDate = mysqli_real_escape_string($this->conn, $import_date);
        $safeNote = $note === null ? 'NULL' : "'" . mysqli_real_escape_string($this->conn, $note) . "'";
        $sql = "UPDATE inventory_receipts
                SET supplier_id = {$supplier_id},
                    import_date = '{$safeDate}',
                    total_value = {$total_value},
                    note = {$safeNote}
                WHERE receipt_id = {$receipt_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function deleteReceiptItems(int $receipt_id): bool {
        $sql = "DELETE FROM inventory_receipt_items WHERE receipt_id = {$receipt_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function deleteReceipt(int $receipt_id): bool {
        $sql = "DELETE FROM inventory_receipts WHERE receipt_id = {$receipt_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function getReceipts(?int $year = null, ?int $month = null): array {
        $sql = "SELECT r.receipt_id, r.receipt_code, r.supplier_id, s.supplier_name, r.import_date, r.total_value, r.note, r.status, r.created_by, a.full_name AS created_by_name
                FROM inventory_receipts r
                INNER JOIN suppliers s ON s.supplier_id = r.supplier_id
                INNER JOIN accounts a ON a.account_id = r.created_by
                WHERE 1=1";

        if ($year !== null) {
            $sql .= " AND YEAR(r.import_date) = {$year}";
        }
        if ($month !== null) {
            $sql .= " AND MONTH(r.import_date) = {$month}";
        }

        $sql .= " ORDER BY r.receipt_id DESC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function getReceiptById(int $receipt_id): ?array {
        $headerSql = "SELECT r.receipt_id, r.receipt_code, r.supplier_id, s.supplier_name, r.import_date, r.total_value, r.note, r.status, r.created_by, a.full_name AS created_by_name
                      FROM inventory_receipts r
                      INNER JOIN suppliers s ON s.supplier_id = r.supplier_id
                      INNER JOIN accounts a ON a.account_id = r.created_by
                      WHERE r.receipt_id = {$receipt_id}
                      LIMIT 1";
        $headerRs = mysqli_query($this->conn, $headerSql);
        if (!$headerRs || mysqli_num_rows($headerRs) === 0) {
            return null;
        }

        $header = mysqli_fetch_assoc($headerRs);

        $detailSql = "SELECT d.receipt_item_id, d.item_id, i.item_name, d.quantity, d.import_price, d.line_total
                      FROM inventory_receipt_items d
                      INNER JOIN items i ON i.item_id = d.item_id
                      WHERE d.receipt_id = {$receipt_id}
                      ORDER BY d.receipt_item_id ASC";
        $detailRs = mysqli_query($this->conn, $detailSql);

        $details = [];
        while ($row = mysqli_fetch_assoc($detailRs)) {
            $details[] = $row;
        }

        $header['items'] = $details;
        return $header;
    }
}

class InventoryExportRepository {
    private \mysqli $conn;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
    }

    public function createExport(array $data): int {
        $export_code = mysqli_real_escape_string($this->conn, $data['export_code']);
        $export_date = mysqli_real_escape_string($this->conn, $data['export_date']);
        $note = isset($data['note']) ? "'" . mysqli_real_escape_string($this->conn, (string)$data['note']) . "'" : 'NULL';
        $sql = "INSERT INTO inventory_exports (export_code, export_date, total_value, note, created_by)
                VALUES ('{$export_code}', '{$export_date}', {$data['total_value']}, {$note}, {$data['created_by']})";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function createExportItem(int $export_id, int $item_id, int $quantity, float $unit_price, float $line_total): int {
        $sql = "INSERT INTO inventory_export_items (export_id, item_id, quantity, unit_price, line_total)
                VALUES ({$export_id}, {$item_id}, {$quantity}, {$unit_price}, {$line_total})";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function updateExportHeader(int $export_id, string $export_date, float $total_value, ?string $note): bool {
        $safeDate = mysqli_real_escape_string($this->conn, $export_date);
        $safeNote = $note === null ? 'NULL' : "'" . mysqli_real_escape_string($this->conn, $note) . "'";
        $sql = "UPDATE inventory_exports
                SET export_date = '{$safeDate}',
                    total_value = {$total_value},
                    note = {$safeNote}
                WHERE export_id = {$export_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function deleteExportItems(int $export_id): bool {
        $sql = "DELETE FROM inventory_export_items WHERE export_id = {$export_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function deleteExport(int $export_id): bool {
        $sql = "DELETE FROM inventory_exports WHERE export_id = {$export_id}";
        return (bool)mysqli_query($this->conn, $sql);
    }

    public function getExports(?int $year = null, ?int $month = null): array {
        $sql = "SELECT e.export_id, e.export_code, e.export_date, e.total_value, e.note, e.created_by, a.full_name AS created_by_name
                FROM inventory_exports e
                INNER JOIN accounts a ON a.account_id = e.created_by
                WHERE 1=1";

        if ($year !== null) {
            $sql .= " AND YEAR(e.export_date) = {$year}";
        }
        if ($month !== null) {
            $sql .= " AND MONTH(e.export_date) = {$month}";
        }

        $sql .= " ORDER BY e.export_id DESC";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function getExportById(int $export_id): ?array {
        $headerSql = "SELECT e.export_id, e.export_code, e.export_date, e.total_value, e.note, e.created_by, a.full_name AS created_by_name
                      FROM inventory_exports e
                      INNER JOIN accounts a ON a.account_id = e.created_by
                      WHERE e.export_id = {$export_id}
                      LIMIT 1";
        $headerRs = mysqli_query($this->conn, $headerSql);
        if (!$headerRs || mysqli_num_rows($headerRs) === 0) {
            return null;
        }

        $header = mysqli_fetch_assoc($headerRs);
        $detailSql = "SELECT d.export_item_id, d.item_id, i.item_name, d.quantity, d.unit_price, d.line_total
                      FROM inventory_export_items d
                      INNER JOIN items i ON i.item_id = d.item_id
                      WHERE d.export_id = {$export_id}
                      ORDER BY d.export_item_id ASC";
        $detailRs = mysqli_query($this->conn, $detailSql);

        $details = [];
        while ($row = mysqli_fetch_assoc($detailRs)) {
            $details[] = $row;
        }

        $header['items'] = $details;
        return $header;
    }
}

class StockMovementRepository {
    private \mysqli $conn;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
    }

    public function create(StockMovement $movement): int {
        $reference_type = $movement->reference_type === null ? 'NULL' : "'" . mysqli_real_escape_string($this->conn, $movement->reference_type) . "'";
        $reference_id = $movement->reference_id === null ? 'NULL' : (string)$movement->reference_id;
        $note = $movement->note === null ? 'NULL' : "'" . mysqli_real_escape_string($this->conn, $movement->note) . "'";

        $sql = "INSERT INTO inventory_stock_movements (
                    item_id, movement_type, quantity_change, stock_before, stock_after,
                    unit_cost, reference_type, reference_id, note, created_by
                ) VALUES (
                    {$movement->item_id}, '{$movement->movement_type}', {$movement->quantity_change}, {$movement->stock_before}, {$movement->stock_after},
                    {$movement->unit_cost}, {$reference_type}, {$reference_id}, {$note}, {$movement->created_by}
                )";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    public function getMovements(?int $item_id = null, ?int $year = null, ?int $month = null): array {
        $sql = "SELECT m.movement_id, m.item_id, i.item_name, m.movement_type, m.quantity_change,
                       m.stock_before, m.stock_after, m.unit_cost, m.reference_type,
                       m.reference_id, m.note, m.created_by, a.full_name AS created_by_name, m.created_at
                FROM inventory_stock_movements m
                INNER JOIN items i ON i.item_id = m.item_id
                INNER JOIN accounts a ON a.account_id = m.created_by
                WHERE 1=1";

        if ($item_id !== null) {
            $sql .= " AND m.item_id = {$item_id}";
        }
        if ($year !== null) {
            $sql .= " AND YEAR(m.created_at) = {$year}";
        }
        if ($month !== null) {
            $sql .= " AND MONTH(m.created_at) = {$month}";
        }

        $sql .= " ORDER BY m.movement_id DESC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
}

class ReportRepository {
    private \mysqli $conn;

    public function __construct(\mysqli $conn) {
        $this->conn = $conn;
    }

    public function getImportSummary(int $year, ?int $month = null): array {
        $where = "WHERE YEAR(r.import_date) = {$year}";
        if ($month !== null) {
            $where .= " AND MONTH(r.import_date) = {$month}";
        }

        $sql = "SELECT
                    COALESCE(SUM(d.quantity), 0) AS total_import_quantity,
                    COALESCE(SUM(d.line_total), 0) AS total_import_value
                FROM inventory_receipts r
                INNER JOIN inventory_receipt_items d ON d.receipt_id = r.receipt_id
                {$where}
                AND r.status = 'completed'";

        $result = mysqli_query($this->conn, $sql);
        $summary = mysqli_fetch_assoc($result) ?: [
            'total_import_quantity' => 0,
            'total_import_value' => 0
        ];

        $stockSql = "SELECT
                        COALESCE(SUM(stock_quantity), 0) AS current_stock_quantity,
                        COALESCE(SUM(stock_quantity * purchase_price), 0) AS current_stock_value
                     FROM items
                     WHERE item_status = 'active'";
        $stockRs = mysqli_query($this->conn, $stockSql);
        $stock = mysqli_fetch_assoc($stockRs) ?: [
            'current_stock_quantity' => 0,
            'current_stock_value' => 0
        ];

        return [
            'year' => $year,
            'month' => $month,
            'total_import_quantity' => (int)$summary['total_import_quantity'],
            'total_import_value' => (float)$summary['total_import_value'],
            'current_stock_quantity' => (int)$stock['current_stock_quantity'],
            'current_stock_value' => (float)$stock['current_stock_value']
        ];
    }

    public function getImportRows(int $year, ?int $month = null): array {
        $sql = "SELECT r.receipt_code, r.import_date, s.supplier_name, i.item_name,
                       d.quantity, d.import_price, d.line_total
                FROM inventory_receipts r
                INNER JOIN suppliers s ON s.supplier_id = r.supplier_id
                INNER JOIN inventory_receipt_items d ON d.receipt_id = r.receipt_id
                INNER JOIN items i ON i.item_id = d.item_id
                WHERE YEAR(r.import_date) = {$year}
                  AND r.status = 'completed'";

        if ($month !== null) {
            $sql .= " AND MONTH(r.import_date) = {$month}";
        }

        $sql .= " ORDER BY r.import_date DESC, r.receipt_id DESC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getInStockProducts(?string $keyword = null): array {
        $sql = "SELECT i.item_id, i.item_name, c.category_name, i.purchase_price, i.unit_price, i.stock_quantity,
                       (i.stock_quantity * i.purchase_price) AS stock_value
                FROM items i
                LEFT JOIN category c ON c.category_id = i.category_id
                WHERE i.item_status = 'active' AND i.stock_quantity > 0";

        if ($keyword !== null && $keyword !== '') {
            $kw = mysqli_real_escape_string($this->conn, $keyword);
            $sql .= " AND i.item_name LIKE '%{$kw}%'";
        }

        $sql .= " ORDER BY i.stock_quantity DESC, i.item_id DESC";
        $result = mysqli_query($this->conn, $sql);

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
