<?php

class Course_Admin
{
    static function School_Term_ACL($e, $me, $perm, $term, $options) {
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

    static function Course_Session_ACL($e, $me, $perm, $session, $options) {
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

    public static function setup()
    {
        $me = L('ME');
        //if ($me->access('添加/修改所有会议室')) {
            Event::bind('admin.index.tab', 'Course_Admin::_primary_tab');
        //}
    }

    static function _primary_tab ($e, $tabs) {
        $tabs->add_tab('course', [
            'url'=>URI::url('admin/course'),
            'title'=> I18N::T('course', '课程管理'),
        ]);
        Event::bind('admin.index.content', 'Course_Admin::_primary_content', 0, 'course');
    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        Event::bind('admin.course.content', 'Course_Admin::_secondary_school_term_content', 0, 'school_term');
        Event::bind('admin.course.content', 'Course_Admin::_secondary_cource_session_content', 0, 'cource_session');

        $secondary_tabs                = Widget::factory('tabs');
        $tabs->content->secondary_tabs = $secondary_tabs
            ->set('class', 'secondary_tabs')
            ->add_tab('school_term', [
                'url'   => URI::url('admin/course.school_term'),
                'title' => I18N::T('course', '教学学期'),
            ])
            ->add_tab('cource_session', [
                'url'   => URI::url('admin/course.cource_session'),
                'title' => I18N::T('course', '课程节次'),
            ])
            ->tab_event('admin.course.tab')
            ->content_event('admin.course.content');

        Event::trigger('admin.course.secondary_tabs', $secondary_tabs);

        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs->select($params[1]);
    }
	
    public static function _secondary_school_term_content ($e, $tabs) {
        $form = Lab::form();
        $selector = 'school_term';
        $selector = $selector;
        $terms = Q($selector);

        $panel_buttons = [];
        $panel_buttons[] = [
            'text' => I18N::T('course', '添加'),
            'extra' => 'q-object="add_school_term" q-event="click" q-src="' . URI::url('!course/admin') .
                    '" class="button button_add"'
        ];

        $start = (int) $form['st'];
        $per_page = 20;
        $pagination = Lab::pagination($terms, $start, $per_page);

        $tabs->content = V('course:school_term/admin/index', [
            'form' => $form,
            'terms' => $terms,
            'pagination' => $pagination,
            'panel_buttons' => $panel_buttons
        ]);
	}

	public static function _secondary_cource_session_content ($e, $tabs) {
        $tabs->content = V('course:course_session/admin/types', ['types' => School_Term_Model::$TYPES]);
    }
    

}
