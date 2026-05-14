<?php
	require_once "db.php";
	require_once "auth.php";

	if (!isset($_SESSION["userID"]))
	{
		header("Location: /login.php");
		exit;
	}
	if (isset($_FILES["image"]))
	{
		if (!isset($_POST["csrfToken"]) || !isset($_SESSION["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $_POST["csrfToken"]))
			exit("Invalid CSRF token.");
		$code = $_FILES["image"]["error"];
		
		if ($code == UPLOAD_ERR_OK)
		{
			$_SESSION["error"] = "";
			$file = $_FILES["image"];
			$filename = $file["name"];
			$tempname = $file["tmp_name"];
			$fileSize = $file["size"];
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime = finfo_file($finfo, $tempname);
			finfo_close($finfo);

			if (($extension != "jpg" && $extension != "jpeg" && $extension != "png") || ($mime != "image/jpeg" && $mime != "image/png"))
				$_SESSION["error"] = "Only JPG or PNG files are allowed.";
			else
			{
				$dir = "/uploads/" . $_SESSION["userID"];
				$path = $dir . "/" . uniqid() . "." . $extension;
				if (!is_dir(__DIR__ . $dir))
					mkdir(__DIR__ . $dir, 0774, true);
				$assets = isset($_POST["assets"]) ? json_decode($_POST["assets"], true) : NULL;
				if (!$assets)
					$_SESSION["error"] = "No asset applied.";
				else
				{
					$allowed = [
						"/images/googly.png",
						"/images/sunglasses.png",
						"/images/moustache.png",
						"/images/anger.png",
						"/images/traffic.png",
						"/images/question.png",
						"/images/tbcontinued.png",
						"/images/error.png",
						"/images/football.png"
					];
					$applied = 0;
					if ($extension === "png")
						$baseImage = imagecreatefrompng($tempname);
					else
						$baseImage = imagecreatefromjpeg($tempname);
					foreach ($assets as $asset)
					{
						if (!in_array($asset["src"], $allowed))
							continue;
						$applied++;
					}
					if ($applied)
					{
						foreach ($assets as $asset)
						{
							if (!in_array($asset["src"], $allowed))
								continue;
							$overlay = imagecreatefrompng(__DIR__ . $asset["src"]);
							imagealphablending($baseImage, true);
							imagesavealpha($baseImage, true);
							$overlayWidth = ($asset["width"] / 100) * imagesx($baseImage);
							$overlayHeight = ($asset["height"] / 100) * imagesy($baseImage);
							$x = ($asset["x"] / 100) * imagesx($baseImage) - ($overlayWidth / 2);
							$y = ($asset["y"] / 100) * imagesy($baseImage) - ($overlayHeight / 2);
							imagecopyresampled($baseImage, $overlay, (int)$x, (int)$y, 0, 0, (int)$overlayWidth, (int)$overlayHeight, imagesx($overlay), imagesy($overlay));
							imagedestroy($overlay);
						}
						$req = $pdo->prepare("INSERT INTO images (path, author) VALUES (?, ?)");
						$req->execute([$path, $_SESSION["userID"]]);
						$_SESSION["success"] = "Image uploaded successfully!";
						$_SESSION["error"] = "";
					}
					else
						$_SESSION["error"] = "No asset applied.";
					if (($extension === "png" && !imagepng($baseImage, __DIR__ . $path)) || (($extension === "jpg" || $extension === "jpeg") && !imagejpeg($baseImage, __DIR__ . $path)))
							$_SESSION["error"] = "An error occurred. Please try again later.";
					imagedestroy($baseImage);
				}
			}
		}
		elseif ($code != UPLOAD_ERR_NO_FILE)
		{
			if ($code == UPLOAD_ERR_INI_SIZE)
				$_SESSION["error"] = "File is too large.";
			elseif ($code == UPLOAD_ERR_FORM_SIZE)
				$_SESSION["error"] = "File is too large.";
			elseif ($code == UPLOAD_ERR_PARTIAL)
				$_SESSION["error"] = "An error occured. Please try again later.";
			elseif ($code == UPLOAD_ERR_NO_TMP_DIR)
				$_SESSION["error"] = "An error occured. Please try again later.";
			elseif ($code == UPLOAD_ERR_CANT_WRITE)
				$_SESSION["error"] = "An error occured. Please try again later.";
			elseif ($code == UPLOAD_ERR_EXTENSION)
				$_SESSION["error"] = "The upload was blocked by an extension.";
			else
				$_SESSION["error"] = "An error occured. Please try again later.";
		}
		$_SESSION["csrfToken"] = bin2hex(random_bytes(32));
		header("Location: /edition.php");
		exit;
	}
	$req = $pdo->prepare("SELECT 
			images.*,
			users.username AS authorName,
			COUNT(DISTINCT likes.id) AS likesAmount,
			COUNT(DISTINCT comments.id) AS commentsAmount
			FROM images
			LEFT JOIN users ON images.author = users.id
			LEFT JOIN likes ON likes.toImage = images.id
			LEFT JOIN comments ON comments.toImage = images.id
			WHERE images.author = ?
			GROUP BY images.id
			ORDER BY images.createdAt DESC, images.id DESC
		");
	$req->execute([$_SESSION["userID"]]);
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Create and share crazy montage pictures with your webcam!">
		<title>camagru - edition</title>
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
		<div class="edition-body">
			<main class="edition-main">
				<div class="edition-main-header">
					<button type="button" class="edition-webcam-button"><span>Use your webcam</span></button>
					<button type="button" class="edition-upload-button"><span>Upload an image</span></button>
				</div>
				<form class="form-edition" action="edition.php" method="POST" enctype="multipart/form-data">
					<input class="edition-input-asset" type="hidden" name="assets">
					<div class="edition-upload">
						<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken']; ?>">
						<input class="edition-upload-input hidden" type="file" name="image" aria-label="Upload an image"/>
						<p class="error">
							<?php 
							echo isset($_SESSION["error"]) ? $_SESSION["error"] : ""; 
							unset($_SESSION["error"]);
						?>
						</p>
						<p class="success">
						<?php
							echo isset($_SESSION["success"]) ? $_SESSION["success"] : " "; 
							unset($_SESSION["success"]);
						?>
						</p>
					</div>
					<div class="edition-preview">
						<div class="edition-video-wrapper">
							<video class="edition-video-preview" autoplay playsinline></video>
							<canvas class="edition-canvas hidden"></canvas>
							<p></p>
						</div>
						<img class="edition-image-preview hidden" aria-label="Preview of montage">
					</div>
					<button class="edition-submit" type="submit" disabled>Upload</button>
				</form>
				<div class="edition-main-assets">
					<button>
						<img src="/images/googly.png" alt="Add asset: Giant googly eyes">
					</button>
					<button>
						<img src="/images/sunglasses.png" alt="Add asset: Cool pixelated sunglasses">
					</button>
					<button>
						<img src="/images/moustache.png" alt="Add asset: Funny moustache">
					</button>
					<button>
						<img src="/images/anger.png" alt="Add asset: Anime's angry veins">
					</button>
					<button>
						<img src="/images/traffic.png" alt="Add asset: Traffic cone's hat">
					</button>
					<button>
						<img src="/images/question.png" alt="Add asset: Lot of question mark in half circle">
					</button>
					<button>
						<img src="/images/tbcontinued.png" alt="Add asset: To be continued">
					</button>
					<button>
						<img src="/images/error.png" alt="Add asset: Windows error">
					</button>
					<button>
						<img src="/images/football.png" alt="Add asset: Football">
					</button>
				</div>
			</main>
			<div class="edition-border"></div>
			<side class="edition-side">
			<?php while ($row = $req->fetch(PDO::FETCH_ASSOC)): ?>
				<div class="grid-items">
					<div class="grid-items-a">
						<img src="<?php echo htmlspecialchars($row['path']); ?>" alt="Former montage by yourself">
						<div class="grid-items-overlay grid-items-overlay-edition">
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
							<form class="form-delete" method="POST" action="/api/delete.php">
								<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken']; ?>">
								<input type="hidden" name="id" value="<?php echo $row['id']?>">
								<button class="delete-button" type="submit" name="submit">
									<i class="fa-solid fa-trash-can"></i>
									<span>Delete</span>
								</button>
							</form>
						</div>
					</div>
				</div>
			<?php endwhile; ?>
			</side>
		</div>
		<footer>
			<p>camagru by apayen@student.42.fr</p>
		</footer>
	</body>
	<script>
		// Vars
		const editionWrapper = document.getElementsByClassName("edition-preview")[0];
		const videoWrapper = document.getElementsByClassName("edition-video-wrapper")[0];
		const video = document.getElementsByClassName("edition-video-preview")[0];
		const canvas = document.getElementsByClassName("edition-canvas")[0];
		const image = document.getElementsByClassName("edition-image-preview")[0];
		const input = document.getElementsByClassName("edition-upload-input")[0];
		const uploadButton = document.getElementsByClassName("edition-upload-button")[0];
		const webcamButton = document.getElementsByClassName("edition-webcam-button")[0];
		const montageButton = document.getElementsByClassName("edition-edition-button")[0];
		const galleryButton = document.getElementsByClassName("edition-gallery-button")[0];
		const montage = document.getElementsByClassName("edition-main")[0];
		const gallery = document.getElementsByClassName("edition-side")[0];
		const webcamError = document.querySelector(".edition-video-wrapper p");
		const assetsContainer = document.getElementsByClassName("edition-main-assets")[0];
		const submitButton = document.getElementsByClassName("edition-submit")[0];
		const formRes = document.querySelectorAll(".edition-upload p");
		let webcamFeed = null;
		let webcamEnabled = false;
		let wrapperRect;
		let nbAsset = 0;
		let xPercent;
		let yPercent;
		let asset = null;
		let offsetX = 0;
		let offsetY = 0;

		// Webcam/Camera
		if (navigator.mediaDevices.getUserMedia === undefined)
		{
			navigator.mediaDevices = {};
			navigator.mediaDevices.getUserMedia = function (constraints) {
				var getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
				if (!getUserMedia)
					return Promise.reject(new Error("getUserMedia is not implemented in this browser"));
				return (new Promise(function (resolve, reject) {
					getUserMedia.call(navigator, constraints, resolve, reject);
				}));
			};
		}
		function handleWebcam()
		{
			navigator.mediaDevices.getUserMedia({video: true})
				.then(stream => {
					if (webcamEnabled)
					{
						webcamFeed = stream;
						video.srcObject = stream;
					}
					else
					{
						webcamFeed = null;
						video.srcObject = null;
					}
				})
				.catch(err => {
					webcamFeed = null;
					video.srcObject = null;
					webcamEnabled = false;
					if (err.name === "NotAllowedError")
						webcamError.innerText = "Camera access was denied";
					else if (err.name === "NotFoundError")
						webcamError.innerText = "No camera found";
					else if (err.name === "NotReadableError")
						webcamError.innerText = "Camera is already in use by another application";
					else
						webcamError.innerText = "Unable to access camera";
					webcamError.style.zIndex = 0;
				});
		}
		// Switching design's display
		webcamButton.addEventListener("click", () => {
			webcamEnabled = true;
			input.classList.add("hidden");
			image.classList.add("hidden");
			videoWrapper.classList.remove("hidden");
			webcamError.style.zIndex = -1;
			formRes[0].innerText = "";
			formRes[1].innerText = "";
			handleWebcam();
		});
		uploadButton.addEventListener("click", () => {
			webcamEnabled = false;
			videoWrapper.classList.add("hidden");
			webcamError.innerText = "";
			webcamError.style.zIndex = -1;
			image.classList.remove("hidden");
			input.classList.remove("hidden");
			formRes[0].innerText = "";
			formRes[1].innerText = "";
			if (webcamFeed)
				webcamFeed.getTracks().forEach(track => track.stop());
			video.srcObject = null;
			webcamFeed = null;
		});
		// Loading image upload preview
		input.addEventListener("change", () => {
			const file = input.files[0];
			if (file)
			{
				image.src = URL.createObjectURL(file);
				image.classList.remove("hidden");
				videoWrapper.classList.add("hidden");
			}
			else
			{
				image.classList.add("hidden");
				videoWrapper.classList.remove("hidden");
			}
		});
		// Adding an asset
		assetsContainer.addEventListener("click", (e) => {
			if (webcamError.classList.contains("hidden") || (!image.classList.contains("hidden") && image.src == ""))
				return;
			const button = e.target.closest("button");
			if (!button)
				return;
			const img = button.querySelector("img");
			const divImg = document.createElement("div");
			const newImg = document.createElement("img");
			newImg.src = img.src;
			newImg.onload = () => {
				const scaleWidth = image.width / image.naturalWidth;
				const scaleHeight = image.height / image.naturalHeight;
				newImg.style.width = newImg.naturalWidth * scaleWidth + "px";
				newImg.style.height = newImg.naturalHeight * scaleHeight + " px";
				wrapperRect = editionWrapper.getBoundingClientRect();
				xPercent = (((wrapperRect.width - newImg.width) / 2) / wrapperRect.width) * 100;
				yPercent = (((wrapperRect.height - newImg.height) / 2) / wrapperRect.height) * 100;
				divImg.style.left = xPercent + "%";
				divImg.style.top = yPercent + "%";
			};
			divImg.appendChild(newImg);
			divImg.classList.add("edition-assets");
			editionWrapper.appendChild(divImg);
			nbAsset++;
			submitButton.disabled = false;
		});
		// Moving assets
		document.addEventListener("dragstart", (e) => {
			if (e.target.classList.contains("edition-assets") || e.target.tagName == "IMG")
				e.preventDefault();
		});
		function getPointerPosition(e)
		{
			if (e.touches && e.touches.length > 0)
				return {x: e.touches[0].clientX, y: e.touches[0].clientY};
			return {x: e.clientX, y: e.clientY};
		}
		function handleSelection(e)
		{
			oldButton = document.getElementsByClassName("delete-asset-button")[0];
			oldButtonIcon = document.getElementsByClassName("delete-asset-icon")[0];
			if (e.target == oldButton || e.target == oldButtonIcon)
			{
				const target = e.target.closest(".edition-assets");
				if (target)
					target.remove();
				if (oldButton)
					oldButton.remove();
				nbAsset--;
				if (!nbAsset)
					submitButton.disabled = true;
				return;
			}
			if (oldButton)
				oldButton.remove();
			const target = e.target.closest(".edition-assets");
			if (!target)
				return;
			asset = target;
			const rect = asset.getBoundingClientRect();
			const pos = getPointerPosition(e);
			offsetX = pos.x - rect.left;
			offsetY = pos.y - rect.top;
			delButton = document.createElement("button");
			delButton.type = "button";
			delButton.classList.add("delete-button", "delete-asset-button");
			iButton = document.createElement("i");
			iButton.classList.add("fa-solid", "fa-trash-can", "delete-asset-icon");
			delButton.append(iButton);
			asset.append(delButton);
		}
		function handleMove(e)
		{
			if (asset)
			{
				wrapperRect = editionWrapper.getBoundingClientRect();
				pos = getPointerPosition(e);
				xPercent = ((pos.x - offsetX - wrapperRect.left) / wrapperRect.width) * 100;
				yPercent = ((pos.y - offsetY - wrapperRect.top) / wrapperRect.height) * 100;
				asset.style.left = xPercent + "%";
				asset.style.top = yPercent + "%";
			}
		}
		document.addEventListener("mousedown", (e) => { handleSelection(e); });
		document.addEventListener("touchstart", (e) => { handleSelection(e); });
		document.addEventListener("mousemove", (e) => { handleMove(e) });
		document.addEventListener("touchmove", (e) => { handleMove(e) });
		document.addEventListener("mouseup", () => { asset = null; });
		document.addEventListener("touchend", () => { asset = null; });
		document.addEventListener("touchcancel", () => { asset = null; });
		// Form submit
		document.addEventListener("submit", async (e) => {
			const form = e.target;
			if (form.classList.contains("form-delete"))
			{
				e.preventDefault();
				const formData = new FormData(form);
				try
				{
					fetch(form.action, {method: "POST", body: formData})
						.then(res => res.json())
						.then(data => {
							if (!data.error)
							{
								const grid = form.closest(".grid-items");
								if (grid)
									grid.remove();
						}
					});
				}
				catch (error) {}
			}
			else if (form.classList.contains("form-edition"))
			{
				if (!nbAsset)
					return;
				if (!videoWrapper.classList.contains("hidden"))
				{
					e.preventDefault();
					const context = canvas.getContext('2d');
					canvas.width = video.videoWidth;
					canvas.height = video.videoHeight;
					context.drawImage(video, 0, 0, canvas.width, canvas.height);
					canvas.toBlob(blob => {
						const file = new File([blob], "photo.png", { type: "image/png" });
						const dt = new DataTransfer();
						dt.items.add(file);
						input.files = dt.files;
						form.submit();
					}, "image/png");
				}
				const assetDivs = document.querySelectorAll(".edition-assets");
				const data = [];
				assetDivs.forEach(div => {
					let img = div.querySelector("img");
					data.push({
						src: new URL(img.src).pathname,
						x: (div.offsetLeft + img.width / 2) / editionWrapper.offsetWidth * 100,
						y: (div.offsetTop + img.height / 2) / editionWrapper.offsetHeight * 100,
						width: img.width / editionWrapper.offsetWidth * 100,
						height: img.height / editionWrapper.offsetHeight * 100
					});
				});
				document.getElementsByClassName("edition-input-asset")[0].value = JSON.stringify(data);
			}
		});
		webcamEnabled = true;
		handleWebcam();
	</script>
</html>
