<?php

class API_Chart {
	
	function get_stat_values($tid, $opt, $precision, $t1, $t2 = NULL) {
		return EQ_Chart::do_stat($tid, $opt, $precision, $t1, $t2);
	}

	function get_years() {
		return EQ_Stat::get_years();
	}

	function get_high_equipments($num=10, $dtstart=NULL, $dtend=NULL) {
        $dtstart = $dtstart ?: mktime(0, 0, 0, Config::get('eq_stat.start_month', '9'), 1, Config::get('eq_stat.start_year') ?: (date('Y') - 1));
        $dtend = $dtend ?: mktime(0, 0, 0, Config::get('eq_stat.end_month', '7'), 1, Config::get('eq_stat.end_year') ?: date('Y'));
        $num = max(1, min((int)$num, 20));

        $high_eq = strtr(Config::get('eq_stat.api.high_equipments_sql'), ['%dtstart'=>$dtstart, '%dtend'=>$dtend, '%num'=>$num]);

        $db = Database::factory();
        $records = $db->query($high_eq)->rows();

        $equipments = [];
        if (count($records)) foreach ($records as $record) {
            $equipment = O('equipment', $record->id);
            $users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $equipments[] = [
                    'name' => $equipment->name,
                    'icon_url' => $equipment->icon_url(),
                    'url' => $equipment->url(),
                    'location' => $equipment->location,
                    'location2' => $equipment->location2,
                    'contact' => join(', ', $users)
                    ];
        }
        return $equipments;
	}

	function get_top_users($num=10, $mode='hours') {

		switch($mode) {
		case 'rating':
			ORM_Model::db('eq_perf_rating');
			$query = 'SELECT DISTINCT equipment_id AS id FROM eq_perf_rating GROUP BY equipment_id ORDER BY SUM(average) DESC';
			break;
		//case 'hours':
		default:
			$query = '
SELECT DISTINCT equipment_id AS id, SUM(dtend-dtstart) AS time
FROM eq_record AS eq
JOIN equipment AS e ON eq.equipment_id = e.id
WHERE e.status = 0
AND eq.dtstart > 0 AND eq.dtend > 0 AND eq.dtend > eq.dtstart
GROUP BY equipment_id
ORDER BY time DESC';
		}

		$num = min(20, max((int)$num, 1));
		$query .= ' LIMIT '.($num*3);
		
		$db = Database::factory();
		$eids = $db->query($query)->rows();
		
		$users = [];
		
		foreach ((array)$eids as $eq) {
			$equipment = O('equipment', $eq->id);
			$user = Q("{$equipment} user.contact:limit(1)")->current();
			if (isset($users[$user->id])) continue;
			
			$data = [];
			$data['name'] = $user->name;
			$data['url'] = $user->url();
			$data['icon_url'] = $user->icon_url('32');
			$data['department'] = $tag->root->id ? $tag->name : '';
			$data['major'] = $user->major;
			$data['description'] = $user->address;
			$users[$user->id] = $data;
			
			if (count($users) >= $num) break;
		}
		
		return $users;
	}
}
