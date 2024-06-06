<?php
class Stat_Basic {

    static function const_tag_equipments_count($e, $tag, $dtstart, $dtend) {
        if ($tag->root->id) {
            $query = 'SELECT COUNT( e.id ) '.
                'FROM equipment e '.
                'JOIN _r_tag_equipment t ON e.id = t.id2 '.
                'WHERE t.id1 = %d AND e.purchased_date >= %d AND e.purchased_date < %d';
        }
        else {
            $query = 'SELECT COUNT(e.id) '.
                'FROM `_r_tag_equipment` te '.
                'JOIN `tag` t ON te.id1 = t.id AND t.root_id = %d '.
                'RIGHT JOIN equipment e ON e.id = te.id2 '.
                'WHERE t.id IS NULL AND e.purchased_date >= %d AND e.purchased_date < %d';
        }
        $db = Database::factory();
        $e->return_value = (int)$db->value($query,$tag->id, $dtstart, $dtend);
        return FALSE;
    }

    static function const_tag_equipments_value($e, $tag, $dtstart, $dtend) {
        if ($tag->root->id) {
            $query = 'SELECT SUM( e.price ) '.
                'FROM equipment e '.
                'JOIN _r_tag_equipment t ON e.id = t.id2 '.
                'WHERE t.id1 = %d AND e.purchased_date >= %d AND e.purchased_date < %d';
        }
        else {
            //其他的仪器
            $query = 'SELECT SUM( e.price ) '.
                'FROM `_r_tag_equipment` te '.
                'JOIN `tag` t ON te.id1 = t.id AND t.root_id = %d '.
                'RIGHT JOIN equipment e ON e.id = te.id2 '.
                'WHERE t.id IS NULL AND e.purchased_date >= %d AND e.purchased_date < %d';

        }
        $db = Database::factory();
        $e->return_value = (int) $db->value($query,$tag->id, $dtstart, $dtend);
        return FALSE;
    }

    static function const_equipment_equipments_count($e, $equipment, $dtstart, $dtend) {
        $query = 'SELECT count(id) FROM equipment AS e WHERE e.id = %d AND e.purchased_date >= %d AND e.purchased_date < %d';
        $db = Database::factory();
        $e->return_value = (int) $db->value($query, $equipment->id, $dtstart, $dtend);
        return FALSE;
    }

    static function const_equipment_equipments_value($e, $equipment, $dtstart, $dtend) {
        $query = 'SELECT e.price FROM equipment AS e WHERE e.id = %d';
        $db = Database::factory();
        $e->return_value = (int) $db->value($query, $equipment->id);
        return FALSE;
    }

	static function top3_pubs($e, $equipment, $dtstart, $dtend) {
		$publication_root = Tag_Model::root('achievements_publication');
		$tag = O('tag', ['root'=>$publication_root, 'name'=>'三大检索']);
		$db = Database::factory();
		$query = "SELECT COUNT(publication.id) FROM publication, " . 
				"(_r_publication_equipment JOIN _r_tag_publication " . 
				"ON _r_tag_publication.id1=%d ". 
				"AND _r_publication_equipment.id2=%d) ".
				"WHERE publication.`date` BETWEEN %d AND %d " . 
				"AND _r_publication_equipment.id1=publication.id ".
				"AND _r_tag_publication.id2=publication.id ". 
				"ORDER BY publication.id";
        if ($tag->id) $e->return_value = (int) $db->value($query, $tag->id, $equipment->id, $dtstart, $dtend);
        else $e->return_value = 0;
		return FALSE;
	}
	
	static function core_pubs($e, $equipment, $dtstart, $dtend) {
		$publication_root = Tag_Model::root('achievements_publication');
		$tag = O('tag', ['root'=>$publication_root, 'name'=>'核心刊物']);
		$db = Database::factory();
		$query = "SELECT COUNT(publication.id) FROM publication, ".
				"(_r_publication_equipment JOIN _r_tag_publication " . 
				"ON _r_tag_publication.id1=%d AND _r_publication_equipment.id2=%d ) ".
				"WHERE publication.`date` BETWEEN %d AND %d " . 
				"AND _r_publication_equipment.id1=publication.id ".
				"AND _r_tag_publication.id2=publication.id ".
				"ORDER BY publication.id";
        if ($tag->id) $e->return_value = (int) $db->value($query, $tag->id, $equipment->id, $dtstart, $dtend);
        else $e->return_value = 0;
		return FALSE;
	}
	
	static function national_awards($e, $equipment, $dtstart, $dtend) {
		$award_root = Tag_Model::root('achievements_award');
		$tag = O('tag', ['root'=>$award_root, 'name'=>'国家级']);
		$db = Database::factory();
		$query = "SELECT COUNT(award.id) FROM award, ".
				"(_r_equipment_award JOIN _r_tag_award " . 
				"ON _r_tag_award.id1=%d AND _r_equipment_award.id1=%d) ".
				"WHERE award.`date` BETWEEN %d AND %d " . 
				"AND _r_equipment_award.id2=award.id ".
				"AND _r_tag_award.id2=award.id ".
				"ORDER BY award.id";
        if ($tag->id) $e->return_value = (int) $db->value($query, $tag->id, $equipment->id, $dtstart, $dtend);
        else $e->return_value = 0;
		return FALSE;
	}
	
