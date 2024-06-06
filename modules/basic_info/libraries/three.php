<?php

class Three
{

    public static function teaching_dur($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT SUM(`e`.`dtend`-`e`.`dtstart`)".
        " FROM `eq_record` AS `e`".
        " INNER JOIN `lab_project` AS `l`".
        " ON `e`.`project_id`=`l`.`id`".
        " WHERE `e`.`equipment_id`=:equipment_id".
        " AND `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `l`.`type`=:type";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':type' => Lab_Project_Model::TYPE_EDUCATION
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function research_dur($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT SUM(`e`.`dtend`-`e`.`dtstart`)".
        " FROM `eq_record` AS `e`".
        " INNER JOIN `lab_project` AS `l`".
        " ON `e`.`project_id`=`l`.`id`".
        " WHERE `e`.`equipment_id`=:equipment_id".
        " AND `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `l`.`type`=:type";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':type' => Lab_Project_Model::TYPE_RESEARCH
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function service_dur($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT SUM(`e`.`dtend`-`e`.`dtstart`)".
        " FROM `eq_record` AS `e`".
        " INNER JOIN `lab_project` AS `l`".
        " ON `e`.`project_id`=`l`.`id`".
        " WHERE `e`.`equipment_id`=:equipment_id".
        " AND `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `l`.`type`=:type";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':type' => Lab_Project_Model::TYPE_SERVICE
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function open_dur($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT SUM(`e`.`dtend`-`e`.`dtstart`)".
        " FROM `eq_record` AS `e`".
        " WHERE `e`.`equipment_id`=:equipment_id".
        " AND `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`dtend`>`e`.`dtstart`".
        " AND `e`.`user_id` NOT IN (".
        "SELECT `r`.`id1` FROM `_r_user_equipment` AS `r`".
        " WHERE `r`.`id2`=:equipment_id".
        " AND `r`.`type`='incharge')";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function samples($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $sum = 0;
        $arr = [];

        // 找出所有送样预约
        $es_result = Q("eq_sample[equipment_id={$equipment->id}][dtend>=$dtstart][dtend<=$dtend]");

        foreach ($es_result as $es) {
            $sum += $es->count;
            $arr[] = $es->record_id;
        }

        // 找处所有使用记录，关联送样的不算在内
        $er_result = Q("eq_record[equipment_id={$equipment->id}][dtend>=$dtstart][dtend<=$dtend]");

        foreach ($er_result as $er) {
            if(in_array($er->id, $arr)) continue;
            $sum += $er->samples;
        }

        $e->return_value = $sum;

        return FALSE;
    }

    public static function train_stu_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(distinct(`u`.`id`))".
        " FROM `ue_training` AS `u`".
        " INNER JOIN `user` AS `s` ".
        " ON `u`.`user_id`=`s`.`id`".
        " INNER JOIN `tag` AS `t`".
        " ON `s`.`group_id`=`t`.`id`".
        " WHERE `u`.`equipment_id`=:equipment_id".
        " AND `t`.`_extra` like '%:root%'".
        " AND `u`.`mtime` BETWEEN :dtstart AND :dtend".
        " AND `u`.`type` BETWEEN 0 AND 3".
        " AND `u`.`status`=:status";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':root' => Config::get('three.tag_root'),
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':status' => UE_Training_Model::STATUS_APPROVED
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function train_tea_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(distinct(`u`.`id`))".
        " FROM `ue_training` AS `u`".
        " INNER JOIN `user` AS `s` ".
        " ON `u`.`user_id`=`s`.`id`".
        " INNER JOIN `tag` AS `t`".
        " ON `s`.`group_id`=`t`.`id`".
        " WHERE `u`.`equipment_id`=:equipment_id".
        " AND `t`.`_extra` like '%:root%'".
        " AND `u`.`mtime` BETWEEN :dtstart AND :dtend".
        " AND `u`.`type` BETWEEN 10 AND 13".
        " AND `u`.`status`=:status";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':root' => Config::get('three.tag_root'),
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':status' => UE_Training_Model::STATUS_APPROVED
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }
    
    public static function train_oth_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(distinct(`u`.`id`))".
        " FROM `ue_training` AS `u`".
        " INNER JOIN `user` AS `s` ".
        " ON `u`.`user_id`=`s`.`id`".
        " INNER JOIN `tag` AS `t`".
        " ON `s`.`group_id`=`t`.`id`".
        " WHERE `u`.`equipment_id`=:equipment_id".
        " AND `t`.`_extra` like '%:root%'".
        " AND `u`.`mtime` BETWEEN :dtstart AND :dtend".
        " AND `u`.`type` BETWEEN 20 AND 22".
        " AND `u`.`status`=:status";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':root' => Config::get('three.tag_root'),
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':status' => UE_Training_Model::STATUS_APPROVED
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function education_pro_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(DISTINCT `id`)".
        " FROM ((".
        " SELECT DISTINCT `l1`.`id` AS `id`".
        " FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2`".
        " ON `e2`.`reserv_id`=`e1`.`id` JOIN `lab_project` `l1`".
        " ON `e1`.`project_id`=`l1`.`id`".
        " WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e1`.`equipment_id`=:equipment_id".
        " AND `l1`.`type`=:type GROUP BY `e1`.`equipment_id`, `e1`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l`.`id` AS `id`".
        " FROM `eq_record` AS `e` INNER JOIN `lab_project` AS `l`".
        " ON `e`.`project_id`=`l`.`id`".
        " WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`equipment_id`=:equipment_id".
        " AND `l`.`type`=:type GROUP BY `e`.`equipment_id`, `e`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l`.`id` AS `id`".
        " FROM `eq_record` AS `e` INNER JOIN `eq_reserv` AS `r`".
        " ON `e`.`reserv_id`=`r`.`id` INNER JOIN `lab_project` AS `l`".
        " ON `r`.`project_id`=`l`.`id`".
        " WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`project_id`=0 AND `e`.`equipment_id`=:equipment_id".
        " AND `l`.`type`=:type GROUP BY `e`.`equipment_id`, `e`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l2`.`id` AS `id`".
        " FROM `eq_sample` `e3` JOIN `lab_project` `l2`".
        " ON `e3`.`project_id`=`l2`.`id`".
        " WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e3`.`equipment_id`=:equipment_id AND `e3`.`status`=:status".
        " AND `l2`.`type`=:type GROUP BY `e3`.`equipment_id`, `e3`.`sender_id`".
        " )) AS `U`";

	    $sql = strtr($sql, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':equipment_id' => $equipment->id,
            ':type' => Lab_Project_Model::TYPE_EDUCATION,
            ':status' => EQ_Sample_Model::STATUS_TESTED
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function research_pro_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(DISTINCT `id`)".
        " FROM ((".
        " SELECT DISTINCT `l1`.`id` AS `id`".
        " FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2`".
        " ON `e2`.`reserv_id`=`e1`.`id` JOIN `lab_project` `l1`".
        " ON `e1`.`project_id`=`l1`.`id`".
        " WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e1`.`equipment_id`=:equipment_id".
        " AND `l1`.`type`=:type GROUP BY `e1`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l`.`id` AS `id`".
        " FROM `eq_record` AS `e` INNER JOIN `lab_project` AS `l`".
        " ON `e`.`project_id`=`l`.`id`".
        " WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`equipment_id`=:equipment_id".
        " AND `l`.`type`=:type GROUP BY `e`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l`.`id` AS `id`".
        " FROM `eq_record` AS `e` INNER JOIN `eq_reserv` AS `r`".
        " ON `e`.`reserv_id`=`r`.`id` INNER JOIN `lab_project` AS `l`".
        " ON `r`.`project_id`=`l`.`id`".
        " WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`project_id`=0 AND `e`.`equipment_id`=:equipment_id".
        " AND `l`.`type`=:type GROUP BY `e`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l2`.`id` AS `id`".
        " FROM `eq_sample` `e3` JOIN `lab_project` `l2`".
        " ON `e3`.`project_id`=`l2`.`id`".
        " WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e3`.`equipment_id`=:equipment_id AND `e3`.`status`=:status".
        " AND `l2`.`type`=:type GROUP BY `e3`.`sender_id`".
        " )) AS `U`";

	    $sql = strtr($sql, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':equipment_id' => $equipment->id,
            ':type' => Lab_Project_Model::TYPE_RESEARCH,
            ':status' => EQ_Sample_Model::STATUS_TESTED
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function service_pro_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(DISTINCT `id`)".
        " FROM ((".
        " SELECT DISTINCT `l1`.`id` AS `id`".
        " FROM `eq_reserv` `e1` LEFT JOIN `eq_record` `e2`".
        " ON `e2`.`reserv_id`=`e1`.`id` JOIN `lab_project` `l1`".
        " ON `e1`.`project_id`=`l1`.`id`".
        " WHERE `e2`.`id` IS NULL AND `e1`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e1`.`equipment_id`=:equipment_id".
        " AND `l1`.`type`=:type GROUP BY `e1`.`equipment_id`, `e1`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l`.`id` AS `id`".
        " FROM `eq_record` AS `e` INNER JOIN `lab_project` AS `l`".
        " ON `e`.`project_id`=`l`.`id`".
        " WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`equipment_id`=:equipment_id".
        " AND `l`.`type`=:type GROUP BY `e`.`equipment_id`, `e`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l`.`id` AS `id`".
        " FROM `eq_record` AS `e` INNER JOIN `eq_reserv` AS `r`".
        " ON `e`.`reserv_id`=`r`.`id` INNER JOIN `lab_project` AS `l`".
        " ON `r`.`project_id`=`l`.`id`".
        " WHERE `e`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e`.`project_id`=0 AND `e`.`equipment_id`=:equipment_id".
        " AND `l`.`type`=:type GROUP BY `e`.`equipment_id`, `e`.`user_id`".
        " ) UNION ALL (".
        " SELECT DISTINCT `l2`.`id` AS `id`".
        " FROM `eq_sample` `e3` JOIN `lab_project` `l2`".
        " ON `e3`.`project_id`=`l2`.`id`".
        " WHERE `e3`.`dtend` BETWEEN :dtstart AND :dtend".
        " AND `e3`.`equipment_id`=:equipment_id AND `e3`.`status`=:status".
        " AND `l2`.`type`=:type GROUP BY `e3`.`equipment_id`, `e3`.`sender_id`".
        " )) AS `U`";

	    $sql = strtr($sql, [
            ':dtstart' => $dtstart,
            ':dtend' => $dtend,
            ':equipment_id' => $equipment->id,
            ':type' => Lab_Project_Model::TYPE_SERVICE,
            ':status' => EQ_Sample_Model::STATUS_TESTED
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function national_awards_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(`a`.`id`)".
        " FROM `award` AS `a`".
        " INNER JOIN `_r_tag_award` AS `r`".
        " ON `a`.`id`=`r`.`id2`".
        " INNER JOIN `_r_equipment_award` AS `e`".
        " ON `a`.`id`=`e`.`id2`".
        " INNER JOIN `tag` AS `t`".
        " ON `r`.`id1`=`t`.`id`".
        " WHERE `e`.`id1`=:equipment_id".
        " AND `a`.`date` BETWEEN :dtstart AND :dtend".
        " AND `t`.`name`='国家级'";
        // " AND `t`.`readonly`=1";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
        
    }

    public static function province_awards_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(`a`.`id`)".
        " FROM `award` AS `a`".
        " INNER JOIN `_r_tag_award` AS `r`".
        " ON `a`.`id`=`r`.`id2`".
        " INNER JOIN `_r_equipment_award` AS `e`".
        " ON `a`.`id`=`e`.`id2`".
        " INNER JOIN `tag` AS `t`".
        " ON `r`.`id1`=`t`.`id`".
        " WHERE `e`.`id1`=:equipment_id".
        " AND `a`.`date` BETWEEN :dtstart AND :dtend".
        " AND `t`.`name`='省部级'";
        // " AND `t`.`readonly`=1";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function tea_patent_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(`a`.`id`)".
        " FROM `patent` AS `a`".
        " INNER JOIN `_r_tag_patent` AS `r`".
        " ON `a`.`id`=`r`.`id2`".
        " INNER JOIN `_r_patent_equipment` AS `e`".
        " ON `a`.`id`=`e`.`id1`".
        " INNER JOIN `tag` AS `t`".
        " ON `r`.`id1`=`t`.`id`".
        " WHERE `e`.`id2`=:equipment_id".
        " AND `a`.`date` BETWEEN :dtstart AND :dtend".
        " AND `t`.`name`='教师'";
        // " AND `t`.`readonly`=1";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function stu_patent_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(`a`.`id`)".
        " FROM `patent` AS `a`".
        " INNER JOIN `_r_tag_patent` AS `r`".
        " ON `a`.`id`=`r`.`id2`".
        " INNER JOIN `_r_patent_equipment` AS `e`".
        " ON `a`.`id`=`e`.`id1`".
        " INNER JOIN `tag` AS `t`".
        " ON `r`.`id1`=`t`.`id`".
        " WHERE `e`.`id2`=:equipment_id".
        " AND `a`.`date` BETWEEN :dtstart AND :dtend".
        " AND `t`.`name`='学生'";
        // " AND `t`.`readonly`=1";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function three_pubs_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(`a`.`id`)".
        " FROM `publication` AS `a`".
        " INNER JOIN `_r_tag_publication` AS `r`".
        " ON `a`.`id`=`r`.`id2`".
        " INNER JOIN `_r_publication_equipment` AS `e`".
        " ON `a`.`id`=`e`.`id1`".
        " INNER JOIN `tag` AS `t`".
        " ON `r`.`id1`=`t`.`id`".
        " WHERE `e`.`id2`=:equipment_id".
        " AND `a`.`date` BETWEEN :dtstart AND :dtend".
        " AND `t`.`name`='三大检索'";
        // " AND `t`.`readonly`=1";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }

    public static function core_pubs_count($e, $equipment, $dtstart, $dtend)
    {
        if (!$equipment->id || !$dtstart || !$dtend) return FALSE;
        
        $db = Database::factory();

        $sql = "SELECT COUNT(`a`.`id`)".
        " FROM `publication` AS `a`".
        " INNER JOIN `_r_tag_publication` AS `r`".
        " ON `a`.`id`=`r`.`id2`".
        " INNER JOIN `_r_publication_equipment` AS `e`".
        " ON `a`.`id`=`e`.`id1`".
        " INNER JOIN `tag` AS `t`".
        " ON `r`.`id1`=`t`.`id`".
        " WHERE `e`.`id2`=:equipment_id".
        " AND `a`.`date` BETWEEN :dtstart AND :dtend".
        " AND `t`.`name`='核心刊物'";
        // " AND `t`.`readonly`=1";

        $sql = strtr($sql, [
            ':equipment_id' => $equipment->id,
            ':dtstart' => $dtstart,
            ':dtend' => $dtend
        ]);

        $result = $db->value($sql) ? : 0;

        $e->return_value = $result;

        return FALSE;
    }
}
