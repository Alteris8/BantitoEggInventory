<?php
session_start();
include_once("database.php");
include_once("capitaltransactionsrepo.php");
include_once("capitaltransaction.php");
include_once("capitalrepo.php");
include_once("salerepo.php");
include_once("capital.php");

$pdo = getPDO();
$capitalTransactionsRepo = new CapitalTransactionRepo($pdo, $_SESSION['admin_id']);
$saleRepo = new SalesRepo($pdo, $_SESSION['admin_id']);
$capitalRepo = new CapitalRepo($pdo, $_SESSION['admin_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$sort  = $_POST['sort']  ?? 'createdAt';
	$order = $_POST['order'] ?? 'DESC';

	if (isset($_POST['capitalSubmit'])) {
		$newInitialBalance = (float)filter_input(
			INPUT_POST,
			'newCapitalAmount',
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		$capital = $capitalRepo->findByAdminId($_SESSION['admin_id']);
		$newCapital = new Capital(
			balance: $capital->getBalance(),
			initialBalance: $newInitialBalance,
			adminId: $_SESSION['admin_id'],
			id: $capital->getId(),
		);
		$capitalRepo->updateInitialBalance($newCapital);
		$capitalTransactionsRepo->recalculateBalance();
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
		$capitalTransactionsRepo->delete((int)$_POST['delete_id']);
		$capitalTransactionsRepo->recalculateBalance();
		header("Location: " . $_SERVER['PHP_SELF']);
		exit();
	}
	if (isset($_POST['void']) && isset($_POST['void_id'])) {
		$selectedId = (int)$_POST['void_id'];
		$transaction = $capitalTransactionsRepo->findById($selectedId);
		if ($transaction->getType() == 'restock' || $transaction->getType() == 'sale') {
			$capitalTransactionsRepo->voidTransaction($selectedId, $saleRepo);
		} else {
			$capitalTransactionsRepo->voidTransaction($selectedId);
		}
		$capitalTransactionsRepo->recalculateBalance();
		header("Location: " . $_SERVER['PHP_SELF']);
		exit();
	}

	header("Location: capitaltestpage.php?sort=$sort&order=$order");
	exit();
}



$sort  = $_POST['sort']  ?? $_GET['sort']  ?? 'createdAt';
$order = $_POST['order'] ?? $_GET['order'] ?? 'DESC';
$nextOrder       = $order === 'ASC' ? 'DESC' : 'ASC';
$search          = $_GET['search']          ?? '';
$transactionType = $_GET['transactionType'] ?? null;
$filter          = $_GET['filter']          ?? 'all';
$error           = $_GET['error']           ?? null;

$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$week  = filter_input(INPUT_GET, 'week',  FILTER_VALIDATE_INT);
$year  = filter_input(INPUT_GET, 'year',  FILTER_VALIDATE_INT) ?? (int)date('Y');

$currentMonth = (int)date('m');
$currentWeek  = (int)ceil(date('j') / 7);

if (!$month && ($filter === 'week' || $filter === 'month')) $month = $currentMonth;
if (!$week  &&  $filter === 'week')                         $week  = $currentWeek;

$capital = $capitalRepo->findByAdminId($_SESSION['admin_id']);
if ($capital === null) {
	$capitalRepo->save(new Capital(0, $_SESSION['admin_id']));
	$capital = $capitalRepo->findByAdminId($_SESSION['admin_id']);
}

$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total      = $capitalTransactionsRepo->countFiltered($search, $transactionType, $filter, $month, $week, $year);
$totalPages = (int)ceil($total / $limit);
$transactions = $capitalTransactionsRepo->paginate($page, $limit, $sort, $order, $search, $transactionType ?? '', $filter, $month, $week, $year);
$transactionTotalRows = $capitalTransactionsRepo->calculateIncomeSummary($search, $transactionType ?? '', $filter, $month, $week, $year);

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

function transactionUrl(array $overrides = []): string
{
	$params = array_merge([
		'sort'            => $GLOBALS['sort'],
		'order'           => $GLOBALS['order'],
		'search'          => $GLOBALS['search'],
		'transactionType' => $GLOBALS['transactionType'],
		'filter'          => $GLOBALS['filter'],
		'month'           => $GLOBALS['month'],
		'week'            => $GLOBALS['week'],
		'year'            => $GLOBALS['year'],
	], $overrides);
	return '?' . http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''));
}
?>
<html>

