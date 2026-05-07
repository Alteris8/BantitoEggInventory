<?php
include_once("database.php");
include_once("inventory.php");
include_once("inventoryrepo.php");

$pdo = getPDO();
$repo = new InventoryRepo($pdo);
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$productName = $_POST["product_name"] ?? '';
	$price = $_POST["price"] ?? 0;
	$quantity = $_POST["quantity"] ?? 0;

	if ($productName && $price && $quantity) {
		$inventory = new Inventory(
			null,
			$productName,
			(int)$quantity,
			(float)$price,
			null
		);
		$repo->save($inventory);
		$message = "Product added successfully!";
	} else {
		$message = "Please fill in all fields.";
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<title>Create Inventory</title>
</head>

<body>
	<h2>Add Product</h2>

	<?php if ($message): ?>
		<p><?= $message ?></p>
	<?php endif; ?>

	<form method="POST">
		<label>Product Name:</label><br>
		<input type="text" name="product_name" required><br>
		<label>Price:</label><br>
		<input type="number" step="0.01" name="price" required><br><br>
		<label>Quantity:</label><br>
		<input type="number" name="quantity" required><br><br>
		<button type="submit">Add Product</button>
	</form>
	<a href="inventorypage.php">Check Inventory</a>
</body>

</html>
