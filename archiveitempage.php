<?php
session_start();
include_once("database.php");
include_once("archiveitemrepo.php");
include_once("producttyperepo.php");
$pdo = getPDO();
$productTypeRepo = new ProductTypeRepo($pdo, $_SESSION['admin_id']);
$archiveRepo     = new ArchiveItemRepo($pdo, $_SESSION['admin_id'], $productTypeRepo);

$sort        = $_GET['sort']        ?? 'lastUpdated';
$order       = $_GET['order']       ?? 'DESC';
$nextOrder   = $order === 'ASC' ? 'DESC' : 'ASC';
$search      = $_GET['search']      ?? '';
$productType = $_GET['productType'] ?? null;
$filter      = $_GET['filter']      ?? 'all';
$error       = $_GET['error']       ?? null;

$month       = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$week        = filter_input(INPUT_GET, 'week',  FILTER_VALIDATE_INT);
$year        = filter_input(INPUT_GET, 'year',  FILTER_VALIDATE_INT) ?? (int)date('Y');

$currentMonth = (int)date('m');
$currentWeek  = (int)ceil(date('j') / 7);

if (!$month && ($filter === 'week' || $filter === 'month')) $month = $currentMonth;
if (!$week  &&  $filter === 'week') $week = $currentWeek;

$allProductTypes = $productTypeRepo->findAllTypes();

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
	$sort  = $_POST['sort']  ?? 'lastUpdated';
	$order = $_POST['order'] ?? 'DESC';

	if (isset($_POST['backToLogin'])) {
		header("Location: admintestpage.php");
		exit();
	}
	if (isset($_POST['return']) && isset($_POST['return_id'])) {
		$id = (int)$_POST['return_id'];
		$returnedArchive = $archiveRepo->findById($id);
		$archiveRepo->transferToInventory($id, $returnedArchive);
		header("Location: archiveitempage.php?sort=$sort&order=$order");
		exit();
	}
	if (isset($_POST['delete']) && isset($_POST['delete_id'])) {
		$id = (int)$_POST['delete_id'];
		$archiveRepo->delete($id);
		header("Location: archiveitempage.php?sort=$sort&order=$order");
		exit();
	}
	header("Location: archiveitempage.php?sort=$sort&order=$order");
	exit();
}

$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total       = $archiveRepo->countFiltered($search, $productType ?? '', $filter, $month, $week, $year);
$totalPages = (int)ceil($total / $limit);
$archives = $archiveRepo->paginate($page, $limit, $sort, $order, $search, $productType ?? '', $filter, $month, $week, $year);

function archiveUrl(array $overrides = []): string
{
	$params = array_merge([
		'sort'        => $GLOBALS['sort'],
		'order'       => $GLOBALS['order'],
		'search'      => $GLOBALS['search'],
		'productType' => $GLOBALS['productType'],
		'filter'      => $GLOBALS['filter'],
		'month'       => $GLOBALS['month'],
		'week'        => $GLOBALS['week'],
		'year'        => $GLOBALS['year'],
	], $overrides);
	return '?' . http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''));
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Archives</title>
</head>

<body>
	<h1>Archive</h1>

	<form method="GET">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">
		<input type="hidden" name="productType" value="<?= htmlspecialchars($productType ?? '') ?>">
		<input type="hidden" name="filter" value="<?= $filter ?>">
		<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
		<button type="submit">Search</button>
	</form>

	<form method="GET">
		<input type="hidden" name="sort" value="<?= $sort ?>">
		<input type="hidden" name="order" value="<?= $order ?>">
		<input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

		<label>Filter:</label>
		<select name="filter" onchange="this.form.submit()">
			<option value="all" <?= $filter === 'all'   ? 'selected' : '' ?>>All</option>
			<option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Monthly</option>
			<option value="week" <?= $filter === 'week'  ? 'selected' : '' ?>>Weekly</option>
			<option value="now" <?= $filter === 'now'   ? 'selected' : '' ?>>Today</option>
		</select>

		<label>Product Type:</label>
		<select name="productType" onchange="this.form.submit()">
			<option value="">All Types</option>
			<?php foreach ($allProductTypes as $type): ?>
				<option value="<?= htmlspecialchars($type) ?>" <?= $productType === $type ? 'selected' : '' ?>>
					<?= htmlspecialchars($type) ?>
				</option>
			<?php endforeach; ?>
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

	<table border="1">
		<tr>
			<th>Name</th>
			<th>Product Type</th>
			<th>Quantity</th>
			<th>>Price</th>
			<th>Status</th>
			<th>Last Updated</th>
			<th>Actions</th>
		</tr>
		<?php if (!empty($archives)): ?>
			<?php foreach ($archives as $archive): ?>
				<tr>
					<td><?= htmlspecialchars($archive->getProductName()) ?></td>
					<td><?= htmlspecialchars($archive->getProductType() ?? '—') ?></td>
					<td><?= htmlspecialchars($archive->getQuantity()) ?></td>
					<td>₱<?= htmlspecialchars($archive->getPrice()) ?></td>
					<td>Archived</td>
					<td><?= $archive->getDateUpdated()->format('d-m-Y') ?></td>
					<td>
						<form method="POST" style="display:inline">
							<input type="hidden" name="return_id" value="<?= $archive->getId() ?>">
							<button type="submit" name="return">Return to Inventory</button>
						</form>
						<form method="POST" onsubmit="return confirm('Delete this item?')" style="display:inline">
							<input type="hidden" name="delete_id" value="<?= $archive->getId() ?>">
							<button type="submit" name="delete">Delete</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="7">Empty Archive</td>
			</tr>
		<?php endif; ?>
	</table>

	<?php if ($totalPages > 1): ?>
		<div>
			<?php if ($page > 1): ?>
				<a href="<?= archiveUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
			<?php endif; ?>
			<?php for ($i = 1; $i <= $totalPages; $i++): ?>
				<a href="<?= archiveUrl(['page' => $i]) ?>" <?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
					<?= $i ?>
				</a>
			<?php endfor; ?>
			<?php if ($page < $totalPages): ?>
				<a href="<?= archiveUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<button onclick="window.location='admintestpage.php'">Back to Login</button>
</body>

</html>
