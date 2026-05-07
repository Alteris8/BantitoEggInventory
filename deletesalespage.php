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

$year  = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : (int)date('Y');
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
	if (isset($_POST['cancel'])) {
		header("Location: salespage.php");
		exit();
	}
}



?>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sales</title>
</head>

<body>
	<h2>Sales</h2>
	<form method="post" action="deletesalespage.php">
		<input type="submit" name="cancel" value="Cancel"><br>
	</form>
	<form method="get" action="deletesalespage.php">
		<select name="filter" onchange="this.form.submit()">
			<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>View All</option>
			<option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Weekly Sales</option>
			<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly Sales</option>
		</select>
		<?php if ($filter === 'week' || $filter === 'month'): ?>
			<select name="month" onchange="this.form.submit()">
				<option value="">Select Month</option>
				<?php foreach ($months as $num => $name): ?>
					<option value="<?= $num ?>" <?= $month === $num ? 'selected' : ($filter !== 'all' && $num === $currentMonth ? 'selected' : '') ?>><?= $name ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<?php if ($filter === 'week'): ?>
			<select name="week" onchange="this.form.submit()">
				<option value="">Select Week</option>
				<?php for ($w = 1; $w <= 4; $w++): ?>
					<option value="<?= $w ?>" <?= $week === $w ? 'selected' : ($filter !== 'week' && $num === $currentWeek ? 'selected' : '') ?>>Week <?= $w ?></option>
				<?php endfor; ?>
			</select>
		<?php endif; ?>
		<input type="hidden" name="year" value="<?= $year ?>">

		</select>

	</form>
	<table>
		<thead>
			<tr>
				<?php
				$columns = [
					'name'        => 'Product Name',
					'itemsSold'   => 'Items Sold',
					'sale'        => 'Sale Amount',
					'dateSold'    => 'Date Sold',
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
						<h3><?= $label ?></h3>
						<button type="button" onclick="window.location='?<?= $sortLink ?>'" value="<?= $arrow ?>"><?= $arrow ?></button>
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
						<td><?= htmlspecialchars($sale->getSale()) ?></td>
						<td><?= htmlspecialchars($sale->getDate()->format('d-m-Y')) ?></td>
						<td>
							<form method="POST" action="" onsubmit="return confirm('Delete this sale record?')">

								<input type='hidden' name='delete_id' value="<?= $sale->getId() ?>">
								<button type='submit' name='delete'>
									Delete
								</button>
							</form>
						</td>

					</tr>

				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="4">No sales</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</body>


</html>
