<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");
include_once("producttyperepo.php");
include_once("capitaltransactionsrepo.php");


$pdo = getPDO();
$productTypeRepo = new ProductTypeRepo($pdo, $_SESSION['admin_id']);
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$inventoryRepo = new InventoryRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);

$inventoryId = isset($_GET['inventory_id']) ? (int)$_GET['inventory_id'] : null;

$sort  = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'DESC';

if (!$inventoryId) {
	header("Location: inventorypage.php");
	exit;
}

$inventory = $inventoryRepo->findById($inventoryId);

if (!$inventory) {
	header("Location: inventorypage.php");
	exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
	$price  = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

	if (!$amount || $amount <= 0) {
		$error = "Amount must be greater than zero.";
	} elseif ($amount > $inventory->getQuantity()) {
		$error = "Not enough stock. Available: " . $inventory->getQuantity();
	} else {

		try {
			$inventoryRepo->processSales($inventoryId, $amount, $price);
			$capitalTransactionsRepo->recalculateBalance();
			header("Location: inventorypage.php?sort=$sort&order=$order");
			exit;
		} catch (Exception $e) {
			$error = "Failed: " . $e->getMessage();
		}
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Process Sale</title>
</head>

<body>

	<h1>Process Sale</h1>

	<?php if ($error): ?>
		<p><?= htmlspecialchars($error) ?></p>
	<?php endif; ?>

	<h3>Product Info</h3>

	<p>
		<strong>Name:</strong>
		<?= htmlspecialchars($inventory->getProductName()) ?>
	</p>

	<p>
		<strong>Stock:</strong>
		<?= $inventory->getQuantity() ?>
	</p>

	<p>
		<strong>Price:</strong>
		₱<?= $inventory->getPrice() ?>
	</p>

	<form method="post"
		action="processsalespage.php?inventory_id=<?= $inventoryId ?>&sort=<?= $sort ?>&order=<?= $order ?>">

		<label>Quantity to Sell</label><br>
		<input type="number"
			name="amount"
			min="1"
			max="<?= $inventory->getQuantity() ?>"
			required><br><br>
		<label>Price</label><br>
		<input type="number" name="price"
			value="<?= $inventory->getPrice() ?>" step="0.01" min="1" required><br><br>


		<button type="button"
			onclick="window.location='inventorypage.php?sort=<?= $sort ?>&order=<?= $order ?>'">
			Cancel
		</button>

		<button type="submit">
			Confirm Sale
		</button>

	</form>

</body>

</html>
