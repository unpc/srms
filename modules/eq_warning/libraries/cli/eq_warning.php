<?php

class CLI_EQ_Warning {

	public static function warning_month_data(){

        $now = time();

        $dtstart_date = date('Y-m-d', strtotime(date('Y-m-01',$now) . ' -1 month'));
        $dtend_date = date('Y-m-d', strtotime(date('Y-m-01',$now) . ' -1 day'));
        $dtstart = strtotime($dtstart_date);
        $dtend = strtotime($dtend_date) + 86399;

        $db = Database::factory();
        $unit = EQ_Warning::UNIT_MONTH;
        $stat_equipments = [];
        $rules = Q("eq_warning_rule[unit={$unit}]");
        foreach($rules as $rule){
            $stat_equipments[] = $rule->equipment->id;
        }
        array_unique($stat_equipments);
        if(empty($stat_equipments)) return;
        
        $sql = "
            SELECT
                if (sum( dtend - dtstart ) , sum( dtend - dtstart ), 0) AS dur,
                r.equipment_id,
                r.machine_hour,
                r.use_limit_max,
                r.use_limit_min 
            FROM
                eq_warning_rule r
                LEFT JOIN eq_record e ON (r.equipment_id = e.equipment_id or e.equipment_id is null) AND e.dtend BETWEEN  {$dtstart} and {$dtend} 
            WHERE
                r.unit = '{$unit}'
            GROUP BY r.equipment_id
        ";

        $rows = $db->query($sql)->rows();

        if(count($rows)){
            foreach($rows as $row){
                $row->dur = $row->dur ?: 0;
                $machine_sec = $row->machine_hour * 3600;
                $use_limit_max_sec = $row->use_limit_max * 3600;
                $use_limit_min_sec = $row->use_limit_min * 3600;
                //分别发送
                if($machine_sec && $row->dur < $machine_sec){
                    self::send($row->equipment_id,$unit,'machine_hour',$row->machine_hour);
                }
                if($use_limit_max_sec && $row->dur > $use_limit_max_sec){
                    // self::send($row->equipment_id,$unit,'use_limit_max',$row->use_limit_max);
                }
                if($use_limit_min_sec && $row->dur < $use_limit_min_sec){
                    self::send($row->equipment_id,$unit,'use_limit_min',$row->use_limit_min);
                }
            }
        }
    }

    public static function warning_quarter_data(){

        $now = time();

        $season = ceil((date('n'))/3);
        $dtstart_date = date('Y-m-d', mktime(0, 0, 0,$season*3-6+1,1,date('Y')));
        $dtend_date = date('Y-m-d', mktime(23,59,59,$season*3-3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));

        $dtstart = strtotime($dtstart_date);
        $dtend = strtotime($dtend_date) + 86399;

        $db = Database::factory();
        $unit = EQ_Warning::UNIT_QUARTER;
        $stat_equipments = [];
        $rules = Q("eq_warning_rule[unit={$unit}]");
        foreach($rules as $rule){
            $stat_equipments[] = $rule->equipment->id;
        }
        array_unique($stat_equipments);
        if(empty($stat_equipments)) return;
        
        $sql = "
            SELECT
                if (sum( dtend - dtstart ) , sum( dtend - dtstart ), 0) AS dur,
                r.equipment_id,
                r.machine_hour,
                r.use_limit_max,
                r.use_limit_min 
            FROM
                eq_warning_rule r
                LEFT JOIN eq_record e ON (r.equipment_id = e.equipment_id or e.equipment_id is null) AND e.dtend BETWEEN  {$dtstart} and {$dtend} 
            WHERE
                r.unit = '{$unit}'
            GROUP BY r.equipment_id
        ";
        $rows = $db->query($sql)->rows();
        if(count($rows)){
            foreach($rows as $row){
                $row->dur = $row->dur ?: 0;
                $machine_sec = $row->machine_hour * 3600;
                $use_limit_max_sec = $row->use_limit_max * 3600;
                $use_limit_min_sec = $row->use_limit_min * 3600;
                //分别发送
                if($machine_sec && $row->dur < $machine_sec){
                    self::send($row->equipment_id,$unit,'machine_hour',$row->machine_hour);
                }
                if($use_limit_max_sec && $row->dur > $use_limit_max_sec){
                    // self::send($row->equipment_id,$unit,'use_limit_max',$row->use_limit_max);
                }
                if($use_limit_min_sec && $row->dur < $use_limit_min_sec){
                    self::send($row->equipment_id,$unit,'use_limit_min',$row->use_limit_min);
                }
            }
        }
    }

