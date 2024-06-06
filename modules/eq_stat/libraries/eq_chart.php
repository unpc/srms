<?php

class EQ_Chart {

	// chart element用的颜色
	private static $_COLORS = [
		'1C9E05','FF368D','0099CC','D853CE', 'FF7400',
		'FF3366','F5B800','CCFF33','33FF66', '33CCFF',
		];

	/**
	 * 执行统计
	 *
	 * @param tid
	 * @param opt
	 * @param precision
	 * @param t1
	 * @param t2
	 *
	 * @return
	 */
	static function do_stat($tid, $opt, $precision, $t1, $t2 = NULL, $select_equipment = FALSE) {
        if ($select_equipment) {
            $collections = self::_get_collections_by_equipment($tid);
        }
        else {
		    $collections = self::_get_collections($tid);
        }

		if (!$t1) {
			$years = Eq_Stat::get_years();
			$t1 = mktime(0, 0, 0, 1, 1, next($years));
			$precision = 'all';
		}
		$durations = self::_get_durations($precision, $t1, $t2);

		$curves = [];
		foreach ($collections as $label => $object) {
			$curve = [];
			$curve['label'] = $label;
			$curve['points'] = [];

			foreach ($durations as $duration) {
				list($dtstart, $dtend) = $duration;
                //通过data_point获取对应值
                $value = Eq_Stat::data_point($opt, $object, $dtstart, $dtend);

                //获取chart显示值
                $value = ((string) (V("eq_stat/chart_value/$opt", ['value'=>$value])));

				$point = [
					'value'=> (double) $value,
					'label'=> self::_range_label($dtstart, $dtend),
					];
				$curve['points'][] = $point;
			}
			$curves[] = $curve;
		}

		return $curves;
	}

	/**
	 * 获取某tag下的统计元素
	 *
	 * @param tid
	 *
	 * @return array，元素名称=>元素对象
	 */
	private static function _get_collections($tid) {
		$tag = O('tag', $tid);

		if (!$tag->name) {
			return [];
		}

		$result = [];

		$children = $tag->children(); /* branch */
		
		if ($tag->root->id==0) {
			$name = I18N::T('eq_stat', '其他');
			$result[$name] = $tag;
		}

		if (!count($children)) {	
			$children = Q("$tag equipment");
			if (count($children)) $result = [];
		}
		

		if (count($children)) foreach ($children as $child) {
			if ($child->name == $name ) {
				$child_name = $child->name . ' (1)';
				$result[$child_name] = $child;
				continue;
			}
			$result[$child->name] = $child;
		}
		return $result;
	}

    /**
     * 获取单一仪器
     * @param eid
     * @result array, 仪器名称=>仪器对象
     */
    private static function _get_collections_by_equipment($eid) {
        $equipment = O('equipment', $eid);
        $result = [];
        $result[$equipment->name] = $equipment;
        return $result;
    }

	/**
	 * 获取统计时段
	 *
	 * @param precision
	 * @param dtfrom
	 * @param dtto
	 *
	 * @return array，统计时段，每个元素由dtstart和dtend的array组成
	 */
	private static function _get_durations($precision, $dtfrom, $dtto) {

		/* 标准化dtfrom和dtto */
		$dtfrom = self::_first_day($precision, $dtfrom);
		if ($dtto) {
			$dtto = self::_first_day($precision, $dtto);
		}
		else {
			$dtto = $dtfrom;
		}
		$dtto = self::_add_date_by_precision($dtto, $precision, 1);

		/* 获取统计时段 */
		$durations = [];
		while ($dtfrom < $dtto) {
			$dtstart = $dtfrom;
			$dtend = self::_add_date_by_precision($dtstart, $precision, 1);
			$durations[] = [$dtfrom, $dtend];

			$dtfrom = $dtend;
		}

		return $durations;
	}

	/**
	 * 获取某timestamp在指定精度下的起始timestamp
	 *
	 * @param precision
	 * @param date
	 *
	 * @return
	 */
	private static function _first_day($precision, $date) {
		if ($precision == 'm') {
			return mktime(0, 0, 0, date('m', $date), 1, date('Y', $date));
		}
		else if ($precision == 'y') {
			return mktime(0, 0, 0, 1, 1, date('Y', $date));
		}
		else {
			return $date;
		}
	}

