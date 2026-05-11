<?php
session_start();

include_once("database.php");
include_once("capitalrepo.php");
include_once("capital.php");
include_once("capitaltransactionsrepo.php");

$pdo = getPDO();
$capitalRepo = new CapitalRepo($pdo, $_SESSION['admin_id']);
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$capital = $capitalRepo->findById($_SESSION['admin_id']);
if ($capital === null) {
	$capitalRepo->save(new Capital(0, $_SESSION['admin_id']));
	$capital = $capitalRepo->findById($_SESSION['admin_id']);
}

$sort   = $_GET['sort'] ?? 'createdAt';
$order  = $_GET['order'] ?? 'DESC';
$filter = $_GET['filter'] ?? 'all';
$type = $_GET['type'] ?? null;
$error = $_GET['error'] ?? null;

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
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByMonthWeek($month, $week, $year, $sort, $order, $type);
		break;
	case 'month':
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByMonth($month, $year, $sort, $order, $type);
		break;
	case 'now':
		$transactions = $capitalTransactionsRepo->findCapitalTransactionsByToday($sort, $order, $type);
		break;
	default:
		$transactions = $capitalTransactionsRepo->findAll($sort, $order, $type);
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
$total      = $capitalTransactionsRepo->countFiltered($type, $filter, $month, $week, $year);
$totalPages = (int)ceil($total / $limit);
$transactions = $capitalTransactionsRepo->paginate($page, $limit, $sort, $order, $type, $filter, $month, $week, $year);
$transactionTotalRows = $capitalTransactionsRepo->calculateIncomeSummary();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$newCapitalAmount = filter_input(
		INPUT_POST,
		"newCapitalAmount",
		FILTER_SANITIZE_NUMBER_FLOAT,
		FILTER_FLAG_ALLOW_FRACTION
	);

	$selectedId = isset($_POST['selected_id']) ? (int)$_POST['selected_id'] : null;
	$sort  = $_POST['sort'] ?? 'name';
	$order = $_POST['order'] ?? 'DESC';
	if (isset($_POST['capitalSubmit'])) {
		$newCapital = new Capital(
			(float)$newCapitalAmount,
			$capital->getInitialBalance(),
			$capital->getId(),
			$_SESSION['admin_id']
		);

		if ($capital->getBalance() == 0) {
			$capitalRepo->save($newCapital);
		} else {
			$capitalRepo->update($newCapital);
		}
		header("Location: " . $_SERVER['PHP_SELF']);
		exit();
	}
	if (isset($_POST['backToLogin'])) {
		header("Location: admintestpage.php");
		exit();
	}
	if (isset($_POST['create'])) {
		header("Location: createtransactionpage.php");
		exit();
	}
	if (isset($_POST['delete']) && isset($_POST['delete_id'])) {
		$id = (int)$_POST['delete_id'];
		$capitalTransactionsRepo->delete($id);
		header("Location: " . $_SERVER['PHP_SELF']);
		exit();
	}


	header("Location: capitaltestpage.php?sort=$sort&order=$order&type=$type");
	exit();
}



?>
<html>

