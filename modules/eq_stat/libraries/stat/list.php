<?php

class Stat_List {

    //按照仪器统计样品数
    static function record_sample($e, $equipment, $dtstart, $dtend) {
        $SQL = "SELECT SUM(`U`.`count`)".
        " FROM (( ".
        " SELECT SUM(`e`.`samples`) `count` ".
        " FROM `eq_record` `e` LEFT JOIN `eq_sample` `e1` ".
        " ON `e1`.`record_id` = `e`.`id` ".
        " WHERE  `e1`.`id` IS NULL AND ( `e`.`dtend` between :dtstart and :dtend )".
        " AND `e`.`equipment_id` = :equipment_id ".
        " ) UNION (".
        " SELECT SUM(`e2`.`count`) `count` ".
        " FROM `eq_sample` `e2` WHERE `e2`.`status` = :status ".
        " AND ( `e2`.`dtend` between :dtstart and :dtend ) ".
        " AND `e2`.`equipment_id` = :equipment_id ".
        " )) AS `U` ";
        $SQL = strtr($SQL, [
                ':dtstart' => $dtstart,
                ':dtend' => $dtend,
                ':equipment_id' => $equipment->id,
                ':status' => EQ_Sample_Model::STATUS_TESTED
            ]);
        $db = Database::factory();
        $e->return_value = (int)$db->value($SQL);
        return FALSE;
    }

    //按照仪器统计测样机时
    static function time_sample($e, $equipment, $dtstart, $dtend) {
        $SQL = "SELECT `e`.`equipment_id`, SUM(`e`.`dtend` - `e`.`dtstart`) `dur` ".
        " FROM `eq_sample` `e`".
        " WHERE `e`.`dtend` BETWEEN :dtstart and :dtend ".
        " AND `e`.`dtend` > `e`.`dtstart` AND `e`.`dtstart` > 0 ".
        " AND `e`.`equipment_id` = :equipment_id ";
        $SQL = strtr($SQL, [
                ':dtstart' => $dtstart,
                ':dtend' => $dtend,
                ':equipment_id' => $equipment->id,
            ]);

        $db = Database::factory();
        $e->return_value = $db->value($SQL);
        return false;
    }

    //按照仪器统计使用机时
    static function time_total($e, $equipment, $dtstart, $dtend) {
        $query = "SELECT SUM( `e`.`dtend` - `e`.`dtstart`) ".
            "FROM `eq_record` `e` ".
            "WHERE `e`.`equipment_id` = %d AND `e`.`dtend` >= %d AND `e`.`dtend` < %d AND `e`.`dtend` IS NOT NULL AND `e`.`dtend` != ''";

        $db = Database::factory();
        $e->return_value = $db->value($query, $equipment->id, $dtstart, $dtend);
        return false;
    }

    //按照仪器统计开放机时 (开放机时 = 总机时 - 管理员使用时间)
    static function time_open($e, $equipment, $dtstart, $dtend) {
        $query = "SELECT `e`.`equipment_id`, `e`.`user_id`, SUM(`e`.`dtend` - `e`.`dtstart`) `dur`
            FROM `eq_record` `e`
            LEFT JOIN `_r_user_equipment` `r` ON `r`.`id1` = `e`.`user_id` AND `r`.`type` = 'incharge' 
            AND `r`.`id2` = `e`.`equipment_id`
            WHERE `r`.`id2` IS NULL 
            AND ( `e`.`dtend` between %d and %d ) AND `e`.`dtend` > `e`.`dtstart`
            AND (`e`.`_extra` NOT LIKE '%\"is_missed\":true%' OR `e`.`_extra` IS NULL)
            AND `e`.`equipment_id` = %d";

        $db = Database::factory();

        $dur = $db->value($query, $dtstart, $dtend, $equipment->id);


        $e->return_value = $dur;
        return FALSE;
    }
    
