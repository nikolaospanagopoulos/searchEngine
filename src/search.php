<?php
include("conf.php");
if (isset($_GET["term"]) && strlen($_GET['term'])) {
	$term = $_GET["term"];
} else {
	exit("You must enter a search term");
}

$type = isset($_GET["type"]) ? $_GET["type"] : "sites";
$page = isset($_GET["page"]) ? $_GET["page"] : 1;

function getNumberOfResults($term)
{
	global $con;


	$query = $con->prepare("
    SELECT COUNT(*) as total 
    FROM sites 
    WHERE title LIKE :term1
    OR url LIKE :term2
    OR keywords LIKE :term3
    OR description LIKE :term4
");

	$searchTerm = "%" . $term . "%";
	$query->bindParam(":term1", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term2", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term3", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term4", $searchTerm, PDO::PARAM_STR);
	$query->execute();
	$row = $query->fetch(PDO::FETCH_ASSOC);
	return $row["total"];
}
function getNumberOfResultsForImgs($term)
{
	global $con;
	$query = $con->prepare("SELECT COUNT(*) as total FROM images WHERE (title LIKE :term1 OR alt LIKE :term2) AND broken=0");
	$searchTerm = "%" . $term . "%";
	$query->bindParam(":term1", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term2", $searchTerm, PDO::PARAM_STR);
	$query->execute();
	$row = $query->fetch(PDO::FETCH_ASSOC);
	return $row['total'];
}

function trimField($str, $limit)
{
	$dots = strlen($str) > $limit ? "..." : "";
	return substr($str, 0, $limit) . $dots;
}

function getResultsInHtml($page, $pageSize, $term)
{
	//pageStartingResult
	$fromLimit = ($page - 1) * $pageSize;
	global $con;
	$query = $con->prepare("
    SELECT *
    FROM sites 
    WHERE title LIKE :term1
    OR url LIKE :term2
    OR keywords LIKE :term3
	OR description LIKE :term4
	ORDER BY clicks DESC
    LIMIT :fromLimit, :pageSize
");

	$searchTerm = "%" . $term . "%";
	$query->bindParam(":term1", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term2", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term3", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":term4", $searchTerm, PDO::PARAM_STR);
	$query->bindParam(":fromLimit", $fromLimit, PDO::PARAM_INT);
	$query->bindValue(":pageSize", $pageSize, PDO::PARAM_INT);
	$query->execute();
	$resultsHtml = "<div class='siteResults'>";
	while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
		$id = $row['id'];
		$title = trimField($row['title'], 55);
		$url = $row['url'];
		$description = trimField($row['description'], 230);

		$resultsHtml .= "<div class='resultContainer'>
			<h3 class='title'>
<a class='result' href='$url' data-linkId='$id'>$title</a>
</h3>
<span class='url'>$url</span>
<span class='description'>$description</span>
			
			
			</div>";
	}
	$resultsHtml .= "</div>";
	return $resultsHtml;
}



?>
<!DOCTYPE html>
<html>

<head>
	<title>Welcome to Goodle</title>

	<link rel="stylesheet" type="text/css" href="assets/css/style.css">

</head>

<body>

	<div class="wrapper">

		<div class="header">


			<div class="headerContent">

				<div class="logoContainer">
					<a href="index.php">
						<img src="assets/img/doodleLogo.png">
					</a>
				</div>

				<div class="searchContainer">

					<form action="search.php" method="GET">

						<div class="searchBarContainer">

							<input class="searchBox" type="text" name="term" value="<?php echo $term ?>">
							<button class="searchButton">
								<img src="assets/img/icons/search.png">
							</button>
						</div>

					</form>

				</div>

			</div>


			<div class="tabsContainer">

				<ul class="tabList">

					<li class="<?php echo $type == 'sites' ? 'active' : '' ?>">
						<a href='<?php echo "search.php?term=$term&type=sites"; ?>'>
							Sites
						</a>
					</li>

					<li class="<?php echo $type == 'images' ? 'active' : '' ?>">
						<a href='<?php echo "search.php?term=$term&type=images"; ?>'>
							Images
						</a>
					</li>

				</ul>


			</div>



		</div>
		<div class="mainResultsSection">
			<?php
			if ($type == 'sites') {
				$pageLimit = 20;
				$numResults = getNumberOfResults($term);
				echo getResultsInHtml($page, $pageLimit, $term);
			} else {

				$numResults = getNumberOfResultsForImgs($term);
				$pageLimit = 30;
			}
			echo  "<p class='resultsCount'>" . $numResults . " results found</p>";

			?>
		</div>
		<div class="paginationContainer">
			<div class="pageButtons">
				<div class="pageNumberContainer">
					<img src="assets/img/pageStart.png">
				</div>

				<?php
				$pagesToShow = 10;
				$numPages = ceil($numResults / $pageLimit);
				$pagesLeft = min($pagesToShow, $numPages);
				$currentPage = $page - floor($pagesToShow / 2);
				if ($currentPage < 1) {
					$currentPage = 1;
				}
				while ($pagesLeft != 0 && $currentPage <= $numPages) {
					if ($page == $currentPage) {
						echo "
<div class='pageNumberContainer'>
<img src='assets/img/pageSelected.png'>
<span class='pageNumber'>$currentPage</span>
</div>
";
					} else {
						echo "
<div class='pageNumberContainer'>
<a href='search.php?term=$term&type=$type&page=$currentPage'>
<img src='assets/img/page.png'>
<span class='pageNumber'>$currentPage</span>
</a>
</div>
";
					}
					$currentPage++;
					$pagesLeft--;
				}

				?>

				<div class="pageNumberContainer">
					<img src="assets/img/pageEnd.png">
				</div>
			</div>
		</div>

	</div>
	<script>
		document.body.addEventListener('click', async (e) => {
			if (e.target.className == 'result') {
				var res = await fetch("ajax/updateLinkCount.php", {
					method: "POST",
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						linkId: e.target.dataset.linkid
					})

				})
				var result = await res.json();
				console.log(result)
			}
		})
	</script>

</body>

</html>
