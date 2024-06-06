<?php
class EQ_Reserv_LUA extends EQ_Lua {

    protected $_component;
	private $_user;
	private $_old_start_time;
	private $_old_end_time;
	private $_start_time;
	private $_end_time;

	function __construct($component) {

        $equipment = $component->calendar->parent;

        parent::__construct($equipment->reserv_script);

        $this->_component = $component;
        $this->_old_start_time = $component->id ? $component->get('dtstart', TRUE) : 0;
        $this->_old_end_time = $component->id ? $component->get('dtend', TRUE) : 0;
        $this->_start_time = $component->dtstart;
		$this->_end_time = $component->dtend;
		$this->_ctime = $component->ctime;
        $this->_user = $this->_component->organizer;

        //add variable
        $this->lua->assign('old_start_time', (int)$this->_old_start_time);
        $this->lua->assign('old_end_time', (int)$this->_old_end_time);
        $this->lua->assign('start_time',  (int)$this->_start_time);
		$this->lua->assign('end_time', (int)$this->_end_time);
		$this->lua->assign('ctime', (int)$this->ctime);


		//add function
		
        $this->lua->registerCallback('is_incharge',[$this,'is_incharge']);
        $this->lua->registerCallback('is_contact',[$this,'is_contact']);
        $this->lua->registerCallback('user_tag', [$this, 'user_tag']);
        $this->lua->registerCallback('user_time', [$this, 'user_time']);
        $this->lua->registerCallback('user_reserv_time', [$this, 'user_reserv_time']);
        $this->lua->registerCallback('user_reserv_times', [$this, 'user_reserv_times']);
        $this->lua->registerCallback('lab_time', [$this, 'lab_time']);
        $this->lua->registerCallback('lab_reserv_time', [$this, 'lab_reserv_time']);
        $this->lua->registerCallback('lab_reserv_times', [$this, 'lab_reserv_times']);
        $this->lua->registerCallback('user_reserv_relative_time', [$this, 'user_reserv_relative_time']);
        $this->lua->registerCallback('lab_reserv_relative_time', [$this, 'lab_reserv_relative_time']);
        $this->lua->registerCallback('caculate_reserv_duration_time', [$this, 'caculate_reserv_duration_time']);
        $this->lua->registerCallback('cut_to_days', [$this, 'cut_to_days']);
        $this->lua->registerCallback('components', [$this, 'components']);
	}
	
	//判断是否是负责人
	function is_incharge() {
		$user = $this->_component->organizer;
		$equipment = $this->_component->calendar->parent;
		
		if (Q("{$equipment} {$user}.incharge")->total_count() > 0){
			return 1;
		}
		else {
			return NULL;
		}
	}
	
	//判断是否是联系人
	function is_contact() {
		$user = $this->_component->organizer;
		$equipment = $this->_component->calendar->parent;
		
		if (Q("{$equipment} {$user}.contact")->total_count() > 0){
			return 1;
		}
		else {
			return NULL;
		}
	}
	
	function user_tag($name) {
		
		$user = $this->_component->organizer;
		$equipment = $this->_component->calendar->parent;
		$root = [$equipment->get_root(), Tag_Model::root('equipment_user_tags')];
		return self::user_has_tag($name, $user, $root);
	}
	
	function user_time($dtstart, $dtend) {
		$user = $this->_user;
		$equipment = $this->_component->calendar->parent;
		$records = Q("eq_record[dtstart>={$dtstart}][dtstart<$dtend][user={$user}][equipment={$equipment}][reserv]");
		return (int)$this->get_real_time($records);
	}

    function user_reserv_time($dtstart, $dtend) {
        $user = $this->_user;
        $equipment = $this->_component->calendar->parent;
        $calendar = $this->_component->calendar;
        
        $components = Q("{$user}<organizer cal_component[dtstart>=$dtstart][dtstart<$dtend][calendar={$calendar}]");
        return (int)$this->get_real_reserv_time($components);
    }

	function user_reserv_times($dtstart, $dtend) {
        $user = $this->_user;
        $equipment = $this->_component->calendar->parent;
        $calendar = $this->_component->calendar;

        return Q("{$user}<organizer cal_component[dtstart>=$dtstart][dtstart<$dtend][calendar={$calendar}]")->total_count();
	}

