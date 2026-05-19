<?php
session_start();

include_once("database.php");
include_once("capitaltransactionsrepo.php");
include_once("capitaltransaction.php");
include_once("inventoryrepo.php");
include_once("inventory.php");
include_once("producttyperepo.php");
include_once("producttype.php");

$pdo = getPDO();
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$productTypeRepo = new ProductTypeRepo($pdo, $_SESSION['admin_id']);
$inventoryRepo = new InventoryRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);

$inventoryId = isset($_GET['restock_id']) ? (int)$_GET['restock_id'] : null;
$inventory = $inventoryId ? $inventoryRepo->findById($inventoryId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$inventory) {
	header("Location: inventorypage.php");
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$selectedId = isset($_POST['selected_id']) ? (int)$_POST['selected_id'] : null;
	$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$cost = filter_input(INPUT_POST, 'cost', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

	if (isset($_POST['cancel'])) {
		header("Location: inventorypage.php");
		exit();
	}
	if (!$cost || $cost <= 0) {
		header("Location: inventorypage.php");
		exit();
	}
	if (isset($_POST['updateStocks'])) {
		if (!$selectedId) {
			header("Location: inventorypage.php");
			exit();
		}
		$restockQuantity = (int)filter_input(INPUT_POST, 'updatedQuantity', FILTER_SANITIZE_NUMBER_INT);
		$cost = filter_input(INPUT_POST, 'cost', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$inventory = $inventoryRepo->findById($selectedId);

		try {
			$pdo->beginTransaction();
			$inventoryRepo->restock($selectedId, $restockQuantity);
			$capitalTransactionsRepo->save(new CapitalTransaction(
				type: 'restock',
				amount: (float)$cost,
				description: $inventory->getProductName(),
			));
			$capitalTransactionsRepo->recalculateBalance();
			$pdo->commit();
		} catch (Exception $e) {
			$pdo->rollBack();
			throw $e;
		}
		header("Location: inventorypage.php");
		exit();
	}
}
?>
<html>

<body>



	<form method="post" action="restockinventorypage.php">
		<input type="hidden" name="selected_id" value="<?= $inventory->getId() ?>">
		<label>Product Name</label>
		<p><?= htmlspecialchars($inventory->getProductName()) ?></p>
		<label>Unit Price</label><br>
		<p><?= htmlspecialchars($inventory->getPrice()) ?></p><br>

		<label>Quantity</label><br>
		<p><?= htmlspecialchars($inventory->getQuantity()) ?></p><br>

		<label>Restock Quantity: </label>
		<input type="number" name="updatedQuantity" min="1" required></input><br>
		<label>Cost: </label>
		<input type="number" name="cost" step="0.01" min="1" required></input><br>
		<input type="submit" name="updateStocks" value="Update Stocks"></input>
		<input type="submit" name="cancel" value="Cancel" formnovalidate>


	</form>

</body>

</html>
