<?php
	require_once "db.php";
	require_once "auth.php";
	
	if (!isset($_SESSION["userID"]))
	{
		header("Location: /login.php");
		exit;
	}
	if (empty($_SESSION["csrfToken"]))
		$_SESSION["csrfToken"] = bin2hex(random_bytes(32));
	if (isset($_POST["submit"]))
	{
		if (!isset($_POST["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $_POST["csrfToken"]))
			exit("Invalid CSRF token");
		$username = trim($_POST["username"]);
		$email = trim($_POST["email"]);
		$password = $_POST["password"];
		$notifMail = isset($_POST["notifMail"]) ? 1 : 0;
		$_SESSION["error"] = "";
		$_SESSION["success"] = "";

		if ($email != "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
			$_SESSION["error"] = "Invalid email format.";
		elseif ($password != "" &&
				(!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) ||
				!preg_match("/[0-9]/", $password) || !preg_match("/[\W_]/", $password) || strlen($password) < 8))
			$_SESSION["error"] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
		else
		{
			if ($email != "")
			{
				$req = $pdo->prepare("SELECT id FROM users WHERE email = ?");
				$req->execute([$email]);
				if ($req->rowCount() > 0)
					$_SESSION["error"] .= "Email already taken.<br>";
				else
				{
					$req = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
					$req->execute([$email, $_SESSION["userID"]]);
					$_SESSION["email"] = $email;
					$_SESSION["success"] .= "Updated email.<br>";
				}
			}
			if ($username != "")
			{
				$req = $pdo->prepare("SELECT id FROM users WHERE username = ?");
				$req->execute([$username]);

				if ($req->rowCount() > 0)
					$_SESSION["error"] .= "Username already taken.";
				else
				{
					$req = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
					$req->execute([$username, $_SESSION["userID"]]);
					$_SESSION["username"] = $username;
					$_SESSION["success"] .= "Updated username.<br>";
				}
			}
			if ($password != "")
			{
				$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
				$req = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
				$req->execute([$hashedPassword, $_SESSION["userID"]]);
				$_SESSION["success"] .= "Updated password.<br>";
			}
			if ($_SESSION["notifMail"] != $notifMail)
			{
				$req = $pdo->prepare("UPDATE users SET notifMail = ? WHERE id = ?");
				$req->execute([$notifMail, $_SESSION["userID"]]);
				$_SESSION["notifMail"] = $notifMail;
				$_SESSION["success"] .= "Updated notification preferences.<br>";
			}
		}
		$_SESSION["csrfToken"] = bin2hex(random_bytes(32));
		header("Location: /settings.php");
		exit;
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Create and share crazy montage pictures with your webcam!">
		<title>camagru</title>
		<link rel="icon" type="image/x-icon" href="/images/favicon.ico">
		<link rel="stylesheet" type="text/css" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
		<script src="https://kit.fontawesome.com/70111f5ad5.js" crossorigin="anonymous"></script>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="./styles.css">
	</head>
	<body class="index-body">
		<header>
			<h1>camagru</h1>
			<nav>
				<ul>
					<li><a href="/"><i class="fa-solid fa-house"></i><span>Home</span></a></li>
					<li><a href='/edition.php'><i class='fa-solid fa-pen-to-square'></i><span>Edition</span></a></li>
					<li><a href='/settings.php'><i class='fa-solid fa-gear'></i><span>Settings</span></a></li>
					<li><a href='/api/logout.php'><i class='fa-solid fa-right-from-bracket'></i><span>Logout</span></a></li>
				</ul>
			</nav>
		</header>
		<main>
			<div class="settings">
				<div>
					<h2>Update my infos</h2>
					<form action="settings.php" method="POST" autocomplete="off">
						<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken']; ?>">
						<input type="text" name="username" placeholder="<?php echo htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="new-username" aria-label="Username">
						<input type="email" name="email" placeholder="<?php echo htmlspecialchars($_SESSION["email"], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="new-username" aria-label="Email">
						<input type="password" name="password" placeholder="Password" autocomplete="new-password" aria-label="Password">
						<label for="notifMail">
							<input type="checkbox" id="notifMail" name="notifMail" <?php if ($_SESSION["notifMail"]) echo "checked";?> aria-label="Checkbox for mail notification"/>
							<span>Comments mail notification</span>
						</label>
						<div class="error-wrapper">
							<p class="error">
								<?php 
									echo isset($_SESSION['error']) ? $_SESSION['error'] : " "; 
									unset($_SESSION['error']);
								?>
							</p>
							<button type="submit" name="submit">Submit</button>
							<p class="success">
							<?php
								echo isset($_SESSION["success"]) ? $_SESSION["success"] : " "; 
								unset($_SESSION["success"]);
							?>
							</p>
						</div>
					</form>
				</div>
			</div>
		</main>
		<footer>
			<p>camagru by apayen@student.42.fr</p>
		</footer>
	</body>
</html>
