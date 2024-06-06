<?php

class Index_Controller extends Base_Controller {

	function index ($tab='own') {
		
		/*
		NO.TASK#236（guoping.zhang@2010.11.18)
		新权限判断规则
		NO.BUG#178（guoping.zhang@2010.11.20)
		列表日程时is_allowed_to的客体是calendar对象
		*/
		$me = L('ME');
		$calendar = O('calendar',['parent'=>$me, 'type'=>'schedule']) ;
		$primary_tabs = Widget::factory('tabs', $calendar);
		if (!$calendar->id) {
			$calendar->parent = $me;
			$calendar->type = 'schedule';
			$calendar->name = I18N::T('schedule', '%name的日程安排', ['%name'=>$me->name]);
			$calendar->save();
		}

		if ($me->is_allowed_to('列表事件', $calendar)) {
			$primary_tabs
				->add_tab('own', [
					'url'=>URI::url('!schedule/index.own'),
					'title'=>I18N::T('schedule', '我的日程安排'),
				]);
			Event::bind('schedule.index.content', [$this, '_index_own'], 0, 'own');
		}
		

        if (!$GLOBALS['preload']['people.multi_lab']) {
            $lab = Q("$me lab")->current();
        }
        if ($lab->id) {
            $calendar = O('calendar', ['parent'=>$lab]);
            if (!$calendar->id) {
                $calendar->parent = $lab;
                $calendar->type = 'schedule';
                $calendar->name = I18N::T('schedule', '%lab的日程安排', ['%lab'=>$lab->name]);
                $calendar->save();
            }

            if ($me->is_allowed_to('列表事件', $calendar)) {
                $primary_tabs
                    ->add_tab('lab', [
                        'url'=>URI::url('!schedule/index.lab'),
                        'title'=>I18N::T('schedule', '实验室日程安排'),
                    ]);

                Event::bind('schedule.index.content', [$this, '_index_lab'], 0, 'lab');
            }
        }

		$primary_tabs->content_event('schedule.index.content');
		$primary_tabs->select($tab);

		$this->layout->body->primary_tabs = $primary_tabs;

	}

	function _index_lab($e, $tabs) {
		$me = L('ME');

		$form =  Input::form();		
		$lab = O('lab',$form['lab']);

		if (!$lab->id && !$GLOBALS['preload']['people.multi_lab']) {
            $lab = Q("$me lab")->current();
        }

		if (!$lab->id) {
			URI::redirect('error/404');
		}

		$calendar = O('calendar', ['parent'=>$lab]);
		/*
		  NO.BUG#205(xiaopei.li@2010.12.03)
		  修正"列表实验室日程安排"权限混乱
		*/
		if (!$me->is_allowed_to('列表事件', $calendar)) {
			URI::redirect('error/401');
			return;
		}

		$tabs->content = V('calendar', ['calendar'=>$calendar]);	
	}
	
	function _index_own($e, $tabs) {
		$me = L('ME');
		$calendar = O('calendar', ['parent'=>$me, 'type'=>'schedule']) ;
		$tabs->content = V('schedule:calendar', ['calendar'=>$calendar]);
	}

