#!/usr/bin/env php
<?php
require 'base.php';

$root = Tag_Model::root('group');
connectLabs($root);
connectEquipments($root);
connectPeople($root);

function connectLabs($root){
	$db = Database::factory();
	foreach (Q('lab') as $lab) {
		echo $lab->name . "\n";
		$tag = $lab->group;
		$id = $lab->id;
		$query = "delete from _r_tag_group_lab where id2 = $id and id1 in (select id from tag_group where root_id = $root->id)";
		$db->query($query);
		$tag->connect($lab);
	}
}

function connectEquipments($root) {
	$db = Database::factory();
	foreach (Q('equipment') as $equipment) {
		echo $equipment->name . "\n";
		$tag = $equipment->group;
		$id = $equipment->id;
		$query = "delete from _r_tag_group_equipment where id2 = $id and id1 in (select id from tag_group where root_id = $root->id)";
		$db->query($query);
		$tag->connect($equipment);
	}
}

function connectPeople($root) {
	$db = Database::factory();
	foreach (Q('user') as $user) {
		echo $user->name . "\n";
		$tag = $user->group;
		$id = $user->id;
		$query = "delete from _r_user_tag_group where id1 = $id and id2 in (select id from tag_group where root_id = $root->id)";
		$db->query($query);
		$tag->connect($user);
	}
}
