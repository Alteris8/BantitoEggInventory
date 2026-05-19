<?php
session_start();
include_once("database.php");
include_once("inventory.php");
include_once("producttyperepo.php");
include_once("producttype.php");

$pdo = getPDO();
$productTypeRepo = new ProductTypeRepo($pdo, $_SESSION['admin_id']);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$productType = filter_input(INPUT_POST, "productType", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

	if (isset($_POST['submit'])) {
		if ($productTypeRepo->findByType($productType) !== null) {
			$message = "Product type already exists.";
			header("Location: " . $_SERVER['PHP_SELF']);
		} else {

			$newProductType = new ProductType($productType);
			$productTypeRepo->save($newProductType);

			header("Location: producttypespage.php");
			exit();
		}
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Create Product Type</title>
</head>

<body>

	<h1>Create Product Type</h1>

	<?php if ($message): ?>
		<p><?= htmlspecialchars($message) ?></p>
	<?php endif; ?>

	<form method="POST" action="createproducttypespage.php">

		<label>Product Type</label><br>
		<input type="text" name="productType" required><br><br>

		<button type="button" onclick="window.location.href='producttypespage.php'" formnovalidate>
			Cancel
		</button>

		<button type="submit" name="submit">
			Create
		</button>

	</form>

</body>

</html>
