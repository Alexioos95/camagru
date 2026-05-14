<?php
	session_start();
	require_once "../db.php";

	header("Content-Type: application/json");

	if (!isset($_SESSION["userID"]))
	{
		echo json_encode(["error" => "Not authenticated"]);
		exit;
	}
	if (!isset($_POST["csrfToken"]) || !isset($_SESSION["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $_POST["csrfToken"]))
	{
		echo json_encode(["error" => "Invalid CSRF token"]);
		exit;
	}

	$id = $_POST["id"] ?? null;

	if (!$id || !is_numeric($id))
	{
		echo json_encode(["error" => "Invalid ID"]);
		exit;
	}
	$req = $pdo->prepare("SELECT * FROM images WHERE id = ? AND author = ?");
	$req->execute([$id, $_SESSION["userID"]]);
	if ($req->rowCount() <= 0)
	{
		echo json_encode(["error" => "Not found"]);
		exit;
	}
	$row = $req->fetch(PDO::FETCH_ASSOC);
	$filePath = __DIR__ . "/.." . $row["path"];
	if (file_exists($filePath))
		unlink($filePath);
	$delReq = $pdo->prepare("DELETE FROM images WHERE id = ? AND author = ?");
	$delReq->execute([$id, $_SESSION["userID"]]);
	echo json_encode(["success" => true]);
	exit;
?>
