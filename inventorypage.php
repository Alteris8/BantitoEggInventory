<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");
$pdo = getPDO();
$inventoryRepo = new InventoryRepo($pdo);

$sort      = $_GET['sort']  ?? 'name';
$order     = $_GET['order'] ?? 'DESC';
$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$error     = $_GET['error'] ?? null;

$search = isset($_GET['search']) ? $_GET['search'] : "";

if ($search !== "") {
	$inventories = $inventoryRepo->searchInventory($search);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$selectedId = isset($_POST['selected_id']) ? (int)$_POST['selected_id'] : null;
	$sort       = $_POST['sort']  ?? 'name';
	$order      = $_POST['order'] ?? 'DESC';

	if (isset($_POST['create'])) {
		header("Location: createinventorypage.php");
		exit();
	}

	if (isset($_POST['edit']) || isset($_POST['delete']) || isset($_POST['processSales'])) {
		if (!$selectedId) {
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
			} else {
				header("Location: processsalespage.php?inventory_id=$selectedId&sort=$sort&order=$order");
				exit();
			}
		}
	}

	if (isset($_POST['processSales'])) {
		header("Location: processsalespage.php");
		exit;
	}

	header("Location: inventorypage.php?sort=$sort&order=$order");
	exit;
}
if ($search !== "") {
	$inventories = $inventoryRepo->searchInventory($search);
} else {
	$inventories = $inventoryRepo->findAll($sort, $order);
}

?>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Inventory</title>
</head>

<body>
	<h2>Inventory</h2>

	<?php if ($error === 'no_selection'): ?>
		<p style="color:red;">Please select an item first.</p>
	<?php elseif ($error === 'empty_quantity'): ?>
		<p style="color:red;">Item is out of stock</p>
	<?php endif; ?>
	<form method="GET">
		<input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>">
		<button type="submit">Search</button>
	</form>

	<form method="post" action="inventorypage.php">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">

		<input type="submit" name="create" value="Create">
		<input type="submit" name="edit" value="Edit">
		<input type="submit" name="delete" value="Delete" onclick="return confirm('Delete this item?')">
		<input type="submit" name="processSales" value="Process Sales">

		<table>
			<thead>
				<tr>
					<th>Select</th>
					<?php
					$columns = [
						'name'        => 'Product Name',
						'quantity'    => 'Quantity',
						'price'       => 'Unit Price',
						'status'      => 'Status',
						'lastUpdated' => 'Last Updated',
					];
					foreach ($columns as $col => $label):
						$arrow = ($sort === $col)
							? ($order === 'ASC' ? '▲' : '▼')
							: '↕';
						$sortLink = http_build_query([
							'sort'  => $col,
							'order' => $sort === $col ? $nextOrder : 'ASC',
						]);
					?>
						<th>
							<h3><?= $label ?></h3>
							<button type="button" onclick="window.location='?<?= $sortLink ?>'"><?= $arrow ?></button>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php if (!empty($inventories)): ?>
					<?php foreach ($inventories as $inventory): ?>
						<tr>
							<td>
								<input type="radio" name="selected_id" value="<?= $inventory->getId() ?>">
							</td>
							<td><?= htmlspecialchars($inventory->getProductName()) ?></td>
							<td><?= htmlspecialchars($inventory->getQuantity()) ?></td>
							<td><?= htmlspecialchars($inventory->getPrice()) ?></td>
							<td><?= htmlspecialchars($inventory->getStatus()) ?></td>
							<td><?= htmlspecialchars($inventory->getDateUpdated()->format('d-m-Y')) ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="6">Empty inventory</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</form>
</body>

</html>