	static function provincial_awards($e, $equipment, $dtstart, $dtend) {
		$award_root = Tag_Model::root('achievements_award');
		$tag = O('tag', ['root'=>$award_root, 'name'=>'省部级']);
		$db = Database::factory();
		$query = "SELECT COUNT(award.id) FROM award, ".
				"(_r_equipment_award JOIN _r_tag_award " . 
				"ON _r_tag_award.id1=%d AND _r_equipment_award.id1=%d) ".
				"WHERE award.`date` BETWEEN %d AND %d " . 
				"AND _r_equipment_award.id2=award.id ".
				"AND _r_tag_award.id2=award.id ".
				"ORDER BY award.id";
        if ($tag->id) $e->return_value = (int) $db->value($query, $tag->id, $equipment->id, $dtstart, $dtend);
        else $e->return_value = 0;
		return FALSE;
	}
	
	static function teacher_patents($e, $equipment, $dtstart, $dtend) {
		$patent_root = Tag_Model::root('achievements_patent');
		$tag = O('tag', ['root'=>$patent_root, 'name'=>'教师']);
		$db = Database::factory();
		$query = "SELECT COUNT(patent.id) FROM patent, ".
				"(_r_patent_equipment JOIN _r_tag_patent " . 
				"ON _r_tag_patent.id1=%d AND _r_patent_equipment.id2=%d) ".
				"WHERE patent.`date` BETWEEN %d AND %d ".
				"AND _r_patent_equipment.id1=patent.id ".
				"AND _r_tag_patent.id2=patent.id  ORDER BY patent.id";
        if ($tag->id) $e->return_value = (int) $db->value($query, $tag->id, $equipment->id, $dtstart, $dtend);
        else $e->return_value = 0;
		return FALSE;
	}
	
	static function student_patents($e, $equipment, $dtstart, $dtend) {
		$patent_root = Tag_Model::root('achievements_patent');
		$tag = O('tag', ['root'=>$patent_root, 'name'=>'学生']);
		$db = Database::factory();
		$query = "SELECT COUNT(patent.id) FROM patent, ".
				"(_r_patent_equipment JOIN _r_tag_patent " . 
				"ON _r_tag_patent.id1=%d AND _r_patent_equipment.id2=%d) ".
				"WHERE patent.`date` BETWEEN %d AND %d ".
				"AND _r_patent_equipment.id1=patent.id ".
				"AND _r_tag_patent.id2=patent.id  ORDER BY patent.id";
        if ($tag->id) $e->return_value = (int) $db->value($query, $tag->id, $equipment->id, $dtstart, $dtend);
        else $e->return_value = 0;
		return FALSE;
	}

	static function projects_teaching($e, $equipment, $dtstart, $dtend) {
		if ( !class_exists('Lab_Project_Model') ) {
			$e->return_value = 0;
			return FALSE;
		}
		$db = Database::factory();
		$query = "SELECT COUNT(`t2`.`id`) FROM `eq_record` `t3` " . 
				"INNER JOIN (`lab_project` `t2`) ON ". 
				"(`t2`.`type`= %d ". 
				"AND `t3`.`equipment_id` = %d ".
				"AND (`t3`.`dtstart`>=%d ".
				"AND `t3`.`dtstart`<=%d) ".
				"AND `t3`.`project_id`=`t2`.`id`) ".
				"ORDER BY `t2`.`id`";
		$e->return_value = (int) $db->value($query, Lab_Project_Model::TYPE_EDUCATION, $equipment->id, $dtstart, $dtend);
		return FALSE;
	}
	
	static function projects_research($e, $equipment, $dtstart, $dtend) {
		if ( !class_exists('Lab_Project_Model') ) {
			$e->return_value = 0;
			return FALSE;
		}
		$db = Database::factory();
		$query = "SELECT COUNT(`t2`.`id`) FROM `eq_record` `t3` " . 
				"INNER JOIN (`lab_project` `t2`) ON ". 
				"(`t2`.`type`= %d ". 
				"AND `t3`.`equipment_id` = %d ".
				"AND (`t3`.`dtstart`>=%d ".
				"AND `t3`.`dtstart`<=%d) ".
				"AND `t3`.`project_id`=`t2`.`id`) ".
				"ORDER BY `t2`.`id`";
		$e->return_value = (int) $db->value($query, Lab_Project_Model::TYPE_RESEARCH, $equipment->id, $dtstart, $dtend);
		return FALSE;
	}
	
