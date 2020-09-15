<?php
require "../private/autoload.php";

use api\models\Db;

function getKedLength(string $start_time, string $end_time){
	$length_in_seconds = strtotime($end_time) - strtotime($start_time);
	$length_in_minutes = (int)($length_in_seconds / 60);
	return $length_in_minutes;
}

function stringTOdatetime($time){
	return date('Y-m-d H:i:s',strtotime(substr($time,0,24)));
}

	try {
		$db = new \PDO('mysql:host=localhost;dbname=ked', 'root', '');
	} catch (PDOException $e) {
		print "Error!: " . $e->getMessage() . "<br/>";
		die();
	}

if(!empty($_POST)){
	$ins = $db->prepare("INSERT INTO ked_items (name,grow_start,grow_end,fall_end) VALUES ('".$_POST['ked_name']."','".stringTOdatetime($_POST['grow_start'])."','".stringTOdatetime($_POST['grow_end'])."','".stringTOdatetime($_POST['fall_end'])."')");
	$ins->execute();
}

$sth = $db->prepare("SELECT * FROM ked_items ORDER BY id ASC");
$sth->execute();

/* Извлечение всех оставшихся строк результирующего набора */
$result = $sth->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $i=>$item){
	$grow_length = getKedLength($item['grow_start'],$item['grow_end']);
	$fall_length = getKedLength($item['grow_end'],$item['fall_end']);
	$result[$i]['ked_rate'] = $fall_length / $grow_length;
	$result[$i]['lose_rate'] = $grow_length / $fall_length;
	$result[$i]['cycle_length'] = $grow_length + $fall_length;
	$result[$i]['cycles_in_day'] = 1440 / $result[$i]['cycle_length'];
	$result[$i]['hours_of_work'] = $result[$i]['cycles_in_day'] * $grow_length / 60;
	$result[$i]['hours_of_economy'] = $result[$i]['cycles_in_day'] * $fall_length / 60;
}

echo json_encode($result);