    public static function warning_year_data(){

        $now = time();

        $dtstart_date = date('Y-m-d', mktime(0,0,0,1,1,date('Y')-1));
        $dtend_date = date('Y-m-d', mktime(23,59,59,12,31,date('Y')-1));

        $dtstart = strtotime($dtstart_date);
        $dtend = strtotime($dtend_date) + 86399;

        $db = Database::factory();
        $unit = EQ_Warning::UNIT_YEAR;
        $stat_equipments = [];
        $rules = Q("eq_warning_rule[unit={$unit}]");
        foreach($rules as $rule){
            $stat_equipments[] = $rule->equipment->id;
        }
        array_unique($stat_equipments);
        if(empty($stat_equipments)) return;
        
        $sql = "
            SELECT
                if (sum( dtend - dtstart ) , sum( dtend - dtstart ), 0) AS dur,
                r.equipment_id,
                r.machine_hour,
                r.use_limit_max,
                r.use_limit_min 
            FROM
                eq_warning_rule r
                LEFT JOIN eq_record e ON (r.equipment_id = e.equipment_id or e.equipment_id is null) AND e.dtend BETWEEN  {$dtstart} and {$dtend} 
            WHERE
                r.unit = '{$unit}'
            GROUP BY r.equipment_id
        ";

        $rows = $db->query($sql)->rows();
        if(count($rows)){
            foreach($rows as $row){
                $row->dur = $row->dur ?: 0;
                $machine_sec = $row->machine_hour * 3600;
                $use_limit_max_sec = $row->use_limit_max * 3600;
                $use_limit_min_sec = $row->use_limit_min * 3600;
                //分别发送
                if($machine_sec && $row->dur < $machine_sec){
                    self::send($row->equipment_id,$unit,'machine_hour',$row->machine_hour);
                }
                if($use_limit_max_sec && $row->dur > $use_limit_max_sec){
                    // self::send($row->equipment_id,$unit,'use_limit_max',$row->use_limit_max);
                }
                if($use_limit_min_sec && $row->dur < $use_limit_min_sec){
                    self::send($row->equipment_id,$unit,'use_limit_min',$row->use_limit_min);
                }
            }
        }
    }

    public static function warning_max_data_everyday(){

        $now = time();
        $db = Database::factory();

        $rules = Q("eq_warning_rule[use_limit_max]");

        $format_rules = [];
        foreach($rules as $rule){
            $format_rules[$rule->equipment->id][$rule->unit] = $rule;
        }

        foreach($format_rules as $eqid => $rules){
            foreach($rules as $unit => $rule){
                $equipment = $rule->equipment;
                if($unit == EQ_Warning::UNIT_MONTH){
                    $dtstart_date = date('Y-m-d', strtotime(date('Y-m-01',$now)));
                    $dtend_date = date('Y-m-d H:i:s', $now);
                }
                if($unit == EQ_Warning::UNIT_QUARTER){
                    $season = ceil((date('n'))/3);
                    $dtstart_date = date('Y-m-d', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));
                    $dtend_date = date('Y-m-d H:i:s', $now);
                }
                if($unit == EQ_Warning::UNIT_YEAR){
                    $dtstart_date = date('Y-m-d', mktime(0,0,0,1,1,date('Y')));
                    $dtend_date = date('Y-m-d H:i:s', $now);
                }

                $dtstart = strtotime($dtstart_date);
                $dtend = $now;

                $sql = "
                    SELECT
                        if (sum( dtend - dtstart ) , sum( dtend - dtstart ), 0) AS dur,
                        e.equipment_id
                    FROM
                        eq_record e  
                    WHERE
                        e.equipment_id = {$equipment->id} AND e.dtend BETWEEN  {$dtstart} and {$dtend} 
                    GROUP BY e.equipment_id
                ";

                $rows = $db->query($sql)->rows();
                if(!count($rows)) continue;
                $row = $rows[0];

                if($rule->use_limit_max && $row->dur > $rule->use_limit_max * 3600){
                    self::send($eqid,$unit,'use_limit_max',$rule->use_limit_max);
                }
                
            }
        }

    }

    public static function send($eqid,$unit,$type,$type_limit){

        $key = '';
        switch ($type) {
            case 'machine_hour':
                $key = 'eq_warning.less_use';
                break;
            case 'use_limit_max':
                $key = 'eq_warning.more_use';
                break;
            case 'use_limit_min':
                $key = 'eq_warning.too_less_use';
                break;
            default:
                $key = '';
                break;
        }

        if(!$key) return;

        $equipment = O('equipment',$eqid);
        if($equipment->is_removable) return;
        $incharges = Q("{$equipment}<incharge user");

        $has_send = [];
        foreach($incharges as $incharge){
            $has_send[] = $incharge->id;
            Notification::send($key, $incharge, [
                '%user' => Markup::encode_Q($incharge),
                '%equipment' => Markup::encode_Q($equipment),
                '%equipment_id' => $equipment->id,
                '%time' => $type_limit.'H'
            ]);
        }

        if(!$equipment->group->id) return;

        //给平台负责人发送
        $roles = Q("perm[name=添加/修改下属机构的仪器,添加/修改仪器] role[weight>0]");
        foreach($roles as $role){
            $start = 0;
		    $per_page = 10;
            for (;;) {
                $users = Q("{$role} user[atime>0]")->limit($start,$per_page);
                if (count($users) == 0) break;
                $start += $per_page;
                foreach($users as $user) {
                    if(!$user->group->id) continue;
                    if(!$user->group->is_itself_or_ancestor_of($equipment->group)) continue;
                    if(in_array($user->id,$has_send)) continue;
                    $has_send[] = $user->id;
                    Notification::send($key, $user, [
                        '%user' => Markup::encode_Q($user),
                        '%equipment' => Markup::encode_Q($equipment),
                        '%equipment_id' => $equipment->id,
                        '%time' => $type_limit.'H'
                    ]);
                }
            }
        }  
    }

}
	