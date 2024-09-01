<?php
include("../conf.php");

$json = file_get_contents('php://input');

$data = json_decode($json, true);

if (isset($data['linkId'])) {
	$query = $con->prepare("UPDATE sites SET clicks = clicks + 1 WHERE id=:id");
	$query->bindParam("id", $data['linkId']);
	$query->execute();
	echo json_encode(['status' => "success", 'linkId' => $data['linkId']]);
} else {
	echo json_encode(['status' => "error"]);
}