	/* 获取用户某天的某个时段内预约时间, $dtstart和$dtend必须在同一天之内, $component_id当用户修改某预约时, 该预约不能在统计之内
	* $dtfrom 统计时段的开始时间
	* $dto 统计时段的结束时间
	* $dtstart 预约的开始时间
	* $dtend 预约的结束时间
	*/
    function user_reserv_relative_time($dtstart, $dtend, $dtfrom, $dto) {
    	$relative_time = 0;
    	if ( !$dtstart || !$dtend ) return $relative_time;
    	$user = $this->_user;
        $equipment = $this->_component->calendar->parent;
        $calendar = $this->_component->calendar;
        $component_id = $this->_component->id;
   
        $components = Q("{$user}<organizer cal_component([dtstart={$dtstart}~{$dtend}|dtstart~dtend={$dtstart}])[calendar={$calendar}][id!={$component_id}]");

        foreach( $components as $component) {
        	$dtstart = $component->dtstart;
        	$dtend = $component->dtend;
	       $relative_time += $this->caculate_reserv_duration_time($dtstart, $dtend, $dtfrom, $dto);
        }
        
        return $relative_time;
    }

    /**
     * 同上，只是获取单人改为获取课题组
     */
    function lab_reserv_relative_time($dtstart, $dtend, $dtfrom, $dto) {
        $relative_time = 0;
        if ( !$dtstart || !$dtend ) return $relative_time;
        $user = $this->_user;
        $lab = Q("$user lab")->current();
        $calendar = $this->_component->calendar;
        $component_id = $this->_component->id;

        $components = Q("{$lab} user<organizer cal_component([dtstart={$dtstart}~{$dtend}|dtstart~dtend={$dtstart}])[calendar={$calendar}][id!={$component_id}]");

        foreach( $components as $component) {
            $dtstart = $component->dtstart;
            $dtend = $component->dtend;
            $relative_time += $this->caculate_reserv_duration_time($dtstart, $dtend, $dtfrom, $dto);
        }

        return $relative_time;
    }
    
    //计算某个时间范围的预约在某个时段内的时间
    function caculate_reserv_duration_time($dtstart, $dtend, $dtfrom, $dto) {
    	$time = 0;
    	$dtfrom_date = getdate( $dtfrom );
		$dto_date = getdate( $dto );

	    if ( !$dtfrom || !$dto || !$dtstart || !$dtend || $dtstart >= $dtend 
	    	|| $dtfrom >= $dto )  {
		    	return $time;
	    	}

	    //年月日相同说明没有跨天
	    if ( (int)date('Ymd' , $dtstart) == (int)date('Ymd', $dtend) ) {
	    	$date = getdate($dtstart);
	    	$dtfrom = mktime($dtfrom_date['hours'], $dtfrom_date['minutes'], $dtfrom_date['seconds'], $date['mon'], $date['mday'], $date['year']);	    	
			$dto = mktime($dto_date['hours'], $dto_date['minutes'], $dto_date['seconds'], $date['mon'], $date['mday'], $date['year']);
			//验证预约的时间和截取的时间是否有交错
	    	if ( $dtend <= $dtfrom || $dtstart >= $dto ) 
		    	return $time;
	    	
		    if ( $dtstart <= $dtfrom && $dtend <= $dto ) {
		       return ($dtend - $dtfrom);
	        }
	        elseif ( $dtstart >= $dtfrom && $dtend <= $dto ) {
		       	return ($dtend - $dtstart);
	        }
	        elseif ( $dtstart >= $dtfrom &&  $dtend >= $dto ) {
	        	return ($dto - $dtstart);
	        }
	        elseif ( $dtstart <= $dtfrom && $dtend >= $dto ) {
		       return ($dto - $dtfrom);
	        }
	    }
	    else {
		    //预约跨天了
		    $reservs = $this->cut_to_days($dtstart, $dtend); 
		    if ( count($reservs) ) {
		    	foreach( $reservs as $reserv ) {	
			    	//此处需要注意dtfrom和dto传递的是当天的dtfrom和dto
				    $time += $this->caculate_reserv_duration_time($reserv['dtstart'], $reserv['dtend'], $dtfrom, $dto);
				}
		    }  
	    }
	    return $time;
    }
    
    //将跨天预约截为同天内的预约数组
    function cut_to_days($dtstart, $dtend) {
    
	    $component_days = [];
	    if ( date('Ymd' , $dtstart) == date('Ymd', $dtend) || $dtstart >= $dtend ) {
	     	$component_days[] = [
	     							'dtstart' => $dtstart, 
		 							'dtend' => $dtend,
		 					];
		 	return $component_days;
	    }
	    
	    //第一天
	    $component_days[] = [
		    		'dtstart' => $dtstart,
		    		'dtend' => Date::get_day_end($dtstart),
		    	];
		$dtstart += 86400;
		    	
	    while ( date('Ymd' , $dtstart) != date('Ymd', $dtend) ) {
			$day_start = Date::get_day_start($dtstart);
    		$component_days[] = [
	    		'dtstart' => Date::get_day_start($dtstart),
	    		'dtend' => ($day_start + 86399),
	    	];
	    	$dtstart += 86400;
	    }
	    
	    //最后一天
    	$component_days[] = [
	    		'dtstart' => Date::get_day_start($dtstart),
	    		'dtend' => $dtend,
	    	];
	    	
   	    return $component_days;  
    }
	
