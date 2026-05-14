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
	
	if (!isset($data["csrfToken"]) || !isset($_SESSION["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $data["csrfToken"]))
	{
		echo json_encode(["error" => "Invalid CSRF token."]);
		exit;
	}
	$id = $data["id"] ?? null;
	if (!$id || !is_numeric($id))
	{
		echo json_encode(["error" => "Invalid link."]);
		exit;
	}

	$imageCheck = $pdo->prepare("SELECT id FROM images WHERE id = ?");
	$imageCheck->execute([$id]);
	if (!$imageCheck->fetch(PDO::FETCH_ASSOC))
	{
		echo json_encode(["error" => "Image does not exist"]);
		exit;
	}

	$status = "";
	$likeCheck = $pdo->prepare("SELECT id FROM likes WHERE author = ? AND toImage = ?");
	$likeCheck->execute([$_SESSION["userID"], $id]);
	if ($likeCheck->fetch(PDO::FETCH_ASSOC))
	{
		$delete = $pdo->prepare("DELETE FROM likes WHERE author = ? AND toImage = ?");
		$delete->execute([$_SESSION["userID"], $id]);
		$status = "unliked";
	}
	else
	{
		$insert = $pdo->prepare("INSERT INTO likes (author, toImage) VALUES (?, ?)");
		$insert->execute([$_SESSION["userID"], $id]);
		$status = "liked";
	}
	$countReq = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE toImage = ?");
	$countReq->execute([$id]);
	$count = $countReq->fetchColumn();
	echo json_encode(["status" => $status, "likesAmount" => $count]);
	exit;
?>
