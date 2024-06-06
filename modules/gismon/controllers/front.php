<?php

class Front_Controller extends Layout_Controller {

	function index() {
		$longitude = (float) Config::get('gis.longitude');
		$latitude = (float) Config::get('gis.latitude');

		$key = Config::get('gismon.web.key');

		$this->add_css('gismon:front');
		$url = Config::get('gismon.web.url');
		$this->add_js($url.'/api?v=2.0&ak='.$key, FALSE);

		$equipments = [];

		$this->layout = V('application:layout_plain');
		$this->layout->body = V('gismon:front/index', ['longitude'=>$longitude, 'latitude'=>$latitude, 'equipments'=>$equipments]);
	}

}

class Front_AJAX_Controller extends AJAX_Controller {

		function index_building_fetch() {
			$form = Input::form();
			$swlat = $form['swlat'];
			$swlon = $form['swlon'];
			$nelat = $form['nelat'];
			$nelon = $form['nelon'];

			$buildings_data = GISMon::get_buildings($swlon, $nelon, $swlat, $nelat, 
			[
				'bubble_view' => 'gismon:building/bubble_front',
			]);

			Output::$AJAX['buildings'] = $buildings_data;
		}

		function index_building_show() {
			$form = Input::form();
			$equipment_uniqid = $form['equipment_uniqid'];
			$bid = $form['building_id'];
			$name = $form['building_name'];

			$equipments = GISMon::get_equipments($bid);

			Output::$AJAX["#".$equipment_uniqid] = [
				'data' => (string)V('front/equipment_list', ['equipments'=>$equipments, 'building_name'=>$name])
			];
		}
}
