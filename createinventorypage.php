<?php
session_start();
include_once("database.php");
include_once("inventory.php");
include_once("inventoryrepo.php");

$pdo = getPDO();
$repo = new InventoryRepo($pdo, $_SESSION['admin_id']);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$productName = filter_input(INPUT_POST, "product_name", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$price = filter_input(INPUT_POST, "price", FILTER_SANITIZE_NUMBER_FLOAT,  FILTER_FLAG_ALLOW_FRACTION);
	$quantity = filter_input(INPUT_POST, "quantity", FILTER_SANITIZE_NUMBER_INT);

	if ($productName && $price && $quantity) {

		$inventory = new Inventory(
			$productName,
			(int)$quantity,
			(float)$price,
			null
		);

		$repo->save($inventory);

		$message = "Product added successfully!";
	} else {
		$message = "Please fill in all fields.";
	}
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Create Inventory</title>
	<style>
		:root {
			--green-dark: #3d5e1a;
			--green-mid: #4a6b24;
			--green-light: #5a7a2e;
			--orange: #e8523a;
			--orange-grad: linear-gradient(135deg, #f07b3f 0%, #e8523a 100%);
			--red-bg: #FCEBEB;
			--red-text: #A32D2D;
			--red-border: #E24B4A;
			--green-bg: #EAF3DE;
			--green-text: #3B6D11;
			--green-border: #639922;
			--bg: #f4f6f0;
			--card: #ffffff;
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
			min-height: 100vh;
			display: flex;
		}


		.sidebar {
			width: var(--sidebar-w);
			background: var(--green-mid);
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			position: fixed;
			top: 0;
			left: 0;
			bottom: 0;
			z-index: 100;
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
			font-family: 'Anton', sans-serif;
			font-size: 14px;
			color: #fff;
			flex-shrink: 0;
			box-shadow: 0 2px 8px rgba(192, 57, 43, 0.4);
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

		.nav-item:hover {
			background: rgba(255, 255, 255, 0.07);
			color: #fff;
		}

		.nav-item.active {
			background: rgba(255, 255, 255, 0.12);
			color: #fff;
			border-left-color: #f39c12;
		}

		.nav-item svg {
			width: 16px;
			height: 16px;
			flex-shrink: 0;
		}

		.main {
			margin-left: var(--sidebar-w);
			flex: 1;
			display: flex;
			flex-direction: column;
			min-height: 100vh;
		}

		.topbar {
			background: var(--orange-grad);
			padding: 0 24px;
			height: 80px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			position: sticky;
			top: 0;
			z-index: 50;
			box-shadow: 0 2px 12px rgba(232, 82, 58, 0.25);
		}

		.page-title {
			font-family: 'Anton', sans-serif;
			font-size: 40px;
			color: #fff;
			letter-spacing: 1.5px;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.btn-back {
			background: rgba(255, 255, 255, 0.2);
			border: 1px solid rgba(255, 255, 255, 0.4);
			border-radius: var(--radius-sm);
			padding: 7px 16px;
			font-size: 17px;
			font-weight: 600;
			color: #fff;
			display: flex;
			align-items: center;
			gap: 6px;
			font-family: inherit;
			text-decoration: none;
			transition: background 0.2s;
		}

		.btn-back:hover {
			background: rgba(255, 255, 255, 0.3);
		}

		.content {
			padding: 32px 24px;
			flex: 1;
			display: flex;
			align-items: flex-start;
			justify-content: center;
		}

		.form-card {
			background: var(--card);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			width: 100%;
			max-width: 480px;
			overflow: hidden;
			box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
			animation: slideUp 0.25s ease;
		}

		@keyframes slideUp {
			from {
				opacity: 0;
				transform: translateY(20px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.form-card-header {
			background: var(--orange-grad);
			padding: 16px 20px;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.form-card-title {
			font-family: 'Anton', sans-serif;
			font-size: 20px;
			color: #fff;
			letter-spacing: 1px;
		}

		.form-body {
			padding: 22px 20px 6px;
		}

		.message {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 18px;
			padding: 11px 14px;
			border-radius: var(--radius-sm);
			font-size: 13px;
			font-weight: 600;
			border: 1px solid transparent;
		}

		.message.success {
			background: var(--green-bg);
			color: var(--green-text);
			border-color: var(--green-border);
		}

		.message.error {
			background: var(--red-bg);
			color: var(--red-text);
			border-color: var(--red-border);
		}

		.message svg {
			flex-shrink: 0;
		}

		.fg {
			display: flex;
			flex-direction: column;
			gap: 5px;
			margin-bottom: 16px;
		}

		.flabel {
			font-size: 10px;
			font-weight: 600;
			color: var(--muted);
			text-transform: uppercase;
			letter-spacing: 0.07em;
		}

		.finput {
			background: #f4f6f0;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			padding: 9px 12px;
			font-size: 13px;
			color: var(--text);
			outline: none;
			width: 100%;
			font-family: 'Plus Jakarta Sans', sans-serif;
			transition: border-color 0.2s, box-shadow 0.2s;
		}

		.finput:focus {
			border-color: var(--green-light);
			box-shadow: 0 0 0 3px rgba(90, 122, 46, 0.12);
			background: #fff;
		}

		.prefix-wrap {
			position: relative;
		}

		.prefix-wrap .finput {
			padding-left: 24px;
		}

		.pfx {
			position: absolute;
			left: 10px;
			top: 50%;
			transform: translateY(-50%);
			font-size: 12px;
			color: var(--muted);
			pointer-events: none;
		}

		.frow {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 14px;
		}

		.divider {
			height: 1px;
			background: var(--border);
			margin: 6px 0 18px;
		}

		.form-footer {
			padding: 0 20px 20px;
			display: flex;
			gap: 10px;
		}

		.btn-cancel {
			flex: 1;
			padding: 11px;
			border-radius: var(--radius-sm);
			border: 1px solid var(--border);
			background: #f4f6f0;
			font-size: 13px;
			font-weight: 600;
			color: var(--muted);
			cursor: pointer;
			font-family: inherit;
			transition: background 0.15s;
		}

		.btn-cancel:hover {
			background: var(--border);
		}

		.btn-save {
			flex: 2;
			padding: 11px;
			border-radius: var(--radius-sm);
			border: none;
			background: var(--orange-grad);
			color: #fff;
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			font-family: inherit;
			box-shadow: 0 2px 8px rgba(232, 82, 58, 0.3);
			transition: opacity 0.15s;
		}

		.btn-save:hover {
			opacity: 0.9;
		}
	</style>
</head>

<body>

	<h1>Create Inventory</h1>

	<?php if ($message): ?>
		<p><?= htmlspecialchars($message) ?></p>
	<?php endif; ?>

	<form method="POST">

		<label>Product Name</label><br>
		<input type="text" name="product_name" required><br><br>

		<label>Price</label><br>
		<input type="number" name="price" step="0.01" min="0" required><br><br>

		<label>Quantity</label><br>
		<input type="number" name="quantity" min="0" required><br><br>

		<button type="button" onclick="window.location.href='inventorypage.php'">
			Cancel
		</button>

		<button type="submit">
			Create
		</button>

	</form>

</body>

</html>
