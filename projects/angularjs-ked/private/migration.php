<?php
require "./autoload.php";
use api\models\Db;

	try {
		$db = new \PDO('mysql:host=localhost;dbname=ked', 'root', '');
	} catch (PDOException $e) {
		print "Error!: " . $e->getMessage() . "<br/>";
		die();
	}

	$crt = $db->exec("CREATE TABLE IF NOT EXISTS ked_items (
    id INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
    grow_start DATETIME,
    grow_end DATETIME,
	fall_end DATETIME,
	name VARCHAR(255) NOT NULL
);");

	$ins = $db->prepare("INSERT INTO ked_items (name,grow_start,grow_end,fall_end) VALUES ('21-21','2018-11-09 13:45:00','2018-11-09 14:25:00','2018-11-09 16:45:00')");
	$ins->execute();