<?php
/**
 * @file   eq_stat.php
 * @author Xiaopei Li <xiaopei.li@geneegroup.com>
 * @date   Thu Dec  9 09:29:27 2010
 *
 * @brief  与统计功能相关的一系列助手函数
 *
 *
 */
class EQ_Stat {

    //获取years
	static function get_years() {
		$start = Config::get('eq_stat.start_year');
		$end = Date::format(Date::time(), 'Y');
		if (!$start) {
			$record_start = Date::format(ORM_Model::db('eq_record')->value('SELECT MIN(dtstart) FROM eq_record'), 'Y');
			$eq_start = Date::format(ORM_Model::db('equipment')->value('SELECT MIN(purchased_date) FROM equipment'), 'Y');
			$start = min($record_start, $eq_start);
			$start = ($end - $start) <= 5 ? $start : $end - 5;
		}
		$years = [];
		$years[0] = '--';
		foreach(range($start, $end) as $year) {
			$years[$year] = $year;
		}
		return $years;
	}
	
	/**
	 * 获取统计选项
	 *
	 *
	 * @return
	 */
	static function get_opts() {
		$stat_options = Config::get('eq_stat.stat_opts');
		$options = $stat_options + (array) Event::trigger('eq_stat.get_stat_options', $stat_options);
		
		uasort($options, function($param_s, $param_n) {
			if ( $param_s['weight'] > $param_n['weight'] ) {
				return 1;
			}
			elseif ( $param_s['weight'] > $param_n['weight'] ) {
				return 0;
			}
			else {
				return -1;
			}
		});
		
        return array_map(function($value) {
            return I18N::T('eq_stat', $value['name']);
        }, $options);
	}
	
    static function get_export_columns() {
        $columns = Config::get('eq_stat.export_columns.eq_stat');
        return $columns + (array) Event::trigger('eq_stat.get_stat_export_options');
    }

    //获取list的opts，会自动过滤const
    static function get_list_opts() {

        $list_opts = [];

        foreach((array) Config::get('eq_stat.stat_opts') as $key=>$value) {
            if (!$value['const']) {
                $list_opts[$key]  = $key;
            }
        }

        $new_opts = Event::trigger('eq_stat.add_save_opts', $list_opts);

        if ($new_opts) return $new_opts;

        return $list_opts;
    }

	/**
	 * 触发相应事件，获取某统计点的数据
	 *
	 * @param opt
	 * @param object
	 * @param dtstart
	 * @param dtend
	 *
	 * @return
	 */
    static function data_point($opt, $object, $dtstart, $dtend) {
        $oname = $object->name();

        $opts = Config::get('eq_stat.stat_opts');

        //进行dtstart dtend 相关处理
        if (!$dtend) {
            $dtend = Date::time();
        }

        if ($dtend < $dtstart) {
            list($dtstart, $dtend) = [$dtend, $dtstart];
        }

        if ($opts[$opt]['const']) {
            //如果为常量，则去trigger获取对应常量
            return Event::trigger("stat.const.$oname.$opt", $object, $dtstart, $dtend);
        }
        else {
            //不为常量，根据不同结果获取值

            switch($oname) {
                case 'tag' :
                	$result = 0;
                	$equipment_root = Tag_Model::root('equipment');
                	if ($object->id && $equipment_root->id && $object->id == $equipment_root->id) {
	                	$query = strtr( "SELECT SUM(%opt) FROM eq_stat WHERE %dtstart<=time AND time<=%dtend AND equipment_id NOT IN ".
	                	"(SELECT DISTINCT id2 FROM _r_tag_equipment WHERE id1 in " .
	                	 "(SELECT id FROM tag WHERE parent_id=%root_id))", [
	                	 	'%opt' => $opt,
	                	 	'%dtstart' => $dtstart,
	                	 	'%dtend' => $dtend,
	                	 	'%root_id' => $object->id,
	                	 ]);
	                	$db = Database::factory();
	                	$result = $db->value($query);
                	}
                	else {
	                	$result = Q("$object equipment eq_stat[time={$dtstart}~{$dtend}]")->SUM($opt);
                	}
                    return $result;
                    break;
                case 'equipment' :
                    $eq_stats = Q("eq_stat[equipment={$object}][time={$dtstart}~{$dtend}]");
                    return $eq_stats->SUM($opt);
                    break;
                default:
                    break;
            }
        }
    }

    //执行eq_stat对象保存
    static function do_stat_list_save($dtstart, $dtend, $clear_table = FALSE) {

        if ($clear_table) {

            //TODO，强制刷新的时候应该重新从有时间开始计算，或者从config获取开始计算时间

            $db = Database::factory();
            $query = 'drop table eq_stat';
            $db->query($query);

            Database::reset();
        }

        $no_longer_in_service = EQ_Status_Model::NO_LONGER_IN_SERVICE;
        $equipments  = Q("equipment[status!=$no_longer_in_service]");

        $stat_opts = self::get_list_opts();

        $dtstart = Date::get_day_start($dtstart);

        foreach($equipments as $e) {

            $stat = O('eq_stat', ['equipment'=>$e, 'time'=>$dtstart]);
            if (!$stat->id) {
	            $stat->equipment = $e;
	            $stat->time = $dtstart;
			}
			
            foreach($stat_opts as $opt) {
                $stat->$opt = Event::trigger("stat.equipment.$opt", $e, $dtstart, $dtend);
            }
            $stat->save();
        }
    }

    static function do_stat_list_save_equipment($dtstart, $dtend, $clear_table = FALSE, $id) {

        if ($clear_table) {

            //TODO，强制刷新的时候应该重新从有时间开始计算，或者从config获取开始计算时间

            $db = Database::factory();
            $query = 'drop table eq_stat';
            $db->query($query);

            Database::reset();
        }

        $no_longer_in_service = EQ_Status_Model::NO_LONGER_IN_SERVICE;
        $equipment  = O("equipment", $id);

        $stat_opts = self::get_list_opts();

        $dtstart = Date::get_day_start($dtstart);

        $stat = O('eq_stat', ['equipment'=>$equipment, 'time'=>$dtstart]);
        if (!$stat->id) {
            $stat->equipment = $equipment;
            $stat->time = $dtstart;
        }
        
        foreach($stat_opts as $opt) {
            $stat->$opt = Event::trigger("stat.equipment.$opt", $equipment, $dtstart, $dtend);
        }
        $stat->save();
    }

    static function people_extra_keys($e, $user, $info) {
        $info['group'] = $user->group->name;
        $info['is_center'] = $user->access('添加/修改下属机构的仪器') ? TRUE : FALSE;
        $info['is_incharge'] = Q("$user<incharge equipment")->total_count() ? TRUE : FALSE;
        
        return TRUE;
    }

    //eq_stat 权限设定
	static function eq_stat_ACL($e, $me, $perm_name, $perf, $options) {
        switch($perm_name) {
            default:
               $e->return_value = TRUE;
                break;
        }
    }
}
