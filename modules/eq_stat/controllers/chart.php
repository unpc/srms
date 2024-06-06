<?php
class Chart_Controller extends Base_Controller {
	function index() {
		$me = L('ME');
		if (!$me->access('查看统计图表'))  URI::redirect('error/401');

		// get opts
        $opts = EQ_Stat::get_opts();

		// get category root
		$cat_root = Tag_Model::root('equipment');

		// get group root
		$group_root = Tag_Model::root('group');

		// get years
		$years = Eq_Stat::get_years();

		// get months
        $months = range(0, 12, 1);
		$months[0] = '--';

		if (Input::form('submit')) {
			// post
			try {
				$form = Form::filter(Input::form());
				// validation(TODO)
				if (!$form->no_error) {
					throw new Exception;
				}

				// tag
				$tag_type = $form['tag'];

				if ($tag_type == 'cat') {
					$cat = $tag = O('tag_equipment', $form['cat_id']);
				}
				else if ($tag_type == 'group') {
					$group = $tag = O('tag_group', $form['group_id']);
				}
				else if ($tag_type == 'equipment') {
                    $equipment = O('equipment', $form['equipment_id']);
				}
                else {
                    throw new Exception;
                }

				// 比较数据=> 台数/总值
				$opt = $form['opt'];
				if (!array_key_exists($opt, $opts)) {
					throw new Exception;
				}

				$precision = 'y';

				// time
				$time = $form['time'];

				// t1
				if ($form['y1']) {
					$y1 = (int) Date::format($form['y1'], 'Y');

					if ($form['m1']) {

						$precision = 'm';
						$m1 = $form['m1'];
					}

					$t1 = mktime(0, 0, 0, $m1 ?: 1, 1, $y1);
				}

				if ($time == 'period') {
					$y2 = (int)Date::format($form['y2'], 'Y') ?: $y1;

					if ($form['m2']) {
						$precision = 'm';
						$m2 = $form['m2'];
						$t2 = mktime(0, 0, 0, $m2, 1, $y2);
					}
					else {
						$t2 = mktime(0, 0, 0, 1, 1, $y2);
					}

				}
				else {
					$t2 = $t1;
				}

				if ($t2 < $t1) {
					// swap t2, t1
					list($t2, $t1) = [$t1, $t2];
				}

				$type = $form['type'];
				if ($time == 'point' && ($type == 'line' || !$type)) {
					// 按时点统计默认为饼图
					$type = 'pie';
				}
				else if ($time == 'period' && ($type == 'pie' || !$type)) {
					// 按时段统计默认为柱状图
					$type = 'bar';
				}

				/* xiaopei.li@2011.02.24 */
				if ($time == 'period' && !($form['m1'] || $form['m2']) && ($y1 == $y2)){
					$precision = 'm';
					$t2 = mktime(0, 0, 0, 1, 1, $y1+1) - 1;
				}

				// stat and parse result to chart

                if ($equipment->id) {
                    $curves = EQ_Chart::do_stat($equipment->id, $opt, $precision, $t1, $t2, TRUE);
                }
                else {
                    $curves = EQ_Chart::do_stat($tag->id, $opt, $precision, $t1, $t2, FALSE);
                }
				$chart = EQ_Chart::parse_curves_to_chart($curves, $type);
			}
			catch (Exception $e) {
			}
		}
		else {
			// show an init statistic instead of blank flash

			$tag_type = 'cat';
			$tag = $cat_root;
			$opt = 'equipments_count';
			$time = 'point';
			$precision = 'y';
			
			$day_info = getdate( Date::time() );
			$y1 = $day_info['year'];
			$t1 = $t2 = mktime(0, 0, 0, 1, 1, $y1);
			$type = 'pie';
			// stat and parse result to chart
			$curves = EQ_Chart::do_stat($tag->id, $opt, $precision, $t1, $t2);
			$chart = EQ_Chart::parse_curves_to_chart($curves, $type);
		}


		$primary_tabs = $this->layout->body->primary_tabs->select('index');
		$primary_tabs->content =  V('chart', [
				  'tag'=>$tag_type,
				  'cat'=>$cat,
				  'group'=>$group,
				  'cat_root'=>$cat_root,
				  'group_root'=>$group_root,
				  'opt'=>$opt,
				  'opts'=>$opts,
				  'time'=>$time,
				  'y1'=>$y1,
				  'm1'=>$m1,
				  'y2'=>$y2,
				  'm2'=>$m2,
				  'years'=>$years,
				  'months'=>$months,
				  'type'=>$type,
				  'chart'=>$chart,
                  'form'=>$form,
                  'equipment'=>$equipment
			  ]);

		$this->add_js('eq_stat:swfobject eq_stat:highcharts eq_stat:fusioncharts');
		$this->add_css('eq_stat:common');
	}

