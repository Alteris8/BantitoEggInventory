<?php

include_once("database.php");
include_once("productrepo.php");


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	if (!isset($_GET["id"])) {
		header("Location: inventory.php");
		exit();
	}
	$id = $_GET["id"] ?? '';

	$stmt = $conn->prepare("
	SELECT * FROM registration WHERE id=?
	");

	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();

	$productName = $row["name"];
	$price = $row["price"];
	$quantity = $row["quantity"];
} else {

	$productName = filter_input(INPUT_POST, "name", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$price = filter_input(INPUT_POST, "price", FILTER_VALIDATE_FLOAT);
	$quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
	if (isset($_POST["cancel"])) {
		unset($_SESSION["result"]);
		unset($_SESSION["result_type"]);
		header("Location: inventory.php");
		exit();
	}
	if (isset($_POST["submit"])) {
		$stmt = $conn->prepare(
			"UPDATE registration SET fullName=?, location=? WHERE username=?"
		);
		$stmt->bind_param("sss", $fullName, $location, $username);
		$stmt->execute();


		header("Location: inventory.php");
		exit();
	}
}

?>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Product</title>
	<link rel="stylesheet" href="output.css">
</head>

<body class="bg-black min-h-screen flex items-center justify-center">
	<div class="bg-gray-900 p-8 rounded-2xl border border-white/10 w-full max-w-md">
		<h2 class="text-2xl font-bold text-center text-white mb-6">Update</h2>
		<form action="editorder.php" method="post" class="space-y-4">
			<div>
				<label class="block text-sm font-medium text-gray-400 mb-1">Product Name</label>
				<input type="text" name="fullName" class="w-full bg-gray-800 border border-white/10 text-black rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" value="<?php echo $fullName ?>">
			</div>
			<div>
				<label class="block text-sm font-medium text-gray-400 mb-1">Price</label>
				<input type="text" name="location" class="w-full bg-gray-800 border border-white/10 text-black rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" value="<?php echo $location ?>">
			</div>
			<div>
				<label class="block text-sm font-medium text-gray-400 mb-1">Quantity</label>
				<input type="text" name="username" class="w-full bg-gray-800 border border-white/10 text-black rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500" value="<?php echo $username ?>">
			</div>
			<input type="submit" name="submit" value="Update" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-lg cursor-pointer transition duration-200">
			<input type="submit" name="cancel" value="Cancel" class="w-full bg-transparent border border-white/10 hover:bg-white/5 text-gray-400 font-semibold py-2 rounded-lg cursor-pointer transition duration-200">
		</form>
	</div>
</body>

</html>
