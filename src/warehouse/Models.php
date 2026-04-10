<?php
/**
 * Warehouse module models
 */

namespace Warehouse\Models;

class Item {
    public int $item_id;
    public string $item_name;
    public ?int $category_id;
    public ?string $category_name;
    public ?string $description;
    public float $unit_price;
    public float $purchase_price;
    public int $stock_quantity;
    public string $item_status;

    public function __construct(array $data = []) {
        $this->item_id = (int)($data['item_id'] ?? 0);
        $this->item_name = (string)($data['item_name'] ?? '');
        $this->category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $this->category_name = $data['category_name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->unit_price = (float)($data['unit_price'] ?? 0);
        $this->purchase_price = (float)($data['purchase_price'] ?? 0);
        $this->stock_quantity = (int)($data['stock_quantity'] ?? 0);
        $this->item_status = (string)($data['item_status'] ?? 'active');
    }

    public function toArray(): array {
        return [
            'item_id' => $this->item_id,
            'item_name' => $this->item_name,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'description' => $this->description,
            'unit_price' => $this->unit_price,
            'purchase_price' => $this->purchase_price,
            'stock_quantity' => $this->stock_quantity,
            'item_status' => $this->item_status
        ];
    }
}

class Supplier {
    public int $supplier_id;
    public string $supplier_code;
    public string $supplier_name;
    public ?string $contact_name;
    public ?string $phone_number;
    public ?string $email;
    public ?string $address;
    public string $status;

    public function __construct(array $data = []) {
        $this->supplier_id = (int)($data['supplier_id'] ?? 0);
        $this->supplier_code = (string)($data['supplier_code'] ?? '');
        $this->supplier_name = (string)($data['supplier_name'] ?? '');
        $this->contact_name = $data['contact_name'] ?? null;
        $this->phone_number = $data['phone_number'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->status = (string)($data['status'] ?? 'active');
    }

    public function toArray(): array {
        return [
            'supplier_id' => $this->supplier_id,
            'supplier_code' => $this->supplier_code,
            'supplier_name' => $this->supplier_name,
            'contact_name' => $this->contact_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status
        ];
    }
}

class InventoryReceipt {
    public int $receipt_id;
    public string $receipt_code;
    public int $supplier_id;
    public string $import_date;
    public float $total_value;
    public ?string $note;
    public string $status;
    public int $created_by;

    public function __construct(array $data = []) {
        $this->receipt_id = (int)($data['receipt_id'] ?? 0);
        $this->receipt_code = (string)($data['receipt_code'] ?? '');
        $this->supplier_id = (int)($data['supplier_id'] ?? 0);
        $this->import_date = (string)($data['import_date'] ?? date('Y-m-d'));
        $this->total_value = (float)($data['total_value'] ?? 0);
        $this->note = $data['note'] ?? null;
        $this->status = (string)($data['status'] ?? 'completed');
        $this->created_by = (int)($data['created_by'] ?? 0);
    }
}

class InventoryReceiptItem {
    public int $receipt_item_id;
    public int $receipt_id;
    public int $item_id;
    public int $quantity;
    public float $import_price;
    public float $line_total;

    public function __construct(array $data = []) {
        $this->receipt_item_id = (int)($data['receipt_item_id'] ?? 0);
        $this->receipt_id = (int)($data['receipt_id'] ?? 0);
        $this->item_id = (int)($data['item_id'] ?? 0);
        $this->quantity = (int)($data['quantity'] ?? 0);
        $this->import_price = (float)($data['import_price'] ?? 0);
        $this->line_total = (float)($data['line_total'] ?? 0);
    }
}

class StockMovement {
    public int $movement_id;
    public int $item_id;
    public string $movement_type;
    public int $quantity_change;
    public int $stock_before;
    public int $stock_after;
    public float $unit_cost;
    public ?string $reference_type;
    public ?int $reference_id;
    public ?string $note;
    public int $created_by;

    public function __construct(array $data = []) {
        $this->movement_id = (int)($data['movement_id'] ?? 0);
        $this->item_id = (int)($data['item_id'] ?? 0);
        $this->movement_type = (string)($data['movement_type'] ?? 'adjustment');
        $this->quantity_change = (int)($data['quantity_change'] ?? 0);
        $this->stock_before = (int)($data['stock_before'] ?? 0);
        $this->stock_after = (int)($data['stock_after'] ?? 0);
        $this->unit_cost = (float)($data['unit_cost'] ?? 0);
        $this->reference_type = $data['reference_type'] ?? null;
        $this->reference_id = isset($data['reference_id']) ? (int)$data['reference_id'] : null;
        $this->note = $data['note'] ?? null;
        $this->created_by = (int)($data['created_by'] ?? 0);
    }
}
