<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");

$pdo = getPDO();
$inventoryRepo = new InventoryRepo($pdo, $_SESSION['admin_id']);

$sort  = $_GET['sort']  ?? 'name';
$order = $_GET['order'] ?? 'DESC';
$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$error = $_GET['error'] ?? null;

$search = $_GET['search'] ?? "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$selectedId = isset($_POST['selected_id']) ? (int)$_POST['selected_id'] : null;
	$sort  = $_POST['sort'] ?? 'name';
	$order = $_POST['order'] ?? 'DESC';
	if (isset($_POST['backToLogin'])) {
		header("Location: admintestpage.php");
		exit();
	}

	if (isset($_POST['create'])) {
		header("Location: createinventorypage.php");
		exit();
	}

	if (!$selectedId && isset($_POST['edit'], $_POST['delete'], $_POST['processSales'])) {
		header("Location: inventorypage.php?sort=$sort&order=$order&error=no_selection");
		exit();
	}

	if (isset($_POST['edit'])) {
		header("Location: editinventorypage.php?edit_id=$selectedId&sort=$sort&order=$order");
		exit();
	}

	if (isset($_POST['delete'])) {
		$inventoryRepo->delete($selectedId);
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

	header("Location: inventorypage.php?sort=$sort&order=$order");
	exit();
}

if ($search !== "") {
	$inventories = $inventoryRepo->searchInventory($search);
} else {
	$inventories = $inventoryRepo->findAll($sort, $order);
}
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total      = $inventoryRepo->countFiltered($search);
$totalPages = (int)ceil($total / $limit);
$inventories = $inventoryRepo->paginate($page, $limit, $sort, $order, $search);

