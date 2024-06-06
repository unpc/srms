<?php

class Tile_Controller extends Controller {
	
	function index($id=0) {
	
		header('Expires: Thu, 15 Apr 2100 20:00:00 GMT'); 
		header('Pragma: public');
		header('Cache-Control: max-age=604800');
		try {
			$building = O('gis_building', $id);
			if (!$building->id) throw new Exception;
	
			$floor = Input::form('floor');
			$zoom = Input::form('zoom');
			$x = Input::form('x');
			$y = Input::form('y');
			
			$path = sprintf('tiles/%d/%d/%d/%d_%d.png', $building->id, $zoom, $floor, $x, $y);

			$path = Core::file_exists(PRIVATE_BASE.$path, ['application', 'gismon']);
			if (!$path) throw new Exception;
			
			header('Content-type: image/png');
		}
		catch (Exception $e) {
			$path = ROOT_PATH.PUBLIC_BASE.'images/blank.gif';
			header('Content-type: image/gif');
		}

		@readfile($path);

		exit;
	}

}
