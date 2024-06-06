<?php

class CLI_Cers {

	static function refresh($type='platform') {
		if ($type == 'instrusandgroups') {
			Lab::set('cers.instrusandgroups_refresh_pid', getmypid());
			$file = Cers::getLabPrivateFile('InstrusAndGroups.xml');
			File::check_path($file);
			@file_put_contents($file, Cers::getSchoolRoot());
			Lab::set('cers.instrusandgroups_refresh_pid', NULL);
			Lab::set('cers.instrusandgroups_refresh_last_time', Date::time());
		}
		elseif ($type == 'shareeffect') {
			Lab::set('cers.shareeffect_refresh_pid', getmypid());

			if ( Config::get('cers.enable_stat_eq_data') ) {
				$configs = Cers::getConfig('cers');
				$year = $configs['Year'];
				$dtstart = mktime(0, 0, 0, $configs['MonthFrom'], 1, $year - 1);
				$dtend = mktime(0, 0, 0, $configs['MonthTo'] + 1, 1, $year);

				while ($dtstart <= $dtend) {
					EQ_Stat::do_stat_list_save($dtstart, $dtstart + 3600*24 - 1);
					$dtstart += 3600*24;
				}
			}

			$instrusConfigs = Cers::getShareEffect(NULL, NULL, TRUE);
			foreach ($instrusConfigs as $configs) {
				$e = O('equipment', $configs['InnerID']);
				if (!$e->id || !$configs['YEAR']) { continue; }
				$c = O('cers_share_data', ['equipment' => $e, 'to_year' => $configs['YEAR']]);
				if (!$c->id) {
					$c = O('cers_share_data');
					$c->equipment = $e;
					$c->to_year = $configs['YEAR'];
					$c->from_year = $configs['YEAR'] - 1;
				}
				$c->description = $configs['OtherInfo'];
				$c->SchoolCode = $configs['SchoolCode'];
				$c->InnerID = $configs['InnerID'];

				foreach ((array)Config::get('equipment.share_fields') as $key => $value) {
					$c->$key = $configs[$key];
				}
				$c->save();
			}

			$file = Cers::getLabPrivateFile('ShareEffect.xml');
			File::check_path($file);
			@file_put_contents($file, (string)V('cers:api/shareeffect', ['instrusConfigs' => $instrusConfigs]));
			Lab::set('cers.shareeffect_refresh_pid', NULL);
			Lab::set('cers.shareeffect_refresh_last_time', Date::time());
		}
		else {
			Lab::set('cers.platform_refresh_pid', getmypid());
			$file = Cers::getLabPrivateFile('Platform.xml');
			File::check_path($file);
			@file_put_contents($file, Cers::getSchoolInfo());
			Lab::set('cers.platform_refresh_pid', NULL);
			Lab::set('cers.platform_refresh_last_time', Date::time());
		}
	}

	static function create_structs($groupid = 0) {
		$root = Tag_Model::root('group');
		$group = O('tag_group', ['parent'=>$root, 'id'=>$groupid]);
		if (!$group->id) {
			$group = O('tag_group', ['parent'=>$root, 'name'=>$groupid]);
		}
		if (!$group->id) {
			$group = Q("tag_group[parent={$root}]:limit(1)")->current();
		}

		foreach ($group->children() as $key => $tag) {
			$struct = O('eq_struct', ['name' => $tag->name]);
			if (!$struct->id) $struct = O('eq_struct');
			$struct->name = $tag->name;
			if ($struct->save()) {
				foreach (Q("$tag equipment") as $equipment) {
					$equipment->struct = $struct;
					$equipment->save();
				}
			}
		}
	}
}