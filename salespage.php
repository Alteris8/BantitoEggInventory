<?php
session_start();
include_once("database.php");
include_once("salerepo.php");

$pdo = getPDO();
$saleRepo = new SalesRepo($pdo, $_SESSION['admin_id']);

$sort   = $_GET['sort'] ?? 'dateSold';
$order  = $_GET['order'] ?? 'DESC';
$filter = $_GET['filter'] ?? 'all';

$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$week  = filter_input(INPUT_GET, 'week', FILTER_VALIDATE_INT);
$year  = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?? (int)date('Y');

$currentMonth = (int)date('m');
$currentWeek  = (int)ceil(date('j') / 7);

if (!$month && ($filter === 'week' || $filter === 'month')) {
	$month = $currentMonth;
}

if (!$week && $filter === 'week') {
	$week = $currentWeek;
}

$nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';

$saleTotal = $saleRepo->totalSales($filter, $month, $week, $year);

switch ($filter) {

	case 'week':
		$sales = ($month && $week && $year)
			? $saleRepo->findSalesByMonthWeek($month, $week, $year, $sort, $order)
			: [];
		break;

	case 'month':
		$sales = $saleRepo->findSalesByMonth($month ?? (int)date('m'), $year, $sort, $order);
		break;
	case 'now':
		$sales = $saleRepo->findToday($sort, $order);
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
	12 => 'December'
];

$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total      = $saleRepo->countFiltered($filter, $month, $week, $year);
$totalPages = (int)ceil($total / $limit);
$sales = $saleRepo->paginate($page, $limit, $sort, $order, $filter, $month, $week, $year);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	if (isset($_POST['deleteSales'])) {
		header("Location: deletesalespage.php");
		exit;
	}
	if (isset($_POST['backToLogin'])) {
		header("Location: admintestpage.php");
		exit;
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Sales</title>
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

	<h1>Sales</h1>

	<form method="POST">
		<button type="submit" name="deleteSales">Delete</button>
	</form>

	<form method="GET">

		<label>Filter</label>
		<select name="filter" onchange="this.form.submit()">
			<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
			<option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Weekly</option>
			<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly</option>
			<option value="now" <?= $filter === 'now' ? 'selected' : '' ?>>Today</option>
		</select>

		<?php if ($filter === 'month' || $filter === 'week'): ?>
			<select name="month" onchange="this.form.submit()">
				<option value="">Select Month</option>
				<?php foreach ($months as $num => $name): ?>
					<option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>>
						<?= $name ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="year" onchange="this.form.submit()">
				<?php for ($y = date('Y'); $y >= 2020; $y--): ?>
					<option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
						<?= $y ?>
					</option>
				<?php endfor; ?>
			</select>
		<?php endif; ?>

		<?php if ($filter === 'week'): ?>

			<label>Week</label>
			<select name="week" onchange="this.form.submit()">
				<option value="">Select</option>
				<?php for ($w = 1; $w <= 4; $w++): ?>
					<option value="<?= $w ?>" <?= $week == $w ? 'selected' : '' ?>>
						Week <?= $w ?>
					</option>
				<?php endfor; ?>
			</select>

		<?php endif; ?>


	</form>

	<h2>Total Sales: ₱<?= number_format($saleTotal, 2) ?></h2>

	<table border="1">

		<tr>
			<th>Product Name</th>
			<th>Items Sold</th>
			<th>Sale Amount</th>
			<th>Date Sold</th>
		</tr>

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
				<td colspan="4">No sales</td>
			</tr>
		<?php endif; ?>

	</table>
	<div>
		<?php if ($page > 1): ?>
			<a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>">Previous</a>
		<?php endif; ?>

		<?php for ($i = 1; $i <= $totalPages; $i++): ?>
			<a href="?page=<?= $i ?>&filter=<?= $filter ?>"
				<?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
				<?= $i ?>
			</a>
		<?php endfor; ?>

		<?php if ($page < $totalPages): ?>
			<a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>">Next</a>
		<?php endif; ?>
	</div>

	<button onclick="window.location='admintestpage.php'">Back to Login</button>

</body>

</html>
