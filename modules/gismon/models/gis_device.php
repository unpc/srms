<?php

/* 属于某座楼宇的仪器 */
class GIS_Device_Model extends ORM_Model {

	function info() {
		
		$object = $this->object;
	
		$name = $object->name();
		
		switch($name) {
		case 'equipment':
			$is_using = $object->is_using;
			$mode = $object->control_mode;
			if (!$mode) $mode = 'power';
            $control_class = $mode;
			$control_class .= '_' . ($is_using ? 'on' : 'off');
            $control_class .= $object->is_monitoring || $object->connect ? '' : '_disconnected';
			$tooltip =  Equipments::get_tool_tip($control_class);
			$file_name = 'device_equipment/' . $control_class;
			
			break;
		default:
			$file_name = 'device';
		}
		
		$icon_url = '!gismon/images/icons/' . $file_name . '.png';
		// NO.BUG#254(xiaopei.li@2010.12.18)
		// 此处会将URL编码，产生%，导致之后输出出错

		return [
				'id' => $this->id,
				'x' => $this->x,
				'y' => $this->y,
				'name' => $object->name,
				'summary' => (string) V('gismon:device_summary/'.$name, ['device'=>$this, 'object'=>$object]),
				'icon_url' => $icon_url,
				'view_url' => $object->url(),
				'tooltip' => $tooltip
			];
	}
	
}
	
