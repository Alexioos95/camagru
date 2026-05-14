<?php
	require_once "../db.php";

	$limit = 12;
	$offset = $_GET["offset"] ?? NULL;

	if (!$offset || !is_numeric($offset))
		$offset = 0;
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
	$req->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
	$req->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
	$req->execute();
?>

	<?php if ($req->rowCount() > 0): ?>
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
	<?php endif; ?>
