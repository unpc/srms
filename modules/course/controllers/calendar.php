<?php

class Calendar_Controller extends Controller
{
    public function courses() {

        // $form = Lab::form(function (&$old_form, &$form) {

        // });

        // $dtstart = (int) $form['dtstart'] ?: strtotime(date('Y-m-d'));
        // $dtend   = (int) $form['dtend'] ?: strtotime(date('Y-m-d 23:59:59'));

        // $form['dtstart'] = $dtstart;
        // $form['dtend'] = $dtend;
        // $this->refresh_history('equipments_day', $form);

        // $me = L('ME');

        Event::bind('calendar.secondary_tabs.content', 'course::_index_course_session', 0, 'course_session');

        $calendar_tabs = Widget::factory('tabs');

        $calendar_tabs->add_tab('course_session', [
                'title'  => I18N::T('calendars', '日历'),
                'weight' => 20,
            ])
            ->tab_event('calendar.secondary_tabs.tab')
            ->content_event('calendar.secondary_tabs.content')
            ->set('user', $me);

        $calendar_tabs->select('course_session');
        $panel_buttons = new ArrayIterator;
        $layout = V('calendar/course/view', ['mode' => 'course_session', 'panel_buttons' => $panel_buttons, 'form' => []]);
        $layout->calendar_tabs = $calendar_tabs;
        $this->add_css('preview calendars:common');
        $this->add_js('preview');
        echo $layout;
    }
}

class Calendar_AJAX_Controller extends AJAX_Controller
{
    public function index_courses_day_components_get()
    {
        $this->cache_header();
        $form = Input::form();

        $dtstart = (int) $form['dtstart'];
        $dtend   = (int) $form['dtend'];
    
        if ($dtend < $dtstart || getdate($dtend)['mday'] > $dtstart + 608400) {
            return;
        }

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

        $start = (int) $form['start'];
        $step   = (int) $form['step'];

        // 获取当前时间的课程
        $classrooms = Q($selector)->limit($start, $step)->to_assoc('classroom_ref_no', 'classroom_name');
		$headers = [];
        if ($school_term->id) foreach (Q("course_session[term=$school_term->term]:sort(session A)") as $session) {
			$headers[] = "第 $session->session 节";
		}

        $cdata = [];
        foreach ($classrooms as $classroom_ref_no => $classroom_name) {
            foreach (Q("{$selector}[classroom_ref_no=$classroom_ref_no]") as $course) {
                $cdata[] = [
                    'id' => $course->id,
                    'classroom_ref_no'  => $classroom_ref_no,
                    'dtStart'  => $dtstart + (($course->course_session - 1) * (24 * 60 * 60 / count($headers))),
                    'dtEnd'    => $dtstart + ($course->course_session * (24 * 60 * 60 / count($headers))) - 1,
                    'course_session' => $course->course_session,
                    'color'    => 'course',
                    'extra_class' => "",
                    'content'  => $course->render('course:calendar/course/content', true, ['course' => $course]),
                ];
            }
            break;
        }

        Output::$AJAX['components'] = $cdata;
    }
}