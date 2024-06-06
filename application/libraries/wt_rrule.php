<?php

class WT_RRule {
	const RRULE_NONE = 0;        //无
	const RRULE_DAILY = 1;       //按日
	const RRULE_WEEKLY = 2;      //按周
	const RRULE_MONTHLY = 3;     //按月
	const RRULE_YEARLY = 4;      //按年
		
	const RRULE_SUNDAY = 0;
	const RRULE_MONDAY = 1;
	const RRULE_TUESDAY = 2;
	const RRULE_WEDNESDAY = 3;
	const RRULE_THURSDAY = 4;
	const RRULE_FRIDAY = 5;
	const RRULE_SATURDAY = 6;
	
	const RRULE_DAY = -1;
	const RRULE_WEEKDAY = -2;
	const RRULE_WEEKEND_DAY = -3;
	
	//定义标志位
	const MASK_YEAR = 0x01;
	const MASK_MONTH = 0x02;
	const MASK_DAY = 0x04;
	const MASK_HOUR = 0x08;
	const MASK_MINUTE = 0x10;
	
	//定义时间选择类型
	const TYPE_NONE = 0;
	const TYPE_DAY = 'day';
	const TYPE_WEEK = 'week';
		
	static $rtype = [
			self::RRULE_DAILY => '日',
			self::RRULE_WEEKDAY => '工作日',
			self::RRULE_WEEKEND_DAY => '周末',
			self::RRULE_WEEKLY => '周',
			self::RRULE_MONTHLY => '月',
			self::RRULE_YEARLY => '年',
		];

	/*
	  NO.TASK#262 (xiaopei.li@2010.11.20)
	*/
	static $cal_rtype = [
		 self::RRULE_DAILY => '每日',
		 self::RRULE_WEEKLY => '每周',
		 ];
	
	static $week = [
			self::RRULE_SUNDAY => '星期日',
			self::RRULE_MONDAY => '星期一',
			self::RRULE_TUESDAY => '星期二',
			self::RRULE_WEDNESDAY => '星期三',
			self::RRULE_THURSDAY => '星期四',
			self::RRULE_FRIDAY => '星期五',
			self::RRULE_SATURDAY => '星期六',
		];

	static $weekend = [
			self::RRULE_SUNDAY => '星期日',
			self::RRULE_SATURDAY => '星期六',
	];

	static $weekday = [
			self::RRULE_MONDAY => '星期一',
			self::RRULE_TUESDAY => '星期二',
			self::RRULE_WEDNESDAY => '星期三',
			self::RRULE_THURSDAY => '星期四',
			self::RRULE_FRIDAY => '星期五',
	];

	static $monthly_types = [
			self::TYPE_NONE => '无',
			self::TYPE_DAY => '某几天',
			self::TYPE_WEEK => '某几周'
		];
	
	static $yearly_types = [
			self::TYPE_NONE => '无',
			self::TYPE_DAY => '某几月',
			self::TYPE_WEEK => '某几月的某几周'
		];
	
	//分析时间
	static function match_time_rule($time, $rule) {
		//判断起止时间
		$dtfrom = $rule['dtfrom'];
		$dtto = $rule['dtto'];
		$dtstart = $rule['dtstart'];
		$dtend = $rule['dtend'];
		$rrule = $rule['rrule'];
		$rnum = $rule['rnum'] ?: 1;
		if ((isset($dtfrom) && $time <= $dtfrom) || (isset($dtto) && $time >= $dtto)) {
			return FALSE;
		}
		
		switch($rule['rtype']) {
			//按照日计算
			case self::RRULE_DAILY:
				//判断间隔时间
				if (self::match_daily($time, $dtfrom, $rnum, $mask)) {
					//判断有效时间(dtstart与dtend)
					if (self::match_time($time, $dtstart, $dtend, $mask)) {
						return TRUE;
					}
				}
				return FALSE;
			//按照周计算
			case self::RRULE_WEEKLY:
				//判断间隔时间
				if (self::match_weekly($time, $dtfrom, $rnum, $mask)) {
					//判断一周中的哪天
					if (self::match_weekly_rrule($time, $rrule, $mask)) {
						//判断有效时间(dtstart与dtend)
						if (self::match_time($time, $dtstart, $dtend, $mask)) {
							return TRUE;
						}
					}
				}
				return FALSE;
			//按月计算
			case self::RRULE_MONTHLY:
				//判断时间间隔
				if (self::match_monthly($time, $dtfrom, $rnum, $mask)) {
					//判断一月中的哪天
					if (self::match_monthly_rrule($time, $rrule, $mask)) {
						//判断有效时间(dtstart与dtend)
						if (self::match_time($time, $dtstart, $dtend, $mask)) {
							return TRUE;
						}
					}
				}
				return FALSE;
			//按年计算
			case self::RRULE_YEARLY:
				//判断时间间隔
				if (self::match_yearly($time, $dtfrom, $rnum, $mask)) {
					//判断一年中的哪天
					if (self::match_yearly_rrule($time, $rrule, $mask)) {
						//判断有效时间(dtstart与dtend)
						if (self::match_time($time, $dtstart, $dtend, $mask)) {
							return TRUE;
						}
					}
				}
				return FALSE;
			//无
			default:
                if ($time <= $dtend && $time >= $dtstart) return TRUE;
                else return FALSE;
		}
		
	}
	
