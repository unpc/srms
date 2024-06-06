<?php

class Device_Controller extends AJAX_Controller {
	
	function index($id=0) {

		$building = O('gis_building', $id);
		if (!$building->id) {
			exit;
		}

		$form = Input::form();
	
		$floor = (int) $form['floor'];
		$left = (float) $form['l'];
		$top = (float) $form['t'];
		$right = $left + (float) $form['w'];
		$bottom = $top + (float) $form['h'];

		$devices = Q("gis_device[x=$left~$right][y=$top~$bottom][building=$building][floor=$floor]");
		
		$dev_infos = [];
		foreach ($devices as $device) {
			if (!$device->object->id) {
				$device->delete();
				continue;
			}
			if(Event::trigger('gismon.device.is_not_display', $device->object))continue;
			$dev_infos[$device->id] = $device->info();
            $dev_infos[$device->id] = Event::trigger('gismon.device.move_url', $dev_infos[$device->id]) ? : $dev_infos[$device->id];
		}

		Output::$AJAX['devices'] = array_values($dev_infos);
	}

}
