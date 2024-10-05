<?php

class ME_Reserv {

	static function setup_index($e, $controller, $method, $params) {
		$me = L('ME');
		Event::bind('meeting.primary.tab', 'ME_Reserv::reserv_primary_tab');
	}

	static function setup_view() {
		Event::bind('meeting.index.tab', 'ME_Reserv::reserv_tab');
		Event::bind('meeting.index.content', 'ME_Reserv::reserv_tab_content', 0, 'reserv');
        Event::bind('meeting.index.tab.tool_box', 'me_reserv::_reserv_calendar_tool',0,'reserv');
	}

	static function user_is_meeting_incharge($user, $meeting) {
		if ($meeting->id && $user->id && Q("{$meeting} user.incharge[id=$user->id]")->total_count() > 0 ) {
			return TRUE;
		}
		return FALSE;
	}

	static function prerender_component($e, $view) {
		$parent = $view->component->calendar->parent;
		$me = L('ME');

        if ($parent->name() == 'meeting') {
			$form = $view->component_form;
            $form['#global_css'] = "
                .dialog_content tr td input.text {
                    width: 160px !important;
                    float: left;
                }
                .dialog_content .max_dialog_W600 td div.title {
                    line-height: 30px;
                    float: left;
                    width: 60px;
                }
                .dialog_content .max_dialog_W600 {
                    min-width: 510px;
                }
            ";
            $form['name']['default_value'] = I18N::T('meeting', '空间预约');
            $form['name']['label'] = I18N::T('meeting', '预约名称');

            $is_admin = $me->is_allowed_to('管理预约', $parent);
            $form['organizer']['label'] = I18N::T('calendars', '预约人');
            
            if (!$me->is_allowed_to('管理预约', $parent)) {
                unset($form['organizer']);
			}

			$form['partner'] = [
				'label' => I18N::T('meeting', '参会人'),
				'weight' => 25,
				'path' => [
					'form' => 'meeting:calendar/component_form/partner',
					'info' => 'meeting:calendar/component_info/'
				]
			];
            $form['description'] = [
                'label' => I18N::T('meeting', '预约内容'),
                'weight' => 100,
                'path' => [
                    'form' => 'meeting:calendar/component_form/description',
                    'info' => 'meeting:calendar/component_info/'
                ]
            ];

            $form['dtstart']['weight'] = 21;
            $form['dtstart']['path']['form'] = 'meeting:calendar/component_form/dtstart';

            $form['dtend']['weight'] = 21;

            $form['is_check'] = [
                'label' => I18N::T('meeting', '是否签到'),
				'weight' => 35,
				'path' => [
					'form' => 'meeting:calendar/component_form/is_check',
					'info' => 'meeting:calendar/component_info/'
				]
            ];

            if ($me->is_allowed_to('添加重复规则', $view->component->calendar)) {
                if ($view->component->id) {
                    $type = $view->component->type;
                    if ($type == Cal_Component_Model::TYPE_VEVENT) {
                        $label = I18N::T('eq_reserv', '预约');
                    } elseif ($type == Cal_Component_Model::TYPE_VFREEBUSY) {
                        $label = I18N::T('eq_reserv', '非预约时段');
                    }
                    if ($label) {
                        $form['rrule'] = [
                            'label'  => $label,
                            'weight' => 1,
                        ];
                    }
                }
            }

            uasort($form, 'Cal_Component_Model::cmp');
            $view->component_form = $form;
        }

        if  ($parent->name() == 'lab') {
            $form = $view->component_form;
            $form['connect_meeting'] = [
                    'label'=>I18N::T('schedule', '会议室'),
				'path'=>[
					'form'=>'meeting:calendar/component_form/',
					'info'=>'meeting:calendar/component_info/',
				],
				'weight'=>12,
			];
			$view->component_form = $form;
		}
	}

	static function reserv_tab($e, $tabs) {
		$meeting = $tabs->meeting;
		$me = L('ME');
		Lab::enable_message(FALSE);
		$tabs->add_tab('reserv', [
				'url'=>$meeting->url('reserv'),
				'title'=>I18N::T('meeting', '使用预约'),
				'weight' => 10
			]);
		Lab::enable_message(TRUE);

	}

	static function reserv_tab_content($e, $tabs) {
		if (!L('ME')->id) URI::redirect('error/401');

        Event::bind('me_reserv.fourth_tabs.content', 'me_reserv::_reserv_tab_content_calendar_meeting', 0, 'calendar');
        Event::bind('me_reserv.fourth_tabs.content', 'me_reserv::_reserv_tab_content_list_meeting', 0, 'list');

        $meeting = $tabs->meeting;

        $content = V('meeting:reserv_view');

        $params=Config::get('system.controller_params');

        $params = $params[2]?$params[2]:'calendar';

        $form = Lab::form();

        $tabs->set('content',$content);

        $content->fourth_tabs = Widget::factory('tabs')
            ->set('class', 'fourth_tabs float_left')
            ->set('form', $form)
            ->add_tab('calendar', [
                'url'    => $meeting->url('reserv.calendar'),
                'title'  => I18N::T('eq_reserv', '日历'),
                'weight' => 20,
            ])
            ->add_tab('list', [
                'url'    => $meeting->url('reserv.list'),
                'title'  => I18N::T('eq_reserv', '列表'),
                'weight' => 30,
            ])
            ->content_event('me_reserv.fourth_tabs.content')
            ->select($params);

	}

	static function _reserv_tab_content_calendar_meeting($e,$tabs){
        $tabs->content = V('meeting:me_reserv/tab.reserv',['type'=>'week']);
    }

    static function _reserv_tab_content_list_meeting($e,$tabs){
        $form = $tabs->form;
        $tabs->content = V('meeting:me_reserv/tab.reserv',['type'=>'list','form'=>$form]);
    }

