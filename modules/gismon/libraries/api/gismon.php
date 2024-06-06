<?php

class API_GISMon {

	function get_buildings($swlon, $nelon, $swlat, $nelat) {
		return GISMon::get_buildings($swlon, $nelon, $swlat, $nelat);
	}
	
	function get_equipments($bid) {
		return GISMon::get_equipments($bid);
	}
}
