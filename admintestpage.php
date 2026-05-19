<?php
session_start();

$adminId = $_SESSION['admin_id'];
include_once("database.php");
include_once("adminrepo.php");

$pdo = getPDO();
$adminRepo = new AdminRepo($pdo);

$admin = $adminRepo->findById($adminId);


$username = $admin->getUsername();
$fullName = $admin->getFullName();
$password = $admin->getPassword();
$id = $admin->getId();
$dateSold = $admin->getCreatedAt()->format('d-m-Y');

echo $username;
echo $fullName;
echo $id;
echo $dateSold;
echo $password;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['logout'])) {
		$_SESSION = [];
		session_destroy();
		header("Location: index.php");
		exit();
	}
	if (isset($_POST['inventory'])) {
		header("Location: inventorypage.php");
		exit();
	}
	if (isset($_POST['sales'])) {
		header("Location: salespage.php");
		exit();
	}
	if (isset($_POST['capital'])) {
		header("Location: capitaltestpage.php");
		exit();
	}
}
?>

<html>

<body>
	<form method="post" action="admintestpage.php">
		<input type="submit" name="logout" value="Logout">
		<input type="submit" name="inventory" value="Inventory Page">
		<input type="submit" name="sales" value="Sale Page">
		<input type="submit" name="capital" value="Capital Page">

	</form>

</body>

</html>
