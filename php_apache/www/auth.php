<?php
	session_start();

	if (!isset($_SESSION["userID"]) && isset($_COOKIE["rememberMe"]))
	{
		$token = $_COOKIE["rememberMe"];
		$hashedToken = hash("sha256", $token);
		$req = $pdo->prepare("SELECT * FROM users WHERE cookieToken = ? AND cookieExpires > NOW() LIMIT 1");
		$req->execute([$hashedToken]);
		$user = $req->fetch();
		if ($user)
		{
			session_regenerate_id(true);
			$_SESSION["userID"] = $user["id"];
			$_SESSION["email"] = $user["email"];
			$_SESSION["username"] = $user["username"];
			$_SESSION["notifMail"] = $user["notifMail"];
			$_SESSION["csrfToken"] = bin2hex(random_bytes(32));
			$newToken = bin2hex(random_bytes(64));
			$newHashedToken = hash("sha256", $newToken);
			$newTime = time() + 60 * 60 * 24;
			$newDate = date("Y-m-d H:i:s", $newTime);
			$update = $pdo->prepare("UPDATE users SET cookieToken = ?, cookieExpires = ? WHERE id = ?");
			$update->execute([$newHashedToken, $newDate, $user["id"]]);
			setcookie( "rememberMe", $newToken, ["expires" => $newTime, "path" => "/", "httponly" => true, "secure" => true, "samesite" => "Lax" ]);
		}
	}
?>
