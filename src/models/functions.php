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
	$sql = "select * from customers where customer_name = '$customer_name' and phone_number = '$phone_number';";
	$result = mysqli_query($conn, $sql);
	$count = mysqli_fetch_row($result);
	if (!empty($count)) {
		return true;
	}
	return false;
}


// $list_products là 1 mảng gồm các sản phẩm, mối sản phẩm gồm product_id và quantity

function taoHoaDon($conn, $customer_name, $phone_number, $account_id, array $list_products, $discount, $total)
{
	if (!kiemtraKHcoTonTai($conn, $customer_name, $phone_number)) {
		$sql = "insert into customers (customer_name, phone_number) values('$customer_name', '$phone_number')";
		mysqli_query($conn, $sql);
	}

	$sql = "select customer_id from customers where customer_name = '$customer_name' and phone_number = '$phone_number'";
	$result = mysqli_query($conn, $sql);
	$customer = mysqli_fetch_row($result);
	$customer_id = $customer['0'];

	$sql = "insert into invoices (account_id, customer_id, discount, total) values('$account_id', '$customer_id', '$discount', '$total')";
	mysqli_query($conn, $sql);

	$invoice_id = mysqli_insert_id($conn);



	foreach ($list_products as $product) {
		$sql = "INSERT INTO invoice_details (invoice_id, item_id, quantity) VALUES ('$invoice_id', '{$product['product_id']}', '{$product['quantity']}')";
		mysqli_query($conn, $sql);
	}
}
