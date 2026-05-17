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


$sort        = $_GET['sort']        ?? 'lastUpdated';
$order       = $_GET['order']       ?? 'DESC';
$nextOrder   = $order === 'ASC' ? 'DESC' : 'ASC';
$search      = $_GET['search']      ?? '';
$productType = $_GET['productType'] ?? null;
$filter      = $_GET['filter']      ?? 'all';
$error       = $_GET['error']       ?? null;
$status = $_GET['status'] ?? null;
$type = $_GET['type'] ?? $_POST['type'] ?? '';

$month       = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$week        = filter_input(INPUT_GET, 'week',  FILTER_VALIDATE_INT);
$year        = filter_input(INPUT_GET, 'year',  FILTER_VALIDATE_INT) ?? (int)date('Y');

$currentMonth = (int)date('m');
$currentWeek  = (int)ceil(date('j') / 7);

if (!$month && ($filter === 'week' || $filter === 'month')) $month = $currentMonth;
if (!$week  &&  $filter === 'week') $week = $currentWeek;

$allProductTypes = $productTypeRepo->findAllTypes();

$months = [
	1 => 'January',
	2 => 'February',
	3 => 'March',
	4 => 'April',
	5 => 'May',
	6 => 'June',
	7 => 'July',
	8 => 'August',
	9 => 'September',
	10 => 'October',
	11 => 'November',
	12 => 'December'
];

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
		if (!$selectedId) {
			header("Location: createtransactionpage.php?type=sale&error=no_selection");
			exit();
		}
		$inventory = $inventoryRepo->findById($selectedId);
		if ($inventory->getQuantity() < 1) {
			header("Location: createtransactionpage.php?type=sale&error=empty_quantity");
			exit();
		}
		header("Location: transactionprocesssalespage.php?inventory_id=$selectedId&sort=$sort&order=$order");
		exit();
	}

	if (isset($_POST['updateStocks'])) {
		if (!$selectedId) {
			header("Location: createtransactionpage.php?type=restock&error=no_selection");
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
		header("Location: capitaltestpage.php");
		exit();
	}

	if (isset($_POST['createTransaction'])) {
		$type = $_POST['type'] ?? '';
		$cost = filter_input(INPUT_POST, 'cost', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (!in_array($type, ['expense', 'deposit'])) {
			header("Location: createtransactionpage.php?error=invalid_type");
			exit();
		}
		$capitalTransactionsRepo->save(new CapitalTransaction(
			type: $type,
			amount: (float)$cost,
			description: $description,
		));
		$capitalTransactionsRepo->recalculateBalance();
		header("Location: capitaltestpage.php");
		exit();
	}
}

$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total       = $inventoryRepo->countFiltered($search, $productType ?? '', $filter, $month, $week, $year, $status);
$totalPages = (int)ceil($total / $limit);
$inventories = $inventoryRepo->paginate($page, $limit, $sort, $order, $search, $productType ?? '', $filter, $month, $week, $year, $status ?? '');
$inventoryTotalValue = $inventoryRepo->totalInventoryValue($filter, $month, $week, $year, $productType, $search, $status);
function inventoryUrl(array $overrides = []): string
{
	$params = array_merge([
		'sort'        => $GLOBALS['sort'],
		'order'       => $GLOBALS['order'],
		'search'      => $GLOBALS['search'],
		'productType' => $GLOBALS['productType'],
		'status'      => $GLOBALS['status'],
		'filter'      => $GLOBALS['filter'],
		'month'       => $GLOBALS['month'],
		'week'        => $GLOBALS['week'],
		'year'        => $GLOBALS['year'],
	], $overrides);
	return '?' . http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''));
}

?>
<html>