<body>

	<?php if ($capital->getBalance() == 0): ?>
		<form method="post" action="capitaltestpage.php">
			<label>Input hands-on money: </label>
			<input type="number" name="newCapitalAmount" step="0.01" min="0" required><br><br>
			<input type="submit" name="capitalSubmit" value="Submit">
		</form>
	<?php else: ?>
		<form method="post" action="capitaltestpage.php">
			<div id="viewMode">
				<label>Balance: PHP<?= htmlspecialchars($capital->getBalance()) ?></label>
				<button type="button" onclick="toggleEdit(true)">Edit</button>
			</div>

			<div id="editMode" style="display:none;">
				<label>Balance: PHP</label>
				<input type="number" name="newCapitalAmount" step="0.01" min="0"
					value="<?= htmlspecialchars($capital->getBalance()) ?>" required>
				<button type="submit" name="capitalSubmit">Save</button>
				<button type="button" onclick="toggleEdit(false)">Cancel</button>
			</div>
		</form>

		<script>
			function toggleEdit(editing) {
				document.getElementById('viewMode').style.display = editing ? 'none' : 'block';
				document.getElementById('editMode').style.display = editing ? 'block' : 'none';
			}
		</script>
		<h1>Capital</h1>
		<label>Total Sales: PHP<?= htmlspecialchars($transactionTotalRows['totalSales']) ?></label><br>
		<label>Total Deposits: PHP<?= htmlspecialchars($transactionTotalRows['totalDeposits']) ?></label><br>
		<label>Total Expenses: PHP<?= htmlspecialchars($transactionTotalRows['totalExpenses']) ?></label><br>
		<label>Total Restocks: PHP<?= htmlspecialchars($transactionTotalRows['totalRestocks']) ?></label><br>
		<label>Total Income: PHP<?= htmlspecialchars($transactionTotalRows['netIncome']) ?></label><br>

		<?php if ($error === 'no_selection'): ?>
			<p>Please select an transaction first.</p>
		<?php endif; ?>
		<form method="get" action="capitaltestpage.php">
			<label>Filter</label>
			<select name="filter" onchange="this.form.submit()">
				<option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
				<option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Weekly</option>
				<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly</option>
				<option value="now" <?= $filter === 'now' ? 'selected' : '' ?>>Today</option>
			</select>
			<select name="type" onchange="this.form.submit()">
				<option value="">All Types</option>
				<option value="sale" <?= $type === 'sale'    ? 'selected' : '' ?>>Sales</option>
				<option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Expenses</option>
				<option value="restock" <?= $type === 'restock' ? 'selected' : '' ?>>Restocks</option>
				<option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>Deposits</option>
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

		<form method="POST">

			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">

			<button type="submit" name="create">Create</button>

		</form>

		<table border="1">

			<tr>
				<th>Type</th>
				<th>Amount</th>
				<th>Description</th>
				<th>Created At</th>
			</tr>

			<?php if (!empty($transactions)): ?>
				<?php foreach ($transactions as $transaction): ?>
					<tr>
						<td><?= htmlspecialchars($transaction->getType()) ?></td>
						<td>₱<?= htmlspecialchars($transaction->getAmount()) ?></td>
						<td><?= htmlspecialchars($transaction->getDescription()) ?></td>
						<td><?= $transaction->getCurrentDate()->format('d-m-Y') ?></td>
						<td>
							<form method="post" action="capitaltestpage.php" onsubmit="return confirm('Delete transaction?')" style="display: inline;">
								<input type="hidden" name="delete_id" value="<?= $transaction->getId() ?>">
								<input type="submit" name="delete" value="Delete">
							</form>

						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="6">Empty transactions</td>
				</tr>
			<?php endif; ?>

		</table>
		<form method="post" action="capitaltestpage.php">
			<input type="submit" name="backToLogin" value="Back to Login">
		</form>

		<form method="GET" action="export.php">

			<input type="hidden" name="filter" value="<?= $filter ?>">
			<input type="hidden" name="month" value="<?= $month ?>">
			<input type="hidden" name="week" value="<?= $week ?>">
			<input type="hidden" name="year" value="<?= $year ?>">
			<input type="hidden" name="type" value="<?= htmlspecialchars($type ?? '') ?>">
			<input type="hidden" name="balance" value="<?= $capital->getBalance() ?>">
			<input type="hidden" name="totalSales" value="<?= $transactionTotalRows['totalSales'] ?>">
			<input type="hidden" name="totalDeposits" value="<?= $transactionTotalRows['totalDeposits'] ?>">
			<input type="hidden" name="totalExpenses" value="<?= $transactionTotalRows['totalExpenses'] ?>">
			<input type="hidden" name="totalRestocks" value="<?= $transactionTotalRows['totalRestocks'] ?>">
			<input type="hidden" name="netIncome" value="<?= $transactionTotalRows['netIncome'] ?>">

			<button type="submit">Export Capital Report</button>

		</form>
		<div>
			<?php if ($page > 1): ?>
				<a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&type=<?= $type ?>">Previous</a>
			<?php endif; ?>

			<?php for ($i = 1; $i <= $totalPages; $i++): ?>
				<a href="?page=<?= $i ?>&filter=<?= $filter ?>&type=<?= $type ?>"
					<?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
					<?= $i ?>
				</a>
			<?php endfor; ?>

			<?php if ($page < $totalPages): ?>
				<a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&type=<?= $type ?>">Next</a>
			<?php endif; ?>
		</div>



	<?php endif; ?>


</body>

</html>
