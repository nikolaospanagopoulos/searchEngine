<?php
include("conf.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function parseRobotsTxt($url)
{
	$robotsTxtFileText = file_get_contents($url . "/robots.txt");
	if ($robotsTxtFileText === false) {
		return false;
	}
	$lines = explode("\n", $robotsTxtFileText);
	$rules = [];
	$currentUserAgent = null;
	foreach ($lines as $line) {
		$line = trim($line);
		if (empty($line) || strpos($line, '#') === 0 || strpos($line, 'Sitemap') === 0) {
			continue;
		}
		if (stripos($line, 'User-agent:') === 0) {
			$currentUserAgent = trim(substr($line, 11));
			$rules[$currentUserAgent] = [
				'Allow' => [],
				'Disallow' => []
			];
		}
		if ($currentUserAgent !== null) {
			if (stripos($line, 'Disallow:') === 0) {
				$rules[$currentUserAgent]['Disallow'][] = trim(substr($line, 9));
			} elseif (stripos($line, 'Allow:') === 0) {
				$rules[$currentUserAgent]['Allow'][] = trim(substr($line, 6));
			}
		}
	}
	if (isset($rules['*'])) {
		foreach ($rules as $userAgent => $directives) {
			if ($userAgent !== '*' && empty($directives['Allow'] && empty($directives['Allow']))) {
				$rules[$userAgent] = $rules['*'];
			}
		}
	}
	return $rules;
}


function insertIntoDb($url, $title, $description, $keywords)
{
	if (linkExists($url)) {
		return;
	}
	global $con;
	$query = $con->prepare("INSERT INTO sites(url,title,description,keywords) VALUES(:url,:title,:description,:keywords)");

	$query->bindParam(":url", $url);
	$query->bindParam(":title", $title);
	$query->bindParam(":description", $description);
	$query->bindParam(":keywords", $keywords);


	$query->execute();
}

function insertImgIntoDb($siteUrl, $imageUrl, $alt, $title)
{
	global $con;
	$query = $con->prepare("INSERT INTO images(siteUrl,imageUrl,alt,title) VALUES(:siteUrl,:imageUrl,:alt,:title)");

	$query->bindParam(":siteUrl", $siteUrl);
	$query->bindParam(":imageUrl", $imageUrl);
	$query->bindParam(":alt", $alt);
	$query->bindParam(":title", $title);


	$query->execute();
}
function linkExists($url)
{
	global $con;
	$query = $con->prepare("SELECT * FROM sites WHERE url=:url");

	$query->bindParam(":url", $url);

	$query->execute();

	return $query->rowCount() != 0;
}



function isUrlAllowed($url, $rules, $userAgent = "*")
{
	$parsedUrl = parse_url($url);
	$path = $parsedUrl['path'] ?? '/';
	$rulesForAgent = getRulesForAgent($rules, $userAgent);
	if ($rulesForAgent) {
		foreach ($rulesForAgent['Disallow'] as $disallowedPath) {
			if ($disallowedPath === '/' || preg_match('/' . str_replace('*', '.*', preg_quote($disallowedPath, '/')) . '/', $path)) {
				return false;
			}
		}
		foreach ($rulesForAgent['Allow'] as $allowedPath) {
			if (preg_match('/' . str_replace("*", ".*", preg_quote($allowedPath, '/')) . '/', $path)) {
				return true;
			}
		}
	}
	return true;
}


function getRulesForAgent($rules, $userAgent)
{
	if (isset($rules[$userAgent])) {
		return $rules[$userAgent];
	}
	if (isset($rules["*"])) {
		return $rules["*"];
	}
	return null;
}

function crawlPage($url, $depth = 1)
{
	static $visited = [];
	if ($depth <= 0 || isset($visited[$url])) {
		return;
	}
	$visited[$url] = true;
	echo "<h4>now crawling->" . $url . "</h4>";

	$html = file_get_contents($url);
	if ($html === false) {
		return;
	}
	$dom = new DOMDocument;
	@$dom->loadHTML($html);
	$links = $dom->getElementsByTagName('a');

	foreach ($links as $link) {
		$href = $link->getAttribute('href');

		if (empty($href) || strpos($href, "#") === 0 || substr($href, 0, 11) == 'javascript:') {
			continue;
		}
		$absoluteUrl = urlToAbsolute($url, $href);
		$robotsTxtRules = parseRobotsTxt(parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST));
		if ($robotsTxtRules && !isUrlAllowed($absoluteUrl, $robotsTxtRules)) {
			echo "<h2>not crawling " . $absoluteUrl . "</h2>";
			continue;
		} else {
			echo "<p>crawling " . $absoluteUrl . "</p>";
			getDetails($absoluteUrl);
		}
		crawlPage($absoluteUrl, $depth - 1);
	}
}
function getDetails($href)
{
	$options = array(
		'http' => array('method' => "GET", 'header' => "User-Agent: nikosBot/0.1\n")
	);
	$context = stream_context_create($options);
	$document = new DOMDocument();
	$content = file_get_contents($href, false, $context);
	if ($content) {
		@$document->loadHTML(file_get_contents($href, false, $context));
		$titles = $document->getElementsByTagName('title');
		if (sizeof($titles) == 0 || $titles->item(0) == NULL) {
			return;
		}
		$title = $titles->item(0)->nodeValue;
		$title = str_replace("\n", "", $title);
		if (trim($title) == "") {
			return;
		}
		$description = "";
		$keywords = "";
		$metaTags = $document->getElementsByTagName('meta');
		foreach ($metaTags as $meta) {
			if ($meta->getAttribute("name") == 'description') {
				$description = $meta->getAttribute('content');
			}
			if ($meta->getAttribute("name") == 'keywords') {
				$keywords = $meta->getAttribute('content');
			}
		}
		$description = str_replace("\n", "", $description);
		$keywords = str_replace("\n", "", $keywords);
		echo "Url: " . $href . " title: " . $title . " description: " . $description . " keywords: " . $keywords;
		insertIntoDb($href, $title, $description, $keywords);

		static $visitedImg = [];
		$images = $document->getElementsByTagName('img');
		foreach ($images as $img) {
			$src = $img->getAttribute('src');
			$alt = $img->getAttribute('alt');
			$title = $img->getAttribute('title');
			if (!in_array($src, $visitedImg)) {
				$visitedImg[] = $src;
				insertImgIntoDb($href, $src, $alt, $title);
			}
		}
	}
}
function urlToAbsolute($baseUrl, $relativeUrl)
{
	$scheme = parse_url($baseUrl)['scheme'];
	$host = parse_url($baseUrl)['host'];
	if (substr($relativeUrl, 0, 2) == '//') {
		return $scheme . ":" . $relativeUrl;
	} else if (substr($relativeUrl, 0, 1) == '/') {
		return $scheme . "://" . $host . $relativeUrl;
	} else if (substr($relativeUrl, 0, 2) == './') {
		return $scheme . "://" . $host . dirname(parse_url($baseUrl)['path']) . substr($relativeUrl, 1);
	} else if (substr($relativeUrl, 0, 3) == '../') {
		return $scheme . "://" . $host . "/" . $relativeUrl;
	} else if (substr($relativeUrl, 0, 4) !== 'http') {
		return $scheme . "://" . $host . "/" . $relativeUrl;
	}
	return $relativeUrl;
}
crawlPage("https://www.bbc.com/");