    public function export() {
        $form = Input::form();

        if ($form['form_token']) {
            $form = array_merge($_SESSION[$form['form_token']], Input::form());
        }

        $calendar = O('calendar', $form['calendar_id']);

        //错误传值
        if (!$calendar->id || !in_array($form['type'], ['print', 'csv'])) URI::redirect('error/401');
		if (($calendar->parent->name() == 'user' && $calendar->type = 'schedule') || ($calendar->parent->name() == 'lab')) {
	        $dtstart = $form['dtstart'];
	        $dtend = $form['dtend'];

	        $components = Q("cal_component[calendar={$calendar}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]");

	        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend);

	        if ($new_components) {
	            $components = $new_components;
	        }

	        //filter
	        $columns = [];

	        foreach(Config::get('calendar.export_columns.schedule') as $key => $value) {
	            if (array_key_exists($key, $form)) {
	                $columns[$key] = $value;
	            }
	        }

	        return call_user_func_array([$this, '_export_'. $form['type']], [$components, $columns, $form]);
		}
		else {
			URI::redirect('error/401');
		}
    }

    private function _export_print($components, $columns, $form) {
        $this->layout = V('schedule:calendar/print', ['columns'=> $columns, 'form'=> $form, 'components'=> $components]);
    }

    private function _export_csv($components, $columns, $form) {

        $csv = new CSV('php://output', 'w');

        $csv->write( I18N::T('schedule', $columns) );

        foreach($components as $component) {
            $data = [];
            if (array_key_exists('name', $columns)) {
            	$data[] = $component->name;
            }
            if (array_key_exists('reserv_type', $columns)) {
                switch($component->subtype) {
                    case 0:
                        $data[] = I18N::T('schedule', '组会');
                        break;
                    case 1:
                        $data[] = I18N::T('schedule', '文献讨论');
                        break;
                    case 2:
                        $data[] = I18N::T('schedule', '其他');
                        break;
                    case 3:
                        $data[] = I18N::T('schedule', '学术报告');
                        break;
                }
            }
            if (array_key_exists('speakers', $columns)) {
            	$data[] = join(', ', json_decode($component->speakers, TRUE));
            }
            if (array_key_exists('attendee', $columns)) {
            	$return = '';
                if ($component->attendee_type == 'all') {
                    $return = I18N::T('schedule', '全部成员');
                }
                else {
                    if ($component->attendee_groups) {
                        $return .= ' '. I18N::T('schedule', '组织机构: ');
                        $return .= join(',', json_decode($component->attendee_groups, TRUE));
                    }

                    if ($component->attendee_roles) {
                        $return .= ' '. I18N::T('schedule', '角色: ');
                        $return .= join(',', json_decode($component->attendee_roles, TRUE));
                    }

                    if ($component->attendee_users) {
                        $return .= ' '. I18N::T('schedule', '个别用户: ');
                        $return .= join(',', json_decode($component->attendee_users, TRUE));
                    }
                }
                $data[] = $return;
            }
            if (array_key_exists('organizer', $columns)) {
            	$data[] = $component->organizer->name;
            }
            if (array_key_exists('meeting', $columns)) {
            	$data[] =  $component->me_room->name;
            }
            if (array_key_exists('time', $columns)) {
            	$data[] = Date::format($component->dtstart, 'Y/m/d H:i:s').  ' - '.  Date::format($component->dtend, 'Y/m/d H:i:s');
            }
            if (array_key_exists('duration', $columns)) {
            	$data[] = I18N::T('schedule', '%duration小时', [ '%duration'=> round(($component->dtend - $component->dtstart) / 3600, 2)]);
            }
            if (array_key_exists('description', $columns)) {
            	$data[] = $component->description;
            }

            $csv->write($data);
        }

        $csv->close();
    }

}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_preview_click() {

		$form = Input::form();
		$component = O('cal_component', $form['component_id']);
		
		if ( !L('ME')->is_allowed_to('查看', $component) ) return;
		$calendar = $component->calendar;
		
		$parent = $calendar->parent;
	
		if ( $calendar->id && $parent->name()=='lab' ) {
			$infos[] = (string) V('schedule:calendar/component_info', ['component' => $component]);
			Output::$AJAX['preview'] = (string) V('schedule:calendar/preview', [
															'infos' => $infos,
															'component' => $component
			]);
		}//个人日程
		else if ( $calendar->id && $parent->name()=='user' ) {
		
			Output::$AJAX['preview'] = (string)V('schedule:calendar/user/preview', [
																	'component' => $component,
			]);

		}
		
		
	}

    public function index_export_components_click() {

        $form = Input::form();
        $calendar = O('calendar', $form['calendar_id']);

        //错误传值
        if (!$calendar->id) return FALSE;
        if (!in_array($form['type'], ['print', 'csv'])) return FALSE;

        $form_token = 'schedule_form_token_'. uniqid();
        $form['form_token'] = $form_token;
        $_SESSION[$form_token] = $form;


        $view_params  = [
                'type'=> $form['type'],
                'dtstart'=> $form['dtstart'],
                'dtend'=> $form['dtend'],
                'calendar_id'=> $form['calendar_id'],
                'form_token'=> $form['form_token']
                ];

        $dialog_params = ['title'=> I18N::T('schedule', $form['type'] == 'print' ?
                    '请选择要打印的列' : '请选择要导出的列')];

        JS::dialog((string) V('schedule:calendar/dialog', $view_params), $dialog_params);
    }
}
