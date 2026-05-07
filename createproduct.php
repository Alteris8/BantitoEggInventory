<?php

include_once("database.php");
include_once("productrepo.php");
$repo = new ProductRepo($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$productName = filter_input(INPUT_POST, "productName", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$price = filter_input(INPUT_POST, "price", FILTER_VALIDATE_FLOAT);
	$quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

	if (isset($_POST["submit"])) {
		$repo->addProduct($productName, $price, $quantity);
		header("Location: inventorypage.php");
		exit();
	}
}

?>
<html>

<body>
	<form method="post" action="createproduct.php">
		<label>Product Name: </label>
		<input type="text" name="productName"><br>
		<label>Price: </label>
		<input type="text" name="price"><br>
		<label>Quantity: </label>
		<input type="text" name="quantity"><br>
		<input type="submit" name="submit" value="Create">

	</form>

</body>

</html>