<body>
	<form method="GET" action="createtransactionpage.php">
		<label>Type: </label>
		<select name="type">
			<option value="">...</option>
			<option value="sale" <?= $type === 'sale'    ? 'selected' : '' ?>>Sale</option>
			<option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Expense</option>
			<option value="restock" <?= $type === 'restock' ? 'selected' : '' ?>>Restock</option>
			<option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>Deposit</option>
		</select>
		<button type="submit">Submit</button>
	</form>
	<?php if ($type === 'sale'): ?>
		<label>Select an inventory to process sales</label>
		<form method="GET">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">
			<input type="hidden" name="productType" value="<?= htmlspecialchars($productType ?? '') ?>">
			<input type="hidden" name="filter" value="<?= $filter ?>">
			<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
			<button type="submit">Search</button>
		</form>
		<form method="GET">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">
			<input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
			<select name="status" onchange="this.form.submit()">
				<option value="">All Statuses</option>
				<option value="Available" <?= $status === 'Available'    ? 'selected' : '' ?>>Available</option>
				<option value="Low Stock" <?= $status === 'Low Stock'    ? 'selected' : '' ?>>Low Stock</option>
				<option value="Out of Stock" <?= $status === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
			</select>

			<label>Filter:</label>
			<select name="filter" onchange="this.form.submit()">
				<option value="all" <?= $filter === 'all'   ? 'selected' : '' ?>>All</option>
				<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly</option>
				<option value="week" <?= $filter === 'week'  ? 'selected' : '' ?>>Weekly</option>
				<option value="now" <?= $filter === 'now'   ? 'selected' : '' ?>>Today</option>
			</select>

			<label>Product Type:</label>
			<select name="productType" onchange="this.form.submit()">
				<option value="">All Types</option>
				<?php foreach ($allProductTypes as $type): ?>
					<option value="<?= htmlspecialchars($type) ?>" <?= $productType === $type ? 'selected' : '' ?>>
						<?= htmlspecialchars($type) ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php if ($filter === 'month' || $filter === 'week'): ?>
				<select name="month" onchange="this.form.submit()">
					<option value="">Select Month</option>
					<?php foreach ($months as $num => $name): ?>
						<option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
					<?php endforeach; ?>
				</select>
				<select name="year" onchange="this.form.submit()">
					<?php for ($y = date('Y'); $y >= 2020; $y--): ?>
						<option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
					<?php endfor; ?>
				</select>
			<?php endif; ?>

			<?php if ($filter === 'week'): ?>
				<select name="week" onchange="this.form.submit()">
					<option value="">Select Week</option>
					<?php for ($w = 1; $w <= 4; $w++): ?>
						<option value="<?= $w ?>" <?= $week == $w ? 'selected' : '' ?>>Week <?= $w ?></option>
					<?php endfor; ?>
				</select>
			<?php endif; ?>
		</form>


		<form method="post" action="createtransactionpage.php?type=<?= htmlspecialchars($type) ?>">
			<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">

			<table border="1">
				<tr>
					<th>Select</th>
					<th>Name</th>
					<th>Product Type</th>
					<th>Quantity</th>
					<th>Unit Price</th>
					<th>Status</th>
					<th>Last Updated</th>
					<th>Actions</th>
				</tr>
				<?php if (!empty($inventories)): ?>
					<?php foreach ($inventories as $inventory): ?>
						<tr>
							<td>
								<input type="radio" name="selected_id" value="<?= $inventory->getId() ?>">
							</td>
							<td><?= htmlspecialchars($inventory->getProductName()) ?></td>
							<td><?= htmlspecialchars($inventory->getProductType() ?? '—') ?></td>
							<td><?= htmlspecialchars($inventory->getQuantity()) ?></td>
							<td>₱<?= number_format($inventory->getPrice(), 2) ?></td>
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
			<?php if ($totalPages > 1): ?>
				<div>
					<?php if ($page > 1): ?>
						<a href="<?= inventoryUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
					<?php endif; ?>
					<?php for ($i = 1; $i <= $totalPages; $i++): ?>
						<a href="<?= inventoryUrl(['page' => $i]) ?>" <?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
							<?= $i ?>
						</a>
					<?php endfor; ?>
					<?php if ($page < $totalPages): ?>
						<a href="<?= inventoryUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<input type="submit" name="processSales" value="Process Sales">
			<input type="submit" name="cancel" value="Cancel">

		</form>
	<?php elseif ($type === 'restock'): ?>
		<label>Select an inventory to restock</label>
		<form method="GET">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">
			<input type="hidden" name="productType" value="<?= htmlspecialchars($productType ?? '') ?>">
			<input type="hidden" name="filter" value="<?= $filter ?>">
			<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
			<button type="submit">Search</button>
		</form>

		<form method="post" action="createtransactionpage.php?type=<?= htmlspecialchars($type) ?>">
			<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">

			<table border="1">
				<tr>
					<th>Select</th>
					<th>Name</th>
					<th>Product Type</th>
					<th>Quantity</th>
					<th>Unit Price</th>
					<th>Status</th>
					<th>Last Updated</th>
					<th>Actions</th>
				</tr>
				<?php if (!empty($inventories)): ?>
					<?php foreach ($inventories as $inventory): ?>
						<tr>
							<td>
								<input type="radio" name="selected_id" value="<?= $inventory->getId() ?>">
							</td>
							<td><?= htmlspecialchars($inventory->getProductName()) ?></td>
							<td><?= htmlspecialchars($inventory->getProductType() ?? '—') ?></td>
							<td><?= htmlspecialchars($inventory->getQuantity()) ?></td>
							<td>₱<?= number_format($inventory->getPrice(), 2) ?></td>
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
			<?php if ($totalPages > 1): ?>
				<div>
					<?php if ($page > 1): ?>
						<a href="<?= inventoryUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
					<?php endif; ?>
					<?php for ($i = 1; $i <= $totalPages; $i++): ?>
						<a href="<?= inventoryUrl(['page' => $i]) ?>" <?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
							<?= $i ?>
						</a>
					<?php endfor; ?>
					<?php if ($page < $totalPages): ?>
						<a href="<?= inventoryUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<label>Restock Quantity: </label>
			<input type="number" name="updatedQuantity" min="1" required></input><br>
			<label>Cost: </label>
			<input type="number" name="cost" step="0.01" min="1" required></input><br>
			<input type="submit" name="updateStocks" value="Update Stocks"></input>
			<input type="submit" name="cancel" value="Cancel" formnovalidate>

		</form>
	<?php elseif ($type === 'expense' || $type === 'deposit'): ?>
		<form method="post" action="createtransactionpage.php?type=<?= htmlspecialchars($type) ?>">
			<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><br>
			<label>Description</label><br>
			<input type="text" name="description" required><br><br>

			<label>Amount</label><br>
			<input type="number" name="cost" step="0.01" min="1" required><br><br>
			<button type="button" onclick="window.location.href='capitaltestpage.php'">
				Cancel
			</button>

			<button type="submit" name="createTransaction">
				Create
			</button>

		</form>
	<?php else: ?>
		<p>Please select a transaction</p>

	<?php endif; ?>

</body>

</html>
