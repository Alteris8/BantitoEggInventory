<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");
include_once("inventory.php");

$pdo = getPDO();
$inventoryRepo = new InventoryRepo($pdo);

$sort   = $_GET['sort']   ?? 'name';
$order  = $_GET['order']  ?? 'DESC';
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

if (!$editId) {
	header("Location: inventorypage.php");
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['update_id'])) {
		try {
			$updatedInventory = new Inventory(
				(int)$_POST['update_id'],
				$_POST['name'],
				(int)$_POST['quantity'],
				(float)$_POST['price'],
			);
			$inventoryRepo->update((int)$_POST['update_id'], $updatedInventory);
		} catch (Exception $e) {
			die("Update failed: " . $e->getMessage());
		}
	}
	$sort  = $_POST['sort']  ?? 'name';
	$order = $_POST['order'] ?? 'DESC';
	header("Location: inventorypage.php?sort=$sort&order=$order");
	exit;
}

$inventory = $inventoryRepo->findById($editId);

if (!$inventory) {
	header("Location: inventorypage.php");
	exit;
}
?>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Inventory</title>
	<!--
	<style>
		:root {

			--green-dark: #3d5e1a;
			--green-mid: #4a6b24;
			--green-light: #5a7a2e;
			--orange: #e8523a;
			--orange-grad: linear-gradient(135deg, #f07b3f 0%, #e8523a 100%);
			--red-bg: #FCEBEB;
			--red-text: #A32D2D;
			--red-border: #E24B4A;
			--green-bg: #EAF3DE;
			--green-text: #3B6D11;
			--green-border: #639922;
			--bg: #f4f6f0;
			--card: #ffffff;
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
			min-height: 100vh;
			display: flex;
		}

		.sidebar {

			width: var(--sidebar-w);
			background: var(--green-mid);
			min-height: 100vh;

			display: flex;
			flex-direction: column;
			position: fixed;
			top: 0;
			left: 0;
			bottom: 0;
		}

		.logo-area {

			padding: 18px 14px;
			border-bottom: 1px solid rgba(255, 255, 255, 0.1);
		}

		.logo-box {

			background: rgba(255, 255, 255, 0.1);
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
			color: white;
		}

		.brand-name {
			font-family: 'Anton', sans-serif;
			font-size: 18px;
			color: white;
			letter-spacing: 1px;
		}

		.nav-section-label {
			padding: 16px 16px 4px;
			font-size: 10px;
			font-weight: 600;
			color: rgba(255, 255, 255, 0.35);
			text-transform: uppercase;
			letter-spacing: 0.1em;
		}

		.nav-item {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 12px 16px;
			color: rgba(255, 255, 255, 0.6);
			font-size: 13px;
			font-weight: 500;
			text-decoration: none;
			border-left: 3px solid transparent;
			transition: 0.2s;
		}

		.nav-item:hover {
			background: rgba(255, 255, 255, 0.07);
			color: white;
		}

		.nav-item.active {
			background: rgba(255, 255, 255, 0.12);
			color: white;
			border-left-color: #f39c12;
		}

		.main {
			margin-left: var(--sidebar-w);
			flex: 1;
			display: flex;
			flex-direction: column;
		}

		.topbar {
			background: var(--orange-grad);
			height: 58px;
			padding: 0 24px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			box-shadow: 0 2px 12px rgba(232, 82, 58, 0.25);
		}

		.page-title {
			font-family: 'Anton', sans-serif;
			font-size: 26px;
			color: white;
			letter-spacing: 1px;
		}

		.btn-back {
			background: rgba(255, 255, 255, 0.2);
			border: 1px solid rgba(255, 255, 255, 0.4);
			border-radius: var(--radius-sm);
			padding: 8px 14px;
			font-size: 12px;
			font-weight: 600;
			color: white;
			text-decoration: none;
			transition: 0.2s;
		}

		.btn-back:hover {
			background: rgba(255, 255, 255, 0.3);
		}

		.content {
			padding: 32px 24px;
			display: flex;
			justify-content: center;
		}

		.form-card {
			background: white;
			border: 1px solid var(--border);
			border-radius: var(--radius);
			width: 100%;
			max-width: 500px;
			box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
			overflow: hidden;
		}

		.form-card-header {
			background: var(--orange-grad);
			padding: 16px 20px;
		}

		.form-card-title {

			font-family: 'Anton', sans-serif;
			font-size: 20px;
			color: white;
			letter-spacing: 1px;
		}

		.form-body {

			padding: 22px 20px 6px;
		}

		.message {

			padding: 11px 14px;

			border-radius: var(--radius-sm);

			font-size: 13px;
			font-weight: 600;

			margin-bottom: 18px;
		}

		.message.success {

			background: var(--green-bg);
			color: var(--green-text);
			border: 1px solid var(--green-border);
		}

		.message.error {

			background: var(--red-bg);
			color: var(--red-text);
			border: 1px solid var(--red-border);
		}

		.fg {

			display: flex;
			flex-direction: column;

			gap: 5px;

			margin-bottom: 16px;
		}

		.flabel {

			font-size: 10px;
			font-weight: 600;

			color: var(--muted);

			text-transform: uppercase;
			letter-spacing: 0.07em;
		}

		.finput {

			background: #f4f6f0;

			border: 1px solid var(--border);

			border-radius: var(--radius-sm);

			padding: 10px 12px;

			font-size: 13px;

			outline: none;

			font-family: inherit;
		}

		.finput:focus {

			border-color: var(--green-light);

			box-shadow: 0 0 0 3px rgba(90, 122, 46, 0.12);

			background: white;
		}

		.prefix-wrap {

			position: relative;
		}

		.prefix-wrap .finput {

			padding-left: 24px;
		}

		.pfx {

			position: absolute;

			left: 10px;
			top: 50%;

			transform: translateY(-50%);

			font-size: 12px;
			color: var(--muted);
		}

		.frow {

			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 14px;
		}

		.divider {

			height: 1px;
			background: var(--border);

			margin: 6px 0 18px;
		}

		.info-box {

			background: #f8faf5;

			border: 1px solid var(--border);

			border-radius: var(--radius-sm);

			padding: 12px;

			margin-bottom: 18px;

			font-size: 13px;
			color: var(--muted);

			line-height: 1.7;
		}

		.form-footer {

			padding: 0 20px 20px;

			display: flex;
			gap: 10px;
		}

		.btn-cancel {

			flex: 1;

			padding: 11px;

			border-radius: var(--radius-sm);

			border: 1px solid var(--border);

			background: #f4f6f0;

			font-size: 13px;
			font-weight: 600;

			color: var(--muted);

			cursor: pointer;
		}

		.btn-save {

			flex: 2;

			padding: 11px;

			border: none;

			border-radius: var(--radius-sm);

			background: var(--orange-grad);

			color: white;

			font-size: 13px;
			font-weight: 600;

			cursor: pointer;

			box-shadow: 0 2px 8px rgba(232, 82, 58, 0.3);
		}
		-->
	</style>
</head>

<body>
	<h2>Edit Product</h2>

	<form method="post" action="editinventorypage.php?edit_id=<?= $editId ?>&sort=<?= $sort ?>&order=<?= $order ?>">
		<input type="hidden" name="update_id" value="<?= $inventory->getId() ?>">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">

		<table>
			<thead>
				<tr>
					<th>Product Name</th>
					<th>Quantity</th>
					<th>Unit Price</th>
					<th>Status</th>
					<th>Last Updated</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><input type="text" name="name" value="<?= htmlspecialchars($inventory->getProductName()) ?>" required></td>
					<td><input type="number" name="quantity" value="<?= $inventory->getQuantity() ?>" min="0" required></td>
					<td><input type="number" name="price" value="<?= $inventory->getPrice() ?>" min="0" step="0.01" required></td>
					<td><?= htmlspecialchars($inventory->getStatus()) ?></td>
					<td><?= htmlspecialchars($inventory->getDateUpdated()->format('d-m-Y')) ?></td>
					<td>
						<button type="submit">Save</button>
						<a href="inventorypage.php?sort=<?= $sort ?>&order=<?= $order ?>">
							<button type="button" formnovalidate>Cancel</button>
						</a>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</body>

</html>
