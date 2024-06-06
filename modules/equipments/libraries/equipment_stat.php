<?php

class Equipment_Stat {

    public static function get_view_dashboard_sections($e, $equipment, $sections) {
		$me = L('ME');
		if ($me->is_allowed_to('管理使用', $equipment) && Config::get('eq_stat.export_sj_tri')) {
		    $sections[] = V('equipments:eq_stat/export_sj_tri', ['equipment' => $equipment]);
		}
	}

	// 测样数
	public static function sj_tri_used_samples($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

		$SQL = "SELECT SUM(`samples`) `count` ".
        " FROM `eq_record`".
        " WHERE `dtend` between :dtstart and :dtend ".
        " AND `equipment_id` = :equipment_id";

		$SQL = strtr($SQL, [
			':dtstart' => $dtstart,
			':dtend' => $dtend,
			':equipment_id' => $equipment->id,
			]);

		$result = $db->value($SQL) ? : 0;

		$e->return_value = $result;

		return FALSE;
	}

	// 收费总额
	public static function sj_tri_used_charge($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

		$SQL = "SELECT SUM(`b`.`outcome`) `charge` ".
        " FROM `billing_transaction` `b` JOIN `eq_charge` `e` ".
        " ON `e`.`transaction_id` = `b`.`id` ".
        " WHERE ( `b`.`ctime` between :dtstart and :dtend ) AND `e`.`amount` > 0 ".
        " AND `e`.`equipment_id` = :equipment_id";
        
        $SQL = strtr($SQL, [
			':dtstart' => $dtstart,
			':dtend' => $dtend,
			':equipment_id' => $equipment->id,
			]);

        $result = $db->value($SQL) ? : 0;

		$e->return_value = $result;

		return FALSE;
	}

	// 培训人数
	public static function sj_tri_train_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

        $SQL = "SELECT SUM(`count`) `count` FROM ((".
		" SELECT COUNT(`ue`.`id`) `count` ".
		" FROM `ue_training` `ue` WHERE `ue`.`status` = :status ".
		" AND ( `ue`.`mtime` BETWEEN :dtstart and :dtend ) ".
        " AND `ue`.`equipment_id`=:equipment_id ) union (".
		" SELECT SUM(`ge`.`napproved`) `count` FROM `ge_training` `ge` ".
		" WHERE `ge`.`date` BETWEEN :dtstart and :dtend ".
        " AND `ge`.`equipment_id`=:equipment_id ".
        " )) `T` ";

        $SQL = strtr($SQL, [
			':dtstart' => $dtstart,
			':dtend'	=> $dtend,
			':equipment_id' => $equipment->id,
			':status' => UE_Training_Model::STATUS_APPROVED
			]);

        $result = $db->value($SQL) ? : 0;

		$e->return_value = $result;

