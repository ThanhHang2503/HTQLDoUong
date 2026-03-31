<?php
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
	if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != '') {
	} else {
		redirect('./index.php');
	}
}

function checkAdmin()
{
	if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] != '') {
	} else {
		redirect('./index.php');
	}
}

function checkValidItemID($id)
{
	$conn = mysqli_connect('localhost', 'root', 'Z!L9@Wfd', 'eldercoffee_db');
	$sql = "select * from itemw where item_id = $id";
	$result = mysqli_query($conn, $sql);
	if (mysqli_num_rows($result) > 0) {
		return true;
	} else {
		return false;
	}
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