	/**
	 * 按指定精度增加日期
	 *
	 * @param date
	 * @param precision
	 * @param n
	 *
	 * @return
	 */
	private static function _add_date_by_precision($date, $precision, $n) {
		if ($precision == 'all') {
			$years = Eq_Stat::get_years();
			return mktime(0, 0, 0, 1, 1, end($years) + 1) - 1;
		}
		$increment = ['h'=>0, 'm'=>0, 's'=>0, 'm'=>0, 'd'=>0, 'y'=>0];
		$increment[$precision] = $n;
		return mktime(
			date('H', $date)+$increment['h'],
			date('i', $date)+$increment['i'],
			date('s', $date)+$increment['s'],
			date('m', $date)+$increment['m'],
			date('d', $date)+$increment['d'],
			date('Y', $date)+$increment['y']
			);
	}


	/**
	 * 通过时间生成label
	 *
	 * @param dtstart
	 * @param dtend
	 *
	 * @return
	 */
	private static function _range_label($dtstart, $dtend) {
		$d1 = getdate($dtstart);
		$d2 = getdate($dtend);
		
		// if ($d1['mon'] != $d2['mon']) return $d1['mon'];
		if ($d1['mon'] != $d2['mon']) return date('y/m', $dtstart);
		return $d1['year'];
	}

	/**
	 * 将$curves数组转换为OpenFlashChart的$chart对象
	 *
	 * @param curves
	 * @param type
	 *
	 * @return
	 */
	static function parse_curves_to_chart($curves, $type){
		//Core::load(THIRD_BASE, 'ofc/open-flash-chart', 'eq_stat');
		switch ($type) {
		case 'pie':
			$chart = self::_parse_curves_to_pie_chart($curves);
			break;
		case 'bar':
			$chart = self::_parse_curves_to_bar_chart($curves);
			break;
		case 'line':
			$chart = self::_parse_curves_to_line_chart($curves);
			break;
		default:
			return;
		}


		return (array)$chart;
	}

	/**
	 * 转换为饼图
	 *
	 * @param curves
	 *
	 * @return
	 */
	private static function _parse_curves_to_pie_chart($curves) {
		$chart['xml'] = (string) V('eq_stat:fusioncharts/pie3d', ['curves'=>$curves, 'colors'=>self::$_COLORS]);
		$chart['swf'] = '!eq_stat/Pie3D.swf';
		return $chart;
	}

	/**
	 * 转换为柱状图
	 *
	 * @param curves
	 *
	 * @return
	 */
	private static function _parse_curves_to_bar_chart($curves) {
		$chart['swf'] = '!eq_stat/Column3D.swf';
		if (count((array)$curves[0]['points']) > 1) {
			$chart['swf'] = '!eq_stat/MSColumn3D.swf';
			$chart['xml'] = (string) V('eq_stat:fusioncharts/mscolumn', ['columns'=>$curves, 'colors'=>self::$_COLORS]);
		}
		else {
			$chart['xml'] = (string) V('eq_stat:fusioncharts/column', ['columns'=>$curves, 'colors'=>self::$_COLORS]);
		}
		return $chart;
	}

	/**
	 * 转换为线图
	 *
	 * @param curves
	 *
	 * @return
	 */
	private static function _parse_curves_to_line_chart($curves) {
		$chart['swf'] = '!eq_stat/Line.swf';
		if (count((array)$curves[0]['points']) > 1) {
			$chart['swf'] = '!eq_stat/MSLine.swf';
			$chart['xml'] = (string) V('eq_stat:fusioncharts/msline', ['lines'=>$curves, 'colors'=>self::$_COLORS]);
		}
		else {
			$chart['xml'] = (string) V('eq_stat:fusioncharts/line', ['lines'=>$curves, 'colors'=>self::$_COLORS]);
		}
		return $chart;
		
	}

	/**
	 * 通过$labels数组生成x轴
	 *
	 * @param labels
	 *
	 * @return
	 *
	private static function _make_x_axis($labels)
	{
		$x = new x_axis();
		$x->set_labels_from_array($labels);
		$x->set_colour('#ffffff');

		$labels = $x->labels;
		$labels->set_size(13);
		$labels->set_colour('#ffffff');
		
		return $x;
	}
	
	/**
	 * 通过$max_value生成y轴
	 *
	 * @param max_value
	 * @param count 默认平分为5个点
	 *
	 * @return
	 *
	private static function _make_y_axis($max_value, $count = 5)
	{
		$y = new y_axis();
		$step = floor($max_value/$count);
		$y->set_range(0, $max_value, $step);
		$y->set_colour('#ffffff');

		$y->set_label_text('#val#');
		$labels = $y->labels;
		$labels->set_size(13);
		$labels->set_colour('#ffffff');

		return $y;
	}
	*/
}
