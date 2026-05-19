<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");
include_once("archiveitemrepo.php");
include_once("producttyperepo.php");

$pdo = getPDO();
$productTypeRepo = new ProductTypeRepo($pdo, $_SESSION['admin_id']);
$inventoryRepo   = new InventoryRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);
$archiveRepo     = new ArchiveItemRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);

$sort        = $_GET['sort']        ?? 'lastUpdated';
$order       = $_GET['order']       ?? 'DESC';
$nextOrder   = $order === 'ASC' ? 'DESC' : 'ASC';
$search      = $_GET['search']      ?? '';
$productType = $_GET['productType'] ?? null;
$filter      = $_GET['filter']      ?? 'all';
$error       = $_GET['error']       ?? null;
$status      = isset($_GET['status']) && is_array($_GET['status']) ? $_GET['status'] : [];
$showArchive = isset($_GET['showArchive']) && $_GET['showArchive'] === '1';

$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$week  = filter_input(INPUT_GET, 'week',  FILTER_VALIDATE_INT);
$year  = filter_input(INPUT_GET, 'year',  FILTER_VALIDATE_INT) ?? (int)date('Y');

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
	$sort  = $_POST['sort']  ?? 'lastUpdated';
	$order = $_POST['order'] ?? 'DESC';

	if (isset($_POST['backToLogin'])) {
		header("Location: admintestpage.php");
		exit();
	}
	if (isset($_POST['checkProductTypes'])) {
		header("Location: producttypespage.php");
		exit();
	}
	if (isset($_POST['create'])) {
		header("Location: createinventorypage.php");
		exit();
	}
	if (isset($_POST['edit'])) {
		header("Location: editinventorypage.php?edit_id=$selectedId&sort=$sort&order=$order");
		exit();
	}
	if (isset($_POST['restock'])) {
		header("Location: restockinventorypage.php?restock_id=$selectedId");
		exit();
	}
	if (isset($_POST['archive'])) {
		$selectedInventory = $inventoryRepo->findById($selectedId);
		$inventoryRepo->transferToArchive($selectedId, $selectedInventory);
		header("Location: inventorypage.php?sort=$sort&order=$order");
		exit();
	}
	if (isset($_POST['processSales'])) {
		$inventory = $inventoryRepo->findById($selectedId);
		if ($inventory->getQuantity() < 1) {
			header("Location: inventorypage.php?sort=$sort&order=$order&error=empty_quantity");
			exit();
		}
		header("Location: processsalespage.php?inventory_id=$selectedId&sort=$sort&order=$order");
		exit();
	}

	if (isset($_POST['return'], $_POST['return_id'])) {
		$id = (int)$_POST['return_id'];
		$returnedArchive = $archiveRepo->findById($id);
		$archiveRepo->transferToInventory($id, $returnedArchive);
		header("Location: inventorypage.php?sort=$sort&order=$order&showArchive=1");
		exit();
	}
	if (isset($_POST['delete'], $_POST['delete_id'])) {
		$archiveRepo->delete((int)$_POST['delete_id']);
		header("Location: inventorypage.php?sort=$sort&order=$order&showArchive=1");
		exit();
	}

	header("Location: inventorypage.php?sort=$sort&order=$order");
	exit();
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

if ($showArchive) {
	$total      = $archiveRepo->countFiltered($search, $productType ?? '', $filter, $month, $week, $year);
	$totalPages = (int)ceil($total / $limit);
	$items      = $archiveRepo->paginate($page, $limit, $sort, $order, $search, $productType ?? '', $filter, $month, $week, $year);
	$inventoryTotalValue = 0;
} else {
	$total      = $inventoryRepo->countFiltered($search, $productType ?? '', $filter, $month, $week, $year, $status);
	$totalPages = (int)ceil($total / $limit);
	$items      = $inventoryRepo->paginate($page, $limit, $sort, $order, $search, $productType ?? '', $filter, $month, $week, $year, $status);
	$inventoryTotalValue = $inventoryRepo->totalInventoryValue($filter, $month, $week, $year, $productType, $search, $status);
}

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
		'showArchive' => $GLOBALS['showArchive'] ? '1' : null,
	], $overrides);
	$params = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== []);
	return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title><?= $showArchive ? 'Archive' : 'Inventory' ?></title>
</head>

