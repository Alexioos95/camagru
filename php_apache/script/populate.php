<?php
	require_once "/var/www/html/db.php";

	$data = json_decode(file_get_contents("/usr/local/bin/script/mixture.json"), true);

	foreach ($data["users"] as $user)
	{
		$hashed = password_hash($user["password"], PASSWORD_DEFAULT);
		$req = $pdo->prepare("INSERT INTO users (id, email, username, password, isActive) VALUES (?, ?, ?, ?, ?)");
		$req->execute([$user["id"], $user["email"], $user["username"], $hashed, $user["isActive"]]);
	}
	foreach ($data["images"] as $image)
	{
		$req = $pdo->prepare("INSERT INTO images (id, path, author) VALUES (?, ?, ?)");
		$req->execute([$image["id"], $image["path"], $image["author"]]);
	}
	foreach ($data["likes"] as $like)
	{
		$req = $pdo->prepare("INSERT INTO likes (author, toImage) VALUES (?, ?)");
		$req->execute([$like["author"], $like["toImage"]]);}
	foreach ($data["comments"] as $comment)
	{
		$req = $pdo->prepare("INSERT INTO comments (author, toImage, message) VALUES (?, ?, ?)");
		$req->execute([$comment["author"], $comment["toImage"], $comment["message"]]);
	}
?>