	function lab_time($dtstart, $dtend) {
		$user = $this->_user;
		if ($this->_component->id) {
			$eq_reserv = O('eq_reserv', ['component' => $this->_component]);
			$lab = $eq_reserv->project->lab->id ?
					$eq_reserv->project->lab :
					Q("{$user} lab")->current();
		} else {
			$lab = Q("{$user} lab")->current();
		}
		
		$equipment = $this->_component->calendar->parent;
		$records = Q("user[lab={$lab}] eq_record[dtstart>=$dtstart][dtstart<$dtend][equipment={$equipment}][reserv]");
		return (int)$this->get_real_time($records);
	}
	
	//课题组所有的预约时间
	function lab_reserv_time($dtstart, $dtend) {
		$user = $this->_user;
		if ($this->_component->id) {
			$eq_reserv = O('eq_reserv', ['component' => $this->_component]);
			$lab = $eq_reserv->project->lab->id ?
					$eq_reserv->project->lab :
					Q("{$user} lab")->current();
		} else {
			$lab = Q("{$user} lab")->current();
		}
        
		$calendar = $this->_component->calendar;
		// 获取所有的非本次预约
		if ($this->_component->id) {
			$components = Q("{$lab} user<organizer cal_component[dtstart>=$dtstart][dtstart<$dtend][calendar={$calendar}][id!={$this->_component->id}]");
		} else {
			$components = Q("{$lab} user<organizer cal_component[dtstart>=$dtstart][dtstart<$dtend][calendar={$calendar}]");
		}
		return (int)$this->get_real_reserv_time($components) + (int)($this->_end_time - $this->_start_time);


	}

	function lab_reserv_times($dtstart, $dtend) {
		$user = $this->_user;
		if ($this->_component->id) {
			$eq_reserv = O('eq_reserv', ['component' => $this->_component]);
			$lab = $eq_reserv->project->lab->id ?
					$eq_reserv->project->lab :
					Q("{$user} lab")->current();
		} else {
			$lab = Q("{$user} lab")->current();
		}
        
		$calendar = $this->_component->calendar;
        return Q("{$lab} user<organizer cal_component[dtstart>=$dtstart][dtstart<$dtend][calendar={$calendar}]")->total_count();
	}

	private function get_real_time($records) {
		$time = 0;
		if (count($records) > 0) {
			foreach ($records as $record) {
				$user = $record->user;
				$equipment = $record->equipment;
				$reserv = $record->reserv;
				if ($record->dtend == 0 || ($reserv->id && $reserv->id == $this->_component->id)) {
					continue;
				}
				$time += ($record->dtend - $record->dtstart);
			}
		}
		return $time;
	}

	//所有预约时间
	private function get_real_reserv_time($components) {
		$time = 0;
		if (count($components) > 0) {
			foreach ($components as $component) {
				$user = $component->organizer;
				$time += ($component->dtend - $component->dtstart);
			}
		}
		return $time;
	}	

    //进行语法检查
    static function check_syntax($script, &$error , $equipment = null) {

        if(!$equipment){
            //Faker创建假数据
            $equipment = Faker::equipment();
        }
        $equipment->reserv_script = $script;

        $component = Faker::cal_component(Faker::calendar($equipment, 'eq_reserv'));

        $lua_script = new EQ_Reserv_Lua($component);

        return $lua_script->_check_syntax($error);
    }


    /* 时间段内的此人的预约记录信息 */
    function components($dtstart, $dtend) {
        $user = $this->_user;
        $equipment = $this->_component->calendar->parent;
        $calendar = $this->_component->calendar;
        $current_component = $this->_component;

        $components = [];
        $num = 1;
        $total_time = 0;
        foreach (Q("{$user}<organizer cal_component([dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}])[calendar={$calendar}]") as $component) {
            $r = ($current_component->id == $component->id) ? $current_component : $component;
            $components[$num] = [
                'id' => (int) $r->id,
                'start_time' => (int) $r->dtstart,
                'end_time' => (int) $r->dtend,
            ];
            $num++;
            $total_time = $total_time + $r->dtend - $r->dtstart;
        }
        $components['total_time'] = $total_time;

        return $components;

    }

    public function __get($key){
        return $this->$key;
    }
}
