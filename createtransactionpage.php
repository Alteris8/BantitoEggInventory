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


$sort   = $_GET['sort'] ?? 'createdAt';
$order  = $_GET['order'] ?? 'DESC';
$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$error = $_GET['error'] ?? null;
$type = $_GET['type'] ?? '';


$search = $_GET['searchInventory'] ?? "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$selectedId = isset($_POST['selected_id']) ? (int)$_POST['selected_id'] : null;
	$sort  = $_POST['sort'] ?? 'name';
	$order = $_POST['order'] ?? 'DESC';
	$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$cost = filter_input(INPUT_POST, 'cost', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	if (isset($_POST['cancel'])) {
		header("Location: capitaltestpage.php");
		exit();
	}

	if (isset($_POST['processSales'])) {
		$inventory = $inventoryRepo->findById($selectedId);
		if ($inventory->getQuantity() < 1) {
			header("Location: inventorypage.php?sort=$sort&order=$order&error=empty_quantity");
			exit();
		}
		header("Location: transactionprocesssalespage.php?inventory_id=$selectedId&sort=$sort&order=$order");
		exit();
	}
	if (isset($_POST['updateStocks'])) {
		$restockQuantity = (int) filter_input(INPUT_POST, 'updatedQuantity', FILTER_SANITIZE_NUMBER_INT);

		$inventory = $inventoryRepo->findById($selectedId);


		try {
			$pdo->beginTransaction();
			$inventoryRepo->restock($selectedId, $restockQuantity);
			$capitalTransactionsRepo->save(new CapitalTransaction(
				'restock',
				$cost,
				$inventory->getProductName()
			));
			$pdo->commit();
		} catch (Exception $e) {
			$pdo->rollBack();
			throw $e;
		}
		header("Location: capitaltestpage.php");
		exit();
	}
	if (isset($_POST['createTransaction'])) {
		$newTransaction = new CapitalTransaction($type, $cost, $description);
		$capitalTransactionsRepo->save($newTransaction);
		header("Location: capitaltestpage.php");
		exit();
	}
}

if ($search !== "") {
	$inventories = $inventoryRepo->searchInventory($search);
} else {
	$inventories = $inventoryRepo->findAll($sort, $order);;
}

$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total      = $inventoryRepo->countFiltered($search);
$totalPages = (int)ceil($total / $limit);
$inventories = $inventoryRepo->paginate($page, $limit, $sort, $order, $search);

?>
<html>

