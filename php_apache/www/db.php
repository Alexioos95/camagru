<?php
	$host = "mysql";
	$dbname = getenv("MYSQL_DATABASE");
	$user = getenv("MYSQL_USER");
	$pass = getenv("MYSQL_PASSWORD");

	set_exception_handler(function ($e) {
		error_log($e->getMessage());
		exit("Something went wrong.");
	});

	if (!$dbname || !$user || !$pass)
	{
		error_log("Missing environment variables");
		die("Couldn't connect to the database");
	}
	try
	{
		$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e)
	{
		error_log($e->getMessage());
		die("Couldn't connect to the database");
	}
?>
