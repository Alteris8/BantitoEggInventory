<?php
session_start();
include_once("database.php");
include_once("inventoryrepo.php");
include_once("producttyperepo.php");
$pdo = getPDO();
$productTypeRepo = new ProductTypeRepo($pdo, $_SESSION['admin_id']);

$order       = $_GET['order']       ?? 'DESC';
$nextOrder   = $order === 'ASC' ? 'DESC' : 'ASC';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	$order = $_POST['order'] ?? 'DESC';
	if (isset($_POST['backToLogin'])) {
		header("Location: admintestpage.php");
		exit();
	}
	if (isset($_POST['create'])) {
		header("Location: createproducttypespage.php");
		exit();
	}
	if (isset($_POST['delete']) && isset($_POST['delete_id'])) {
		$id = (int)$_POST['delete_id'];
		$productTypeRepo->delete($id);
		header("Location: " . $_SERVER['PHP_SELF']);
		exit();
	}
}

function productTypeUrl(array $overrides = []): string
{
	$params = array_merge([
		'order' => $GLOBALS['order'],
	], $overrides);

	return '?' . http_build_query($params);
}

$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;
$total      = $productTypeRepo->countFiltered();
$totalPages = (int)ceil($total / $limit);
$productTypes = $productTypeRepo->paginate($page, $limit, $order);


?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Inventory Product Types</title>
</head>

<body>

	<h1>Inventory Product Types</h1>

	<form method="GET">
		<input type="hidden" name="order" value="<?= $order ?>">
	</form>
	<form method="post" action="producttypespage.php">
		<input type="submit" name="create" value="Create">

	</form>




	<table border="1">

		<tr>
			<th>Product Type</th>
			<th>Actions</th>
		</tr>

		<?php if (!empty($productTypes)): ?>
			<?php foreach ($productTypes as $productType): ?>
				<tr>
					<td><?= htmlspecialchars($productType->getProductType()) ?></td>
					<td>
						<form method="POST" onsubmit="return confirm('Delete this type?')" style="display:inline">
							<input type="hidden" name="delete_id" value="<?= $productType->getId() ?>">
							<button type="submit" name="delete">Delete</button>
						</form>


					</td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="1">Empty product types</td>
			</tr>
		<?php endif; ?>


	</table>
	<?php if ($totalPages > 1): ?>
		<div>
			<?php if ($page > 1): ?>
				<a href="<?= productTypeUrl(['page' => $page - 1]) ?>">&larr; Prev</a>
			<?php endif; ?>
			<?php for ($i = 1; $i <= $totalPages; $i++): ?>
				<a href="<?= productTypeUrl(['page' => $i]) ?>" <?= $i === $page ? 'style="font-weight:bold"' : '' ?>>
					<?= $i ?>
				</a>
			<?php endfor; ?>
			<?php if ($page < $totalPages): ?>
				<a href="<?= productTypeUrl(['page' => $page + 1]) ?>">Next &rarr;</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<form action="producttypespage.php" method="post">
		<input type="submit" name="backToLogin" value="Back to Login">
	</form>

</body>

</html>
