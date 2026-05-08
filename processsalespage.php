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

<!DOCTYPE html>
<html>

<head>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>Process Sale – Bantito</title>

	<link href="https://fonts.googleapis.com/css2?family=Anton&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">

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
			padding: 28px 24px;
			display: flex;
			justify-content: center;
		}

		.card {
			width: 100%;
			max-width: 520px;
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			overflow: hidden;
			box-shadow: 0 4px 24px rgba(0, 0, 0, .05);
		}

		.card-header {
			background: var(--orange-grad);
			padding: 16px 20px;
		}

		.card-title {
			font-family: 'Anton', sans-serif;
			font-size: 20px;
			color: #fff;
			letter-spacing: 1px;
		}

		.card-body {
			padding: 20px;
		}

		.alert {
			padding: 12px 14px;
			border-radius: var(--radius-sm);
			font-size: 13px;
			font-weight: 600;
			margin-bottom: 18px;
			background: var(--red-bg);
			color: var(--red-text);
			border: 1px solid var(--red-border);
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}

		th,
		td {
			padding: 14px;
			border-bottom: 1px solid #edf2e5;
			font-size: 13px;
			text-align: left;
		}

		th {
			font-size: 12px;
			font-weight: 700;
			color: var(--muted);
			text-transform: uppercase;
			letter-spacing: .08em;
			background: #f8faf5;
		}

		.fg {
			display: flex;
			flex-direction: column;
			gap: 6px;
			margin-bottom: 18px;
		}

		.flabel {
			font-size: 10px;
			font-weight: 700;
			color: var(--muted);
			text-transform: uppercase;
			letter-spacing: .08em;
		}

		.finput {
			padding: 10px 12px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: #f4f6f0;
			outline: none;
			font-family: inherit;
		}

		.finput:focus {
			border-color: var(--green-light);
			background: #fff;
		}

		.actions {
			display: flex;
			gap: 10px;
		}

		.btn {
			flex: 1;
			padding: 11px;
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
	</style>

</head>

<body>

	<div class="sidebar">

		<div class="logo-area">

			<div class="logo-box">

				<div class="logo-circle">B</div>

				<span class="brand-name">Bantito</span>

			</div>

		</div>

		<div style="padding-top:8px;">

			<div class="nav-section-label">Main</div>

			<a class="nav-item" href="home.php">
				Overview
			</a>

			<div class="nav-section-label">Manage</div>

			<a class="nav-item active" href="inventorypage.php">
				Inventory
			</a>

			<a class="nav-item" href="salespage.php">
				Sales
			</a>

		</div>

	</div>

	<div class="main">

		<div class="topbar">

			<div class="page-title">
				Process Sale
			</div>

		</div>

		<div class="content">

			<div class="card">

				<div class="card-header">

					<div class="card-title">
						Confirm Sale
					</div>

				</div>

				<div class="card-body">

					<?php if (isset($error)): ?>

						<div class="alert">
							<?= htmlspecialchars($error) ?>
						</div>

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
							<td>₱<?= $inventory->getPrice() ?></td>
						</tr>

					</table>

					<form method="post" action="processsalespage.php?inventory_id=<?= $inventoryId ?>&sort=<?= $sort ?>&order=<?= $order ?>">

						<div class="fg">

							<label class="flabel" for="amount">
								Quantity to Sell
							</label>

							<input class="finput"
								type="number"
								name="amount"
								id="amount"
								min="1"
								max="<?= $inventory->getQuantity() ?>"
								required>

						</div>

						<div class="actions">

							<button type="submit" class="btn btn-primary">
								Confirm Sale
							</button>

							<button type="button"
								class="btn btn-secondary"
								onclick="window.location='inventorypage.php?sort=<?= $sort ?>&order=<?= $order ?>'">

								Cancel

							</button>

						</div>

					</form>

				</div>

			</div>

		</div>

	</div>

</body>

</html>
