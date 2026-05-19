<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['login'])) {
		header("Location: adminloginpage.php");
		exit();
	}
	if (isset($_POST['register'])) {
		header("Location: adminregisterpage.php");
		exit();
	}
}


?>
<html>

<body>
	<form method="post" action="index.php">
		<input type="submit" name="login" value="Login">
		<input type="submit" name="register" value="Register">

	</form>

</body>

</html>
