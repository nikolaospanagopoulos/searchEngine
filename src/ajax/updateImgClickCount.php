<?php
include("../conf.php");

$json = file_get_contents('php://input');

$data = json_decode($json, true);

if (isset($data['imgId'])) {
	$query = $con->prepare("UPDATE images SET clicks = clicks + 1 WHERE id=:imgId");
	$query->bindParam(":imgId", $data['imgId']);
	$query->execute();
	echo json_encode(['status' => "success", 'imgId' => $data['imgId']]);
} else {
	echo json_encode(['status' => "error"]);
}