<body>
	<form method="GET" action="createtransactionpage.php">
		<label>Type: </label>
		<select name="type" onchange="this.form.submit()">
			<option value="">...</option>
			<option value="sale" <?= ($_GET['type'] ?? '') === 'sale'    ? 'selected' : '' ?>>Sale</option>
			<option value="expense" <?= ($_GET['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
			<option value="restock" <?= ($_GET['type'] ?? '') === 'restock' ? 'selected' : '' ?>>Restock</option>
			<option value="deposit" <?= ($_GET['type'] ?? '') === 'deposit' ? 'selected' : '' ?>>Deposit</option>
		</select>
	</form> <?php if ($type === 'sale'): ?>
		<label>Select an inventory to process sales</label>
		<form method="GET">
			<input type="text" name="searchInventory" value="<?= htmlspecialchars($search) ?>">
			<button type="submit">Search</button>
		</form>

		<form method="post" action="createtransactionpage.php">
			<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

			<table border="1">

				<tr>
					<th>Select</th>
					<th>Name</th>
					<th>Quantity</th>
					<th>Price</th>
					<th>Status</th>
					<th>Last Updated</th>
				</tr>

				<?php if (!empty($inventories)): ?>


					<input type="hidden" name="sort" value="<?= $sort ?>">
					<input type="hidden" name="order" value="<?= $order ?>">
					<?php foreach ($inventories as $inventory): ?>

						<tr>
							<td>
								<input type="radio" name="selected_id" value="<?= $inventory->getId() ?>">
							</td>
							<td><?= htmlspecialchars($inventory->getProductName()) ?></td>
							<td><?= htmlspecialchars($inventory->getQuantity()) ?></td>
							<td>₱<?= htmlspecialchars($inventory->getPrice()) ?></td>
							<td><?= htmlspecialchars($inventory->getStatus()) ?></td>
							<td><?= $inventory->getDateUpdated()->format('d-m-Y') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="6">Empty inventory</td>
					</tr>
				<?php endif; ?>

			</table>

			<input type="submit" name="processSales" value="Process Sales">
			<input type="submit" name="cancel" value="Cancel">

		</form>
		<div>
			<?php if ($page > 1): ?>
				<a href="?page=<?= $page - 1 ?>&type=<?= $type ?>&searchInventory=<?= urlencode($search) ?>">Previous</a> <?php endif; ?>

			<?php for ($i = 1; $i <= $totalPages; $i++): ?>
				<a href="?page=<?= $i ?>"
					<?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
					<?= $i ?>
				</a>
			<?php endfor; ?>

			<?php if ($page < $totalPages): ?>
				<a href="?page=<?= $page + 1 ?>&type=<?= $type ?>&searchInventory=<?= urlencode($search) ?>">Next</a> <?php endif; ?>
		</div>

	<?php elseif ($type === 'restock'): ?>
		<label>Select an inventory to restock</label>
		<form method="GET">
			<input type="text" name="searchInventory" value="<?= htmlspecialchars($search) ?>">
			<button type="submit">Search</button>
		</form>

		<form method="post" action="createtransactionpage.php">

			<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

			<table border="1">

				<tr>
					<th>Select</th>
					<th>Name</th>
					<th>Quantity</th>
					<th>Price</th>
					<th>Status</th>
					<th>Last Updated</th>
				</tr>

				<?php if (!empty($inventories)): ?>


					<input type="hidden" name="sort" value="<?= $sort ?>">
					<input type="hidden" name="order" value="<?= $order ?>">
					<?php foreach ($inventories as $inventory): ?>

						<tr>
							<td>
								<input type="radio" name="selected_id" value="<?= $inventory->getId() ?>">
							</td>
							<td><?= htmlspecialchars($inventory->getProductName()) ?></td>
							<td><?= htmlspecialchars($inventory->getQuantity()) ?></td>
							<td>₱<?= htmlspecialchars($inventory->getPrice()) ?></td>
							<td><?= htmlspecialchars($inventory->getStatus()) ?></td>
							<td><?= $inventory->getDateUpdated()->format('d-m-Y') ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="6">Empty inventory</td>
					</tr>
				<?php endif; ?>

			</table>

			<label>Restock Quantity: </label>
			<input type="number" name="updatedQuantity" min="0" required></input><br>
			<label>Cost: </label>
			<input type="number" name="cost" step="0.01" min="0" required></input><br>
			<input type="submit" name="updateStocks" value="Update Stocks"></input>
			<input type="submit" name="cancel" value="Cancel" formnovalidate>


		</form>
		<div>
			<?php if ($page > 1): ?>
				<a href="?page=<?= $page - 1 ?>&type=<?= $type ?>&searchInventory=<?= urlencode($search) ?>">Previous</a> <?php endif; ?>

			<?php for ($i = 1; $i <= $totalPages; $i++): ?>
				<a href="?page=<?= $i ?>"
					<?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
					<?= $i ?>
				</a>
			<?php endfor; ?>

			<?php if ($page < $totalPages): ?>
				<a href="?page=<?= $page + 1 ?>&type=<?= $type ?>&searchInventory=<?= urlencode($search) ?>">Next</a> <?php endif; ?>
		</div>
	<?php else: ?>
		<form method="POST">
			<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><br>
			<label>Description</label><br>
			<input type="text" name="description" required><br><br>

			<label>Amount</label><br>
			<input type="number" name="cost" step="0.01" min="0" required><br><br>
			<button type="button" onclick="window.location.href='capitaltestpage.php'">
				Cancel
			</button>

			<button type="submit" name="createTransaction">
				Create
			</button>

		</form>




	<?php endif; ?>

</body>

</html>
