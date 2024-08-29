<?php

try {
	$host = 'db';  // This is the service name defined in docker-compose.yml
	$db = 'search_engine';
	$user = 'root';
	$pass = 'rootpassword';
	$charset = 'utf8mb4';

	$options = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
	];

	$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

	$con =  new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
	echo "Db failure: " . $e->getMessage();
}
