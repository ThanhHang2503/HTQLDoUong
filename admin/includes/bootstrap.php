<?php

session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/models/functions.php';
require_once __DIR__ . '/../../src/models/authorization.php';

requireRole(AppRole::ADMIN);

function adminText(string $value): string
{
    // htmlspecialchars chỉ escape HTML special chars, không ảnh hưởng UTF-8
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function adminMoney($value): string
{
    return number_format((float) $value, 0, ',', '.');
}

function adminDateTime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    return date('d/m/Y H:i', strtotime($value));
}

function adminNormalizeDirection(?string $direction): string
{
    return strtolower((string) $direction) === 'desc' ? 'DESC' : 'ASC';
}

function adminBuildOrderBy(array $allowed, string $sort, string $direction, string $default): string
{
    $sortKey = $allowed[$sort] ?? $default;
    $directionSql = adminNormalizeDirection($direction);

    return $sortKey . ' ' . $directionSql;
}

function adminFetchProducts(mysqli $conn, array $filters): array
{
    $conditions = [];
    $search = trim((string) ($filters['q'] ?? ''));
    $categoryId = (int) ($filters['category_id'] ?? 0);
    $status = trim((string) ($filters['status'] ?? ''));
    $priceMin = trim((string) ($filters['price_min'] ?? ''));
    $priceMax = trim((string) ($filters['price_max'] ?? ''));
    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    $dateTo = trim((string) ($filters['date_to'] ?? ''));

    if ($search !== '') {
        $escaped = mysqli_real_escape_string($conn, $search);
        if (ctype_digit($search)) {
            $conditions[] = "(i.item_id = " . (int) $search . " OR i.item_name LIKE '%$escaped%' )";
        } else {
            $conditions[] = "(i.item_name LIKE '%$escaped%' OR c.category_name LIKE '%$escaped%')";
        }
    }

    if ($categoryId > 0) {
        $conditions[] = 'i.category_id = ' . $categoryId;
    }

    if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
        $conditions[] = "i.item_status = '" . mysqli_real_escape_string($conn, $status) . "'";
    }

    if ($priceMin !== '' && is_numeric($priceMin)) {
        $conditions[] = 'i.unit_price >= ' . (float) $priceMin;
    }

    if ($priceMax !== '' && is_numeric($priceMax)) {
        $conditions[] = 'i.unit_price <= ' . (float) $priceMax;
    }

    if ($dateFrom !== '') {
        $conditions[] = "DATE(i.added_date) >= '" . mysqli_real_escape_string($conn, $dateFrom) . "'";
    }

    if ($dateTo !== '') {
        $conditions[] = "DATE(i.added_date) <= '" . mysqli_real_escape_string($conn, $dateTo) . "'";
    }

    $sort = (string) ($filters['sort'] ?? 'date');
    $direction = (string) ($filters['direction'] ?? 'desc');
    $orderBy = adminBuildOrderBy([
        'name' => 'i.item_name',
        'price' => 'i.unit_price',
        'date' => 'i.added_date',
        'code' => 'i.item_id',
    ], $sort, $direction, 'i.added_date');

    $sql = "SELECT i.item_id, i.item_name, i.description, i.unit_price, i.added_date, i.item_status, c.category_id, c.category_name
            FROM items i
            LEFT JOIN category c ON c.category_id = i.category_id";

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function adminFetchSuppliers(mysqli $conn, array $filters): array
{
    $conditions = [];
    $search = trim((string) ($filters['q'] ?? ''));
    $status = trim((string) ($filters['status'] ?? ''));
    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    $dateTo = trim((string) ($filters['date_to'] ?? ''));

    if ($search !== '') {
        $escaped = mysqli_real_escape_string($conn, $search);
        if (ctype_digit($search)) {
            $conditions[] = "(supplier_id = " . (int) $search . " OR supplier_code LIKE '%$escaped%' OR supplier_name LIKE '%$escaped%')";
        } else {
            $conditions[] = "(supplier_code LIKE '%$escaped%' OR supplier_name LIKE '%$escaped%' OR contact_name LIKE '%$escaped%')";
        }
    }

    if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
        $conditions[] = "status = '" . mysqli_real_escape_string($conn, $status) . "'";
    }

    if ($dateFrom !== '') {
        $conditions[] = "DATE(created_at) >= '" . mysqli_real_escape_string($conn, $dateFrom) . "'";
    }

    if ($dateTo !== '') {
        $conditions[] = "DATE(created_at) <= '" . mysqli_real_escape_string($conn, $dateTo) . "'";
    }

    $sort = (string) ($filters['sort'] ?? 'date');
    $direction = (string) ($filters['direction'] ?? 'desc');
    $orderBy = adminBuildOrderBy([
        'name' => 'supplier_name',
        'code' => 'supplier_code',
        'date' => 'created_at',
    ], $sort, $direction, 'created_at');

    $sql = 'SELECT supplier_id, supplier_code, supplier_name, contact_name, phone_number, email, address, status, created_at FROM suppliers';

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function adminCountValue(mysqli $conn, string $sql): int
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_row($result);
    return (int) ($row[0] ?? 0);
}