	//判断daily
	static private function match_daily($time, $dtfrom, $rnum, &$mask) {
		$mask |= self::MASK_YEAR | self::MASK_MONTH | self::MASK_DAY;
		$time_arr = getdate($time);
		$dtfrom = getdate($dtfrom);
		$new_time = mktime(0, 0, 0, $time_arr['mon'], $time_arr['mday'], $time_arr['year']);
		$new_dtfrom = mktime(0, 0, 0, $dtfrom['mon'], $dtfrom['mday'], $dtfrom['year']);
		if (($new_time - $new_dtfrom) / 86400 % $rnum) {
			return FALSE;
		}
		return TRUE;			
	}
	
	//判断weekly
	static private function match_weekly($time, $dtfrom, $rnum, &$mask) {
		$mask |= self::MASK_YEAR | self::MASK_MONTH | self::MASK_DAY;
		$time_arr = getdate($time);
		$dtfrom = getdate($dtfrom);
		$new_time = mktime(0, 0, 0, $time_arr['mon'], $time_arr['mday'], $time_arr['year']);
		$new_dtfrom = mktime(0, 0, 0, $dtfrom['mon'], $dtfrom['mday'], $dtfrom['year']);
		if (($new_time - $new_dtfrom) / (86400*7) % $rnum) {
			return FALSE;
		}
		return TRUE;			
	}
	
	static private function match_weekly_rrule($time, $rrule, &$mask) {
		if (!$rrule) return TRUE;
		$time_arr = getdate($time);
		$wday = $time_arr['wday'];
		if (in_array($wday, $rrule[0])) {
			return TRUE;
		}
		if (in_array(self::RRULE_DAY, $rrule[0])) {
			return TRUE;
		}
		if (in_array(self::RRULE_WEEKDAY, $rrule[0])) {
			if ($wday >= self::RRULE_MONDAY && $wday <= self::RRULE_FRIDAY) {
				return TRUE;
			}
		}
		if (in_array(self::RRULE_WEEKEND_DAY, $rrule[0])) {
			if ($wday == self::RRULE_SUNDAY || $way == self::RRULE_SATURDAY) {
				return TRUE;
			}
		}
		return FALSE;
	}
	//判断monthly
	static private function match_monthly($time, $dtfrom, $rnum, &$mask) {
        $mask |= self::MASK_YEAR | self::MASK_MONTH;
        $time_arr = getdate($time);
        $dtfrom_arr = getdate($dtfrom);

        //跨年, 需增加12进行数值矫正
        //例如上一年11月起始, 次数为5
        //11 12 1 2 3  为一次
        //4月匹配
        //(4 + 12 - 11) % 5 匹配
        //不跨年
        //1月起始, 次数为5
        //1 2 3 4 5 为一次
        //6月匹配
        // (6 - 1) % 5 匹配

        if ($time_arr['mon'] < $dtfrom_arr['mon']) $time_arr['mon'] += 12;

        if (floor(($time_arr['mon'] - $dtfrom_arr['mon'])) % $rnum) {
            return FALSE;
        }
		return TRUE;
	}
	
