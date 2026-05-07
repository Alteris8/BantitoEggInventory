<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");

$pdo           = getPDO();
$inventoryRepo = new InventoryRepo($pdo);

$inventoryId = isset($_GET['inventory_id']) ? (int)$_GET['inventory_id'] : null;
$sort        = $_GET['sort']  ?? 'name';
$order       = $_GET['order'] ?? 'DESC';

if (!$inventoryId) {
	header("Location: inventorypage.php");
	exit;
}

$inventory = $inventoryRepo->findById($inventoryId);
if (!$inventory) {
	header("Location: inventorypage.php");
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

	if ($amount <= 0) {
		$error = "Amount must be greater than zero.";
	} elseif ($amount > $inventory->getQuantity()) {
		$error = "Not enough stock. Available: " . $inventory->getQuantity();
	} else {
		try {
			$inventoryRepo->reduceQuantity($inventoryId, $amount);
			header("Location: inventorypage.php?sort=$sort&order=$order");
			exit;
		} catch (Exception $e) {
			$error = "Failed: " . $e->getMessage();
		}
	}
}
?>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Process Sale</title>
</head>

<body>
	<h2>Process Sale</h2>

	<?php if (isset($error)): ?>
		<p style="color:red;"><?= htmlspecialchars($error) ?></p>
	<?php endif; ?>

	<table>
		<tr>
			<th>Product</th>
			<th>Available Stock</th>
			<th>Unit Price</th>
		</tr>
		<tr>
			<td><?= htmlspecialchars($inventory->getProductName()) ?></td>
			<td><?= $inventory->getQuantity() ?></td>
			<td><?= $inventory->getPrice() ?></td>
		</tr>
	</table>

	<form method="post" action="processsalespage.php?inventory_id=<?= $inventoryId ?>&sort=<?= $sort ?>&order=<?= $order ?>">
		<label for="amount">Quantity to Sell:</label>
		<input type="number"
			name="amount"
			id="amount"
			min="1"
			max="<?= $inventory->getQuantity() ?>"
			required>

		<br><br>

		<button type="submit">Confirm Sale</button>
		<button type="button" onclick="window.location='inventorypage.php?sort=<?= $sort ?>&order=<?= $order ?>'">Cancel</button>
	</form>
</body>

</html>
