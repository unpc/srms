<?php

class GISMon {


	static function lon_lat_format($lon, $lat) {

		$lon_sign = $lon >= 0 ? 'E':'W';
		$lat_sign = $lat >= 0 ? 'N':'S';

		return $lon_sign . ' ' . Number::degree($lon).' / '. $lat_sign . ' ' . Number::degree($lat);
	}

	/**
	  * gis_building的权限规则
	  * (xiaopei.li@2010.12.20)
	  *
	  * @param e
	  * @param me
	  * @param perm_name
	  * @param object
	  * @param options
	  *
	  * @return
	  */
	static function gis_building_ACL($e, $me, $perm_name, $object, $options)
	{
		switch ($perm_name) {
		case '列表':
		case '查看':
			if ($me->access('查看仪器地图')) {
				$e->return_value = true;
				return false;
			}
			break;
		case '添加':
		case '修改':
		case '删除':
			if ($me->access('添加/修改楼宇')) {
				$e->return_value = true;
				return false;
			}
			break;
		}
	}

	/**
	 * gis_device的权限规则
	 * (xiaopei.li@2010.12.20)
	 *
	 * @param e
	 * @param me
	 * @param perm_name
	 * @param object
	 * @param options
	 *
	 * @return
	 */
	static function gis_device_ACL($e, $me, $perm_name, $object, $options)
	{
		switch ($perm_name) {
		case '修改':
			if ($me->access('调整GIS监控设备位置')) {
				$e->return_value = true;
				return false;
			}
			break;
		}
	}

	/**
	 * NO.BUG#287(xiaopei.li@2010.12.22)
	 * 判断用户是否有操作此模块最基础的权限
	 * 若无，则不在sidebar中显示此模块图标
	 *
	 * @param e
	 * @param module
	 *
	 * @return
	 */
	static function is_accessible($e, $name)
	{
		if (!L('ME')->is_allowed_to('列表', 'gis_building')) {
			$e->return_value = false;
			return false;
		}
	}

	static function get_buildings($swlon, $nelon, $swlat, $nelat, $opts = NULL) {
		$buildings = Q("gis_building[longitude=$swlon~$nelon][latitude=$swlat~$nelat]");
		$buildings_data = [];

		$buildings_data['total'] = $buildings->total_count();

		$bubble_view = 'gismon:building/bubble';
		if ((array)$opts) {
			if (isset($opts['bubble_view']) && $opts['bubble_view']) {
				$bubble_view = $opts['bubble_view'];
			}
		}

		foreach ($buildings as $building) {
			$data = [
					'id' => $building->id,
					'name' => $building->name,
					'longitude' => $building->longitude,
					'latitude' => $building->latitude,
					'bubble' => (string)V($bubble_view, ['building'=>$building])
					];
			$buildings_data[] = $data;
		}
		return $buildings_data;
	}

	static function get_equipments($bid) {
		$building = O('gis_building', $bid);

		/*
		$name = $building->name;
		$equipments = Q("equipment[location*={$name}|location2*={$name}]");
		*/

		// 与 views/building/bubble.phtml 统一由 building 获得 equipment 的写法(xiaopei.li@2013-06-01)
		$equipments = Q("gis_device[building=$building]<object equipment");

		$equipments_data = [];
		foreach ($equipments as $equipment) {
			$data = [
				'id'=>$equipment->id,
				'icon_url'=>$equipment->icon_url('32'),
				'url'=>$equipment->url(),
				'name'=>$equipment->name
			];
			$equipments_data[] = $data;
		}

		return $equipments_data;
	}
}
