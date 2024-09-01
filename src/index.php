<!DOCTYPE html>
<html>

<head>
	<title>Welcome to Doodle</title>

	<link rel="stylesheet" type="text/css" href="assets/css/style.css">

</head>

<body>

	<div class="wrapper indexPage">


		<div class="mainSection">

			<div class="logoContainer">
				<img src="assets/img/doodleLogo.png">
			</div>


			<div class="searchContainer">

				<form action="search.php" method="GET">

					<input class="searchBox" type="text" name="term">
					<input class="searchButton" type="submit" value="Search">


				</form>

			</div>


		</div>


	</div>

	<script>
		var form = document.querySelector('form')
		form.addEventListener('submit', function(e) {
			var formData = new FormData(form);
			if (formData.get('term').trim().length == 0) {
				e.preventDefault()
			}
		})
	</script>
</body>

</html>
</body>
</body>
</body>
</body>
</body>
</body>
</body>
</body>
</body>
