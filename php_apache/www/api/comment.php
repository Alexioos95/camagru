<?php
	session_start();
	require_once "../db.php";

	header('Content-Type: application/json');
	if (!isset($_SESSION["userID"]))
	{
		echo json_encode(["error" => "Not authenticated."]);
		exit;
	}
	$data = json_decode(file_get_contents("php://input"), true);

	if (!$data)
	{
		echo json_encode(["error" => "Invalid link."]);
		exit;
	}
	if (!isset($data["csrfToken"]) || !isset($_SESSION["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $data["csrfToken"]))
	{
		echo json_encode(["error" => "Invalid CSRF token."]);
		exit;
	}
	$id = $data["toImage"] ?? null;
	$msg = $data["msg"] ?? "";
	$msg = trim($msg);
	if (!$id || !is_numeric($id)) 
	{
		echo json_encode(["error" => "Invalid link."]);
		exit;
	}
	elseif ($msg === "" || strlen($msg) > 255)
	{
		echo json_encode(["error" => "Invalid message."]);
		exit;
	}

	$imageReq = $pdo->prepare("SELECT author FROM images WHERE id = ?");
	$imageReq->execute([$id]);
	if ($imageReq->rowCount() > 0)
		$imageAuthor = $imageReq->fetch(PDO::FETCH_ASSOC);
	else
	{
		echo json_encode(["error" => "Image does not exist."]);
		exit;
	}

	$commentReq = $pdo->prepare("INSERT INTO comments (author, message, toImage) VALUES (?, ?, ?)");
	$commentReq->execute([$_SESSION["userID"], $msg, $id]);
	if ($commentReq->rowCount() <= 0)
	{
		echo json_encode(["error" => "An error occured."]);
		exit;
	}
	$countReq = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE toImage = ?");
	$countReq->execute([$id]);
	$count = $countReq->fetchColumn();
	echo json_encode(["msg" => $msg, "commentsAmount" => $count]);
	
	if ($imageAuthor["author"] != $_SESSION["userID"])
	{
		$userReq = $pdo->prepare("SELECT email, notifMail FROM users WHERE id = ?");
		$userReq->execute([$imageAuthor["author"]]);
		if ($userReq->rowCount() > 0)
		{
			$userRow = $userReq->fetch(PDO::FETCH_ASSOC);
			if ($userRow["notifMail"] == true)
			{
				$emailLink = htmlspecialchars(("https://" . getenv("DUMP") . ":8443/image.php?id=" . $id), ENT_QUOTES, "UTF-8");
				$emailBody = "
					<html>
						<head>
							<title>camagru - You received a comment</title>
							</head>
						<body>
							<p>Someone commented on one of your image! Check what it says:</p>
							<p><a href='$emailLink'>$emailLink</a></p>
						</body>
					</html>
				";
				$mailReq = $pdo->prepare("INSERT INTO mailQueue (email, subject, body) VALUES (?, ?, ?)");
				$mailReq->execute([$userRow["email"], "camagru - You received a comment", $emailBody]);
			}
		}
	}
	exit;
?>
