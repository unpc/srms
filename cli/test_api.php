<?php
require 'base.php';

$arr = [
	'equipment' => 15,
];
$rpc = new RPC('http://202.119.214.54/lims/api');
$locations = $rpc->eq_reserv_aatc->searchReservs($arr);
$token = $locations['token'];
$locations = $rpc->eq_reserv_aatc->getReservs($token,0,1);
var_dump($locations);
