<?php
session_start();
include_once("database.php");
include_once("capitaltransactionsrepo.php");
include_once("capitaltransaction.php");
include_once("inventoryrepo.php");
include_once("inventory.php");

$pdo = getPDO();
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$inventoryRepo = new InventoryRepo($pdo, $_SESSION['admin_id']);

$editId = (int)($_GET['edit_id'] ?? 0);
$sort   = $_GET['sort'] ?? 'createdAt';
$order  = $_GET['order'] ?? 'DESC';

if (!$editId) {
	header("Location: capitaltestpage.php");
	exit();
}

$transaction = $capitalTransactionsRepo->findById($editId);
$inventory = $inventoryRepo->findById($editId);
if (!$transaction) {
	header("Location: capitaltestpage.php?error=not_found");
	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['cancel'])) {
		header("Location: capitaltestpage.php?sort=$sort&order=$order");
		exit();
	}

	if (isset($_POST['save'])) {
		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$amount      = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$type        = $transaction->getType();

		$capitalTransactionsRepo->update($editId, new CapitalTransaction(
			$type,
			$amount,
			$description,
			null,
			$editId,
			$_SESSION['admin_id']
		));

		header("Location: capitaltestpage.php?sort=$sort&order=$order");
		exit();
	}
}
?>
<html>

<body>
	<h1>Edit Transaction</h1>

	<form method="POST">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">

		<label>Description</label><br>
		<input type="text" name="description"
			value="<?= htmlspecialchars($transaction->getDescription()) ?>"
			readonly><br><br>

		<label>Amount</label><br>
		<input type="number" name="amount" step="0.01" min="0" value="<?= htmlspecialchars($transaction->getAmount()) ?>" required><br><br>

		<input type="submit" name="save" value="Save">
		<input type="submit" name="cancel" value="Cancel" formnovalidate>
	</form>
</body>

</html>
