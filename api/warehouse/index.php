<?php
/**
 * Warehouse API Endpoints
 * Base URL: /api/warehouse
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/warehouse/Models.php';
require_once __DIR__ . '/../../src/warehouse/Repositories.php';
require_once __DIR__ . '/../../src/warehouse/Services.php';
require_once __DIR__ . '/../../src/warehouse/Middleware.php';

use Warehouse\Middleware\WarehouseAuthMiddleware;
use Warehouse\Services\{ProductService, InventoryService, GoodsReceiptService, GoodsExportService, SupplierService, WarehouseReportService};

ini_set('display_errors', '0');
error_reporting(E_ALL);

WarehouseAuthMiddleware::requireWarehouseAccess($conn);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', trim((string)$path, '/'))));

$warehouseIndex = array_search('warehouse', $segments, true);
$endpointSegments = $warehouseIndex !== false ? array_slice($segments, $warehouseIndex + 1) : [];
$endpoint = implode('/', $endpointSegments);

$rawBody = file_get_contents('php://input');
$decodedBody = json_decode($rawBody, true);
$input = is_array($decodedBody) ? $decodedBody : $_POST;
if ($method === 'GET' && empty($input)) {
    $input = $_GET;
}

$productService = new ProductService($conn);
$inventoryService = new InventoryService($conn);
$receiptService = new GoodsReceiptService($conn);
$exportService = new GoodsExportService($conn);
$supplierService = new SupplierService($conn);
$reportService = new WarehouseReportService($conn);

try {
    switch (true) {
        // ==================== PRODUCT MANAGEMENT ====================
        case $method === 'GET' && preg_match('/^products$/', $endpoint):
            $keyword = $input['keyword'] ?? null;
            $data = $productService->getProducts($keyword);
            exit(json_encode(['success' => true, 'data' => $data, 'count' => count($data)]));

        case $method === 'GET' && preg_match('/^products\/(\d+)$/', $endpoint, $m):
            $item = $productService->getProduct((int)$m[1]);
            if (!$item) {
                http_response_code(404);
                exit(json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']));
            }
            exit(json_encode(['success' => true, 'data' => $item]));

        case $method === 'POST' && preg_match('/^products$/', $endpoint):
            $result = $productService->createProduct($input);
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case ($method === 'PUT' || $method === 'PATCH') && preg_match('/^products\/(\d+)$/', $endpoint, $m):
            $result = $productService->updateProduct((int)$m[1], $input);
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'DELETE' && preg_match('/^products\/(\d+)$/', $endpoint, $m):
            $result = $productService->deleteProduct((int)$m[1]);
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        // ==================== INVENTORY MANAGEMENT ====================
        case ($method === 'PUT' || $method === 'PATCH') && preg_match('/^inventory\/products\/(\d+)\/stock$/', $endpoint, $m):
            if (!isset($input['stock_quantity'])) {
                http_response_code(400);
                exit(json_encode(['success' => false, 'message' => 'Thiếu stock_quantity']));
            }

            $result = $inventoryService->adjustStock(
                (int)$m[1],
                (int)$input['stock_quantity'],
                WarehouseAuthMiddleware::currentUserId(),
                $input['note'] ?? null
            );

            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'GET' && preg_match('/^inventory\/movements$/', $endpoint):
            $item_id = isset($input['item_id']) ? (int)$input['item_id'] : null;
            $year = isset($input['year']) ? (int)$input['year'] : null;
            $month = isset($input['month']) ? (int)$input['month'] : null;
            $rows = $inventoryService->getMovements($item_id, $year, $month);
            exit(json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]));

        // ==================== GOODS RECEIPTS ====================
        case $method === 'POST' && preg_match('/^receipts$/', $endpoint):
            $result = $receiptService->createReceipt($input, WarehouseAuthMiddleware::currentUserId());
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'GET' && preg_match('/^receipts$/', $endpoint):
            $year = isset($input['year']) ? (int)$input['year'] : null;
            $month = isset($input['month']) ? (int)$input['month'] : null;
            $rows = $receiptService->getReceipts($year, $month);
            exit(json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]));

        case $method === 'GET' && preg_match('/^receipts\/(\d+)$/', $endpoint, $m):
            $detail = $receiptService->getReceiptById((int)$m[1]);
            if (!$detail) {
                http_response_code(404);
                exit(json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu nhập']));
            }
            exit(json_encode(['success' => true, 'data' => $detail]));

        case ($method === 'PUT' || $method === 'PATCH') && preg_match('/^receipts\/(\d+)$/', $endpoint, $m):
            $result = $receiptService->updateReceipt((int)$m[1], $input, WarehouseAuthMiddleware::currentUserId());
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'DELETE' && preg_match('/^receipts\/(\d+)$/', $endpoint, $m):
            $result = $receiptService->deleteReceipt((int)$m[1], WarehouseAuthMiddleware::currentUserId());
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        // ==================== GOODS EXPORTS ====================
        case $method === 'POST' && preg_match('/^exports$/', $endpoint):
            $result = $exportService->createExport($input, WarehouseAuthMiddleware::currentUserId());
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'GET' && preg_match('/^exports$/', $endpoint):
            $year = isset($input['year']) ? (int)$input['year'] : null;
            $month = isset($input['month']) ? (int)$input['month'] : null;
            $rows = $exportService->getExports($year, $month);
            exit(json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]));

        case $method === 'GET' && preg_match('/^exports\/(\d+)$/', $endpoint, $m):
            $detail = $exportService->getExportById((int)$m[1]);
            if (!$detail) {
                http_response_code(404);
                exit(json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu xuất']));
            }
            exit(json_encode(['success' => true, 'data' => $detail]));

        case ($method === 'PUT' || $method === 'PATCH') && preg_match('/^exports\/(\d+)$/', $endpoint, $m):
            $result = $exportService->updateExport((int)$m[1], $input, WarehouseAuthMiddleware::currentUserId());
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'DELETE' && preg_match('/^exports\/(\d+)$/', $endpoint, $m):
            $result = $exportService->deleteExport((int)$m[1], WarehouseAuthMiddleware::currentUserId());
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        // ==================== SUPPLIER MANAGEMENT ====================
        case $method === 'GET' && preg_match('/^suppliers$/', $endpoint):
            $keyword = $input['keyword'] ?? null;
            $data = $supplierService->getSuppliers($keyword);
            exit(json_encode(['success' => true, 'data' => $data, 'count' => count($data)]));

        case $method === 'POST' && preg_match('/^suppliers$/', $endpoint):
            $result = $supplierService->createSupplier($input);
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case ($method === 'PUT' || $method === 'PATCH') && preg_match('/^suppliers\/(\d+)$/', $endpoint, $m):
            $result = $supplierService->updateSupplier((int)$m[1], $input);
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        case $method === 'DELETE' && preg_match('/^suppliers\/(\d+)$/', $endpoint, $m):
            $result = $supplierService->deleteSupplier((int)$m[1]);
            if (!$result['success']) {
                http_response_code(400);
            }
            exit(json_encode($result));

        // ==================== REPORTS ====================
        case $method === 'GET' && preg_match('/^reports\/summary$/', $endpoint):
            $year = isset($input['year']) ? (int)$input['year'] : (int)date('Y');
            $month = isset($input['month']) ? (int)$input['month'] : null;
            $summary = $reportService->getSummary($year, $month);
            exit(json_encode(['success' => true, 'data' => $summary]));

        case $method === 'GET' && preg_match('/^reports\/in-stock$/', $endpoint):
            $keyword = $input['keyword'] ?? null;
            $rows = $reportService->getInStockProducts($keyword);
            exit(json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]));

        case $method === 'GET' && preg_match('/^reports\/export$/', $endpoint):
            $year = isset($input['year']) ? (int)$input['year'] : (int)date('Y');
            $month = isset($input['month']) ? (int)$input['month'] : null;
            $format = strtolower((string)($input['format'] ?? 'excel'));

            if ($format !== 'excel') {
                http_response_code(400);
                exit(json_encode([
                    'success' => false,
                    'message' => 'Hiện tại hỗ trợ export Excel (CSV). Dùng format=excel.'
                ]));
            }

            $csv = $reportService->exportCsv($year, $month);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="warehouse-report-' . $year . ($month ? ('-' . $month) : '') . '.csv"');
            echo $csv;
            exit;

        default:
            http_response_code(404);
            exit(json_encode(['success' => false, 'message' => 'Endpoint không tồn tại']));
    }
} catch (\Throwable $e) {
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => 'Lỗi server',
        'error' => $e->getMessage()
    ]));
}
