<?php

class Analysis_Field {

    static function use_dur_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`e`.`dtend` - `e`.`dtstart`) AS `dur`
        FROM
        `eq_record` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND (`e`.`_extra` NOT LIKE '%%\"is_missed\":true%%' OR `e`.`_extra` IS NULL)
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`dtend` > `e`.`dtstart`
        AND `e`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function sample_dur_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`e`.`dtend` - `e`.`dtstart`) AS `dur`
        FROM
        `eq_sample` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`sender_id` = %d
        AND (`e`.`status` = %d OR `e`.`status` = %d)
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`dtend` > `e`.`dtstart`
        AND `e`.`dtsubmit` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, 
        EQ_Sample_Model::STATUS_TESTED, EQ_Sample_Model::STATUS_APPROVED, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function reserv_dur_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`e`.`dtend` - `e`.`dtstart`) AS `dur`
        FROM
        `eq_reserv` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`dtend` > `e`.`dtstart`
        AND `e`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function use_time_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        COUNT(`e`.`id`) AS `count`
        FROM
        `eq_record` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = :equipment_id
        AND `e`.`user_id` = :user_id
        AND `l`.`id` :lab
        AND `p`.`type` :project
        AND `e`.`dtend` > 0
        AND (`e`.`dtend` BETWEEN :dtstart AND :dtend)
        AND (`e`.`_extra` NOT LIKE '%\"is_missed\":true%' OR `e`.`_extra` IS NULL)";

        $query = strtr($query, [
            ':equipment_id' => $mark->equipment->id,
            ':user_id' => $mark->user->id,
            ':lab' => $lab,
            ':project' => $project,
            ':dtstart' => $start,
            ':dtend' => $end
        ]);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function sample_time_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        COUNT(`e`.`id`) AS `count`
        FROM
        `eq_sample` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`sender_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`dtend` > `e`.`dtstart`
        AND `e`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function reserv_time_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        COUNT(`e`.`id`) AS `count`
        FROM
        `eq_reserv` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`dtend` > `e`.`dtstart`
        AND `e`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function use_fee_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`amount`) AS `fee`
        FROM
        `eq_charge` AS `e`
        INNER JOIN `eq_record` AS `r` ON `e`.`source_id` = `r`.`id` AND  `e`.`source_name` = 'eq_record'
        LEFT JOIN `lab_project` AS `p` ON `p`.`id` = `r`.`project_id`
        LEFT JOIN `lab` AS `l` ON `p`.`lab_id` = `l`.`id`
        WHERE
        `r`.`equipment_id` = %d
        AND `r`.`user_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `r`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function sample_fee_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`amount`) AS `fee`
        FROM
        `eq_charge` AS `e`
        INNER JOIN `eq_sample` AS `r` ON `e`.`source_id` = `r`.`id` AND  `e`.`source_name` = 'eq_sample'
        LEFT JOIN `lab_project` AS `p` ON `p`.`id` = `r`.`project_id`
        LEFT JOIN `lab` AS `l` ON `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `r`.`dtsubmit` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function reserv_fee_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`amount`) AS `fee`
        FROM
        `eq_charge` AS `e`
        INNER JOIN `eq_reserv` AS `r` ON `e`.`source_id` = `r`.`id` AND  `e`.`source_name` = 'eq_reserv'
        LEFT JOIN `lab_project` AS `p` ON `p`.`id` = `r`.`project_id`
        LEFT JOIN `lab` AS `l` ON `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `r`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function success_sample_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`e`.`success_samples`) AS `count`
        FROM
        `eq_sample` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`sender_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`status` = 5
        AND `e`.`dtsubmit` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function use_sample_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`e`.`samples`) AS `count`
        FROM
        `eq_record` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`dtend` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }
    
    static function sample_sample_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $project = $mark->project != -1 ? '= '.$mark->project : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
        "SELECT
        SUM(`e`.`count`) AS `count`
        FROM
        `eq_sample` AS `e`
        LEFT JOIN
        `lab_project` AS `p`
        ON 
        `p`.`id` = `e`.`project_id`
        LEFT JOIN
        `lab` AS `l`
        ON
        `p`.`lab_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`sender_id` = %d
        AND `l`.`id` %s
        AND `p`.`type` %s
        AND `e`.`status` = 5
        AND `e`.`dtsubmit` BETWEEN %d AND %d";

        $query = sprintf($query, $mark->equipment->id, $mark->user->id, $lab, $project, $start, $end);

        $e->return_value = $db->value($query);

        return FALSE;
    }

    static function use_project_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query = 
       "SELECT COUNT(DISTINCT `e`.`id`) `count` 
        FROM `eq_record` AS `e` 
        INNER JOIN `lab_project` AS `p` ON `e`.`project_id` = `p`.`id`
        LEFT JOIN `lab` AS `l` ON `p`.`lab_id` = `l`.`id`
        WHERE
        `p`.`type` = %d
        AND `e`.`equipment_id` = %d
        AND `e`.`user_id` = %d
        AND `l`.`id` %s
        AND `e`.`dtend` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->project, $mark->equipment->id, 
        $mark->user->id, $lab, $start, $end);
        return FALSE;
    }
    
    static function sample_project_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);
        
        $query =
       "SELECT COUNT(DISTINCT `e`.`id`) `count` 
        FROM `eq_sample` AS `e` 
        INNER JOIN `lab_project` `p` ON `e`.`project_id` = `p`.`id`
        LEFT JOIN `lab` AS `l` ON `p`.`lab_id` = `l`.`id`
        WHERE
        `p`.`type` = %d
        AND `e`.`equipment_id` = %d
        AND `e`.`sender_id` = %d
        AND (`e`.`status` = %d OR `e`.`status` = %d)
        AND `l`.`id` %s
        AND `e`.`dtend` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->project, $mark->equipment->id, 
        $mark->user->id, EQ_Sample_Model::STATUS_TESTED, EQ_Sample_Model::STATUS_APPROVED, $lab, $start, $end);
        return FALSE;
    }

    static function reserv_project_refresh($e, $db, $mark) {
        $lab = $mark->lab->id ? '= '.$mark->lab->id : 'IS NULL';
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
       "SELECT COUNT(DISTINCT `r`.`id`) `count` 
        FROM `eq_reserv` `r` 
        INNER JOIN `lab_project` `p` ON `r`.`project_id` = `p`.`id` 
        LEFT JOIN `eq_record` `e` ON `e`.`reserv_id` = `r`.`id` 
        LEFT JOIN `lab` AS `l` ON `p`.`lab_id` = `l`.`id`
        WHERE
        `p`.`type` = %d
        AND `r`.`equipment_id` = %d
        AND `r`.`user_id` = %d
        AND `l`.`id` %s
        AND (`e`.`project_id` = 0 OR `e`.`project_id` IS NULL)
        AND `r`.`dtend` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->project, $mark->equipment->id, 
        $mark->user->id, $lab, $start, $end);
        return FALSE;
    }
}