    //按照仪器统计有效机时
    static function time_valid($e, $equipment, $dtstart, $dtend) {

        //如果安装了 eq_meter 模块
        if (!Module::is_installed('labs')) {
            $query = "SELECT `U`.`equipment_id`, `U`.`user_id`, SUM(`U`.`dur`) `dur` FROM 
                ((SELECT `e`.`equipment_id`, `e`.`user_id`, SUM(`e`.`dtend` - `e`.`dtstart`) `dur` 
                FROM `eq_record` `e`
                LEFT JOIN `_r_user_equipment` `r` ON `r`.`id1` = `e`.`user_id` AND `r`.`type` = 'incharge' 
                LEFT JOIN `eq_meter` `m` ON `m`.`equipment_id` = `e`.`equipment_id`  
                WHERE `e`.`equipment_id` = :equipment_id AND 
                `r`.`id2` IS NULL AND `m`.`id` IS NULL 
                AND ( `e`.`dtend` between :dtstart and :dtend ) AND `e`.`dtend` > `e`.`dtstart`
                ) UNION ALL (
                SELECT `e1`.`equipment_id`, `e1`.`user_id`, SUM(`e1`.`dtend` - `e1`.`dtstart`) `dur` 
                FROM `eq_record` `e1` LEFT JOIN `eq_meter` `m1` ON `m1`.`equipment_id` = `e1`.`equipment_id` 
                JOIN `_r_user_equipment` `r1`
                ON `r1`.`id1` = `e1`.`user_id` AND `r1`.`id2` = `e1`.`equipment_id`
                JOIN `eq_reserv` `e2` ON `e2`.`id` = `e1`.`reserv_id`  
                WHERE `e1`.`equipment_id` = :equipment_id AND `m1`.`id` IS NULL  AND `r1`.`type` = 'incharge' AND `e1`.`dtend` > `e1`.`dtstart` 
                AND `e1`.`dtend` between :dtstart and :dtend 
                ) UNION ALL (
                SELECT `e3`.`equipment_id`, `e3`.`user_id`, SUM(`m2`.`dtend` - `m2`.`dtstart`) `dur` 
                FROM `eq_meter_record` `m2`
                JOIN `eq_meter` `m3` ON `m2`.`eq_meter_id` = `m3`.`id` 
                JOIN `eq_record` `e3` ON `e3`.`equipment_id` = `m3`.`equipment_id` 
                AND `e3`.`dtstart` <= `m2`.`dtend` AND `e3`.`dtend` >= `m2`.`dtend` 
                WHERE `m3`.`equipment_id` = :equipment_id AND `m2`.`dtend` BETWEEN :dtstart and :dtend AND `m2`.`dtend` > 0
                )) AS `U` ";
        }
        else {
            $query = "SELECT SUM(`U`.`dur`) `dur` FROM 
                ((SELECT `e`.`equipment_id`, `e`.`user_id`, SUM(`e`.`dtend` - `e`.`dtstart`) `dur` 
                FROM `eq_record` `e`
                LEFT JOIN `_r_user_equipment` `r` ON `r`.`id1` = `e`.`user_id` AND `r`.`type` = 'incharge' 
                LEFT JOIN `eq_meter` `m` ON `m`.`equipment_id` = `e`.`equipment_id`  
                WHERE `e`.`equipment_id` = :equipment_id AND `r`.`id2` IS NULL AND `m`.`id` IS NULL 
                AND ( `e`.`dtend` between :dtstart and :dtend ) AND `e`.`dtend` > `e`.`dtstart`
                ) UNION ALL (
                SELECT `e1`.`equipment_id`, `e1`.`user_id`, SUM(`e1`.`dtend` - `e1`.`dtstart`) `dur` 
                FROM `eq_record` `e1` LEFT JOIN `eq_meter` `m1` ON `m1`.`equipment_id` = `e1`.`equipment_id` 
                JOIN `_r_user_equipment` `r1`
                ON `r1`.`id1` = `e1`.`user_id` AND `r1`.`id2` = `e1`.`equipment_id`
                JOIN `eq_reserv` `e2` ON `e2`.`id` = `e1`.`reserv_id`  
                WHERE `e1`.`equipment_id` = :equipment_id AND `m1`.`id` IS NULL  AND `r1`.`type` = 'incharge' AND `e1`.`dtend` > `e1`.`dtstart` 
                AND `e1`.`dtend` between :dtstart and :dtend 
                ) UNION ALL (
                SELECT `e3`.`equipment_id`, `e3`.`user_id`, SUM(`m2`.`dtend` - `m2`.`dtstart`) `dur` 
                FROM `eq_meter_record` `m2`
                JOIN `eq_meter` `m3` ON `m2`.`eq_meter_id` = `m3`.`id` 
                JOIN `eq_record` `e3` ON `e3`.`equipment_id` = `m3`.`equipment_id` 
                AND `e3`.`dtstart` <= `m2`.`dtend` AND `e3`.`dtend` >= `m2`.`dtend` 
                WHERE `m3`.`equipment_id` = :equipment_id AND `m2`.`dtend` BETWEEN :dtstart and :dtend AND `m2`.`dtend` > 0
                )) AS `U` ";
        }

        $query = strtr($query, [
                ':dtstart' => $dtstart,
                ':dtend' => $dtend,
                ':equipment_id' => $equipment->id,
                ':status' => EQ_Sample_Model::STATUS_TESTED
            ]);
        $db = Database::factory();
        $e->return_value = (int)$db->value($query);
        return FALSE;
    }   
    //按照仪器统计教学机时
    static function time_class ($e, $equipment, $dtstart, $dtend) {
		$description = Config::get('eq_stat.cal_des');
		$type = Config::get('eq_stat.cal_type');
		$equipment_id = $equipment->id;
		$calendar = Q("calendar[parent_id=$equipment_id][parent_name=equipment]:limit(1)")->current();
		$calendar_id = $calendar->id;
		$query = "SELECT `e`.`equipment_id`, `e`.`user_id`, SUM(`e`.`dtend` - `e`.`dtstart`) `dur` ".
            " FROM `eq_record` `e` ".
            " LEFT JOIN `lab_project` `p` ON `e`.`project_id` = `p`.`id` ".
            " LEFT JOIN ( ".
            "   SELECT `l0`.`type` AS `type`, `r0`.`id` as `id` ".
            "   FROM `eq_reserv` `r0`  ".
            "   LEFT JOIN `lab_project` `l0` ON `r0`.`project_id` = `l0`.`id` ".
            " ) `r` ON `e`.`reserv_id` = `r`.`id` ".
            " WHERE `e`.`dtend` between :dtstart and :dtend ".
            " AND `e`.`equipment_id` > 0 ".
            " AND (`e`.`_extra` NOT LIKE '%\"is_missed\":true%' OR `e`.`_extra` IS NULL)".
            " AND (`p`.`type` = :type OR `r`.`type` = :rtype) ".
            " AND e.equipment_id = :equipment_id";	
		$db = Database::factory();	
		$query = strtr($query, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':type' => $type,
            ':rtype' => $type,
            ':equipment_id' => $equipment->id
            ]);
		$e->return_value = (int)$db->value($query);
		return false;
	}

    //按照仪器统计使用次数
    static function use_time($e, $equipment, $dtstart, $dtend) {
        $records = Q("eq_record[equipment={$equipment}][dtstart={$dtstart}~{$dtend}]");
        $e->return_value = (int) $records->total_count();
        return FALSE;
    }

    //按照仪器统计培训人数
    static function total_trainees($e, $equipment, $dtstart, $dtend) {
        $ue_training = (int) Q("{$equipment} ue_training[mtime={$dtstart}~{$dtend}]")->total_count();
        $ge_training = (int) Q("{$equipment} ge_training[date={$dtstart}~{$dtend}]")->sum('napproved');
        $e->return_value = $ue_training + $ge_training;
        return FALSE;
    }

    //按照仪器统计论文数
    static function pubs($e, $equipment, $dtstart, $dtend) {
        $pubs = (int) Q("{$equipment} publication[date={$dtstart}~{$dtend}]")->total_count();
        $e->return_value = $pubs;
        return FALSE;
    }

    //按照仪器统计使用收费
    static function charge_total($e, $equipment, $dtstart, $dtend) {
        $pre_selector = [];

        $pre_selector[] = "eq_charge[amount!=0][equipment_id={$equipment->id}]";

        $selector = "eq_charge[amount!=0][equipment_id={$equipment->id}] billing_transaction[ctime>=$dtstart][ctime<=$dtend].transaction";

        $e->return_value = Q($selector)->sum('outcome');
        return FALSE;
    }

    //统计服务项目数目
    static function project_statistic_values($e, $equipment, $search_from, $search_end, $type=NULL) {
    	if ( !class_exists('Lab_Project_Model') ) return;
    	$dtstart = $search_from ? : 1;
    	//没有时间按当前时间搜索
    	$dtend = $search_end ? : Date::time();

        $sql = "SELECT `U`.`type`, SUM( `U`.`count`) `total_count` 
        FROM ((
            SELECT  `l`.`type`, COUNT(`l`.`id`) `count` 
            FROM  `eq_record` AS `e`  INNER JOIN `lab_project` AS `l` 
            ON `e`.`project_id` = `l`.`id` AND `e`.`dtend` BETWEEN :dtstart AND :dtend 
            AND `e`.`equipment_id` = :equipment_id
            GROUP BY  `l`.`type` 
        ) UNION ALL ( 
            SELECT  `l1`.`type`, COUNT(`l1`.`id`) `count` 
            FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2` 
            ON `e2`.`reserv_id` = `e1`.`id` JOIN `lab_project` `l1` 
            ON `e1`.`project_id` = `l1`.`id` 
            WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend 
            AND `e1`.`equipment_id` = :equipment_id
            GROUP BY  `l1`.`type`
        ) UNION ALL ( 
            SELECT `l2`.`type`, COUNT(`l2`.`id`) `count` 
            FROM `eq_sample` `e3` JOIN `lab_project` `l2` 
            ON `e3`.`project_id` = `l2`.`id` 
            WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend 
            AND `e3`.`equipment_id` = :equipment_id
            GROUP BY  `l2`.`type`
        )) AS `U` GROUP BY `U`.`type`";
    	
    	$exe_sql = strtr($sql, [
    						':equipment_id' => $equipment->id,
    						':dtstart' => $dtstart,
    						':dtend' => $dtend
                    ]);
    				
        $result = [];

    	$db = Database::factory();

        $query = $db->query($exe_sql);
        if ($query) $result = $query->rows('assoc');

    	$arr = [];
    	foreach( $result as $vl) {
	    	$arr[Lab_Project_Model::$stat_types[$vl['type']]] = $vl['total_count'];
    	}
		$e->return_value =  $arr;
		return FALSE;
    }
}
