<?php
session_start();
include_once("admin.php");
include_once("adminrepo.php");
include_once("baserepo.php");
$pdo = getPDO();
$adminRepo = new AdminRepo($pdo);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (isset($_POST["cancel"])) {
		header("Location: index.php");
		exit();
	}

	if (isset($_POST["register"])) {
		$userName = filter_input(INPUT_POST, "userName", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$fullName = filter_input(INPUT_POST, "fullName", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ($adminRepo->findByUsername($userName) !== null) {
			$errorMessage = "Username already taken";
			header("Location: adminregister.php");
			exit();
		} else {

			$newAdmin = new Admin($fullName, $userName, $password);
			$adminRepo->save($newAdmin);
			$_SESSION['admin_id'] = $adminRepo->findByUsername($userName)->getId();
			header("Location: admintestpage.php");
			exit();
		}
	}
}

?>


<html>

<body>
	<form method="post" action="adminregister.php">
		<label>Full Name:</label>
		<input type="text" name="fullName" required>

		<label>Username:</label>
		<input type="text" name="userName" required>

		<label>Password:</label>
		<input type="password" name="password" required>

		<input type="submit" name="register" value="Register">
		<input type="submit" name="cancel" value="Cancel" formnovalidate>

	</form>

</body>

</html>