<body>

	<h1><?= $showArchive ? 'Archive' : 'Inventory' ?></h1>
	<?php if (!$showArchive): ?>
		<h2>Total Inventory Value: ₱<?= number_format($inventoryTotalValue, 2) ?></h2>
	<?php endif; ?>

	<?php if ($error === 'no_selection'): ?>
		<p>Please select an item first.</p>
	<?php elseif ($error === 'empty_quantity'): ?>
		<p>Item is out of stock.</p>
	<?php endif; ?>

	<!-- Search form -->
	<form method="GET">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">
		<input type="hidden" name="productType" value="<?= htmlspecialchars($productType ?? '') ?>">
		<input type="hidden" name="filter" value="<?= $filter ?>">
		<input type="hidden" name="showArchive" value="<?= $showArchive ? '1' : '0' ?>">
		<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
		<button type="submit">Search</button>
	</form>

	<!-- Filter form -->
	<form method="GET">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">
		<input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

		<!-- Show Archive toggle -->
		<label>
			<input type="checkbox" name="showArchive" value="1"
				<?= $showArchive ? 'checked' : '' ?>
				onchange="this.form.submit()">
			Show Archive
		</label>

		<?php if (!$showArchive): ?>
			<fieldset>
				<legend>Status</legend>
				<?php foreach (['Available', 'Low Stock', 'Out of Stock'] as $s): ?>
					<label>
						<input type="checkbox" name="status[]" value="<?= $s ?>"
							<?= in_array($s, $status) ? 'checked' : '' ?>
							onchange="this.form.submit()">
						<?= $s ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php endif; ?>

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
			<?php foreach ($allProductTypes as $t): ?>
				<option value="<?= htmlspecialchars($t) ?>" <?= $productType === $t ? 'selected' : '' ?>>
					<?= htmlspecialchars($t) ?>
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

	<!-- Action buttons (inventory only) -->
	<?php if (!$showArchive): ?>
		<form method="POST">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">
			<button type="submit" name="create">Create</button>
			<button type="submit" name="edit">Edit</button>
			<button type="submit" name="restock">Restock</button>
			<button type="submit" name="archive" onclick="return confirm('Archive this item?')">Archive</button>
			<button type="submit" name="processSales">Process Sales</button>
			<button type="submit" name="checkProductTypes">Edit Product Types</button>
		<?php endif; ?>

		<table border="1">
			<tr>
				<?php if (!$showArchive): ?>
					<th>Select</th>
				<?php endif; ?>
				<th>Name</th>
				<th>Product Type</th>
				<th>Quantity</th>
				<th>Unit Price</th>
				<th>Status</th>
				<th>Last Updated</th>
				<?php if ($showArchive): ?>
					<th>Actions</th>
				<?php endif; ?>
			</tr>

			<?php if (!empty($items)): ?>
				<?php foreach ($items as $item): ?>
					<tr>
						<?php if (!$showArchive): ?>
							<td><input type="radio" name="selected_id" value="<?= $item->getId() ?>"></td>
						<?php endif; ?>
						<td><?= htmlspecialchars($item->getProductName()) ?></td>
						<td><?= htmlspecialchars($item->getProductType() ?? '—') ?></td>
						<td><?= htmlspecialchars($item->getQuantity()) ?></td>
						<td>₱<?= number_format($item->getPrice(), 2) ?></td>
						<td><?= $showArchive ? 'Archived' : htmlspecialchars($item->getStatus()) ?></td>
						<td><?= $item->getDateUpdated()->format('d-m-Y') ?></td>
						<?php if ($showArchive): ?>
							<td>
								<form method="POST" style="display:inline">
									<input type="hidden" name="return_id" value="<?= $item->getId() ?>">
									<button type="submit" name="return">Return to Inventory</button>
								</form>
								<form method="POST" onsubmit="return confirm('Delete this item?')" style="display:inline">
									<input type="hidden" name="delete_id" value="<?= $item->getId() ?>">
									<button type="submit" name="delete">Delete</button>
								</form>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="8"><?= $showArchive ? 'Empty Archive' : 'Empty Inventory' ?></td>
				</tr>
			<?php endif; ?>
		</table>

		<?php if (!$showArchive): ?>
			<input type="submit" name="backToLogin" value="Back to Login">
		</form>
	<?php endif; ?>

	<!-- Pagination -->
	<?php if ($totalPages > 1): ?>
		<div>
			<?php if ($page > 1): ?>
				<a href="<?= inventoryUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
			<?php endif; ?>
			<?php for ($i = 1; $i <= $totalPages; $i++): ?>
				<a href="<?= inventoryUrl(['page' => $i]) ?>"
					<?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
					<?= $i ?>
				</a>
			<?php endfor; ?>
			<?php if ($page < $totalPages): ?>
				<a href="<?= inventoryUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($showArchive): ?>
		<button onclick="window.location='admintestpage.php'">Back to Login</button>
	<?php endif; ?>

</body>

</html>