		return FALSE;
	}

	// 培训学生
	public static function sj_tri_train_stu_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

        $SQL = "SELECT `t`.`id` FROM `tag_group` `t` ". 
	        " INNER JOIN `tag_group` `t1` ON `t`.`parent_id` = `t1`.`id`". 
	        " WHERE `t1`.`root_id` = 0 AND `t1`.`parent_id` = 0".
	        " AND `t`.`name` = ':group'";

        $tagId = $db->value(strtr($SQL, [
        	':group' => Config::get('eq_stat.people.self_group_name', '南开大学')
        	]));

        if (!$tagId) {
        	$e->return_value = 0;
			return FALSE;
        }

        $student_roles = Config::get('eq_stat.people.role.student', ['min' => 0, 'max' => 9]);

        $SQL = "SELECT COUNT(`ue`.`id`) `count` ".
		" FROM `ue_training` `ue` INNER JOIN `user` `u` ON `u`.`id` = `ue`.`user_id` ".
        " JOIN `_r_user_tag_group` `r` ON `r`.id1 = `u`.`id` ".
		" WHERE `u`.`member_type` between :min and :max ".
        " AND `r`.`id2` = :tagId ".
		" AND `ue`.`mtime` between :dtstart and :dtend ".
		" AND `ue`.`status` = :status ".
        " AND `ue`.`equipment_id` = :equipment_id ";

        $SQL = strtr($SQL, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':min' => $student_roles['min'],
            ':max' => $student_roles['max'],
            ':status' => UE_Training_Model::STATUS_APPROVED,
            ':tagId' => $tagId,
            ':equipment_id' => $equipment->id
        	]);

        $result = $db->value($SQL) ? : 0;
		
		$e->return_value = $result;

		return FALSE;
	}

	// 培训教师
	public static function sj_tri_train_tea_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

        $SQL = "SELECT `t`.`id` FROM `tag_group` `t` ". 
	        " INNER JOIN `tag_group` `t1` ON `t`.`parent_id` = `t1`.`id`". 
	        " WHERE `t1`.`root_id` = 0 AND `t1`.`parent_id` = 0".
	        " AND `t`.`name` = ':group'";

        $tagId = $db->value(strtr($SQL, [
        	':group' => Config::get('eq_stat.people.self_group_name', '南开大学')
        	]));

        if (!$tagId) {
        	$e->return_value = 0;
			return FALSE;
        }

        $teacher_roles = Config::get('eq_stat.people.role.teacher', ['min' => 10, 'max' => 19]);

        $SQL = "SELECT COUNT(`ue`.`id`) `count` ".
		" FROM `ue_training` `ue` INNER JOIN `user` `u` ON `u`.`id` = `ue`.`user_id` ".
        " JOIN `_r_user_tag_group` `r` ON `r`.id1 = `u`.`id` ".
		" WHERE `u`.`member_type` between :min and :max ".
        " AND `r`.`id2` = :tagId ".
		" AND `ue`.`mtime` between :dtstart and :dtend ".
		" AND `ue`.`status` = :status ".
        " AND `ue`.`equipment_id` = :equipment_id ";

        $SQL = strtr($SQL, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':min' => $teacher_roles['min'],
            ':max' => $teacher_roles['max'],
            ':status' => UE_Training_Model::STATUS_APPROVED,
            ':tagId' => $tagId,
            ':equipment_id' => $equipment->id
        	]);

        $result = $db->value($SQL) ? : 0;
		
		$e->return_value = $result;

		return FALSE;
	}

	// 培训其他人
	public static function sj_tri_train_oth_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
		
		$db = Database::factory();

		$SQL = "SELECT `t`.`id` FROM `tag_group` `t` ". 
		" INNER JOIN `tag_group` `t1` ON `t`.`parent_id` = `t1`.`id`". 
		" WHERE `t1`.`root_id` = 0 AND `t1`.`parent_id` = 0".
		" AND `t`.`name` = ':group'";

		$tagId = $db->value(strtr($SQL, [
        	':group' => Config::get('eq_stat.people.self_group_name', '南开大学')
			]));
			
		if (!$tagId) {
			$e->return_value = 0;
			return FALSE;
		}

        $other_roles = Config::get('eq_stat.people.role.other', ['min' => 20, 'max' => 29]);
		
		$SQL = "SELECT COUNT(`ue`.`id`) `count` ".
		" FROM `ue_training` `ue` INNER JOIN `user` `u` ON `u`.`id` = `ue`.`user_id` ".
		" JOIN `_r_user_tag_group` `r` ON `r`.id1 = `u`.`id` ".
		" WHERE `u`.`member_type` between :min and :max ".
		" AND `r`.`id2` = :tagId ".
		" AND `ue`.`mtime` between :dtstart and :dtend ".
		" AND `ue`.`status` = :status ".
		" AND `ue`.`equipment_id` = :equipment_id ";

		$SQL = strtr($SQL, [
			':dtstart' => $dtstart,
			':dtend' => $dtend,
			':min' => $other_roles['min'],
			':max' => $other_roles['max'],
			':status' => UE_Training_Model::STATUS_APPROVED,
			':tagId' => $tagId,
			':equipment_id' => $equipment->id
			]);

		$result = $db->value($SQL) ? : 0;
		
		$e->return_value = $result;

		return FALSE;
	}

	// 服务教学项目数
	public static function sj_tri_education_pro_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

        $SQL = "SELECT COUNT(DISTINCT `id`)
        FROM ((
            SELECT DISTINCT `l1`.`id` AS `id`
            FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2`
            ON `e2`.`reserv_id` = `e1`.`id` JOIN `lab_project` `l1`
            ON `e1`.`project_id` = `l1`.`id`
            WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e1`.`equipment_id` = :equipment_id
            AND `l1`.`type` = :type GROUP BY `e1`.`equipment_id`, `e1`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l`.`id` AS `id`
            FROM  `eq_record` AS `e` INNER JOIN `lab_project` AS `l`
            ON `e`.`project_id` = `l`.`id`
            WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e`.`equipment_id` = :equipment_id
            AND `l`.`type` = :type GROUP BY `e`.`equipment_id`, `e`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l`.`id` AS `id`
            FROM  `eq_record` AS `e` INNER JOIN `eq_reserv` AS `r`
            ON `e`.`reserv_id` = `r`.`id` INNER JOIN `lab_project` AS `l`
            ON `r`.`project_id` = `l`.`id`
            WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e`.`project_id` = 0 AND `e`.`equipment_id` = :equipment_id
            AND `l`.`type` = :type GROUP BY `e`.`equipment_id`, `e`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l2`.`id` AS `id`
            FROM `eq_sample` `e3` JOIN `lab_project` `l2`
            ON `e3`.`project_id` = `l2`.`id`
            WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e3`.`equipment_id` = :equipment_id AND `e3`.`status` = :status
            AND `l2`.`type` = :type GROUP BY `e3`.`equipment_id`, `e3`.`sender_id`
        )) AS `U`";

	    $SQL = strtr($SQL, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend - 86399,
            ':type' => Lab_Project_Model::TYPE_EDUCATION,
            ':status' => EQ_Sample_Model::STATUS_TESTED,
            ':equipment_id' => $equipment->id
        	]);

        $result = $db->value($SQL) ? : 0;
		
		$e->return_value = $result;

		return FALSE;
	}

	// 服务科研项目数
	public static function sj_tri_research_pro_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

        $SQL = "SELECT COUNT(DISTINCT `id`)
        FROM ((
            SELECT DISTINCT `l1`.`id` AS `id`
            FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2`
            ON `e2`.`reserv_id` = `e1`.`id` JOIN `lab_project` `l1`
            ON `e1`.`project_id` = `l1`.`id`
            WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e1`.`equipment_id` = :equipment_id
            AND `l1`.`type` = :type GROUP BY `e1`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l`.`id` AS `id`
            FROM  `eq_record` AS `e` INNER JOIN `lab_project` AS `l`
            ON `e`.`project_id` = `l`.`id`
            WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e`.`equipment_id` = :equipment_id
            AND `l`.`type` = :type GROUP BY `e`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l`.`id` AS `id`
            FROM  `eq_record` AS `e` INNER JOIN `eq_reserv` AS `r`
            ON `e`.`reserv_id` = `r`.`id` INNER JOIN `lab_project` AS `l`
            ON `r`.`project_id` = `l`.`id`
            WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e`.`project_id` = 0 AND `e`.`equipment_id` = :equipment_id
            AND `l`.`type` = :type GROUP BY `e`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l2`.`id` AS `id`
            FROM `eq_sample` `e3` JOIN `lab_project` `l2`
            ON `e3`.`project_id` = `l2`.`id`
            WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e3`.`equipment_id` = :equipment_id AND `e3`.`status` = :status
            AND `l2`.`type` = :type GROUP BY `e3`.`sender_id`
        )) AS `U`";

	    $SQL = strtr($SQL, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend - 86399,
            ':type' => Lab_Project_Model::TYPE_RESEARCH,
            ':status' => EQ_Sample_Model::STATUS_TESTED,
            ':equipment_id' => $equipment->id
        	]);

        $result = $db->value($SQL) ? : 0;
		
		$e->return_value = $result;

		return FALSE;
	}

	// 服务社会项目数
	public static function sj_tri_service_pro_count($e, $equipment, $dtstart, $dtend) {
		if (!$equipment->id || !$dtstart || !$dtend) return FALSE;

		$db = Database::factory();

        $SQL = "SELECT COUNT(DISTINCT `id`)
        FROM ((
            SELECT DISTINCT `l1`.`id` AS `id`
            FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2`
            ON `e2`.`reserv_id` = `e1`.`id` JOIN `lab_project` `l1`
            ON `e1`.`project_id` = `l1`.`id`
            WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e1`.`equipment_id` = :equipment_id
            AND `l1`.`type` = :type GROUP BY `e1`.`equipment_id`, `e1`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l`.`id` AS `id`
            FROM  `eq_record` AS `e` INNER JOIN `lab_project` AS `l`
            ON `e`.`project_id` = `l`.`id`
            WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e`.`equipment_id` = :equipment_id
            AND `l`.`type` = :type GROUP BY `e`.`equipment_id`, `e`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l`.`id` AS `id`
            FROM  `eq_record` AS `e` INNER JOIN `eq_reserv` AS `r`
            ON `e`.`reserv_id` = `r`.`id` INNER JOIN `lab_project` AS `l`
            ON `r`.`project_id` = `l`.`id`
            WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e`.`project_id` = 0 AND `e`.`equipment_id` = :equipment_id
            AND `l`.`type` = :type GROUP BY `e`.`equipment_id`, `e`.`user_id`
        ) UNION ALL (
            SELECT DISTINCT `l2`.`id` AS `id`
            FROM `eq_sample` `e3` JOIN `lab_project` `l2`
            ON `e3`.`project_id` = `l2`.`id`
            WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend
            AND `e3`.`equipment_id` = :equipment_id AND `e3`.`status` = :status
            AND `l2`.`type` = :type GROUP BY `e3`.`equipment_id`, `e3`.`sender_id`
        )) AS `U`";

	    $SQL = strtr($SQL, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend - 86399,
            ':type' => Lab_Project_Model::TYPE_SERVICE,
            ':status' => EQ_Sample_Model::STATUS_TESTED,
            ':equipment_id' => $equipment->id
        	]);

        $result = $db->value($SQL) ? : 0;
		
		$e->return_value = $result;

		return FALSE;
	}
}