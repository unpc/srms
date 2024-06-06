<?php

class Map_Controller extends Base_Controller {
	
	function index($id=0) {
		/* NO.TASK#310 为gismon设置权限	*/
		/* (xiaopei.li@2010.12.20)		*/

		if (!L('ME')->is_allowed_to('列表', 'gis_building')) {
			URI::redirect('error/401');
		}
		
		$building = O('gis_building', $id);

		if ($building->id) {
			$longitude = $building->longitude;
			$latitude = $building->latitude;
			$this->layout->body->primary_tabs
					->add_tab('map', [
									'url'=> $building->url(NULL, NULL, NULL, 'map'),
									'title'=>I18N::T('gismon', '仪器地图'),
							]);
		}
		else {
			$longitude = (float) Config::get('gis.longitude');
			$latitude = (float) Config::get('gis.latitude');
		}

		$this->layout->body->primary_tabs->select('map');

		/* (jipeng.huang@2015.04/03) */
		/* 这里先用的我的key, 有需要再进行修改 */
		$key = Config::get('gismon.web.key');
		$url = Config::get('gismon.web.url');
		$this->add_js($url.'/api?v=2.0&ak='.$key, FALSE);
		/* 纠偏所需js文件 */
		//$this->add_js('http://developer.baidu.com/map/jsdemo/demo/convertor.js', FALSE);

		$this->layout->body->primary_tabs->content = V('gismon:map', ['longitude'=>$longitude, 'latitude'=>$latitude]);
		
	}
	
}

class Map_AJAX_Controller extends AJAX_Controller {
		
		function index_building_fetch() {
			$form = Input::form();
			$swlat = $form['swlat'];
			$swlon = $form['swlon'];
			$nelat = $form['nelat'];
			$nelon = $form['nelon'];
			$selector = "gis_building[longitude=$swlon~$nelon][latitude=$swlat~$nelat]";
            $selector = Event::trigger('gismon.buildings.extra_selector', $selector, $form) ? : $selector;
			$buildings = Q("$selector");
			$buildings_data = [];
            foreach ($buildings as $building) {
				$data = [
						'id' => $building->id,
						'name' => $building->name,
						'longitude' => $building->longitude,
						'latitude' => $building->latitude,
						'bubble' => (string) V('gismon:building/bubble', ['building'=>$building])
						];
                $data = Event::trigger('gismon.buildings.extra_data', $data) ? : $data;
				$buildings_data[] = $data;
			}

			Output::$AJAX['buildings'] = $buildings_data;
		}

		/*
		  NO.TASK#279(xiaopei.li@2010.12.02)
		*/
		function index_building_move() {
			$form = Input::form();

			$building_id = $form['building_id'];
			$new_longitude = $form['new_longitude'];
			$new_latitude = $form['new_latitude'];

			$building = O("gis_building", $building_id);

			if (!L('ME')->is_allowed_to('修改', $building)) {
				return;
			}

			$building->longitude = $new_longitude;
			$building->latitude = $new_latitude;
			$building->save();
		}
}