    static function _reserv_calendar_tool($e,$tabs){

        $form_token = Session::temp_token('me_reserv_',300);
        $meeting = $tabs->meeting;
        $calendar = O('calendar' , ['parent'=>$meeting]);
        if (!$calendar->id) {
            $calendar = O('calendar');
            $calendar->parent = $meeting;
            $calendar->type = 'me_reserv';
            $calendar->name = I18N::T('meeting', '%meeting的预约', ['%meeting' => $meeting->name]);
            $calendar->save();
        }

        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['date_check']) {
                    unset($old_form['date_check']);
                }
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                }
                unset($form['date_filter']);
            }
        });

        if ($form['dtstart']) {
            $dtstart = $form['dtstart'];
        } else {
            $dtstart = time();
            $date=getdate($dtstart);
            $dtstart = mktime(0,0,0,$date['mon'], $date['mday'], $date['year']);
        }
        $dtend = $form['dtend'] ?: $dtstart + 604800;

        $me            = L('ME');
        $panel_buttons = [];

        // if ($me->is_allowed_to('添加事件', $calendar)) {
        //     $panel_buttons[] = [
        //         'tip'   => I18N::HT(
        //             'me_reserve',
        //             '添加使用预约'
        //         ),
        //         'extra' => 'q-object="just_show_insert_component" q-event="click" q-src="' . H(URI::url('!calendars/calendar')) .
        //             '" q-static="' . H(['id' => $calendar->id]) .
        //             '" class="button button_add"',
        //     ];
        // }

        $panel_buttons[] = [
            'text'   => I18N::T(
                'me_reserve',
                '导出Excel'
            ),
            'tip'   => I18N::T(
                'me_reserve',
                '导出Excel'
            ),
            'extra' => 'q-object="export_components" q-event="click" q-src="' . H(URI::url('!meeting/reserv')) .
                '" q-static="' . H(['type' => 'csv', 'dtstart' => $dtstart, 'dtend' => $dtend, 'form_token' => $form_token, 'calendar_id' => $calendar->id]) .
                '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text'   => I18N::T(
                'me_reserve',
                '打印'
            ),
            'tip'   => I18N::T(
                'me_reserve',
                '打印'
            ),
            'extra' => 'q-object="export_components" q-event="click" q-src="' . H(URI::url('!meeting/reserv')) .
                '" q-static="' . H(['type' => 'print', 'dtstart' => $dtstart, 'dtend' => $dtend, 'form_token' => $form_token, 'calendar_id' => $calendar->id]) .
                '" class="button button_print  middle"',
        ];

        if ($tabs->content->fourth_tabs->selected == 'list') {
            $columns = $calendar->list_columns($form);
            $tabs->content->fourth_tabs->set('columns', $columns);
            $tabs->content->fourth_tabs->search_box = V('application:search_box', ['top_input_arr' => ['date'], 'panel_buttons' => $panel_buttons, 'panel_buttons_float' => 'float_right', 'columns' => $columns]);
        }
    }

    static function _reserv_all_calendar_tool($e,$tabs){

        $form_token = Session::temp_token('me_reserv_',300);
        $meeting = $tabs->meeting;

        $calendar = $tabs->calendar;

        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['date_check']) {
                    unset($old_form['date_check']);
                }
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                }
                unset($form['date_filter']);
            }
        });

        if ($form['dtstart']) {
            $dtstart = $form['dtstart'];
        } else {
            $dtstart = time();
            $date=getdate($dtstart);
            $dtstart = mktime(0,0,0,$date['mon'], $date['mday'], $date['year']);
        }
        $dtend = $form['dtend'] ?: $dtstart + 604800;

        $me            = L('ME');
        $panel_buttons = [];

        $panel_buttons[] = [
            'text'   => I18N::T(
                'me_reserve',
                '导出Excel'
            ),
            'tip'   => I18N::T(
                'me_reserve',
                '导出Excel'
            ),
            'extra' => 'q-object="export_components" q-event="click" q-src="' . H(URI::url('!meeting/reserv')) .
                '" q-static="' . H(['type' => 'csv', 'dtstart' => $dtstart, 'dtend' => $dtend, 'form_token' => $form_token, 'calendar_id' => $calendar->id]) .
                '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text'   => I18N::T(
                'me_reserve',
                '打印'
            ),
            'tip'   => I18N::T(
                'me_reserve',
                '打印'
            ),
            'extra' => 'q-object="export_components" q-event="click" q-src="' . H(URI::url('!meeting/reserv')) .
                '" q-static="' . H(['type' => 'print', 'dtstart' => $dtstart, 'dtend' => $dtend, 'form_token' => $form_token, 'calendar_id' => $calendar->id]) .
                '" class="button button_print  middle"',
        ];

        if ($tabs->selected == 'list') {
            $columns = $calendar->list_columns($form);
            $tabs->set('columns', $columns);
            $tabs->search_box = V('application:search_box', ['top_input_arr' => ['date'], 'panel_buttons' => $panel_buttons, 'panel_buttons_float' => 'float_right', 'columns' => $columns]);
            //$tabs->panel_buttons = $panel_buttons;
            //$tabs->panel_buttons=V('application:panel_buttons', ['panel_buttons'=>$panel_buttons]);
        }
    }

	static function _reserv_tab_content_calendar($e,$tabs){
        $calendar = $tabs->calendar;
        $tabs->content = V('meeting:incharge/calendar', ['calendar'=>$calendar,'type'=>'week']);
    }

    static function _reserv_tab_content_list($e,$tabs){
        $form = $tabs->form;
        $calendar = $tabs->calendar;
        $tabs->content = V('meeting:incharge/calendar', ['calendar'=>$calendar,'type'=>'list','form'=>$form]);
    }


    static function component_form_delete($e, $form, $component) {

		$parent = $component->calendar->parent;
		$pname = $parent->name();
		if (!$component->me_room->id) return;
		if ( $pname != 'lab') return;

		Cache::L('skip_meeting_reserv_delete_check', TRUE);
		$can_delete = L('ME')->is_allowed_to('删除', $component);
		Cache::L('skip_meeting_reserv_delete_check', FALSE);
		
		if (!$can_delete) {
			$user = $component->organizer;
			$dtstart = $component->dtstart;
			$dtend = $component->dtend;

			Notification::send('meeting.disconnect_meeting_confirmed', $user, [
				'%user' => Markup::encode_Q($user),
				'%schedule' => $component->name,
				'%meeting' => $component->me_room->name,
				'%dtstart' => Date::format($dtstart),
				'%dtend' => Date::format($dtend)
				]);
			$component->me_room = NULL;
			$component->save();
			$e->return_value = TRUE;	//override original delete action?
		}

    }

    static function component_form_submit($e, $form, $component, $var = []){
		$parent = $component->calendar->parent;
    	$pname = $parent->name();
		$me = L('ME');

		$dtstart = $form['dtstart'];
        $dtend   = $form['dtend'];
		
		if ($pname == 'lab') {
			$meeting = O('meeting', (int)$form['connect_meeting']);

			if (!$me->is_allowed_to('修改', $meeting) && !$me->is_allowed_to('管理预约', $meeting) 
			&& !ME_Reserv_Access::check_authorized($me, $meeting)) {
				$form->set_error('connect_meeting', I18N::T('meeting', '您还未通过此会议室的授权，请向会议室管理员申请授权。'));
				return FALSE;
			}

			$component->me_room = $meeting;
		}
		elseif ($pname == 'meeting') {
			$meeting = $parent;

			if ($meeting->accept_block_time) {
                $interval = $meeting->reserv_interval_time;
                $align    = $meeting->reserv_align_time;
                $blocks   = $meeting->reserv_block_data;
                if ($interval || $align || count($blocks)) {

                    /*
                     * $dtstart为用于辅助计算component->dtstart的值
                     * $dtend为用于辅助计算component->dtend的值
                     * $form['dtstart'] $form['dtend']表单传入值，该值在最后被修正
                     */
                    if ($form['dtend'] < $form['dtstart']) {
                        list($form['dtstart'], $form['dtend']) = [$form['dtend'], $form['dtstart']];
                    }

                    //如果跨天
                    if (
                        (date('d', $form['dtend']) != date('d', $form['dtstart']))
                        ||
                        ($form['dtend'] - $form['dtstart'] >= 86400)
                    ) {
                        $form->set_error('dtstart', I18N::T('meeting', '时间对齐的预约不允许跨零点使用!'));
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '时间对齐的预约不允许跨零点使用!'));
                        return false;
                    }

                    /*
                    前台js中已经进行矫正，但如果用户强硬的手动改写块状数字， 则我们需要在后台进行矫正。
                     */

                    //起始时间format, 为便于获取，不设定为start_format
					$format = self::get_format_block($meeting, $form['dtstart']);
				

                    /*
                     * 第一步，修改、创建的component进行block位置判定，设定align interval
                     */

                    //设定align interval为dtstart所在的时段的align interval
                    $align    = $format['align'];
                    $interval = $format['interval'];

                    /*
                     * 2,先纠正dtstart位置
                     */

                    //块起始时间
                    $block_start = $format['start'];
					$block_end   = $format['end'];
					
                    /*BUG #4984 when $dtstart divides $align is an interge, it's not necessary to correct!*/
                    if ($dtstart % $align != 0) {
                        $dtstart = $block_start + round(($dtstart - $block_start) / $align) * $align;
                    }
					
                    //如果位移后超出block，向前移动
                    if ($dtstart >= $block_end) {
                        $dtstart -= $align;
                    }
					
                    /*
                     * 3，纠正dtend位置，如果错位，还原到块状规矩的时间点
                     */

                    $dtend = $dtstart + round(min($dtend - $dtstart, $block_end - $dtstart) / $interval) * $interval - 1;

                    //如果校正后dtend 小于dtstart，无法生成component
                    if ($dtend <= $dtstart) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '不满足最小块状预约规则!'));
                        $form->set_error('dtstart', I18N::T('meeting', '不满足最小块状预约规则!'));
                        $dtend = $dtstart;
                    }

                    //将处理过的dtstart dtend 赋值给form
                    $form['dtstart'] = $dtstart;
					$form['dtend']   = $dtend;
					
                }
            }
            
			if ($form['organizer']) {
				$organizer = O('user', ['id' => $form['organizer']]);
				if (!$organizer->id) {
					$form->set_error('organizer', I18N::T('meeting', '请填写有效的预约者信息!'));
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '请填写有效的预约者信息!'));
				}

				if ($organizer->id != $me->id
				&& !$me->is_allowed_to('管理预约', $parent)) {
					$form->set_error('organizer', I18N::T('meeting', '您无权添加他人预约!'));
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '您无权添加他人预约!'));
				}
			}
		}
	}

	private static function get_format_block($meeting, $time)
    {
        $blocks = (array) $meeting->reserv_block_data;
        $year   = date('Y', $time);
        $month  = date('m', $time);
        $day    = date('d', $time);

        $day_start = mktime(0, 0, 0, $month, $day, $year);
        $day_end   = $day_start + 86400;

        //系统设定的跨天的块进行拆分处理
        $temp_blocks = [];

        foreach ($blocks as $block) {
            $b = [
                'start'    => mktime($block['dtstart']['h'], $block['dtstart']['i'], 0, $month, $day, $year),
                'end'      => mktime($block['dtend']['h'], $block['dtend']['i'], 0, $month, $day, $year),
                'align'    => $block['align_time'],
                'interval' => $block['interval_time'],
            ];

            if ($b['start'] > $b['end']) {
                $nb            = $b;
                $nb['start']   = $day_start;
                $temp_blocks[] = $nb;

                $nb            = $b;
                $nb['end']     = $day_end;
                $temp_blocks[] = $nb;
            } else {
                $temp_blocks[] = $b;
            }
        }

        $blocks = $temp_blocks;

        //计算非系统设定块外的系统默认块
        $default_blocks = [
            [
                'start'    => $day_start,
                'end'      => $day_end,
                'interval' => (int) $meeting->reserv_interval_time,
                'align'    => (int) $meeting->reserv_align_time,
            ],
        ];

        foreach ($blocks as $block) {
            $start = $block['start'];
            $end   = $block['end'];

            $temp_array = [];
            foreach ($default_blocks as $dblock) {
                if ($start > $dblock['start'] && $end < $dblock['end']) {
                    //如果block在dblock中间, 拆分为左右的两个block
                    $nb           = $dblock;
                    $nb['end']    = $start;
                    $temp_array[] = $nb;

                    $nb           = $dblock;
                    $nb['start']  = $end;
                    $temp_array[] = $nb;
                } elseif ($start > $dblock['start'] && $start < $dblock['end'] && $end >= $dblock['end']) {
                    //如果block截取dblock的后侧
                    $dblock['end'] = $start;
                    $temp_array[]  = $dblock;
                } elseif ($end > $dblock['start'] && $end < $dblock['end'] && $start <= $dblock['start']) {
                    //如果block截取dblock的前侧
                    $dblock['start'] = $end;
                    $temp_array[]    = $dblock;
                } elseif (!($start <= $dblock['start'] && $end >= $dblock['end'])) {
                    $temp_array[] = $dblock;
                }
            }
            $default_blocks = $temp_array;
        }

        $blocks = array_merge($blocks, $default_blocks);

        //进行块状匹配
        foreach ($blocks as $b) {
            if ($time >= $b['start'] && $time < $b['end']) {
                //如果时间在块内, start end 为块时间
                return $b;
            }
        }
    }
	
	static function component_form_post_submit($e, $component, $form, $var = []) {
		$parent = $component->calendar->parent;
		
		if ($parent->name()) {
			$reserv = O('me_reserv', ['component' => $component]);
			$reserv->component = $component;
			$reserv->meeting = $parent;
			$reserv->type = $form['type'];
			$reserv->user = $component->organizer;
			$reserv->users = $form['users'] ?: '';
            $reserv->is_check = (int)$form['is_check'];
			$reserv->dtstart = $component->dtstart;
			$reserv->dtend = $component->dtend;
			$reserv->save();
		}
	}

	static function cal_component_saved ($e, $component, $old_data, $new_data) {
        $me = L('ME');
		$parent = $component->calendar->parent;
		$pname = $parent->name();
		if (count($new_data) == 0) return;

        $new_compoennt = (bool)$new_data['id'];
		//来自meeting模块的save
		if ($pname == 'meeting') {
            if ($new_compoennt) {
                Log::add(strtr('[meeting] %user_name[%user_id] 于 %time 成功创建 %meeting_name[%meeting_id] 的新的会议室预约[%compoonent_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%time'=> Date::format(Date::time()),
                    '%meeting_name'=> $parent->name,
                    '%meeting_id'=> $parent->id,
                    '%compoonent_id'=> $component->id
                ]), 'journal');
            }
            else {
                Log::add(strtr('[meeting] %user_name[%user_id] 于 %time 成功修改 %meeting_name[%meeting_id] 的会议室预约[%component_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%time'=> Date::format(Date::time()),
                    '%meeting_name'=> $parent->name,
                    '%meeting_id'=> $parent->id,
                    '%component_id'=> $component->id
                ]), 'journal');
            }

			$contacts = Q("{$parent} user.contact");
			$contacts->append($component->organizer);
			$dtstart = $component->dtstart;
			$dtend = $component->dtend;
			$difference = array_diff_assoc($new_data, $old_data);
			$arr = array_keys($difference);
			
			if (!in_array('id', $arr)) {
				//对预约进行修改
				//通知会议室联系人会议室预约被人修改
				foreach($contacts as $user) {
					Notification::send('meeting.component_be_edited', $user, [
						'%user' => Markup::encode_Q($me),
						'%meeting' => $component->calendar->parent->name,
						'%title' => $component->name,
						'%dtstart' => Date::format($dtstart),
						'%dtend' => Date::format($dtend),
						'%link' => $parent->url('reserv', ['st' => $dtstart ]),
					]);
				}
			}
			else {
				//新建预约
				foreach ($contacts as $receiver) {
					Notification::send('meeting.meeting_room_be_reserved', $receiver, [
						'%user' => Markup::encode_Q($me),
						'%meeting' => $component->calendar->parent->name,
						'%title' => $component->name,
						'%dtstart' => Date::format($dtstart),
						'%dtend' => Date::format($dtend),
						'%link' => $parent->url('reserv', ['st' => $dtstart ]),
					]);	
				}
			}
		}
		//来自lab的与会议室关联的预约save
		elseif ($pname == 'lab' && $component->me_room->id) {
			$dtstart = $component->dtstart;
			$dtend = $component->dtend;
			$description = $component->description;
			$name = $component->name;
			$me_room_name = $component->me_room->name;
			$organizer = L('ME');
			$schedule_speakers = (array)json_decode($new_data['speakers'], TRUE);
			$attendees_from_groups = [];
			$attendees_from_roles = [];
			$attendees = (array)json_decode($new_data['attendee_users'],TRUE);
			$groups = (array)json_decode($new_data['attendee_groups'],TRUE);
			$roles = (array)json_decode($new_data['attendee_roles'],TRUE);

			$receivers = [];
			$me = L('ME');
			$contacts = Q("{$component->me_room} user.contact");
			$user = $component->organizer;
			$users = Q('user:empty');
			foreach ($contacts as $contact) {
				$users->append($contact);
			}
			foreach($users as $user) {
				$receivers[] = $user;
			}
			$dtstart = $component->dtstart;
			$dtend = $component->dtend;
			$difference = array_diff_assoc($new_data,$old_data);
		  	$arr = array_keys($difference);
		  	if (!in_array('id', $arr)) {
		  		foreach ($receivers as $receiver) {
				  		Notification::send('meeting.lab_component_be_edited', $receiver,[
							'%user' => Markup::encode_Q($me),
							'%meeting' => $component->me_room->name,
							'%title' => $component->name,
							'%dtstart' => Date::format($dtstart),
							'%dtend' => Date::format($dtend),
							'%link' => $component->me_room->url('reserv', ['st' => $dtstart ]),
				  		]);	
		  		}	   		
		  	}
		  	//发送通知申请了新的预约
		  	else {
		  		foreach ($receivers as $receiver) {
			  			Notification::send('meeting.lab_component_be_reserved', $receiver,[
							'%user' => Markup::encode_Q($me),
							'%meeting' => $component->me_room->name,
							'%title' => $component->name,
							'%dtstart' => Date::format($dtstart),
							'%dtend' => Date::format($dtend),
							'%link' => $component->me_room->url('reserv', ['st' => $dtstart ]),
			  			]);	
		  		}		  		
		  	}
		}
	}

	static function cal_component_deleted($e, $component) {

		$parent = $component->calendar->parent;
		$pname = $parent->name();
		if ($pname == 'meeting') {
			$receivers = [];
			$contacts = Q("{$parent} user.contact");
			$user = $component->organizer;
			$users = Q('user:empty');
			foreach ($contacts as $contact) {
				$users->append($contact);
			}
			$users->append($user);
			foreach($users as $user) {
				$receivers[] = $user;
			}
			$dtstart = $component->dtstart;
			$dtend = $component->dtend;
			//删除预约通知会议室联系人
			foreach ($receivers as $receiver) {
				Notification::send('meeting.meeting_component_delete', $receiver, [
				'%user' => Markup::encode_Q($me),
				'%meeting' => $component->calendar->parent->name,
				'%title' => $component->name,
				'%dtstart' => Date::format($dtstart),
				'%dtend' => Date::format($dtend),
				'%link' => $parent->url('reserv', ['st' => $dtstart ]),
				]);
			}
			$reserv = O('me_reserv', ['component' => $component]);
			$reserv->delete();
		}
		elseif ($pname == 'lab' && $component->id) {
			$attendee_roles = [];
			$attendee_groups = [];
			$dtstart = $component->dtstart;
			$dtend = $component->dtend;
			$description = $component->description;
			$name = $component->name;
			$me_room_name = $component->me_room->name;
			$schedule_speakers = Q("schedule_speaker[component={$component}] user")->to_assoc('id', 'name');
			$organizer = $component->organizer;
			$attendees = [];
			$sch_att_users = Q("sch_att_user[component={$component}] user")->to_assoc('id', 'name');
			$attendees += $sch_att_users;
			$sch_att_roles = Q("sch_att_role[component={$component}]")->to_assoc('role_id', 'role_id');
			foreach ($sch_att_roles as $key => $value) {
				$attendee_roles = Q("role[id={$key}] user")->to_assoc('id','name');
				$attendees += $attendee_roles;
			}
			$sch_att_groups = Q("sch_att_group[component={$component}]")->to_assoc('group_id', 'group_id');
			if ($sch_att_groups) {
				foreach ($sch_att_groups as $key => $value) {
					$attendee_groups = (array)Q('(tag_group#'.$key.') user')->to_assoc('id', 'name');
					if (count($attendee_groups) > 0) {
					   $attendees += $attendee_groups;
					}
				}
			}
		}
	}

    //获取meeting和schedule中相关预约
    static function calendar_components_get($e, $calendar, $components, $dtstart, $dtend, $limit = 0, $form = []) {
		$parent = $calendar->parent;

		$mids = Event::trigger('get_schedule_component_ids', $calendar) ?: null;

		if ($parent->name() == 'meeting' && $calendar->type == 'me_reserv') {
            if ($mids) {
                $new_components = Q("cal_component[calendar={$calendar}|me_room={$mids}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)");
            }
            else {
                $new_components = Q("cal_component[calendar={$calendar}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)");
            }
	    	$e->return_value = $new_components;    	
	    	return;
	    }
	    elseif($parent->name() == 'user' && $calendar->type == 'me_incharge') {
	    	//所有负责会议室的预约和关联会议室的日程
			$calendar_ids = Q("$parent<incharge meeting<parent calendar")->to_assoc('id', 'id');

			$cids = implode(',', $calendar_ids);

            if ($mids) {
                $components = Q("cal_component[calendar_id={$cids}|me_room={$mids}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
            } else {
                $components = Q("cal_component[calendar_id={$cids}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
            }
			
			$e->return_value = $components;
			return;
	    }
	    elseif($calendar->type == 'all_meetings') {
	    	//所有会议室预约和关联了会议室的日程
	    	$calendar_ids = Q("meeting<parent calendar")->to_assoc('id', 'id');

			$cids = implode(',', $calendar_ids);
            
            if ($cids) {
                if ($mids) {
                    $components = Q("cal_component[calendar_id={$cids}|me_room={$mids}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
                }
                else {
                    $components = Q("cal_component[calendar_id={$cids}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
                }
            }
            else {
                $components = Q("cal_component:empty");
            }
			$e->return_value = $components;
			return;
	    }
    }

	static function cal_component_get_color($e, $component, $calendar) {
		$parent_name = $calendar->parent->name();
		$cal_type = $calendar->type;

		$return = 0;

		 if (($parent_name == 'user' && $cal_type=='me_incharge') || ($parent_name == 'calendar' && $cal_type=='all_meetings')) {
		 	if (!$component->me_room->id) {
				$return = (int) ($component->calendar->parent->id % 6);
				$e->return_value = $return;
				return false;
			}
			else {
				$return = (int) ($component->me_room->id % 6);
				$e->return_value = $return;
				return false;
			}
		}

        if ($parent_name == 'meeting') {
            $reserv = O('me_reserv', ['component' => $component]);
            $return = (int)$reserv->status;
        }

        $e->return_value = $return;
		return true;
	}

    static function component_content_render($e, $component, $current_calendar = NULL) {
        $calendar = $component->calendar;

		$parent = $calendar->parent;
		if ($calendar->id && $parent->name() == 'meeting') {
			$e->return_value = V('meeting:calendar/component_content', ['component'=>$component, 'current_calendar'=>$current_calendar]);
			return false;
		}//来自课题组日程的会议室预约，应触发schedule模块的课题组日程显示
	}

    static function is_check_overlap($e, $component) {
		$calendar = $component->calendar;
		$parent = $calendar->parent;
		if ($parent->name() == 'meeting') {
			$meeting = $parent;
		}
		elseif ($parent->name() == 'lab' && $component->me_room->id) {
			$meeting = $component->me_room;
			$calendar = O('calendar', ['parent'=>$meeting]);
		}
		else {
			return;
		}

		$dtstart = $component->dtstart;
		$dtend = $component->dtend;

    	/* 如果预约已经和别的预约冲突了，则给予提示 */
	    if (Q("cal_component[calendar={$calendar}|me_room={$parent}][id!={$component->id}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]")->total_count() > 0) {
	    	Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '您预约的时段与其他预约时段冲突!'));
	    	$e->return_value = TRUE;
	    	return FALSE;
    	}

	}

    static function schedule_component_info($e, $component) {
    	if ($component->calendar->parent_name != 'lab') return;
    	if (!$component->me_room->id) return;
    	$e->return_value[] = V('meeting:calendar/schedule.component_info', ['meeting'=>$component->me_room]);

    }
	
	static function empty_meeting_reserv_message($e, $calendar) {
		if ( $calendar->type == 'me_reserv' && $calendar->parent_name == 'meeting' ) {
			$e->return_value = I18N::T('meeting', '该会议室没有符合条件的预约记录');
			return TRUE;
		}
		else if ( $calendar->type == 'all_meetings' && $calendar->parent_name=='calendar' ) {
			$e->return_value = I18N::T('meeting', '没有符合条件的预约记录');
			return TRUE;
		}
		else if ( $calendar->type == 'me_incharge' && $calendar->parent_name == 'user' ) {
			$e->return_value = I18N::T('meeting', '您负责的会议室没有符合条件的预约记录');
			return TRUE;
		}
		
	}
	
	static function operate_door_is_allowed($e, $user, $direction, $door) {
		$meetings = Q("{$door}<asso meeting");

        //direction无用
        foreach ($meetings as $meeting) {
			if ($user->is_allowed_to('关联门禁', $meeting)) {
				$e->return_value = TRUE;
                return FALSE;
			}

			$end = Date::time();
			$start = $end + $meeting->ahead_time * 60;
			$reserv = Q("me_reserv[meeting={$meeting}][dtstart~dtend={$start}|dtstart~dtend={$end}]")->current();

			if ($reserv->user->id == $user->id
			|| $reserv->type == 0) {
				$e->return_value = TRUE;
                return TRUE;
			}

			$roles = @json_decode($reserv->roles, true) ? : [];
			$groups = @json_decode($reserv->groups, true) ? : [];
			$users = @json_decode($reserv->users, true) ? : [];

			if (array_intersect_key($user->roles(), $roles)) {
				$e->return_value = TRUE;
                return TRUE;
			}

			$user_groups = Q("{$user} tag_group")->to_assoc('id', 'name');
			if (array_intersect_key($user_groups, $groups)) {
				$e->return_value = TRUE;
                return TRUE;
			}
			
			if (array_key_exists($user->id, $users)) {
				$e->return_value = TRUE;
                return TRUE;
			}
        }
    }
    
    static function notice($e, $component = null, $calendar = null) 
    {
        if (!is_object($component) || !is_object($calendar) || !is_object($calendar->parent)) {
            return false;
        }
        
        if ($component && $calendar && $calendar->parent->name() == 'meeting') {
            $meeting = O('meeting', $calendar->parent->id);
            $result = self::_check_meeting_workingtime($meeting, $component->dtstart, $component->dtend);
            if (!$result) $e->return_value = I18N::T('meeting', '非工作时间需额外收费!');
            return FALSE;
        }
    }

    static function _check_meeting_workingtime($meeting, $dtstart, $dtend, $user=null, $empower=null) {
        if (!$user) {
            $user = L('ME');
        }
        
        // 这为了区分是否是无权申请
        $is_allowed_user = false;
        $eq_reserv_times = Q("eq_reserv_time[meeting={$meeting}][ltstart=$dtstart~$dtend|ltend=$dtstart~$dtend|ltstart~ltend=$dtstart|ltstart~ltend=$dtend]");
        if ($user->is_allowed_to('修改', $meeting) || $eq_reserv_times->total_count() == 0) {
            return true;
        }
        
        $ids = [];
        
        $times = mktime(date('H', $dtstart), date('i', $dtstart), date('s', $dtstart), 1, 1, 1971);
        $timee = mktime(date('H', $dtend), date('i', $dtend), date('s', $dtend), 1, 1, 1971);
        $dates = strtotime(date('Y-m-d', $dtstart));
        $datee = strtotime(date('Y-m-d', $dtend));
        
        foreach ($eq_reserv_times as $eq_reserv_time) {
            $users = array_keys((array)json_decode($eq_reserv_time->controluser));
            if (in_array($user->id, $users)) {
                $is_allowed_user = true;
                $ids[] = $eq_reserv_time->id;
            }
            
            $labs = (array)json_decode($eq_reserv_time->controllab);
            if (!in_array($eq_reserv_time->id, $ids) && array_intersect_key(Q("$user lab")->to_assoc('id', 'id'), $labs)) {
                $is_allowed_user = true;
                $ids[] = $eq_reserv_time->id;
            }
            
            $groups = array_keys((array)json_decode($eq_reserv_time->controlgroup));
            foreach ($groups as $group) {
                $group = O('tag', $group);
                if (!in_array($eq_reserv_time->id, $ids) && $group->is_itself_or_ancestor_of($user->group)) {
                    $is_allowed_user = true;
                    $ids[] = $eq_reserv_time->id;
                }
            }
        }
        
        if ($is_allowed_user) {
            return true;
        }
        else {
            foreach ($eq_reserv_times as $eq_reserv_time) {
                $diff = date('d', $dates) - date('d', $datee);
                if ($diff) {
                    $days = [[
                            'dates' => $dates, 
                            'times' => $times,
                            'datee' => $dates, 
                            'timee' => 31507200,
                        ],[
                            'dates' => $datee,
                            'times' => 31507200,
                            'datee' => $datee,
                            'timee' => $timee,
                        ]];
                    if ($diff > 1) for ($i = 1; $i < $diff ; $i++) { 
                        $days[] = [
                            'dates' => Date::next_time($dates, $i),
                            'times' => 31507200,
                            'datee' => Date::next_time($dates, $i),
                            'timee' => 31593599,
                        ];
                    }
                    foreach ($days as $day) {
                        $is_allowed_time = $is_allowed_time || (self::_check_meeting_workingtime_time($eq_reserv_time, $day['dates'], $day['times']) && self::_check_meeting_workingtime_time($eq_reserv_time, $day['datee'], $day['timee']));
                    }
                }
                else {
                    $is_allowed_time = $is_allowed_time || (self::_check_meeting_workingtime_time($eq_reserv_time, $dates, $times) && self::_check_meeting_workingtime_time($eq_reserv_time, $datee, $timee));
                }
            }
        }

        return $is_allowed_time;
    }
    
    static function _check_meeting_workingtime_time($eq_reserv_time, $date, $time) {
        $num = $eq_reserv_time->num;
        $diff1 = date_create(date('Y-m-d', $date));
        $diff2 = date_create(date('Y-m-d', $eq_reserv_time->ltstart));
        $diff = date_diff($diff1, $diff2);
        $days = explode(',', $eq_reserv_time->days);
        switch ($eq_reserv_time->type) {
            case WT_RRule::RRULE_DAILY:
                if ($diff->d % $eq_reserv_time->num == 0) {
                    if ($time >= $eq_reserv_time->dtstart && $time <= $eq_reserv_time->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            case WT_RRule::RRULE_WEEKDAY:
            case WT_RRule::RRULE_WEEKEND_DAY:
            case WT_RRule::RRULE_WEEKLY:
                $diff->w = abs(date('W', $date) - date('W', $eq_reserv_time->dtstart));
                if (($diff->w % $eq_reserv_time->num) == 0 && in_array(date('w', $date), $days)) {
                    if ($time >= $eq_reserv_time->dtstart && $time <= $eq_reserv_time->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            case WT_RRule::RRULE_MONTHLY:
                if (($diff->m % $eq_reserv_time->num) == 0 && in_array(date('d', $date), $days)) {
                    if ($time >= $eq_reserv_time->dtstart && $time <= $eq_reserv_time->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            case WT_RRule::RRULE_YEARLY:
                if (($diff->y % $eq_reserv_time->num) == 0 && in_array(date('m', $date), $days)) {
                    if ($time >= $eq_reserv_time->dtstart && $time <= $eq_reserv_time->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            default:
            	return false;
                break;
        }
        return false;
    }
    
    public static function check_create_time($meeting, $component)
    {
        $user = L('ME');
        $now  = Date::time();

        $dtstart = $component->get('dtstart', true);

        $newStart = $component->dtstart;
        $newEnd   = $component->dtend;

        /*
         *
         * 预约的起始结束时间均不得早于当前时间，因为普通用户无法增加该类过去时间预约
         * Cheng Liu <cheng.liu@geneegroup.com>
         * Release-2.9.5中调整预约限制时间增加限制处理
         */
        if ($newStart && $newStart < $now) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '过去时段不能创建预约!'));
            return false;
        }

        $time_limit = self::get_time_limit($user, $meeting);

        $add_reserv_earliest_limit = $time_limit['add_reserv_earliest_limit'];
        $add_reserv_latest_limit   = $time_limit['add_reserv_latest_limit'];

        list($add_earliest_time, $add_earliest_format) = Date::format_interval($add_reserv_earliest_limit, 'hid');
        $add_earliest_str                              = $add_earliest_time . I18N::T('eq_reserv', Date::unit($add_earliest_format));

        list($add_latest_time, $add_latest_format) = Date::format_interval($add_reserv_latest_limit, 'hid');
        $add_latest_str                            = $add_latest_time . I18N::T('eq_reserv', Date::unit($add_latest_format));

        $judgeEndLimit   = $add_reserv_latest_limit;
        $judgeStartLimit = $add_reserv_earliest_limit;

        /**
         *
         *  $now    $newStart  $judgeEndLimit        $judgeStartLimit      $newEnd
         *  |___________*_____________|______________________|________________*_____________|
         *
         *  创建时间不得超越最早和最晚时间
         *
         */


        // if (($judgeStartLimit != 0 && $newEnd - $now > $judgeStartLimit) || ($judgeEndLimit != 0 && $newStart - $now < $judgeEndLimit)) {
        if (($judgeStartLimit != 0 && $newStart - $now > $judgeStartLimit) || ($judgeEndLimit != 0 && $newStart - $now < $judgeEndLimit)) {
            $message = [];

            if ($add_earliest_str != 0) {
                $message[earliest] = I18N::T('eq_reserv', '此会议室创建预约的最早提前时间是 %start;', [
                    '%start' => $add_earliest_str,
                ]);
            }

            if ($add_latest_str != 0) {
                $message[latest] = I18N::T('eq_reserv', '此会议室最晚提前预约时间是 %end;', [
                    '%end' => $add_latest_str,
                ]);
            }

            if (count($message)) {
                $message[extra] = I18N::T('eq_reserv', '请选择有效时间段!');
                Lab::message(Lab::MESSAGE_ERROR, join("\n", $message));
            }

            return false;
        }

        return true;
    }

    public static function check_edit_time($meeting, $component)
    {
        $user = L('ME');

        $now = Date::time();

        $dtstart = $component->get('dtstart', true);

        if ($component->dtstart && $component->dtstart < $now) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '过去时段不能创建预约!'));
            return false;
        }

        $time_limit                 = self::get_time_limit($user, $meeting);
        $modify_reserv_latest_limit = $time_limit['modify_reserv_latest_limit'];

        list($modify_latest_time, $modify_latest_format) = Date::format_interval($modify_reserv_latest_limit, 'hid');

        /*
         *
         *  $now       $dtstart           $judgeEndLimit
         *  |______________*____________________|
         *
         *  已有的修改预约开始时间在最晚预约时间设定之前
         */

        if ($modify_reserv_latest_limit != 0 && $dtstart - $now < $modify_reserv_latest_limit) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '此预约记录已生效，不可修改!'));
            return false;
        }

        return true;
    }

    public static function check_delete_time($meeting, $component)
    {
        $user = L('ME');

        $now = Date::time();

        $dtstart = $component->get('dtstart', true);

        if ($component->dtstart && $component->dtstart < $now) {
            return false;
        }

        $time_limit                 = self::get_time_limit($user, $meeting);
        $modify_reserv_latest_limit = $time_limit['modify_reserv_latest_limit'];

        list($modify_latest_time, $modify_latest_format) = Date::format_interval($modify_reserv_latest_limit, 'hid');

        /*
         *
         *  $now       $dtstart           $judgeEndLimit
         *  |______________*____________________|
         *
         *  已有的修改预约开始时间在最晚预约时间设定之前
         */

        if ($modify_reserv_latest_limit != 0 && $dtstart - $now < $modify_reserv_latest_limit) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '此预约记录已生效，不可修改!'));
            return false;
        }

        return true;
    }

    public static function get_time_limit($user, $equipment)
    {
        $user = L('ME');
        $root = $equipment->tag_root;

        if ($root->id) {
            $current_tag = Q("$user tag[root=$root]:sort(weight A):limit(1)")->current();

            $labs = Q("$user lab");
            if ($labs->total_count()) {
                $lab_ids = implode(',', $labs->to_assoc('id', 'id'));
                $weight  = $current_tag->weight;
                if ($weight != null) {
                    $tag = Q("lab[id={$lab_ids}] tag[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                    if ($tag->id && $tag->weight < $weight) {
                        $current_tag = $tag;
                    }
                } else {
                    $current_tag = Q("lab[id={$lab_ids}] tag[root=$root]:sort(weight A):limit(1)")->current();
                }
            }

            foreach ($labs as $lab) {
                $group = $lab->group;
                if (!$group->id) {
                    continue;
                }
                $groot = Tag_Model::root('group');
                foreach (Q("tag[root=$root] tag[root=$groot]") as $g) {
                    if (!$g->is_itself_or_ancestor_of($group)) {
                        continue;
                    }
                    $weight = $current_tag->weight;
                    if ($weight != null) {
                        $tag = Q("$g tag[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                        if ($tag->id && $tag->weight < $weight) {
                            $current_tag = $tag;
                        }
                    } else {
                        $current_tag = Q("$g tag[root=$root]:sort(weight A):limit(1)")->current();
                    }
                }
            }

            $group = $user->group;
            if ($group->id) {
                $groot = $group->root;
                if (!$groot->id) {
                    $groot = Tag_Model::root('group');
                }
                foreach (Q("tag[root=$root] tag[root=$groot]") as $g) {
                    if (!$g->is_itself_or_ancestor_of($group)) {
                        continue;
                    }
                    $weight = $current_tag->weight;
                    if ($weight != null) {
                        $tag = Q("$g tag[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                        if ($tag->id && $tag->weight < $weight) {
                            $current_tag = $tag;
                        }
                    } else {
                        $current_tag = Q("$g tag[root=$root]:sort(weight A):limit(1)")->current();
                    }
                }
            }

            //按照weight进行排序只获取最优先匹配的那个Tag
            // TODO: 我觉得这里按name取肯定有BUG！ 但是没时间改了
            if ($current_tag->id) {
                $current_tag = $current_tag->name;
            }
        }

        //如果当前用户有对应用个别预约设置
        $tagged = (array) P($equipment)->get('@TAG', '@');
        if ($tagged && count($tagged[$current_tag])) {
            //tag中存储的，都是以specific_为前缀的
            $reserv_time_limits         = $tagged[$current_tag];
            $add_reserv_earliest_limit  = $reserv_time_limits['specific_add_earliest_limit']; //最早提前预约时间
            $add_reserv_latest_limit    = $reserv_time_limits['specific_add_latest_limit']; //最晚提前预约的时间
            $modify_reserv_latest_limit = $reserv_time_limits['specific_modify_latest_limit']; //最晚提前修改时间
        }

        // 将没有找到的预约设置用仪器的预约设置
        if (is_numeric($add_reserv_earliest_limit) &&
            is_numeric($add_reserv_latest_limit) && is_numeric($modify_reserv_latest_limit)) {
            goto output;
        }
        if (!is_numeric($add_reserv_earliest_limit)) {
            $add_reserv_earliest_limit = $equipment->add_reserv_earliest_limit;
        }
        if (!is_numeric($add_reserv_latest_limit)) {
            $add_reserv_latest_limit = $equipment->add_reserv_latest_limit;
        }
        if (!is_numeric($modify_reserv_latest_limit)) {
            $modify_reserv_latest_limit = $equipment->modify_reserv_latest_limit;
        }

        // 如果还有没有的预约设置则找全局的个别预约设置
        if (is_numeric($add_reserv_earliest_limit) &&
            is_numeric($add_reserv_latest_limit) && is_numeric($modify_reserv_latest_limit)) {
            goto output;
        }
        $tagged = array_filter((array) Lab::get('@TAG'), function ($v, $k) {
            return !!array_filter($v, function ($v, $k) {
                return is_numeric($v);
            }, ARRAY_FILTER_USE_BOTH);
        }, ARRAY_FILTER_USE_BOTH);
        $group = $user->group;
        while ($group->id != $group->root->id) {
            if (!array_key_exists($group->name, $tagged)) {
                $group = $group->parent;
                continue;
            }

            $reserv_time_limits = $tagged[$group->name];
            if (!is_numeric($add_reserv_earliest_limit)) {
                $add_reserv_earliest_limit = $reserv_time_limits['equipment.add_reserv_earliest_limit'];
            }
            if (!is_numeric($add_reserv_latest_limit)) {
                $add_reserv_latest_limit = $reserv_time_limits['equipment.add_reserv_latest_limit'];
            }
            if (!is_numeric($modify_reserv_latest_limit)) {
                $modify_reserv_latest_limit = $reserv_time_limits['equipment.modify_reserv_latest_limit'];
            }
            break;
        }

        // 再没有就用全局的预约设置
        if (is_numeric($add_reserv_earliest_limit) &&
            is_numeric($add_reserv_latest_limit) && is_numeric($modify_reserv_latest_limit)) {
            goto output;
        }
        if (!is_numeric($add_reserv_earliest_limit)) {
            $add_reserv_earliest_limit = Lab::get('equipment.add_reserv_earliest_limit');
        }
        if (!is_numeric($add_reserv_latest_limit)) {
            $add_reserv_latest_limit = Lab::get('equipment.add_reserv_latest_limit');
        }
        if (!is_numeric($modify_reserv_latest_limit)) {
            $modify_reserv_latest_limit = Lab::get('equipment.modify_reserv_latest_limit');
        }

        output:
        return [
            'add_reserv_earliest_limit'  => max(0, $add_reserv_earliest_limit),
            'add_reserv_latest_limit'    => max(0, $add_reserv_latest_limit),
            'modify_reserv_latest_limit' => max(0, $modify_reserv_latest_limit),
        ];
    }

}
