<?php

class Sj_Info {

    private $id;
    private $dtstart;
    private $dtend;

    function __construct($id, $dtstart, $dtend){
        $this->id = $id;  
        $this->dtstart = $dtstart;
        $this->dtend = $dtend;
    }

    function get_time_project($project_type) {
        $id = $this->id;
        $dtstart = $this->dtstart;
        $dtend = $this->dtend;

        $query =
       "SELECT 
        SUM(`e`.`dtend` - `e`.`dtstart`) 
        FROM 
        `eq_record` AS `e` 
        INNER JOIN `lab_project` AS `l`
        ON `e`.`project_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`dtend` BETWEEN %d AND %d
        AND `l`.`type` = %d";

        $db = Database::factory();
        return $db->value($query, $id, $dtstart, $dtend, $project_type);
    }

    function get_time_open() {
        $id = $this->id;
        $dtstart = $this->dtstart;
        $dtend = $this->dtend;

        $query =
       "SELECT 
        SUM(`e`.`dtend` - `e`.`dtstart`) 
        FROM 
        `eq_record` AS `e`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`dtend` BETWEEN %d AND %d
        AND `e`.`dtend` > `e`.`dtstart`
        AND `e`.`user_id` NOT IN (
                SELECT 
                `r`.`id1` 
                FROM 
                `_r_user_equipment` AS `r`
                WHERE
                `r`.`id2` = %d
                AND `r`.`type` = 'incharge'
            )";

        $db = Database::factory(); 
        return $db->value($query, $id, $dtstart, $dtend, $id);
    }

    function get_traning_inside($member_type) {
        $id = $this->id;
        $dtstart = $this->dtstart;
        $dtend = $this->dtend;
        
        $member_type_s = $member_type - 9;
        $root = '%"' . Config::get('sj_tri.root_name') . '"%';
        $query =
       "SELECT 
        COUNT(`u`.`id`) 
        FROM 
        `ue_training` AS `u`
        INNER JOIN `user` AS `s` ON `u`.`user_id` = `s`.`id`
        INNER JOIN `tag_group` AS `t` ON `s`.`group_id` = `t`.`id`
        WHERE
        `u`.`equipment_id` = $id
        AND `t`.`_extra` like '$root'
        AND `u`.`mtime` BETWEEN $dtstart AND $dtend
        AND `u`.`type` BETWEEN $member_type_s AND $member_type
        AND `u`.`status` = 2";
        
        $db = Database::factory(); 
        return $db->value($query);
    }

    function get_project_count($project_type) {
        $id = $this->id;
        $dtstart = $this->dtstart;
        $dtend = $this->dtend;
        
        $value = 0;
        $db = Database::factory(); 

        $query =
       "SELECT 
        COUNT(distinct `e`.`reserv_id`) 
        FROM 
        `eq_record` AS `e` 
        INNER JOIN `lab_project` AS `l`
        ON `e`.`project_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`dtend` BETWEEN %d AND %d
        AND `l`.`type` = %d
        AND `e`.`reserv_id` <> 0";

        $value += $db->value($query, $id, $dtstart, $dtend, $project_type);

        $query =
       "SELECT 
        COUNT(`e`.`reserv_id`) 
        FROM 
        `eq_record` AS `e` 
        INNER JOIN `lab_project` AS `l`
        ON `e`.`project_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`dtend` BETWEEN %d AND %d
        AND `l`.`type` = %d
        AND `e`.`reserv_id` = 0";

        $value += $db->value($query, $id, $dtstart, $dtend, $project_type);

        $query =
       "SELECT 
        COUNT(`e`.`id`) 
        FROM 
        `eq_sample` AS `e` 
        INNER JOIN `lab_project` AS `l`
        ON `e`.`project_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`dtend` BETWEEN %d AND %d
        AND `l`.`type` = %d";

        $value += $db->value($query, $id, $dtstart, $dtend, $project_type);

        $query =
       "SELECT 
        COUNT(`e`.`id`) 
        FROM 
        `eq_reserv` AS `e` 
        INNER JOIN `lab_project` AS `l`
        ON `e`.`project_id` = `l`.`id`
        WHERE
        `e`.`equipment_id` = %d
        AND `e`.`dtend` BETWEEN %d AND %d
        AND `l`.`type` = %d
        AND `e`.`id` NOT IN (
            SELECT
            `r`.`reserv_id`
            FROM
            `eq_record` AS `r`
            WHERE `r`.`equipment_id` = %d
            AND `r`.`dtend` BETWEEN %d AND %d
            AND `r`.`reserv_id` <> 0
        )";

        $value += $db->value($query, $id, $dtstart, $dtend, $project_type, $id, $dtstart, $dtend);

        return $value;
    }

    function get_achievements_count($name, $type) {
        $id = $this->id;
        $dtstart = $this->dtstart;
        $dtend = $this->dtend;

        switch ($type) {
            case 'award':
                $r_table = '_r_equipment_award';
                break;
            case 'patent':
                $r_table = '_r_patent_equipment';
                break;
            case 'publication':
                $r_table = '_r_publication_equipment';
                break;
            default:
                break;
        }
        
        $query = 
       "SELECT 
        COUNT(`a`.`id`) 
        FROM 
        `$type` AS `a` 
        INNER JOIN `_r_tag_$type` AS `r`
        ON `a`.`id` = `r`.`id2`
        INNER JOIN `$r_table` AS `e` ";
        $query .= ($type == 'award') ? "ON `a`.`id` = `e`.`id2` " : "ON `a`.`id` = `e`.`id1` ";
        $query .=
       "INNER JOIN `tag_achievements_{$type}` AS `t`
        ON `r`.`id1` = `t`.`id`
        WHERE ";
        $query .= ($type == 'award') ? "`e`.`id1` = %d " : "`e`.`id2` = %d ";
        $query .=
       "AND `a`.`date` BETWEEN %d AND %d
        AND `t`.`name` = '$name'
        AND `t`.`readonly` = 1";

        $db = Database::factory(); 
        return $db->value($query, $id, $dtstart, $dtend);
    }
}