?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Inventory</title>
	<style>
		:root {
			--green-mid: #4a6b24;
			--green-light: #5a7a2e;
			--orange-grad: linear-gradient(135deg, #f07b3f 0%, #e8523a 100%);
			--red-bg: #FCEBEB;
			--red-text: #A32D2D;
			--red-border: #E24B4A;
			--bg: #f4f6f0;
			--card: #fff;
			--border: #e2e8d8;
			--text: #1a2410;
			--muted: #6b7c5a;
			--sidebar-w: 300px;
			--radius: 12px;
			--radius-sm: 8px;
		}

		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		body {
			font-family: 'Plus Jakarta Sans', sans-serif;
			background: var(--bg);
			color: var(--text);
			display: flex;
			min-height: 100vh;
		}

		.sidebar {
			width: var(--sidebar-w);
			background: var(--green-mid);
			position: fixed;
			top: 0;
			left: 0;
			bottom: 0;
		}

		.logo-area {
			padding: 18px 14px;
			border-bottom: 1px solid rgba(255, 255, 255, .1);
		}

		.logo-box {
			background: rgba(255, 255, 255, .1);
			border-radius: var(--radius-sm);
			padding: 10px 12px;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.logo-circle {
			width: 34px;
			height: 34px;
			border-radius: 50%;
			background: #c0392b;
			display: flex;
			align-items: center;
			justify-content: center;
			font-family: 'Anton', sans-serif;
			font-size: 14px;
			color: #fff;
		}

		.brand-name {
			font-family: 'Anton', sans-serif;
			font-size: 30px;
			color: #fff;
			letter-spacing: 1px;
		}

		.nav-section-label {
			padding: 16px 16px 4px;
			font-size: 10px;
			font-weight: 600;
			color: rgba(255, 255, 255, .35);
			text-transform: uppercase;
			letter-spacing: .1em;
		}

		.nav-item {
			display: flex;
			align-items: center;
			padding: 12px 16px;
			font-size: 20px;
			font-weight: 500;
			color: rgba(255, 255, 255, .6);
			text-decoration: none;
			border-left: 3px solid transparent;
			transition: .2s;
		}

		.nav-item:hover {
			background: rgba(255, 255, 255, .07);
			color: #fff;
		}

		.nav-item.active {
			background: rgba(255, 255, 255, .12);
			color: #fff;
			border-left-color: #f39c12;
		}

		.main {
			margin-left: var(--sidebar-w);
			flex: 1;
			display: flex;
			flex-direction: column;
		}

		.topbar {
			height: 80px;
			padding: 0 24px;
			background: var(--orange-grad);
			display: flex;
			align-items: center;
			justify-content: space-between;
			box-shadow: 0 2px 12px rgba(232, 82, 58, .25);
		}

		.page-title {
			font-family: 'Anton', sans-serif;
			font-size: 40px;
			color: #fff;
			letter-spacing: 1px;
		}

		.content {
			padding: 24px;
		}

		.alert {
			padding: 12px 14px;
			border-radius: var(--radius-sm);
			font-size: 13px;
			font-weight: 600;
			margin-bottom: 18px;
		}

		.alert.error {
			background: var(--red-bg);
			color: var(--red-text);
			border: 1px solid var(--red-border);
		}

		.toolbar,
		.table-card {
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
		}

		.toolbar {
			padding: 16px;
			margin-bottom: 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
		}

		.search-form,
		.actions {
			display: flex;
			gap: 10px;
		}

		.search-input {
			width: 250px;
			padding: 10px 12px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: #f4f6f0;
			outline: none;
		}

		.search-input:focus {
			border-color: var(--green-light);
			background: #fff;
		}

		.btn {
			padding: 10px 16px;
			border: none;
			border-radius: var(--radius-sm);
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			transition: .2s;
		}

		.btn:hover {
			opacity: .9;
		}

		.btn-primary {
			background: var(--orange-grad);
			color: #fff;
		}

		.btn-secondary {
			background: #f4f6f0;
			border: 1px solid var(--border);
			color: var(--muted);
		}

		.table-card {
			overflow: hidden;
			box-shadow: 0 4px 24px rgba(0, 0, 0, .05);
		}

		table {
			width: 100%;
			border-collapse: collapse;
		}

		thead {
			background: #f8faf5;
		}

		th,
		td {
			padding: 14px;
			border-bottom: 1px solid #edf2e5;
			font-size: 13px;
		}

		th {
			font-size: 12px;
			font-weight: 700;
			color: var(--muted);
			text-transform: uppercase;
			letter-spacing: .08em;
		}

		tr:hover {
			background: #fafcf7;
		}

		.sort-btn {
			background: none;
			border: none;
			cursor: pointer;
			font-size: 12px;
			color: var(--muted);
			margin-left: 4px;
		}

		.status {
			display: inline-block;
			padding: 6px 10px;
			border-radius: 999px;
			font-size: 11px;
			font-weight: 700;
		}

		.status.in-stock {
			background: #eef6e6;
			color: #4f7a1e;
		}

		.status.low-stock {
			background: #fff3e0;
			color: #ff9800;
		}

		.status.out-of-stock {
			background: #ffebee;
			color: #f44336;
		}

		.empty {
			text-align: center;
			padding: 30px;
			color: var(--muted);
		}
	</style>
</head>

<body>

	<h1>Inventory</h1>

	<?php if ($error === 'no_selection'): ?>
		<p>Please select an item first.</p>
	<?php elseif ($error === 'empty_quantity'): ?>
		<p>Item is out of stock.</p>
	<?php endif; ?>

	<form method="GET">
		<input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
		<button type="submit">Search</button>
	</form>

	<form method="POST">

		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">

		<button type="submit" name="create">Create</button>
		<button type="submit" name="edit">Edit</button>
		<button type="submit" name="delete" onclick="return confirm('Delete this item?')">Delete</button>
		<button type="submit" name="processSales">Process Sales</button>

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
		<input type="submit" name="backToLogin" value="Back to Login">

	</form>
	<div>
		<?php if ($page > 1): ?>
			<a href="?page=<?= $page - 1 ?>">Previous</a>
		<?php endif; ?>

		<?php for ($i = 1; $i <= $totalPages; $i++): ?>
			<a href="?page=<?= $i ?>"
				<?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
				<?= $i ?>
			</a>
		<?php endfor; ?>

		<?php if ($page < $totalPages): ?>
			<a href="?page=<?= $page + 1 ?>">Next</a>
		<?php endif; ?>
	</div>

</body>

</html>
