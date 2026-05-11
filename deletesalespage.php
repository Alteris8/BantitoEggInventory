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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$deleteId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);

	if (isset($_POST['delete']) && $deleteId) {
		$saleRepo->delete($deleteId);
		header("Location: deletesalespage.php");
		exit;
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Delete Sales</title>
</head>

<body>

	<h1>Delete Sales</h1>

	<form method="GET">

		<label>Filter</label>
		<select name="filter" onchange="this.form.submit()">
			<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
			<option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Week</option>
			<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Month</option>
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
				<?php for ($w = 1; $w <= 4; $w++): ?>
					<option value="<?= $w ?>" <?= $week == $w ? 'selected' : '' ?>>
						Week <?= $w ?>
					</option>
				<?php endfor; ?>
			</select>

		<?php endif; ?>


	</form>

	<table border="1">

		<tr>
			<th>Product</th>
			<th>Items Sold</th>
			<th>Sale</th>
			<th>Date</th>
			<th>Action</th>
		</tr>

		<?php if (!empty($sales)): ?>
			<?php foreach ($sales as $sale): ?>
				<tr>
					<td><?= htmlspecialchars($sale->getProductName()) ?></td>
					<td><?= htmlspecialchars($sale->getItemsSold()) ?></td>
					<td>₱<?= htmlspecialchars($sale->getSale()) ?></td>
					<td><?= htmlspecialchars($sale->getDate()->format('d-m-Y')) ?></td>

					<td>
						<form method="POST"
							onsubmit="return confirm('Delete this sale?')">

							<input type="hidden"
								name="delete_id"
								value="<?= $sale->getId() ?>">

							<button type="submit" name="delete">
								Delete</button>

						</form>


					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="5">No sales found</td>
			</tr>
		<?php endif; ?>

	</table>
	<button onclick="window.location='salespage.php'">Cancel </button>



</body>

</html>