	static function projects_public_service($e, $equipment, $dtstart, $dtend) {
		if ( !class_exists('Lab_Project_Model') ) {
			$e->return_value = 0;
			return FALSE;
		}
		$db = Database::factory();
		$query = "SELECT COUNT(`t2`.`id`) FROM `eq_record` `t3` " . 
				"INNER JOIN (`lab_project` `t2`) ON ". 
				"(`t2`.`type`= %d ". 
				"AND `t3`.`equipment_id` = %d ".
				"AND (`t3`.`dtstart`>=%d ".
				"AND `t3`.`dtstart`<=%d) ".
				"AND `t3`.`project_id`=`t2`.`id`) ".
				"ORDER BY `t2`.`id`";
		$e->return_value = (int) $db->value($query, Lab_Project_Model::TYPE_SERVICE, $equipment->id, $dtstart, $dtend);
		return FALSE;
	}

	static function teaching_time($e, $equipment, $dtstart, $dtend) {
		if ( !class_exists('Lab_Project_Model') ) {
			$e->return_value = 0;
			return FALSE;
		}
		$db = Database::factory();
		$query = "SELECT SUM(`t3`.`dtend` - `t3`.`dtstart`) FROM `eq_record` `t3` " . 
			"INNER JOIN (`lab_project` `t2`) ON ( ".
			"`t2`.`type`= %d " .
			"AND `t3`.`equipment_id` = %d ".
			"AND (`t3`.`dtstart`>= %d AND `t3`.`dtstart`<= %d) ".
			"AND `t3`.`dtend`>'0' " .
			"AND `t3`.`project_id`=`t2`.`id`)";

		$e->return_value = (int) $db->value($query, Lab_Project_Model::TYPE_EDUCATION, $equipment->id, $dtstart, $dtend);
		return FALSE;
	}

	static function research_time($e, $equipment, $dtstart, $dtend) {
		if ( !class_exists('Lab_Project_Model') ) {
			$e->return_value = 0;
			return FALSE;
		}
		$db = Database::factory();
		$query = "SELECT SUM(`t3`.`dtend` - `t3`.`dtstart`) FROM `eq_record` `t3` " . 
			"INNER JOIN (`lab_project` `t2`) ON ( ".
			"`t2`.`type`= %d " .
			"AND `t3`.`equipment_id` = %d ".
			"AND (`t3`.`dtstart`>= %d AND `t3`.`dtstart`<= %d) ".
			"AND `t3`.`dtend`>'0' " .
			"AND `t3`.`project_id`=`t2`.`id`)";
		$e->return_value = (int) $db->value($query, Lab_Project_Model::TYPE_RESEARCH, $equipment->id, $dtstart, $dtend);
		return FALSE;
	}

	static function social_services_time($e, $equipment, $dtstart, $dtend) {
		if ( !class_exists('Lab_Project_Model') ) {
			$e->return_value = 0;
			return FALSE;
		}
		$db = Database::factory();
		$query = "SELECT SUM(`t3`.`dtend` - `t3`.`dtstart`) FROM `eq_record` `t3` " . 
			"INNER JOIN (`lab_project` `t2`) ON ( ".
			"`t2`.`type`= %d " .
			"AND `t3`.`equipment_id` = %d ".
			"AND (`t3`.`dtstart`>= %d AND `t3`.`dtstart`<= %d) ".
			"AND `t3`.`dtend`>'0' " .
			"AND `t3`.`project_id`=`t2`.`id`)";
		$e->return_value = (int) $db->value($query, Lab_Project_Model::TYPE_SERVICE, $equipment->id, $dtstart, $dtend);
		return FALSE;
	}

	static function teacher_trainees($e, $equipment, $dtstart, $dtend) {
		$return_value = 0;

        $status = UE_Training_Model::STATUS_APPROVED;

        foreach(Q("ue_training[status={$status}][equipment={$equipment}][ctime={$dtstart}~{$dtend}]<user user") as $user) {
            if (in_array(ROLE_TEACHERS, $user->roles())) {
                $return_value ++;
            }
        }

        $e->return_value = $return_value;
        return FALSE;
	}

	static function student_trainees($e, $equipment, $dtstart, $dtend) {
		$return_value = 0;

        $status = UE_Training_Model::STATUS_APPROVED;

        foreach(Q("ue_training[status={$status}][equipment={$equipment}][ctime={$dtstart}~{$dtend}]<user user") as $user) {
            if (in_array(ROLE_STUDENTS, $user->roles())) {
                $return_value ++;
            }
        }

        $otherCount = (int) Q("{$equipment} ge_training[date={$dtstart}~{$dtend}]")->SUM('napproved');

        $e->return_value = $return_value + $otherCount;
        return FALSE;
	}

	static function other_trainees($e, $equipment, $dtstart, $dtend) {
		$return_value = 0;

        $status = UE_Training_Model::STATUS_APPROVED;

        foreach(Q("ue_training[status={$status}][equipment={$equipment}][ctime={$dtstart}~{$dtend}]<user user") as $user) {
            if (!in_array(ROLE_TEACHERS, $user->roles()) && !in_array(ROLE_STUDENTS, $user->roles())) {
                $return_value ++;
            }
        }

        $e->return_value = $return_value;
        return FALSE;
	}
}
