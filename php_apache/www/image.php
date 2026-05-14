<?php
	require_once "db.php";
	require_once "auth.php";

	if (empty($_SESSION["csrfToken"]))
		$_SESSION["csrfToken"] = bin2hex(random_bytes(32));

	$toImage = $_GET["id"] ?? NULL;
	$notFound = false;

	if (!$toImage || !is_numeric($toImage))
		exit("Invalid link.");

	$imageReq = $pdo->prepare("SELECT 
		images.*,
		users.username AS authorName,
		COUNT(DISTINCT likes.id) AS likesAmount,
		COUNT(DISTINCT comments.id) AS commentsAmount
		FROM images
		LEFT JOIN users ON images.author = users.id
		LEFT JOIN likes ON likes.toImage = images.id
		LEFT JOIN comments ON comments.toImage = images.id
		WHERE images.id = ?
		GROUP BY images.id
	");
	$imageReq->execute([$toImage]);
	if ($imageReq->rowCount() <= 0)
		$notFound = true;
	$image = $imageReq->fetch(PDO::FETCH_ASSOC);


	$commentsReq = $pdo->prepare("SELECT
		comments.*,
		users.username AS authorName
		FROM comments
		LEFT JOIN users ON comments.author = users.id
		WHERE comments.toImage = ?
		ORDER BY comments.createdAt ASC
	");
	$commentsReq->execute([$toImage]);
	$comments = $commentsReq->fetchAll(PDO::FETCH_ASSOC);

	$isLiked = false;
	if (isset($_SESSION["userID"]))
	{
		$likeReq = $pdo->prepare("SELECT 1 FROM likes WHERE toImage = ? AND author = ? LIMIT 1");
		$likeReq->execute([$toImage, $_SESSION["userID"]]);
		$isLiked = $likeReq->fetchColumn();
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
		<main class="image-main">
			<?php if (!$notFound): ?>
				<div class="image-image">
					<img src="<?php echo htmlspecialchars($image['path']) ?>" alt="Montage uploaded by <?php $image['authorName']?>">
				</div>
				<div class="image-side">
					<div class="image-side-body">
						<div class="image-side-comments">
						<?php if ($comments): ?>
							<?php foreach ($comments as $comment): ?>
								<div>
									<p><strong><?php echo htmlspecialchars($comment["authorName"]); ?></strong>: <?php echo htmlspecialchars($comment["message"]); ?></p>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p class="no-comment">No comments yet.</p>
						<?php endif; ?><div>
						</div>
					</div>
					<form class="image-side-form">
						<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken']; ?>">
						<input type="text" class="image-side-input" name="comment" placeholder="Write a comment..." minlength="1" maxlength="255" <?php echo !isset($_SESSION['userID']) ? "disabled" : "";?> aria-label="Write a comment"/>
						<button type="submit" class="send-button" <?php echo !isset($_SESSION['userID']) ? "disabled" : "";?>>
							<span>Send</span>
							<i class="fa-solid fa-paper-plane"></i>
						</button>
					</form>
				</div>
				<div class="image-side-footer">
					<div class="like-button-wrapper">
						<button type="submit" class="like-button">
							<?php if ($isLiked): ?>
								<i class="fa-solid fa-heart color-red"></i>
							<?php else: ?>
								<i class="fa-regular fa-heart"></i>
							<?php endif; ?>
							<span class="like-amount"><?php echo htmlspecialchars($image['likesAmount']); ?></span>
						</button>
					</div>
					<div class="comment-button-wrapper">
						<button class="comment-button">
							<i class="fa-solid fa-comment"></i>
							<span class="comments-amount"><?php echo htmlspecialchars($image['commentsAmount']); ?></span>
						</button>
					</div>
				</div>
			<?php else: ?>
				<p>Oops, the image was not found.<br>Maybe it was deleted.</p>
			<?php endif; ?>
		</main>
		<footer class="image-footer">
			<p>camagru by apayen@student.42.fr</p>
		</footer>
		<script>
			let likeButton = document.getElementsByClassName("like-button")[0];
			let sendButton = document.getElementsByClassName("send-button")[0];
			if (likeButton)
			{
				likeButton.addEventListener("click", () => {
					fetch("/api/like.php", {method: "POST", headers: {"Content-Type": "application/json"},
						body: JSON.stringify({
							id: <?php echo $toImage; ?>,
							csrfToken: <?php echo json_encode($_SESSION['csrfToken']); ?>
						})})
						.then(res => res.json())
						.then(data => {
							if (!data.error)
							{
								document.getElementsByClassName("like-amount")[0].innerHTML = data.likesAmount;
								let icon = document.getElementsByClassName("fa-heart")[0];
								if (data.status == "liked")
								{
									icon.classList.add("fa-solid");
									icon.classList.remove("fa-regular");
									icon.classList.add("color-red");
								}
								else
								{
									icon.classList.add("fa-regular");
									icon.classList.remove("fa-solid");
									icon.classList.remove("color-red");
								}
							}
						});
				});
			}
			if (sendButton)
			{
				sendButton.addEventListener("click", (e) => {
					e.preventDefault();
					const msgInput = document.getElementsByClassName("image-side-input")[0];
					let msg = "";

					if (msgInput)
					{
						msg = msgInput.value;
						msg = msg.trim();
					}
					if (msg != "")
					{
						fetch("/api/comment.php", {method: "POST", headers: {"Content-Type": "application/json"},
							body: JSON.stringify({
								toImage: <?php echo $toImage; ?>,
								msg: msg,
								csrfToken: <?php echo json_encode($_SESSION['csrfToken']); ?>
							})})
							.then(res => res.json())
							.then(data => {
								if (!data.error)
								{
									container = document.getElementsByClassName("image-side-comments")[0];
									if (container)
									{
										const first = document.getElementsByClassName("no-comment")[0];
										if (first)
											first.remove();
										const div = document.createElement("div");
										const p = document.createElement("p");
										const strong = document.createElement("strong");
										strong.textContent = <?php echo json_encode($_SESSION['username'] ?? ''); ?>;
										p.appendChild(strong);
										p.appendChild(document.createTextNode(": " + data.msg));
										div.appendChild(p);
										container.appendChild(div);
										container.scrollTop = container.scrollHeight;
									}
									document.getElementsByClassName("comments-amount")[0].innerHTML = data.commentsAmount;
								}
							});
					}
					msgInput.value = "";
				});
			}
		</script>
	</body>
</html>
