<?php
include_once("database.php");
include_once("salerepo.php");

$pdo = getPDO();
$saleRepo = new SalesRepo($pdo);

$sort = $_GET['sort'] ?? 'dateSold';
$order = $_GET['order'] ?? 'DESC';
$filter = $_GET['filter'] ?? 'all';
$currentMonth = (int)date('m');
$currentWeek = (int)date((int)date('j') / 7);

$month = isset($_GET['month']) && $_GET['month'] !== ''
	? (int)$_GET['month']
	: ($filter === 'week' || $filter === 'month' ? $currentMonth : null);

$week = isset($_GET['week']) && $_GET['week'] !== ''
	? (int)$_GET['week']
	: ($filter === 'week' ? $currentWeek : null);

$year = isset($_GET['year']) && $_GET['year'] !== ''
	? (int)$_GET['year']
	: (int)date('Y');

$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';

switch ($filter) {
	case 'week':
		if ($month && $week && $year) {
			$sales = $saleRepo->findSalesByMonthWeek($month, $week, $year, $sort, $order);
		} else {
			$sales = [];
		}
		break;

	case 'month':
		$sales = $saleRepo->findSalesByMonth($month ?? (int)date('m'), $year, $sort, $order);
		break;

	default:
		$sales = $saleRepo->findAll($sort, $order);
		break;
}

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
	12 => 'December',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['delete']) && isset($_POST['delete_id'])) {
		$id = (int)$_POST['delete_id'];
		$saleRepo->delete($id);

		header("Location: " . $_SERVER['PHP_SELF']);
		exit;
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Delete Sales – Bantito</title>

	<link href="https://fonts.googleapis.com/css2?family=Anton&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet" />

	<style>
		:root {
			--green-dark: #3d5e1a;
			--green-mid: #4a6b24;
			--green-light: #5a7a2e;
			--orange: #e8523a;
			--orange-grad: linear-gradient(135deg, #f07b3f 0%, #e8523a 100%);
			--bg: #f4f6f0;
			--card: #ffffff;
			--border: #e2e8d8;
			--text: #1a2410;
			--muted: #6b7c5a;
			--sidebar-w: 300px;
			--radius: 12px;
			--radius-sm: 8px;
			--danger: #d63031;
		}

		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
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
			color: white;
			font-family: 'Anton', sans-serif;
		}

		.brand-name {
			font-family: 'Anton', sans-serif;
			color: white;
			font-size: 30px;
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
			font-size: 20px;
			font-weight: 500;
			border-left: 3px solid transparent;
			transition: all 0.2s;
			text-decoration: none;
		}

		.nav-item:hover,
		.nav-item.active {
			background: rgba(255, 255, 255, 0.1);
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
			background: var(--orange-grad);
			height: 80px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0 24px;
			box-shadow: 0 2px 12px rgba(232, 82, 58, 0.25);
		}

		.page-title {
			font-family: 'Anton', sans-serif;
			font-size: 40px;
			color: white;
			letter-spacing: 1px;
		}

		.btn-back {
			background: rgba(255, 255, 255, 0.2);
			border: 1px solid rgba(255, 255, 255, 0.4);
			border-radius: var(--radius-sm);
			padding: 8px 16px;
			color: white;
			text-decoration: none;
			font-size: 17px;
			font-weight: 600;
		}

		.content {
			padding: 30px 24px;
		}

		.table-card {
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			overflow: hidden;
			box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
		}

		.table-header {
			background: var(--orange-grad);
			color: white;
			padding: 18px 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
		}

		.table-title {
			font-family: 'Anton', sans-serif;
			font-size: 20px;
			letter-spacing: 1px;
		}

		.filter-form {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
		}

		select {
			padding: 10px 12px;
			border-radius: var(--radius-sm);
			border: 1px solid var(--border);
			font-family: inherit;
			background: white;
		}

		table {
			width: 100%;
			border-collapse: collapse;
		}

		th {
			background: #f7f9f3;
			padding: 14px;
			text-align: left;
			font-size: 12px;
			text-transform: uppercase;
			color: var(--muted);
			border-bottom: 1px solid var(--border);
		}

		td {
			padding: 16px 14px;
			border-bottom: 1px solid var(--border);
			font-size: 14px;
		}

		tr:hover {
			background: #fafcf7;
		}

		.sort-btn {
			border: none;
			background: transparent;
			cursor: pointer;
			margin-left: 4px;
			color: var(--green-mid);
			font-size: 12px;
		}

		.delete-btn {
			border: none;
			background: var(--danger);
			color: white;
			padding: 8px 14px;
			border-radius: var(--radius-sm);
			cursor: pointer;
			font-size: 12px;
			font-weight: 600;
		}

		.delete-btn:hover {
			opacity: 0.9;
		}

		.empty {
			text-align: center;
			padding: 30px;
			color: var(--muted);
		}

		@media (max-width: 768px) {
			.sidebar {
				display: none;
			}

			.main {
				margin-left: 0;
			}

			.table-header {
				flex-direction: column;
				align-items: stretch;
			}

			table {
				display: block;
				overflow-x: auto;
			}
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

			<a class="nav-item" href="inventorypage.php">
				Inventory
			</a>

			<a class="nav-item active" href="salespage.php">
				Sales
			</a>
		</div>
	</div>

	<div class="main">

		<div class="topbar">
			<div class="page-title">Delete Sales</div>

			<a class="btn-back" href="salespage.php">
				Back to Sales
			</a>
		</div>

		<div class="content">

			<div class="table-card">

				<div class="table-header">
					<div class="table-title">Sales Records</div>

					<form method="GET" class="filter-form">

						<select name="filter" onchange="this.form.submit()">
							<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>View All</option>
							<option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Weekly Sales</option>
							<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly Sales</option>
						</select>

						<?php if ($filter === 'week' || $filter === 'month'): ?>
							<select name="month" onchange="this.form.submit()">
								<?php foreach ($months as $num => $name): ?>
									<option value="<?= $num ?>" <?= $month === $num ? 'selected' : '' ?>>
										<?= $name ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>

						<?php if ($filter === 'week'): ?>
							<select name="week" onchange="this.form.submit()">
								<?php for ($w = 1; $w <= 4; $w++): ?>
									<option value="<?= $w ?>" <?= $week === $w ? 'selected' : '' ?>>
										Week <?= $w ?>
									</option>
								<?php endfor; ?>
							</select>
						<?php endif; ?>

						<input type="hidden" name="year" value="<?= $year ?>">
					</form>
				</div>

				<table>
					<thead>
						<tr>
							<?php
							$columns = [
								'name' => 'Product Name',
								'itemsSold' => 'Items Sold',
								'sale' => 'Sale Amount',
								'dateSold' => 'Date Sold',
							];

							foreach ($columns as $col => $label):
								$arrow = ($sort === $col)
									? ($order === 'ASC' ? '▲' : '▼')
									: '↕';

								$sortLink = http_build_query([
									'filter' => $filter,
									'sort' => $col,
									'order' => $sort === $col ? $nextOrder : 'ASC',
									'month' => $month,
									'week' => $week,
									'year' => $year,
								]);
							?>

								<th>
									<?= $label ?>
									<button class="sort-btn"
										onclick="window.location='?<?= $sortLink ?>'"
										type="button">
										<?= $arrow ?>
									</button>
								</th>

							<?php endforeach; ?>

							<th>Action</th>
						</tr>
					</thead>

					<tbody>
						<?php if (!empty($sales)): ?>

							<?php foreach ($sales as $sale): ?>
								<tr>
									<td><?= htmlspecialchars($sale->getProductName()) ?></td>
									<td><?= htmlspecialchars($sale->getItemsSold()) ?></td>
									<td>₱<?= htmlspecialchars($sale->getSale()) ?></td>
									<td><?= htmlspecialchars($sale->getDate()->format('d-m-Y')) ?></td>

									<td>
										<form method="POST"
											onsubmit="return confirm('Delete this sale record?')">

											<input type="hidden"
												name="delete_id"
												value="<?= $sale->getId() ?>">

											<button type="submit"
												name="delete"
												class="delete-btn">
												Delete
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>

						<?php else: ?>

							<tr>
								<td colspan="5" class="empty">
									No sales records found.
								</td>
							</tr>

						<?php endif; ?>
					</tbody>
				</table>

			</div>
		</div>
	</div>

</body>

</html>
