<?php

class Site_Model extends Presentable_Model {

	static function root($lab_id, $site_id = 'cf') {

		$site = O('site', ['site_id' => $site_id, 'lab_id' => $lab_id]);
		if (!$site->id) {
			$site = O('site');
			$site_config = Config::get('sites.children_stage');

            if (!$site_config[$lab_id]) return FALSE;
			$site->name = $site_config[$lab_id]['name'];
			$site->site_id = $site_config[$lab_id]['site_id'];
			$site->lab_id = $site_config[$lab_id]['lab_id'];
			$site->base_url = $site_config[$lab_id]['base_url'];
            $site->save();
            return $site;
		}

		return $site;

	}
}
