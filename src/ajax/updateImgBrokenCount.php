<?php


include("../conf.php");

$json = file_get_contents('php://input');

$data = json_decode($json, true);


if (isset($data['id'])) {
	$query = $con->prepare('UPDATE images SET broken = 1 WHERE id=:id');
	$query->bindValue(':id', $data['id']);
	$query->execute();
	echo json_encode(['status' => "image failed", 'imgId' => $data['id']]);
} else {
	echo json_encode(['status' => "something went wrong while flagging img"]);
}
