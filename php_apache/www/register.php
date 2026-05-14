<?php
	require_once "db.php";
	require_once "auth.php";

	if (isset($_SESSION["userID"]))
	{
		header("Location: /");
		exit;
	}
	if (empty($_SESSION["csrfToken"]))
		$_SESSION["csrfToken"] = bin2hex(random_bytes(32));
	$username = "";
	$email = "";
	if (empty($_SESSION["pleaseVerify"]))
		$_SESSION["pleaseVerify"] = false;
	if (isset($_POST["submit"]))
	{
		if (!isset($_POST["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $_POST["csrfToken"]))
			exit("Invalid CSRF token");
		$username = trim($_POST["username"]);
		$email = trim($_POST["email"]);
		$password = $_POST["password"];
		$confirm = $_POST["confirm"];

		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			$_SESSION["error"] = "Invalid email format.";
		elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\W_]/", $password) || strlen($password) < 8)
			$_SESSION["error"] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
		elseif ($password !== $confirm)
			$_SESSION["error"] = "Passwords not matching.";
		else
		{
			$req = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
			$req->execute([$username, $email]);
			if ($req->rowCount() > 0)
			{
				$email = "";
				$username = "";
				$_SESSION["error"] = "Username or email not available.";
			}
			else
			{
				$hashed = password_hash($password, PASSWORD_DEFAULT);
				$verifCode = bin2hex(random_bytes(16));
				$verifCodeExpires = date("Y-m-d H:i:s", strtotime("+24 hours"));
				$req = $pdo->prepare("INSERT INTO users (username, email, password, verifCode, verifCodeExpires) VALUES (?, ?, ?, ?, ?)");
				$req->execute([$username, $email, $hashed, $verifCode, $verifCodeExpires]);
				$emailLink = htmlspecialchars(("https://" . getenv("DUMP") . ":8443/api/activate.php?code=" . $verifCode . "&email=" . urlencode($email)), ENT_QUOTES, "UTF-8");
				$emailBody = "
					<html>
						<head>
							<title>camagru - Activate your account</title>
						</head>
						<body>
							<p>Click the link below to confirm your registration to camagru:</p>
							<p><a href='$emailLink'>$emailLink</a></p>
						</body>
					</html>
				";
				$mailReq = $pdo->prepare("INSERT INTO mailQueue (email, subject, body) VALUES (?, ?, ?)");
				$mailReq->execute([$email, "camagru - Activate your account", $emailBody]);
				$_SESSION["csrfToken"] = bin2hex(random_bytes(32));
				$_SESSION["pleaseVerify"] = true;
				header("Location: /register.php");
				exit;
			}
		}
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Create and share crazy montage pictures with your webcam!">
		<title>camagru - register</title>
		<link rel="icon" type="image/x-icon" href="/images/favicon.ico">
		<link rel="stylesheet" type="text/css" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="./styles.css">
	</head>
	<body class="login-body">
		<?php if (!$_SESSION["pleaseVerify"]): ?>
			<div class="login-container">
				<h1>Register</h1>
				<form action="register.php" method="POST" autocomplete="off">
					<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken']; ?>">
					<input type="text" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="new-email" autocorrect="off" autocapitalize="off" aria-label="Email" required>
					<input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="new-username" autocorrect="off" autocapitalize="off" aria-label="Username" required>
					<input type="password" name="password" placeholder="Password" autocomplete="new-password" aria-label="Password" required>
					<input type="password" name="confirm" placeholder="Confirm password" autocomplete="new-password" aria-label="Confirm password" required>
					<div class="error-wrapper">
						<p class="error">
							<?php 
								echo isset($_SESSION["error"]) ? $_SESSION["error"] : " "; 
								unset($_SESSION["error"]);
							?>
						</p>
						<button type="submit" name="submit">Submit</button>
					</div>
				</form>
				<p>Already have an account? <a href="login.php">Login</a></p>
			</div>
		<?php else: ?>
			<p>Please check your mails to activate your account.</p>
			<?php unset($_SESSION["pleaseVerify"]); ?>
		<?php endif; ?>
	</body>
</html>
