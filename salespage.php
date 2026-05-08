<?php

include_once("database.php");
include_once("salerepo.php");
include_once("salesexport.php");
$pdo = getPDO();
$saleRepo = new SalesRepo($pdo);
$exporter = new SalesExporter();

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

$year  = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : (int)date('Y');
$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';

$total = $saleRepo->totalSales($filter, $month, $week, $year);


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
	if (isset($_POST["deleteSales"])) {
		header("Location: deletesalespage.php");
		exit();
	}
}



?>
<!DOCTYPE html>
<html>

<head>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>Sales – Bantito</title>

	<link href="https://fonts.googleapis.com/css2?family=Anton&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">

	<style>
		:root {
			--green-mid: #4a6b24;
			--green-light: #5a7a2e;
			--orange-grad: linear-gradient(135deg, #f07b3f 0%, #e8523a 100%);
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

		.toolbar {
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			padding: 16px;
			margin-bottom: 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 12px;
		}

		.filters {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
		}

		select {
			padding: 10px 12px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: #f4f6f0;
			font-family: inherit;
			outline: none;
		}

		select:focus {
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
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			overflow: hidden;
			box-shadow: 0 4px 24px rgba(0, 0, 0, .05);
			margin-bottom: 20px;
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
			text-align: left;
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

		.empty {
			text-align: center;
			padding: 30px;
			color: var(--muted);
		}

		.total-card {
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			padding: 18px;
			font-size: 18px;
			font-weight: 700;
			margin-bottom: 18px;
		}

		.export-form {
			display: flex;
			justify-content: flex-end;
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

			<div class="page-title">
				Sales
			</div>

		</div>

		<div class="content">

			<div class="toolbar">

				<form method="post" action="salespage.php">

					<input type="submit"
						name="deleteSales"
						value="Delete"
						class="btn btn-secondary">

				</form>

				<form method="get" action="salespage.php" class="filters">

					<select name="filter" onchange="this.form.submit()">

						<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>
							View All
						</option>

						<option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>
							Weekly Sales
						</option>

						<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>
							Monthly Sales
						</option>

					</select>

					<?php if ($filter === 'week' || $filter === 'month'): ?>
						<select name="month" onchange="this.form.submit()">
							<option value="">Select Month</option>
							<?php foreach ($months as $num => $name): ?>
								<option value="<?= $num ?>" <?= $month === $num ? 'selected' : '' ?>>
									<?= $name ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					<?php if ($filter === 'week'): ?>
						<select name="week" onchange="this.form.submit()">
							<option value="">Select Week</option>
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

			<div class="table-card">

				<table>

					<thead>

						<tr>

							<?php
							$columns = [
								'name'      => 'Product Name',
								'itemsSold' => 'Items Sold',
								'sale'      => 'Sale Amount',
								'dateSold'  => 'Date Sold',
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

									<button type="button"
										class="sort-btn"
										onclick="window.location='?<?= $sortLink ?>'">

										<?= $arrow ?>

									</button>

								</th>

							<?php endforeach; ?>

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

								</tr>

							<?php endforeach; ?>

						<?php else: ?>

							<tr>

								<td colspan="4" class="empty">
									No sales
								</td>

							</tr>

						<?php endif; ?>

					</tbody>

				</table>

			</div>

			<div class="total-card">

				Total Sales: ₱<?= number_format($total, 2) ?>

			</div>

			<form method="get" action="export.php" class="export-form">

				<input type="hidden" name="filter" value="<?= $filter ?>">
				<input type="hidden" name="month" value="<?= $month ?>">
				<input type="hidden" name="week" value="<?= $week ?>">
				<input type="hidden" name="year" value="<?= $year ?>">

				<button type="submit" class="btn btn-primary">
					Export Sales Report
				</button>

			</form>

		</div>

	</div>

</body>

</html>
