<?php

session_start();
include_once("database.php");
include_once("adminrepo.php");

$pdo = getPDO();
$adminRepo = new AdminRepo($pdo);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$admin = $adminRepo->findByUsername($username) ?? null;
	if (isset($_POST['cancel'])) {
		header("Location: index.php");
		exit();
	}
	if (isset($_POST['login'])) {
		if ($admin === null || !password_verify($password, $admin->getPassword())) {
			$errorMessage = "Wrong username or password";
			header("Location: adminloginpage.php");
			exit();
		} else {
			$_SESSION['admin_id'] = $admin->getId();
			header("Location: admintestpage.php");
			exit();
		}
	}
}


?>
<html>

<body>
	<form method="post" action="adminloginpage.php">
		<label>Username:</label>
		<input type="text" name="username" required>

		<label>Password:</label>
		<input type="password" name="password" required>

		<input type="submit" name="login" value="Login">
		<input type="submit" name="cancel" value="Cancel" formnovalidate>

	</form>

</body>

</html>
