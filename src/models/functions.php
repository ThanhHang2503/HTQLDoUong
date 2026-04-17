<?php
require_once __DIR__ . '/authorization.php';

/**
 * Sinh data-* attributes để gắn vào thẻ <body> hoặc page container.
 * JS trong layout_end sẽ đọc và tự động hiển thị modal thông báo.
 *
 * @param string $type    'success' | 'error' | 'warning' | 'info'
 * @param string $message Nội dung thông báo
 * @param string $title   Tiêu đề (để trống = dùng default theo type)
 */
function notifyAttrs(string $type, string $message, string $title = ''): string
{
    if ($message === '') return '';
    $t = htmlspecialchars($type,    ENT_QUOTES);
    $m = htmlspecialchars($message, ENT_QUOTES);
    $h = htmlspecialchars($title,   ENT_QUOTES);
    return " data-notify-type=\"$t\" data-notify-message=\"$m\"" . ($h ? " data-notify-title=\"$h\"" : '');
}

/**
 * Lưu thông báo vào session flash (dùng trước redirect).
 * Layout sẽ tự đọc và hiển thị modal khi trang load.
 */
function setNotify(string $type, string $message, string $title = ''): void
{
    $_SESSION['notify_type']    = $type;
    $_SESSION['notify_message'] = $message;
    if ($title !== '') $_SESSION['notify_title'] = $title;
}

function redirect($link)
{
?>
	<script>
		window.location.href = "<?php echo $link ?>";
	</script>
<?php
}
function checkUser()
{
	requireLogin();
}

function checkAdmin()
{
	requirePermission(AppPermission::MANAGE_STAFF);
}

function checkValidItemID($id)
{
	global $conn;
	$id = (int)$id;
	$sql = "SELECT item_id FROM items WHERE item_id = $id AND item_status = 'active' LIMIT 1";
	$result = mysqli_query($conn, $sql);
	if ($result && mysqli_num_rows($result) > 0) {
		return true;
	}
	return false;
}

function kiemtraKHcoTonTai($conn, $customer_name, $phone_number)
{
    $safe_name = mysqli_real_escape_string($conn, $customer_name);
    $safe_phone = mysqli_real_escape_string($conn, $phone_number);
	$sql = "select * from customers where customer_name = '$safe_name' and phone_number = '$safe_phone';";
	$result = mysqli_query($conn, $sql);
	$count = mysqli_fetch_row($result);
	if (!empty($count)) {
		return true;
	}
	return false;
}


// $list_products là 1 mảng gồm các sản phẩm, mối sản phẩm gồm product_id và quantity

function taoHoaDon($conn, $customer_name, $phone_number, $account_id, array $list_products, $discount, $total, $email = '')
{
    $safe_name = mysqli_real_escape_string($conn, $customer_name);
    $safe_phone = mysqli_real_escape_string($conn, $phone_number);
    $safe_email = mysqli_real_escape_string($conn, $email);

    // 1. Tìm hoặc tạo khách hàng
    $customer_id = null;
    $existing_customer = null;

    // Ưu tiên kiểm tra theo Email (Phù hợp yêu cầu mới của người dùng)
    if ($safe_email !== '') {
        $check_email = mysqli_query($conn, "SELECT customer_id FROM customers WHERE email = '$safe_email' LIMIT 1");
        $existing_customer = mysqli_fetch_assoc($check_email);
    }

    // Nếu không có email hoặc không tìm thấy theo Email, kiểm tra theo Số điện thoại (Fallback để tránh trùng)
    if (!$existing_customer && $safe_phone !== '') {
        $check_phone = mysqli_query($conn, "SELECT customer_id FROM customers WHERE phone_number = '$safe_phone' LIMIT 1");
        $existing_customer = mysqli_fetch_assoc($check_phone);
    }

    if ($existing_customer) {
        $customer_id = $existing_customer['customer_id'];
    } else {
        // Tạo mới nếu cả Email và SĐT đều chưa có trong hệ thống
        $sql_insert_kh = "INSERT INTO customers (customer_name, phone_number, email) VALUES ('$safe_name', '$safe_phone', '$safe_email')";
        if (mysqli_query($conn, $sql_insert_kh)) {
            $customer_id = mysqli_insert_id($conn);
        } else {
            throw new Exception("Lỗi khi tự động tạo khách hàng: " . mysqli_error($conn));
        }
    }

    if (!$customer_id) throw new Exception("Không thể xác định ID khách hàng.");

    mysqli_begin_transaction($conn);
    try {
        $sql = "insert into invoices (account_id, customer_id, discount, total, status) values('$account_id', '$customer_id', '$discount', '$total', 'completed')";
        if (!mysqli_query($conn, $sql)) throw new Exception("Error creating invoice: " . mysqli_error($conn));

        $invoice_id = mysqli_insert_id($conn);

        foreach ($list_products as $product) {
            $item_id = (int)$product['product_id'];
            $qty = (int)$product['quantity'];

            // 1. Get current stock, cost AND unit_price
            $res_item = mysqli_query($conn, "SELECT stock_quantity, purchase_price, unit_price FROM items WHERE item_id = $item_id");
            $item_data = mysqli_fetch_assoc($res_item);
            if (!$item_data) throw new Exception("Product #$item_id not found");

            $stock_before = (int)$item_data['stock_quantity'];
            $purchase_price = (float)$item_data['purchase_price'];
            $unit_price = (float)$item_data['unit_price'];
            $stock_after = $stock_before - $qty;

            // 2. Insert invoice details (with unit_price)
            $sql_det = "INSERT INTO invoice_details (invoice_id, item_id, quantity, unit_price) VALUES ('$invoice_id', '$item_id', '$qty', '$unit_price')";
            if (!mysqli_query($conn, $sql_det)) throw new Exception("Error creating invoice details");

            // 3. Update stock and sale status
            $sale_status = ($stock_after <= 0) ? 'stopped' : 'selling';
            $sql_update = "UPDATE items SET stock_quantity = $stock_after, sale_status = '$sale_status' WHERE item_id = $item_id";
            if (!mysqli_query($conn, $sql_update)) throw new Exception("Error updating stock");

            // 4. Log movement
            $sql_mov = "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, note, created_by) 
                        VALUES ($item_id, 'export', -$qty, $stock_before, $stock_after, $purchase_price, 'invoice', $invoice_id, 'Bán hàng (Số HĐ: $invoice_id)', $account_id)";
            if (!mysqli_query($conn, $sql_mov)) throw new Exception("Error logging movement");
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        die("System Error: " . $e->getMessage());
    }
}
