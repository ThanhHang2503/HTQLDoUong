<?php

if (isset($_GET['nhansu']) && $_GET['nhansu'] == 'khoiphuc') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        header('location:user_page.php?nhansu&status=invalid_id');
        exit;
    }

    try {
        $sql = "UPDATE accounts SET type = 'user' WHERE account_id = $id AND type = 'inactive'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_affected_rows($conn) > 0) {
            header('location:user_page.php?nhansu&status=reactivated');
            exit;
        }

        header('location:user_page.php?nhansu&status=not_found');
        exit;
    } catch (Throwable $e) {
        header('location:user_page.php?nhansu&status=error');
        exit;
    }
}

if (isset($_GET['nhansu']) && $_GET['nhansu'] == 'xoa') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $currentAdminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;

    if ($id <= 0) {
        header('location:user_page.php?nhansu&status=invalid_id');
        exit;
    }

    if ($id === $currentAdminId) {
        header('location:user_page.php?nhansu&status=self_delete');
        exit;
    }

    try {
        $checkInvoiceSql = "SELECT COUNT(*) AS total FROM invoices WHERE account_id = $id";
        $checkResult = mysqli_query($conn, $checkInvoiceSql);
        $checkRow = $checkResult ? mysqli_fetch_assoc($checkResult) : ['total' => 0];

        if ((int) ($checkRow['total'] ?? 0) > 0) {
            $archivedPassword = md5(uniqid((string) $id, true));
            $archiveSql = "UPDATE accounts 
                SET type = 'inactive', 
                    password = '$archivedPassword'
                WHERE account_id = $id";
            $archiveResult = mysqli_query($conn, $archiveSql);

            if ($archiveResult && mysqli_affected_rows($conn) > 0) {
                header('location:user_page.php?nhansu&status=deactivated');
                exit;
            }

            header('location:user_page.php?nhansu&status=error');
            exit;
        }

        $sql = "DELETE FROM accounts WHERE account_id = $id";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_affected_rows($conn) > 0) {
            header('location:user_page.php?nhansu&status=deleted');
            exit;
        }

        header('location:user_page.php?nhansu&status=not_found');
        exit;
    } catch (Throwable $e) {
        header('location:user_page.php?nhansu&status=error');
        exit;
    }
}

?>
