<?php

class Perf {
	
	/*
		查找出有效时间内，但未选人的绩效评估(perf)，并进行选定用户进行评分
	*/
	static function can_grade_perfs() {
		$now = Date::time();
		$perfs = Q("eq_perf[rating_from][rating_from<={$now}][rating_to>{$now}][can_grade=0]");
		foreach($perfs as $perf) {
			$tag = $perf->collection;
			$selector = $tag->id ? "{$tag} equipment" : "equipment";
			$equipments = Q($selector);
			$start = $perf->dfrom;
			$end = $perf->dto;
			$perf->can_grade = 1;
			$perf->save();
		}
	}
	
	/*
		查找出过了有效期的绩效评估，将其状态设定为锁定
	*/
	
	static function lock_overdue_perfs() {
		$now = Date::time();
		$perfs = Q("eq_perf[rating_to][rating_to<={$now}]");
		foreach ($perfs as $perf) {
			$perf->can_grade = 2;
			$perf->save();
		}
	}
	
	/*
		将评分标准选项格式化 组合成1个数组
	*/
	static function filter_opts($options) {
		$real_opts = [];
		foreach ($options as $key => $opts) {
			if (!is_array($opts)) {
				$real_opts[$key] = $opts;
			}
			else {
				unset($opts['name']);
				$real_opts += $opts;
			}
		}
		
		return (array)$real_opts;
	}
	
	/*
		通过equipment 和 perf 获取该仪器的总分数
	*/
	static function perf_score($equipment, $perf) {
		if (!$equipment->id || !$perf->id) {
			return;
		}
		$formula = $perf->formula;
		$dtstart = $perf->dfrom;
		$dtend = $perf->dto;
		$scores = [];
		$total_score = 0;

        $times = Config::get('eq_stat.formula.time'); 
		foreach ($formula as $key => $value) {
			$name = "stat.equipment.".$key;
			if ($key == 'user_score') {
				$u_score = (array)self::owner_score($equipment, $perf);
				$scores[$key] = $u_score['average'] * (int)$value;
			}
			else {

                $data = Event::trigger($name, $equipment, $dtstart, $dtend);

                if (in_array($data, $times)) $data = round($data / 3600, 2);

				$scores[$key] = $data * $value;
			}	
			$total_score += $scores[$key];
		}
		$scores['total'] = $total_score;
		return $scores;		
	}
	
	/*
		通过equipment 和 perf 来获取该仪器的用户评分
	*/
	static function owner_score($equipment, $perf) {
		if (!$equipment->id || !$perf->id) {
			return;
		}
		$ratings = Q("eq_perf_rating[perf={$perf}][equipment={$equipment}]");
		$scores = [];
		$scores['num'] = count($ratings);
		$scores['score'] = $ratings->sum(average);
		$scores['average'] = $scores['num'] ? round($scores['score']/$scores['num']) : 0;
		return $scores;
	}
}
