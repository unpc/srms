<?php

include 'base.php';

// main

$header = [
	'ID','名称','地址','负责人','控制地址','离线开门人员','关联仪器',
	];

tr_echo($header);


foreach (Q('door') as $door) {
	$equipments = Q("{$door}<asso equipment");
	$free_users = array_values($door->get_free_access_cards());
	
	$tr = [
		$door->id,
		$door->name,
		$door->location1 . ' ' . $door->location2,
		get_object_name($door->incharger),
		$door->in_addr . ' ' . 'lock#' . $door->lock_id . ' ' . 'detector#' . $door->detector_id,
		get_objects_names($free_users),
		get_objects_names($equipments),
		];

	tr_echo($tr);
	
}


// functions

function td_echo() {
	$content = join(' ', func_get_args());
	// echo '"' . $content . '"' . "\t";
	echo $content . "\t";
}

function tr_echo($tr) {
	foreach ($tr as $td) {
		td_echo($td);
	}
	echo "\n";
}

function get_objects_names($objects) {
	$names = [];
	
	foreach ($objects as $object) {
		$names[] = get_object_name($object);
	}

	return join(' ', $names);
}

function get_object_name($object) {
	return $object->name . '[' . $object->id . ']';
}