	static private function match_monthly_rrule($time, $rrule, &$mask) {
		if (!$rrule) return TRUE;
		$mask |= self::MASK_DAY;
		$time_arr = getdate($time);
		//$rrule有两个参数,代表哪周的哪天

		if (count($rrule) == 2) {
			// rrule  =>>  array(array(), array())
			//用几号除以7取整即为第几个星期
			$mweek = ceil($time_arr['mday'] / 7);
			$wday = $time_arr['wday'];
			if (in_array($mweek, $rrule[0])) {
				if (in_array($wday, $rrule[1])) {
					return TRUE;
				}
			}
		}
		//$rrule有一个参数，代表哪天
		else {
			$mday = $time_arr['mday'];
			if (in_array($mday, $rrule[0])) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	//判断yearly
	static private function match_yearly($time, $dtfrom, $rnum, &$mask) {
		$mask |= self::MASK_YEAR;
		$time_arr = getdate($time);
		$dtfrom = getdate($dtfrom);
		$new_time = mktime(0, 0, 0, 0, 0, $time_arr['year']);
		$new_dtfrom = mktime(0, 0, 0, 0, 0, $dtfrom['year']);


        if (floor($time_arr['year'] - $dtfrom['year']) % $rnum) {
            return FALSE;
        }

		return TRUE;
	}
	
	static private function match_yearly_rrule($time, $rrule, &$mask) {
		if (!$rrule) return TRUE;
		$mask |= self::MASK_MONTH | self::MASK_DAY;
		$time_arr = getdate($time);
		switch (count($rrule)) {
			case 1 :
				$yday = $time_arr['yday'];
				if (in_array($yday, $rrule[0])) {
					return TRUE;
				}
				break;
			case 2 :
				$yweek = date('W', $time);
				$wday = $time_arr['wday'];
				if (in_array($yweek, $rrule[0])) {
					if (in_array($wday, $rrule[1])) {
						return TRUE;
					}
				}
				break;
				
			case 3 :
				$mon = $time_arr['mon'];
				$mweek = ceil($time_arr['mday'] / 7);
				$mday = $time_arr['mday'];
				$wday = $time_arr['wday'];
				//第几月的第几天
				if (in_array('-1', $rrule[1])) {
					if (in_array($mon, $rrule[0])) {
						if (in_array($mday, $rrule[2])) {
							return TRUE;
						}
					}
				}
				//第几月的第几周的第几天
				else {
					if (in_array($mon, $rrule[0])) {
						if (in_array($mweek, $rrule[1])) {
							if (in_array($wday, $rrule[2])) {
								return TRUE;
							}
						}
					}
				}
				break;
			default:
			return TRUE;
		}
		return FALSE;
	}
	
	//判断有效时间 (dtstart - dtend)
	static private function match_time($time, $dtstart, $dtend, $mask) {

		$dtstart_arr = getdate($dtstart);
		$dtend_arr = getdate($dtend);
		$time_arr = getdate($time);
		
		if($mask & self::MASK_YEAR) {
			$dtstart_arr['year'] = $time_arr['year'];
			$dtend_arr['year'] = $time_arr['year'];
		}
		
		if($mask & self::MASK_MONTH) {
			$dtstart_arr['mon'] = $time_arr['mon'];
			$dtend_arr['mon'] = $time_arr['mon'];
		}
		
		if($mask & self::MASK_DAY) {
			$dtstart_arr['mday'] = $time_arr['mday'];
			$dtend_arr['mday'] = $time_arr['mday'];
		}
		
		if($mask & self::MASK_HOUR) {
			$dtstart_arr['hours'] = $time_arr['hours'];
			$dtend_arr['hours'] = $time_arr['hours'];
		}
		
		if($mask & self::MASK_MINUTE) {
			$dtstart_arr['minutes'] = $time_arr['minutes'];
			$dtend_arr['minutes'] = $time_arr['minutes'];
		}
		
		$new_dtstart = mktime($dtstart_arr['hours'], $dtstart_arr['minutes'], 0, $dtstart_arr['mon'], $dtstart_arr['mday'], $dtstart_arr['year']);
		$new_dtend = mktime($dtend_arr['hours'], $dtend_arr['minutes'], 0, $dtend_arr['mon'], $dtend_arr['mday'], $dtend_arr['year']);
		$new_time = mktime($time_arr['hours'], $time_arr['minutes'], 0, $time_arr['mon'], $time_arr['mday'], $time_arr['year']);
		
		if ($new_time >= $new_dtstart && $new_time <= $new_dtend) {
			return TRUE;
		}
		return FALSE;
	}

}
