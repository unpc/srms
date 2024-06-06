<?php

class Date extends _Date {
	static function get_work_days($start_date,$end_date,$is_workday = true){
		if (strtotime($start_date) > strtotime($end_date)) list($start_date, $end_date) = [$end_date, $start_date];
		$start_reduce = $end_add = 0;
		$start_N = date('N',strtotime($start_date));
		$start_reduce = ($start_N == 7) ? 1 : 0;
		$end_N = date('N',strtotime($end_date));
		in_array($end_N,[6,7]) && $end_add = ($end_N == 7) ? 2 : 1;
		$alldays = abs(strtotime($end_date) - strtotime($start_date))/86400 + 1;
		$weekend_days = floor(($alldays + $start_N - 1 - $end_N) / 7) * 2 - $start_reduce + $end_add;

		if ($is_workday){
			$workday_days = $alldays - $weekend_days;
			return $workday_days;
		}
		return $weekend_days;
	}

    static function format($time=NULL, $format=NULL) {
        if (!$time) $time = time();

        $time = (int) $time;

        $date = getdate($time);

        if (!$format) $format = Date::default_format();

		$format = T($format);
		if (Config::get('debug.i18n_ipe')) {
			$format = preg_replace('/\{\[.+?\]\}/', '', $format);
		}

        return date($format, $time);
    }

    static function fuzzy_date_format($dfrom=0, $dto=0, $format=NULL) {
    	$now = time();
	    if ( $dfrom>0 ) {
	    	$now = $dfrom;
	    	$dfrom = $format ?  Date::format($dfrom, $format) : Date::format($dfrom, 'Y/m/d');
	    }
	    else {
	    	$dfrom = T('最初');
	    }

	    if ( $dto>0 ) {
            if ( $format ) {
                $dto = Date::format($dto, $format);
            }
            else{
                $dn = getdate($now);
                $dt = getdate($dto);
		    	if ( $dn['year']==$dt['year'] ) {
			        $dto = Date::format($dto, 'm/d');
			    }
			    else {
			        $dto = Date::format($dto, 'Y/m/d');
			     }
            }
	    }
	    else {
	    	$dto = T('现在');

	    }
	    return $dfrom . ' - ' . $dto;

    }

    //得到两个时区的时间差 Date::get_timezone_offset('UTC', 'America/Los_Angeles')
    static function get_timezone_offset($remote_tz, $origin_tz = null) {
        if($origin_tz === null) {
            if(!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime('now', $origin_dtz);
        $remote_dt = new DateTime('now', $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
	}

	//根据给定的时间戳, 返回当天23:59:59结束的时间戳
	static function get_day_end($timestamp = 0) {

		if (!$timestamp) $timestamp = Date::time();
		// if (!$timestamp) return ;

		$date_info = getdate($timestamp);

		return mktime(23, 59, 59, $date_info['mon'], $date_info['mday'],$date_info['year']);
	}

	static function get_day_start($timestamp = 0) {

		if (!$timestamp) $timestamp = Date::time();
		// if (!$timestamp) return ;

		$date_info = getdate($timestamp);

		return mktime(0, 0, 0, $date_info['mon'], $date_info['mday'],$date_info['year']);
	}

    static function get_week_start($timestamp = 0) {
        $time = $timestamp ?: Date::time();
        // 此处应考虑使用默认的Date系列函数进行运算
        $year = date('Y', $time);
        $month = date('m', $time);
        $week = date('d', $time) - date('w', $time);

        return mktime(0, 0, 0, $month, $week, $year);
    }

    static function get_week_end($timestamp = 0) {
        $time = $timestamp ?: Date::time();
        //此处应考虑使用默认的Date系列函数进行运算
        $year = date('Y', $time);
        $month = date('m', $time);
        $week = date('d', $time) - date('w', $time);

        return mktime(0, 0, 0, $month, $week + 7, $year) - 1;

    }

    static function get_minute_start($timestamp = 0) {

        if (!$timestamp) $timestamp = Date::time();

        $date_info = getdate($timestamp);

        return mktime($date_info['hours'], $date_info['minutes'], 0, $date_info['mon'], $date_info['mday'],$date_info['year']);
    }

    static function get_minute_end($timestamp = 0) {

        if (!$timestamp) $timestamp = Date::time();

        $date_info = getdate($timestamp);

        return mktime($date_info['hours'], $date_info['minutes'], 59, $date_info['mon'], $date_info['mday'],$date_info['year']);
    }


    static function fuzzy_range($dfrom, $dto, $detail=FALSE){

        if ($dfrom) $sfrom = Date::fuzzy($dfrom, $detail);
        else $sfrom = T('最初');

        if ($dto) $sto = Date::fuzzy($dto, $detail);
        else $sto = T('现在');

        return $sfrom.' - '.$sto;
    }

    static function range($dfrom, $dto, $from_format=NULL){

        if ($dfrom) $sfrom = Date::format($dfrom, $from_format);
        else $sfrom = T('最初');

        if ($dto) $sto = Date::relative($dto, $dfrom);
        else $sto = T('现在');

        return $sfrom.' - '.$sto;
    }

    static function get_month_start($timestamp = NULL) {
        if (!$timestamp) $timestamp = Date::time();

        $date_info = getdate($timestamp);

        return mktime(0, 0, 0, $date_info['mon'], 1, $date_info['year']);
    }

    static function get_month_end($timestamp = NULL) {
        if (!$timestamp) $timestamp = Date::time();

        $date_info = getdate($timestamp);

        return mktime(0, 0, 0, $date_info['mon'] + 1, 1, $date_info['year']) - 1;
    }

    static function get_year_start($timestamp = NULL) {
        if (!$timestamp) $timestamp = Date::time();

        $date_info = getdate($timestamp);

        return mktime(0, 0, 0, 1, 1, $date_info['year']);
    }

    static function get_year_end($timestamp = NULL) {
        if (!$timestamp) $timestamp = Date::time();

        $date_info = getdate($timestamp);

        return mktime(0, 0, 0, 1, 1, $date_info['year'] + 1) - 1;
    }

    static function prev_time($time = NULL, $unit = 1, $format = 'd') {
        if (!$time) $time = Date::time();
        $opt = getdate($time);
        $mday = $opt['mday'];
        $month = $opt['mon'];
        $year = $opt['year'];
        switch ($format) {
            case 'm':
                $month -= $unit;
                break;
            case 'y':
                $year -= $unit;
                break;
            default:
                $mday -= $unit;
                break;
        }
        return mktime($opt['hours'], $opt['minutes'], $opt['seconds'], $month, $mday, $year);
    }

    static function next_time($time = NULL, $unit = 1, $format = 'd') {
        if (!$time) $time = Date::time();
        $opt = getdate($time);
        $mday = $opt['mday'];
        $month = $opt['mon'];
        $year = $opt['year'];
        switch ($format) {
            case 'm':
                $month += $unit;
                break;
            case 'y':
                $year += $unit;
                break;
            default:
                $mday += $unit;
                break;
        }
        return mktime($opt['hours'], $opt['minutes'], $opt['seconds'], $month, $mday, $year);
    }
}
