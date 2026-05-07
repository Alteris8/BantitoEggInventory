<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if (isset($_POST["inventory"])) {
		header("Location: inventorypage.php");
		exit();
	}
	if (isset($_POST["sales"])) {
		header("Location: salespage.php");
		exit();
	}
}



?>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Main Menu</title>
	<link rel="stylesheet" href="output.css">
</head>

<body>
	<form action="index.php" method="post">
		<h2>
			Main Menu
		</h2><br>
		<input type="submit" name="inventory" value="Inventory"><br>
		<input type="submit" name="sales" value="Sales"><br>


	</form>

</body>

</html>