<body>

	<?php if ($capital->getInitialBalance() == 0): ?>
		<form method="post" action="capitaltestpage.php">
			<label>Input hands-on money: </label>
			<input type="number" name="newCapitalAmount" step="0.01" min="0"
				value="<?= htmlspecialchars($capital->getInitialBalance()) ?>" required>
			<input type="submit" name="capitalSubmit" value="Submit">
		</form>
	<?php else: ?>
		<form method="post" action="capitaltestpage.php">
			<div id="viewMode">
				<label>Balance: PHP<?= htmlspecialchars($capital->getBalance()) ?></label>
				<button type="button" onclick="toggleEdit(true)">Edit</button>
			</div>

			<div id="editMode" style="display:none;">
				<label>Initial Balance: PHP</label>
				<input type="number" name="newCapitalAmount" step="0.01" min="0"
					value="<?= htmlspecialchars($capital->getInitialBalance()) ?>" required>
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

		<form method="GET">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">
			<input type="hidden" name="transactionType" value="<?= htmlspecialchars($transactionType ?? '') ?>">
			<input type="hidden" name="month" value="<?= $month ?>">
			<input type="hidden" name="week" value="<?= $week ?>">
			<input type="hidden" name="year" value="<?= $year ?>">
			<input type="hidden" name="filter" value="<?= $filter ?>">
			<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
			<button type="submit">Search</button>
		</form>

		<form method="GET">
			<input type="hidden" name="sort" value="<?= $sort ?>">
			<input type="hidden" name="order" value="<?= $order ?>">
			<input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
			<?php if ($filter !== 'month' && $filter !== 'week'): ?>
				<input type="hidden" name="month" value="<?= $month ?>">
				<input type="hidden" name="week" value="<?= $week ?>">
				<input type="hidden" name="year" value="<?= $year ?>">
			<?php endif; ?>

			<label>Filter:</label>
			<select name="filter" onchange="this.form.submit()">
				<option value="all" <?= $filter === 'all'   ? 'selected' : '' ?>>All</option>
				<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly</option>
				<option value="week" <?= $filter === 'week'  ? 'selected' : '' ?>>Weekly</option>
				<option value="now" <?= $filter === 'now'   ? 'selected' : '' ?>>Today</option>
			</select>

			<label>Transaction Type:</label>
			<select name="transactionType" onchange="this.form.submit()">
				<option value="">All Types</option>
				<option value="sale" <?= $transactionType === 'sale'    ? 'selected' : '' ?>>Sale</option>
				<option value="expense" <?= $transactionType === 'expense' ? 'selected' : '' ?>>Expense</option>
				<option value="restock" <?= $transactionType === 'restock' ? 'selected' : '' ?>>Restock</option>
				<option value="deposit" <?= $transactionType === 'deposit' ? 'selected' : '' ?>>Deposit</option>
			</select>
			<?php if ($filter === 'month' || $filter === 'week'): ?>
				<select name="month" onchange="this.form.submit()">
					<option value="">Select Month</option>
					<?php foreach ($months as $num => $name): ?>
						<option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
					<?php endforeach; ?>
				</select>
				<select name="year" onchange="this.form.submit()">
					<?php for ($y = date('Y'); $y >= 2020; $y--): ?>
						<option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
					<?php endfor; ?>
				</select>
			<?php endif; ?>

			<?php if ($filter === 'week'): ?>
				<select name="week" onchange="this.form.submit()">
					<option value="">Select Week</option>
					<?php for ($w = 1; $w <= 4; $w++): ?>
						<option value="<?= $w ?>" <?= $week == $w ? 'selected' : '' ?>>Week <?= $w ?></option>
					<?php endfor; ?>
				</select>
			<?php endif; ?>
		</form>
		<form method="POST">
			<button type="submit" name="create">Create</button>
		</form>

		<table border="1">

			<tr>
				<th>Description</th>
				<th>Amount</th>
				<th>Type</th>
				<th>Created At</th>
			</tr>

			<?php if (!empty($transactions)): ?>
				<?php foreach ($transactions as $transaction): ?>
					<tr>
						<td><?= htmlspecialchars($transaction->getDescription()) ?></td>
						<td>₱<?= htmlspecialchars($transaction->getAmount()) ?></td>
						<td><?= htmlspecialchars($transaction->getType()) ?></td>
						<td><?= $transaction->getCurrentDate()->format('d-m-Y') ?></td>
						<td>
							<form method="post" action="capitaltestpage.php" onsubmit="return confirm('Void this transaction?')" style="display: inline;">
								<input type="hidden" name="void_id" value="<?= $transaction->getId() ?>">
								<input type="submit" name="void" value="Void">
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
		<?php if ($totalPages > 1): ?>
			<div>
				<?php if ($page > 1): ?>
					<a href="<?= transactionUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
				<?php endif; ?>
				<?php for ($i = 1; $i <= $totalPages; $i++): ?>
					<a href="<?= transactionUrl(['page' => $i]) ?>" <?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
						<?= $i ?>
					</a>
				<?php endfor; ?>
				<?php if ($page < $totalPages): ?>
					<a href="<?= transactionUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form method="post" action="capitaltestpage.php">
			<input type="submit" name="backToLogin" value="Back to Login">
		</form>
		<?php if (!empty($transactions)): ?>
			<form method="GET" action="export.php">
				<input type="hidden" name="filter" value="<?= $filter ?>">
				<input type="hidden" name="month" value="<?= $month ?>">
				<input type="hidden" name="week" value="<?= $week ?>">
				<input type="hidden" name="year" value="<?= $year ?>">
				<input type="hidden" name="transactionType" value="<?= htmlspecialchars($type ?? '') ?>">
				<input type="hidden" name="search" value="<?= htmlspecialchars($search ?? '') ?>">
				<button type="submit">Export Capital Report</button>
			</form>

		<?php endif; ?>



	<?php endif; ?>


</body>

</html>
