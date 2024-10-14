<?php

class Course {
		
	static function course_ACL($e, $me, $perm, $course, $options) {
		switch($perm) {
			case '列表':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '添加':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '删除':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '修改':
				$e->return_value = TRUE;
				return FALSE;
		}		
	}    

	public static function refresh_history_form($form)
    {
        if ($_SESSION['calendar_index_form']) {
            $form['st'] = $_SESSION['calendar_index_form']['st'] ?: $form['st'];
            $form['sort_asc'] = $_SESSION['calendar_index_form']['sort_asc'] ?: $form['sort_asc'];
            $form['sort'] = $_SESSION['calendar_index_form']['sort'] ?: $form['sort'];
            $form['date'] = $_SESSION['calendar_index_form']['date'] ?: $form['date'];
        }
        return $form;
    }

	public static function _index_course_session($e, $tabs)
    {

        $user = $tabs->user;
        $form = Lab::form(function (&$old_form, &$form) {
        });
        $form_token = $form['form_token'] ?: Session::temp_token();
        $_SESSION[$form_token] = $form;
        $form = Input::form();
        // $form = self::refresh_history_form($form) ?: $form;
        $dtstart = $form['st'] ?: Date::time();
        $date = getdate($dtstart);
        $fday = $date['mday'];
        $dtstart = mktime(0, 0, 0, $date['mon'], $fday, $date['year']);
        $dtend = mktime(0, 0, 0, $date['mon'], $fday + 1, $date['year']);
        $dtprev = mktime(0, 0, 0, $date['mon'], $fday - 1, $date['year']);
        $dtnext = mktime(0, 0, 0, $date['mon'], $fday + 1, $date['year']);

        $form['dtstart'] = $dtstart;
        $form['dtend'] = $dtend;

        $pre_selector = [];

        $selector = "course";
        $selector_week = "term_week[dtstart~dtend=$dtstart]";

        $week_day = Date::format($dtstart, 'N');
        if ($week_day) {
            $selector .= "[week_day=$week_day]";
        }

        $school_term = school_term_model::current();
        if ($school_term->id) {
            $pre_selector["school_term"] = $school_term;
            $selector_week = "$school_term term_week[dtstart~dtend=$dtstart]";
        }
        $weeks = Q($selector_week)->to_assoc('week', 'week');

        if (count($weeks)) {
            $pre_selector["course_week"] = "course_week[week=".implode(',', $weeks)."]";
        }
        
        if (count($pre_selector)) {
            $selector = "(" . implode(',', $pre_selector) . ") " . $selector;
        }

        // 获取当前时间的课程
        $classrooms = Q($selector)->to_assoc('classroom_ref_no', 'classroom_name');

		$headers = [];
		if ($school_term->id) foreach (Q("course_session[term=$school_term->term]:sort(session A)") as $session) {
			$headers[] = "第 $session->session 节";
		}

        $view = V('calendar/course/day', ['classrooms' => $classrooms, 'classrooms_count' => $classrooms_count]);
        $view->cal_week_rel = 'calweek_' . uniqid();
        $view->dtstart = $dtstart;
        $view->dtprev = $dtprev;
        $view->dtnext = $dtnext;
        $view->user = $user;
		$view->headers = $headers;
        $now = Date::time();
        $view->whour_now = ($now >= $dtstart && $now <= $dtnext) ? Date::format($now, 'H') : -1;
        $tabs->content = $view;
    }
}