	function front() {

		// get opts
        $opts = EQ_Stat::get_opts();

		// get category root
		$cat_root = Tag_Model::root('equipment');

		// get group root
		$group_root = Tag_Model::root('group');

		// get years
		$years = Eq_Stat::get_years();

		// get months
        $months = range(0, 12, 1);
		$months[0] = '--';

		if (Input::form('submit')) {
			// post
			try {
				$form = Form::filter(Input::form());
				// validation(TODO)
				if (!$form->no_error) {
					throw new Exception;
				}

				// tag
				$tag_type = $form['tag'];

				if ($tag_type == 'cat') {
					$cat = $tag = O('tag_equipment', $form['cat_id']);
				}
				else if ($tag_type == 'group') {
					$group = $tag = O('tag_group', $form['group_id']);
				}
				else if ($tag_type == 'equipment') {
                    $equipment = O('equipment', $form['equipment_id']);
				}
                else {
                    throw new Exception;
                }

				// 比较数据=> 台数/总值
				$opt = $form['opt'];
				if (!array_key_exists($opt, $opts)) {
					throw new Exception;
				}

				$precision = 'y';

				// time
				$time = $form['time'];

				// t1
				if ($form['y1']) {
					$y1 = (int)Date::format($form['y1'], 'Y');

					if ($form['m1']) {

						$precision = 'm';
						$m1 = $form['m1'];
					}

					$t1 = mktime(0, 0, 0, $m1 ?: 1, 1, $y1);
				}

				if ($time == 'period') {
					$y2 = (int)Date::format($form['y2'], 'Y') ?: $y1;

					if ($form['m2']) {
						$precision = 'm';
						$m2 = $form['m2'];
						$t2 = mktime(0, 0, 0, $m2, 1, $y2);
					}
					else {
						$t2 = mktime(0, 0, 0, 1, 1, $y2);
					}

				}
				else {
					$t2 = $t1;
				}

				if ($t2 < $t1) {
					// swap t2, t1
					list($t2, $t1) = [$t1, $t2];
				}

				$type = $form['type'];
				if ($time == 'point' && ($type == 'line' || !$type)) {
					// 按时点统计默认为饼图
					$type = 'pie';
				}
				else if ($time == 'period' && ($type == 'pie' || !$type)) {
					// 按时段统计默认为柱状图
					$type = 'bar';
				}

				/* xiaopei.li@2011.02.24 */
				if ($time == 'period' && !($form['m1'] || $form['m2']) && ($y1 == $y2)){
					$precision = 'm';
					$t2 = mktime(0, 0, 0, 1, 1, $y1+1) - 1;
				}

				// stat and parse result to chart

                if ($equipment->id) {
                    $curves = EQ_Chart::do_stat($equipment->id, $opt, $precision, $t1, $t2, TRUE);
                }
                else {
                    $curves = EQ_Chart::do_stat($tag->id, $opt, $precision, $t1, $t2, FALSE);
                }
				$chart = EQ_Chart::parse_curves_to_chart($curves, $type);
			}
			catch (Exception $e) {
			}
		}
		else {
			// show an init statistic instead of blank flash

			$tag_type = 'cat';
			$tag = $cat_root;
			$opt = 'equipments_count';
			$time = 'point';
			$precision = 'y';
			$y1 = 0;
			$t1 = 0;
			$type = 'pie';
			// stat and parse result to chart
			$curves = EQ_Chart::do_stat($tag->id, $opt, $precision, $t1, $t2);
			$chart = EQ_Chart::parse_curves_to_chart($curves, $type);
		}


		$this->layout = V('application:layout_plain');
		$this->layout->body =  V('chart', [
				  'tag'=>$tag_type,
				  'cat'=>$cat,
				  'group'=>$group,
				  'cat_root'=>$cat_root,
				  'group_root'=>$group_root,
				  'opt'=>$opt,
				  'opts'=>$opts,
				  'time'=>$time,
				  'y1'=>$y1,
				  'm1'=>$m1,
				  'y2'=>$y2,
				  'm2'=>$m2,
				  'years'=>$years,
				  'months'=>$months,
				  'type'=>$type,
				  'chart'=>$chart,
                  'form'=>$form,
                  'equipment'=>$equipment
			  ]);

		$this->add_js('eq_stat:swfobject eq_stat:highcharts eq_stat:fusioncharts');
		$this->add_css('eq_stat:common');
	}

}
