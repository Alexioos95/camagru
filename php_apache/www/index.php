<?php
	require_once "db.php";
	require_once "auth.php";

	$req = $pdo->prepare("SELECT 
		images.*,
		users.username AS authorName,
		COUNT(DISTINCT likes.id) AS likesAmount,
		COUNT(DISTINCT comments.id) AS commentsAmount
		FROM images
		LEFT JOIN users ON images.author = users.id
		LEFT JOIN likes ON likes.toImage = images.id
		LEFT JOIN comments ON comments.toImage = images.id
		GROUP BY images.id
		ORDER BY images.createdAt DESC, images.id DESC
		LIMIT :limit OFFSET :offset
	");
	$req->bindValue(":limit", 12, PDO::PARAM_INT);
	$req->bindValue(":offset", 0, PDO::PARAM_INT);
	$req->execute();
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
					<?php
						if (isset($_SESSION["userID"]))
						{
							echo "<li><a href='/edition.php'><i class='fa-solid fa-pen-to-square'></i><span>Edition</span></a></li>";
							echo "<li><a href='/settings.php'><i class='fa-solid fa-gear'></i><span>Settings</span></a></li>";
							echo "<li><a href='/api/logout.php'><i class='fa-solid fa-right-from-bracket'></i><span>Logout</span></a></li>";
						}
						else
							echo "<li><a href='/login.php'><i class='fa-solid fa-user'></i><span>Login</span></a></li>";
					?>
				</ul>
			</nav>
		</header>
		<main class="index-main">
			<div class="grid-container">
			<?php while ($row = $req->fetch(PDO::FETCH_ASSOC)): ?>
				<div class="grid-items">
					<a class="grid-items-a" href="/image.php?id=<?php echo htmlspecialchars($row['id']); ?>">
					<img src="<?php echo htmlspecialchars($row['path']); ?>">
					<div class="grid-items-overlay">
						<p><?php echo htmlspecialchars($row['authorName']); ?></p>
						<div>
							<div>
								<i class="fa-solid fa-heart"></i>
								<?php echo htmlspecialchars($row['likesAmount']); ?>
							</div>
							<div>
								<i class="fa-solid fa-comment"></i>
								<?php echo htmlspecialchars($row['commentsAmount']); ?>
							</div>
						</div>
					</div>
					</a>
				</div>
			<?php endwhile; ?>
			</div>
			<div class="load-trigger"></div>
		</main>
		<footer>
			<p>camagru by apayen@student.42.fr</p>
		</footer>
		<script>
			let loading = false;
			let container = 0;
			let offset = 12;

			const trigger = document.getElementsByClassName("load-trigger")[0];
			const observer = new IntersectionObserver(entries => {
				if (entries[0].isIntersecting && !loading)
				{
					loading = true;
					fetch("/api/load_more.php?offset=" + offset)
						.then(res => res.text())
						.then(data => {
							if (data.trim() === "")
							{
								observer.disconnect();
								return;
							}
							document.getElementsByClassName("grid-container")[container].insertAdjacentHTML("afterend", data);
							container++;
							offset += 12;
							loading = false;
						})
						.catch(() => loading = false);
				}
			}, {});
			observer.observe(trigger);
		</script>
	</body>
</html>
