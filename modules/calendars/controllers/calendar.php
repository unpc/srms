<?php

class Calendar_Controller extends Controller
{
    /*
     * @Date:2018-10-10 09:56:34
     * @Author: LiuHongbo
     * @Email: hongbo.liu@geneegroup.com
     * @Description:这里将之前mode='list'的项剔除，只提供日历的周月显示
     */
    public function index($id = 0, $mode = 'week')
    {
        $calendar = O('calendar', $id);
        /*
        NO.TASK#260（guoping.zhang@2010.11.19)
        列表日程权限判断
        NO.BUG#178（guoping.zhang@2010.11.20)
        列表日程时is_allowed_to的客体是calendar对象
         */
        $me = L('ME');
        if (!$me->is_allowed_to('列表事件', $calendar)) {
            return;
        }

        $form = Input::form();
        $this->refresh_history($mode, $form);
        Event::bind('calendar.secondary_tabs.content', 'Calendar::_index_month', 0, 'month');
        Event::bind('calendar.secondary_tabs.content', 'Calendar::_index_week', 0, 'week');
        Event::bind('calendar.secondary_tabs.content', 'Calendar::_index_list', 0, 'list');
        Event::bind('calendar.secondary_tabs.content', 'Calendar::_index_day', 0, 'day');

        $calendar_tabs = Widget::factory('tabs', $calendar);

        $params = Config::get('system.controller_params');
        $mode   = $params[1] ?: 'week';
        $mode = $this->refresh_mode($mode) ? : $mode;

//        $calendar_tabs
//            ->set('class', 'calendar_width fourth_tabs');
        if ($mode == 'week') {
            $calendar_tabs->add_tab('week', [
                'url'    => $calendar->url('week', Input::get()),
                'title'  => I18N::T('calendars', '日历'),
                'weight' => 10,
            ]);
        }
        if ($mode == 'month') {
            $calendar_tabs->add_tab('month', [
                'url'    => $calendar->url('month', Input::get()),
                'title'  => I18N::T('calendars', '日历'),
                'weight' => 20,
            ]);
        }
        if ($mode == 'day' || $mode == 'list') {
            $calendar_tabs->add_tab('day', [
                'url'    => $calendar->url('day', Input::get()),
                'title'  => I18N::T('calendars', '日历'),
                'weight' => 20,
            ]);
        }

        $calendar_tabs->add_tab('list', [
            'url'    => $calendar->url('list', Input::get()),
            'title'  => I18N::T('calendars', '列表'),
            'weight' => 30,
        ])
            ->tab_event('calendar.secondary_tabs.tab')
            ->content_event('calendar.secondary_tabs.content')
            ->set('user', $me)
            ->set('calendar', $calendar)
            ->set('form', $form);

        if ($form['disable_list']) {
            $calendar_tabs->delete_tab('list');
        }

        if ($form['disable_week']) {
            $calendar_tabs->delete_tab('week');
        }

        if ($form['disable_month']) {
            $calendar_tabs->delete_tab('month');
        }

        if ($form['disable_day']) {
            $calendar_tabs->delete_tab('day');
        }

        $calendar_tabs->select($mode);
        $layout                = V('calendar/view');
        $layout->calendar      = $calendar;
        $layout->calendar_tabs = $calendar_tabs;
        $layout->hidden_tabs   = $form['hidden_tabs'];
        $layout->form_token   = $form['form_token'];

        $this->add_css('preview calendars:common');
        $this->add_js('preview');
        echo $layout;
    }

    private function refresh_history($mode, $form) {
        if ($_SESSION['first_view']) {
			unset($_SESSION['first_view']);
		} else {
            $_SESSION['calendar_index_form'] = $form;
		}
        $_SESSION['mode'] = $mode;
	}

	private function refresh_mode($mode) {
		$_SESSION['mode'] ? $mode = $_SESSION['mode'] : '';
		return $mode;
	}
}

class Calendar_AJAX_Controller extends AJAX_Controller
{
    private $donot_check_time = false;

    public function index_month_insert_component_dblclick($id = 0)
    {
        $this->donot_check_time = true;
        $this->index_insert_component($id, 'month');
    }

    public function index_just_show_insert_component_click($id = 0)
    {
        $form = Form::filter(Input::form());
        //点击添加按钮添加
        $calendar            = O('calendar', $form['id']);
        $component           = O('cal_component');
        $component->calendar = $calendar;

        $me = L('ME');
        if ($me->is_allowed_to('添加事件', $calendar)) {
            $now                = time();
            $component->dtstart = $now;
            $component->dtend   = $now;

            // $title = Event::trigger('calendar.insert_component.title', $calendar) ?: '';
            $title = '添加预约';

            if(!isset($form['reserv_form_token'])) $form['reserv_form_token'] = uniqid();
            EQ_Reserv_Access::cache_access_token($form['reserv_form_token'],['uid'=>$me->id,'eqid'=>$calendar->parent->id,'ctime'=>time()]);
            EQ_Reserv::cache_reserv_log($form['reserv_form_token'],'init_form',$form);

            JS::dialog(V('calendar/component_form', ['component' => $component, 'calendar' => $calendar, 'form' => $form]), ['title' => $title]);
        } else {
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages) > 0) {
                JS::alert(implode("\n", $messages));
            } else {
                JS::alert(I18N::T('calendars', '您无权添加事件'));
            }
        }
    }

    public function index_insert_component($id = 0, $mode = 'week')
    {
        $form       = $this->form ?: Form::filter(Input::form());
        $this->form = null;

        $calendar            = O('calendar', $id);
        $component           = O('cal_component');
        $component->calendar = $calendar;

        $component->type    = $form['type'];
        $component->dtstart = $form['dtstart'];
        $component->dtend   = $form['dtend'];

        /*
        NO.TASK#260（guoping.zhang@2010.11.19)
        添加日程的权限判断
        NO.BUG#178（guoping.zhang@2010.11.20)
        判断用户是否有为某calendar对象添加schedule事件的权限，is_allowed_to的客体是calendar对象
        NO.BUG#212(xiaopei.li@2010.12.03)
        修正重叠时也打开新建component对话框的bug
        bugfix  添加预约权限判断，
        '添加事件'-$calendar是判断机主、通过培训等；
        '添加'-$component是run脚本判断预约规则
         */
        $me = L('ME');
        if ($me->is_allowed_to('添加事件', $calendar) && $me->is_allowed_to('添加', $component)) {
            $title = Event::trigger('calendar.insert_component.title', $calendar) ?: '';
            $form['reserv_form_token'] = 'reserv_'.uniqid();
            EQ_Reserv_Access::cache_access_token($form['reserv_form_token'],['uid'=>$me->id,'eqid'=>$calendar->parent->id,'ctime'=>time()]);
            EQ_Reserv::cache_reserv_log($form['reserv_form_token'],'init_form',$form);
            // 此处为reserv的弹出层代码
            JS::dialog(V('calendar/component_form', ['form' => $form, 'component' => $component, 'calendar' => $calendar, 'mode' => $mode]), ['title' => $title]);
        } else {
            $confirms = Lab::confirms(Lab::MESSAGE_ERROR);
            if (count($confirms)) {
                JS::dialog(V('confirm', [
                        'confirms'=> $confirms // mssage/url/confirm
                ]), ['title'=> I18N::T('calendars', '您无权添加事件')]);
            } else {
                $messages = Lab::messages(Lab::MESSAGE_ERROR);
                if (count($messages) > 0) {
                    JS::alert(implode("\n", $messages));
                } else {
                    JS::alert(I18N::T('calendars', '您无权添加事件'));
                }
            }
        }
    }

    public function index_component_dblclick($id = 0, $mode = 'week')
    {
        $this->index_select_component($id, $mode);
    }

    public function index_list_edit_component_click($id = 0, $mode = 'list')
    {
        $this->index_select_component($id, $mode);
    }

    public function index_edit_component_click($id = 0, $mode = 'list')
    {
        $this->index_select_component($id, $mode);
    }

    public function index_select_component($id = 0, $mode = 'week')
    {
        $form = $this->form ?: Form::filter(Input::form());

        $this->form = null;

        $component = O('cal_component', $form['id'] ?: $form['component_id']);
        $calendar  = O('calendar', $form['calendar_id']);
        /*
        NO.TASK#260（guoping.zhang@2010.11.19)
        查看、修改日程的权限判断
        NO.BUG#292(guoping.zhang@2010.12.24)
        普通用户的实验室的帐号余额不足时，不能修改预约
         */
        $me = L('ME');
        /*
         *   2016-1-29 unpc BUG#10664
         *   判断能否修改的时候都应该进行原始数据的判断，否则可能造成之前判断和之后判断不一致的现象发生
         *   我们可以再判断之后将对应的数值再补充到component中进行显示
         */
        $component->dtstart = $form->no_error ? $form['dtstart'] : $component->dtstart;
        $component->dtend   = $form->no_error ? $form['dtend'] : $component->dtend;

        if ($me->is_allowed_to('修改事件', $component->calendar) && $me->is_allowed_to('修改', $component)) {
            $component->dtstart = $form['dtstart'];
            $component->dtend   = $form['dtend'];

            $title = Event::trigger('calendar.edit_component.title', $calendar) ?: I18N::T('calendars', '修改事件');
            JS::dialog(V('calendars:calendar/component_form', ['component' => $component, 'calendar' => $calendar, 'form' => $form, 'mode' => $mode]), ['title' => $title]);
        } elseif ($me->is_allowed_to('查看', $component)) {
            $component->dtstart = $component->get('dtstart', true);
            $component->dtend   = $component->get('dtend', true);
            /*
            BUG #407 (Cheng.liu@2011.03.25)
            将错误消息直接塞到info显示页面中显示，避免alert弹出消息影响用户视觉感
             */
            $title = Event::trigger('calendar.select_view_component.title', $calendar) ?: I18N::T('calendars', '查看事件');
            JS::dialog(V('calendars:calendar/component_info', ['component' => $component]), ['title' => $title]);
        }
    }

    public function index_update_component($id = 0, $mode = 'week')
    {
        $form               = Input::form();
        $calendar           = O('cal_calendar', $id);
        $component          = O('cal_component', $form['id']);
        $component->dtstart = $form['dtstart'];
        $component->dtend   = $form['dtend'];

        /*
        NO.TASK#260（guoping.zhang@2010.11.19)
        修改日程的日期权限判断
        NO.BUG#292(guoping.zhang@2010.12.24)
        普通用户的实验室的帐号余额不足时，不能修改预约
         */
        $me = L('ME');
        if ($me->is_allowed_to('修改事件', $component->calendar) && $me->is_allowed_to('修改', $component)) {
            //guoping.zhang@2011.01.15
            #ifdef(calendars.enable_repeat_event)
            if ($GLOBALS['preload']['calendars.enable_repeat_event']) {
                /*
                NO.TASK#262 (xiaopei.li@2010.11.22)
                component修改后应取消其与cal_rrule的关系
                 */
                $component->cal_rrule = 0;
            }
            #endif

            $component->save();
        } else {
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages) > 0) {
                JS::alert(implode("\n", $messages));
            }
        }

        $this->_show_week_component($component, $calendar);
    }

    private function _show_week_component($component, $calendar = null)
    {

        $component = ORM_Model::refetch($component);
        $form      = Input::form();
        $rel       = $form['cal_week_rel'];
        $calendar  = $calendar->id ? $calendar : $component->calendar;

        $current_calendar = O('calendar', $form['calendar_id']);
        $cdata            = json_encode([
            'id'      => (int) $component->id,
            'dtStart' => (int) $component->get('dtstart', true),
            'dtEnd'   => (int) $component->get('dtend', true),
            'color'   => $component->color($calendar),
            'content' => $component->render('calendar/component_content', true, ['current_calendar' => $current_calendar, 'mode' => 'week']),
        ]);

        JS::run("(new Q.Calendar.Week($('{$rel}')[0])).getComponent({$cdata}).render();");
    }

    private function _show_month_component($component, $calendar = null)
    {
        JS::refresh();
    }

    private function _show_list_component($component, $calendar = null)
    {
        JS::refresh();
    }

    private function _delete_list_component($component)
    {
        $browser_id = Input::form('browser_id');
        JS::refresh("#{$browser_id}");
    }

    private function _delete_month_component($component)
    {
        $browser_id = Input::form('browser_id');
        JS::refresh("#{$browser_id}");
    }

    private function _delete_week_component($component)
    {
        $rel = Input::form('cal_week_rel');

        $cdata = json_encode([
            'id' => (int) $component->id,
        ]);

        JS::run("(new Q.Calendar.Week($('{$rel}')[0])).getComponent({$cdata}).remove();");
    }
    /*BUG 4746*/
    public function index_delete_component_click($id = 0, $mode = 'week')
    {
        $form      = Form::filter(Input::form());
        $component = O('cal_component', $form['component_id']);

        $me = L('ME');

        if ($me->is_allowed_to('删除', $component)) {

            //如果事件返回TRUE，则停止删除
            if (Event::trigger('calendar.component_form.before_delete', $form, $component)) {
                return false;
            } else {
                $ret = JS::confirm(I18N::T('calendars', '你确定要删除吗?请谨慎操作!'));
                if ($ret) {
                    $overrided = Event::trigger('calendar.component_form.delete', $form, $component);
                    if ($overrided || $component->delete()) {
                        Event::trigger('calendar.component_form.after_delete', $form, $component);
                        JS::refresh();
                    } else {
                        JS::alert(I18N::T('calendars', '删除失败!'));
                    }
                }
            }
        } else {
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages) > 0) {
                JS::alert(implode("\n", $messages));
            }
        }
    }

    /*
     * @Description:提交后请求的方法
     */
    public function index_component_form_submit($id = 0, $mode = 'week')
    {
        $form = Form::filter(Input::form());

        if ($id == '0') {
            $id = $form['calendar_id'];
        }

        $component = O('cal_component', $form['component_id']);
        $me        = L('ME');

        $mode = $form['mode'] ?: $mode;
        $mode = ($mode != 'month' && $mode != 'list') ? 'week' : $mode;
        if ($form['submit'] == 'delete') {
            if ($me->is_allowed_to('删除', $component)) {
                // 如果事件返回TRUE，则停止删除
                if (Event::trigger('calendar.component_form.before_delete', $form, $component)) {
                    return false;
                } else {
                    $ret = JS::confirm(I18N::T('calendars', '你确定要删除吗?请谨慎操作!'));
                    if ($ret) {
                        $overrided = Event::trigger('calendar.component_form.delete', $form, $component);
                        if ($overrided || $component->delete()) {
                            JS::close_dialog();
                            $func = "_delete_{$mode}_component";
                            $this->$func($component);
                            Event::trigger('calendar.component_form.after_delete', $form, $component);
                            // JS::refresh();
                        } else {
                            JS::alert(I18N::T('calendars', '删除失败!'));
                        }
                    }
                }
            } else {
                $messages = Lab::messages(Lab::MESSAGE_ERROR);
                if (count($messages) > 0) {
                    JS::alert(implode("\n", $messages));
                }
            }
        } elseif ($form['submit'] == 'save') {
            $calendar = O('calendar', $id);

            $component->calendar    = !$component->calendar->parent->id ? $calendar : $component->calendar;
            $component->organizer   = $form['organizer'] ? O('user', $form['organizer']) : $component->organizer;
            $component->organizer   = $component->organizer->id ? $component->organizer : $me;
            $component->name        = $form['name'];
            $component->description = $form['description'];
            $component->type        = isset($form['type']) ? $form['type'] : $component->type;

            // 调整位置
            $component->dtstart = $form['dtstart'];
            $component->dtend   = $form['dtend'];

            // 数据验证
            Event::trigger('calendar.component_form.submit', $form, $component, ['calendar' => $calendar]);

            //预约次数&时长限制
            if (Module::is_installed('eq_time_counts')) {
    			$parent = $component->calendar->parent;
    			if ($parent->name() == 'equipment') $equipment = $parent;
				$check_time_counts = Event::trigger('eq_time_counts.check_time_counts', $equipment, $component);
                if ($check_time_counts['allow'] === false) {
    				$form->set_error('dtend', I18N::T('calendars', $check_time_counts['msg']));
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_time_counts', $check_time_counts['msg']));
                }
            }

			//预约次数&时长限制
            if (Module::is_installed('eq_time_counts')) {
				$parent = $component->calendar->parent;
				if ($parent->name() == 'equipment') $equipment = $parent;
				$check_time_counts = Event::trigger('eq_time_counts.check_time_counts', $equipment, $component);
                if ($check_time_counts['allow'] === false) {
					$form->set_error('dtend', I18N::T('calendars', $check_time_counts['msg']));
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_time_counts', $check_time_counts['msg']));
                }
            }

            $dtstart = $form['dtstart'];
            $dtend = $form['dtend'];

            if ($form->no_error && $dtstart == $dtend) {
                $form->set_error('dtend', I18N::T('calendars', '开始、结束时间重叠，请输入正确的时间!'));
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('calendars', '开始、结束时间重叠，请输入正确的时间!'));
            }

            if ($dtstart > $dtend) {
                list($dtstart, $dtend) = [$dtend, $dtstart];
            }

            // 时间赋值的代码调整顺序，以便在eq_reserv中进行块状限时调整
            $component->dtstart = $dtstart;
            $component->dtend = $dtend;

            if ($form->no_error) {
                $msg = Event::trigger('calendar.component_form.attempt_submit.log', $form, $component, $calendar);
                if ( !$msg ) {
                    if (!$component->id) {
                        $msg = sprintf('[calendars] %s[%d] 于 %s 尝试创建新的预约!', $me->name, $me->id, Date::format(Date::time()));
                    }
                    else {
                        $msg = sprintf('[calendars] %s[%d] 于 %s 尝试修改预约[%d]!', $me->name, $me->id, Date::format(Date::time()), $component->id);
                    }
                    Log::add($msg, 'journal');
                }
                
                $mutex_file = Config::get('system.tmp_dir').Misc::key('calendar', $calendar->id);
                $fp = fopen($mutex_file, 'w+');
                if ($fp) {
                    //独享、不阻塞锁
					if (flock($fp, LOCK_EX | LOCK_NB)) {
                        $can_save = $component->id ? $me->is_allowed_to('修改', $component) : $me->is_allowed_to('添加', $component);
                        if ($can_save) {
                            Config::set('debug.database', 1);
							$component->save();
                            Cache::L('YiQiKongReservAction', true); // bug: 21835 用户预约后发了两条提醒消息
                            if (isset($form['count'])) $form['extra_fields']['count'] = $form['count'];
                            Event::trigger('calendar.component_form.post_submit', $component, $form);

                            if (!$component->id) {
								Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 成功创建新的预约[%component_id]!', array(
										'%user_name' => $me->name,
										'%user_id' => $me->id,
										'%date' => Date::format(Date::time()),
										'%component_id' => $component->id,
								)), 'journal');
							}
							else {
								Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 成功修改预约[%component_id]!', array(
										'%user_name' => $me->name,
										'%user_id' => $me->id,
										'%date' => Date::format(Date::time()),
										'%component_id' => $component->id,
								)), 'journal');
							}

                            $merge = L('MERGE_COMPONENT_ID');
                            if ($merge) {
                                $component = O('cal_component', $merge);
                                Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 合并预约[%component_id], 时间为[%dtstart ~ %dtend]', array(
                                        '%user_name' => $me->name,
                                        '%user_id' => $me->id,
                                        '%date' => Date::format(Date::time()),
                                        '%component_id' => $merge,
                                        '%dtstart' => Date::format($component->dtstart),
                                        '%dtend' => Date::format($component->dtend)
                                        )), 'journal');

                                $ids = join(',', (array)L('REMOVE_COMPONENT_IDS'));

                                Cache::L('MERGE_COMPONENT_ID', NULL);
                                Cache::L('REMOVE_COMPONENT_IDS', NULL);
                            }

							$func = "_show_{$mode}_component";
							$this->$func($component, $calendar);
						}
						else {
							$messages = Lab::messages(Lab::MESSAGE_ERROR);
							if (count($messages) > 0) {
								JS::alert(implode("\n", $messages));
							}
						}
					    JS::close_dialog();
						flock($fp, LOCK_UN);
						fclose($fp);
                    }
                    else {
						JS::alert(I18N::T('calendars', '系统数据繁忙，请稍后重试!'));
					}
                }
            } 
            else {
                $form['id'] = $component->id;
                $this->form = $form;
                if ($component->id) {
                    $this->index_edit_component_click($id, $mode);
                } else {
                    $this->index_insert_component($id, $mode);
                }
            }
        }
    }

    public function index_day_components_get()
    {
        $this->cache_header();
		$form = Input::form();
		$uuid = $form['dtstart'].$form['dtend'].$form['calendar'].$form['start'].$form['step'].$form['user_id'];
		$cache_data = $this->get_components_cache($uuid);
		if ($cache_data) {
			Output::$AJAX['components'] = json_decode($cache_data);
			return;
		}
        
        $calendar = O('calendar', (int) $form['calendar']);
        $user = O('user', (int) $form['user_id']);

        if (!L('ME')->is_allowed_to('列表事件', $calendar)) {
            return;
        }

        $dtstart = (int) $form['dtstart'];
        $dtend   = (int) $form['dtend'];

        if ($dtend < $dtstart || getdate($dtend)['mday'] > $dtstart + 608400) {
            return;
        }

        //hook here
        if ($calendar->id) {
            $components = Q("cal_component[calendar=$calendar][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
        } else {
            $components = Q("cal_componet:empty");
        }

        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form);
        if ($new_components) {
            $components = $new_components;
        }

        $cdata = [];
        if ($form['start'] || $form['step']) {
            if (is_object($components)) {
                $components = $components->limit($form['start'], $form['step']);
            } else {
                $components = array_slice($components, $form['start'], $form['step']);
            }
        }
        
        foreach ($components as $component) {
            if ($component->dtend < $component->dtstart) {
                continue;
            }
            $reserv    = O('eq_reserv', ['component' => $component]);
            $equipment = $component->calendar->parent;
            if ($equipment->control_mode && $reserv->id && $reserv->dtend <= Date::time() && $reserv->status == EQ_Reserv_Model::PENDING) {
                $record         = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();
                $reserv->status = $reserv->get_status(true, $record);
            }
            if ($component->type == '3') {
                $color = 1;
            }

            $cdata[] = [
                'id' => $component->id,
                'equipment_id'  => $reserv->equipment->id,
                'dtStart'  => (int) $component->dtstart,
                'dtEnd'    => (int) $component->dtend,
                'color'    => $color ?: $reserv->status,
                'calendar' => $calendar,
                'extra_class' => (int) $component->dtend < Date::time() ? "calendar_opacity_5":"calendar_opacity_9",
                'content'  => $component->render('calendars:calendar/component_content', true, ['current_calendar' => $calendar, 'mode' => 'day']),
            ];
            unset($color);
        }
        $cdata = array_merge($cdata, Event::trigger('calendar.cdata.get', $calendar, $dtstart, $dtend, $form) ? : []);
		$this->set_components_cache($uuid, json_encode($cdata));
        Output::$AJAX['components'] = $cdata;
    }

    public function index_week_components_get()
    {
        $this->cache_header();
		$form = Input::form();
		//本来获取公共可见的预约数据没问题 但是下面的calendar.cdata.get获取工作时间块是根据不同用户来的 所以再拼个用户id
		$me_id = (int) L('ME')->id;
		$uuid = $form['dtstart'].$form['dtend'].$form['calendar'].$form['start'].$form['step'].$me_id;
		$cache_data = $this->get_components_cache($uuid);
		if ($cache_data) {
			Output::$AJAX['components'] = json_decode($cache_data);
			return;
		}
        
        $calendar = O('calendar', $form['calendar']);

        if (!L('ME')->is_allowed_to('列表事件', $calendar)) {
            return;
        }

        $dtstart = (int) $form['dtstart'];
        $dtend   = (int) $form['dtend'];

        if ($dtend < $dtstart || getdate($dtend)['mday'] > $dtstart + 608400) {
            return;
        }

        //hook here
        if ($calendar->id) {
            $components = Q("cal_component[calendar=$calendar][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
        } else {
            $components = Q("cal_componet:empty");
        }

        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form);
        if ($new_components) {
            $components = $new_components;
        }

        $cdata = [];
        if ($form['start'] || $form['step']) {
            if (is_object($components)) {
                $components = $components->limit($form['start'], $form['step']);
            } else {
                $components = array_slice($components, $form['start'], $form['step']);
            }
        }
        foreach ($components as $component) {
            if ($component->dtend < $component->dtstart) {
                continue;
            }
            $reserv    = O('eq_reserv', ['component' => $component]);
            $object = $component->calendar->parent;
            if ($object->name() == 'equipment') {
                if ($equipment->control_mode && $reserv->id && $reserv->dtend <= Date::time() && $reserv->status == EQ_Reserv_Model::PENDING) {
                    $record         = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();
                    $reserv->status = $reserv->get_status(true, $record);
                }
            }

            $color = $component->color($calendar);

            $cdata[] = [
                'id'       => $component->id,
                'dtStart'  => (int) $component->dtstart,
                'dtEnd'    => (int) $component->dtend,
                'color'    => $color,
                'calendar' => $calendar,
                'extra_class' => (int) $component->dtend < Date::time() ? "calendar_opacity_5":"calendar_opacity_9",
                'content'  => $component->render('calendars:calendar/component_content', true, ['current_calendar' => $calendar, 'mode' => 'week']),
            ];
            unset($color);
        }
        $cdata = array_merge($cdata, Event::trigger('calendar.cdata.get', $calendar, $dtstart, $dtend, $form) ? : []);
		$this->set_components_cache($uuid, json_encode($cdata));
        Output::$AJAX['components'] = $cdata;
    }

    public function index_month_components_get()
    {
        $form = Input::form();
        $uuid = $form['dtstart'].$form['dtend'].$form['calendar'].$form['container'];
		$cache_data = $this->get_components_cache($uuid);
		if ($cache_data) {
			Output::$AJAX[$form['container']] = $cache_data;
			return;
		}
        $calendar = O('calendar', $form['calendar']);
        if (!L('ME')->is_allowed_to('列表事件', $calendar)) {
            return;
        }

        $dtstart = (int) $form['dtstart'];
        $dtend   = (int) $form['dtend'];
        if ($dtend < $dtstart || $dtend > $dtstart + 608400) {
            return;
        }

        //hook here
        if ($calendar->id) {
            $components = Q("cal_component[calendar=$calendar][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
        } else {
            $components = Q("cal_componet:empty");
        }

        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form);
        if ($new_components) {
            $components = $new_components;
        }

        $this->set_components_cache($uuid, (string) V('calendars:calendar/month_day', ['components'=>$components, 'calendar'=>$calendar]));
        Output::$AJAX[$form['container']] = (string) V('calendars:calendar/month_day', ['components' => $components, 'calendar' => $calendar]);
    }

    //guoping.zhang@2011.01.15
    #ifdef(calendars.enable_repeat_event)
    /*
    NO.TASK#262 (xiaopei.li@2010.11.22)
     */
    public function index_rrule_click()
    {
        $form         = Input::form();
        $component_id = $form['component_id'];
        $component    = O('cal_component', $component_id);
        $me           = L('ME');
        if (!$me->is_allowed_to('添加重复规则', $component->calendar)) {
            return;
        }
        JS::dialog(V('calendar/rrule_form', ['component' => $component]), ['title' => I18N::T('eq_reserv', '重复预约')]);
    }

    public function index_rrule_submit()
    {
        $form = Form::filter(Input::form());

        $submit       = $form['submit'];
        $component_id = $form['component_id'];
        $component    = O('cal_component', $component_id);
        $rrule        = $component->cal_rrule;
        $me           = L('ME');

        if ($submit == 'save') {
            if (!$me->is_allowed_to('添加重复规则', $component->calendar)) {
                return;
            }

            if (!$me->is_allowed_to('添加事件', $component->calendar)) {
                return;
            }

            $dtfrom = $form['dtfrom'];
            $dtto   = $form['dtto'];
            if ($dtto <= $dtfrom) {
                JS::alert(I18N::T('calendars', '结束日期需晚于起始日期'));
                return;
            }

            if ($form['cal_rtype'] == TM_RRule::RRULE_WEEKLY
                && !in_array('on', $form['week_day'])) {
                $form->set_error('week_day', I18N::T('calendars', '请选择星期'));
                JS::dialog(V('calendar/rrule_form', ['component' => $component, 'form' => $form]));
                return;
            }

            $rule = $this->_parse_form_to_rule($form);

            if (!$rrule->id
                || $rule != $rrule->rule
                || $dtfrom != $rrule->dtfrom
                || $dtto != $rrule->dtto) {
                // new rrule

                $rrule = O('cal_rrule');

                $rrule->rule   = $rule;
                $rrule->dtfrom = $dtfrom;
                $rrule->dtto   = $dtto;

                $rrule->save();
            }

            $messages = $rrule->sync_components($component, $rrule->dtfrom, $rrule->dtto);
        } else {
            if ($submit == 'delete_all') {
                $dtfrom = $rrule->dtfrom;
                $dtto   = $rrule->dtto;
            } elseif ($submit == 'delete_following') {
                $dtfrom = $component->dtstart;
                $dtto   = $rrule->dtto;
            } else {
                JS::alert(T('非法输入'));
                return;
            }

            $ret = JS::confirm(T('您确定要删除吗?请谨慎操作!'));
            if (!$ret) {
                return;
            }

            $messages = $rrule->delete_components($dtfrom, $dtto);
            $this->_delete_rrule_if_empty($rrule);
        }

        JS::refresh();

        if (!count($messages)) {
            //JS::alert(implode("\n", $messages));
        }
    }

    private function _parse_form_to_rule($form)
    {
        $rule = [];

        $rule['rnum']  = $form['rnum'];
        $rule['rtype'] = $form['cal_rtype'];
        if ($rule['rtype'] == TM_RRule::RRULE_WEEKLY) {
            $week_days = [];

            foreach ($form['week_day'] as $key => $value) {
                if ($value == 'on') {
                    $week_days[] = $key;
                }
            }

            sort($week_days, SORT_NUMERIC);

            $rule['rrule'][] = $week_days;
        }

        return json_encode($rule);
    }

    private function _delete_rrule_if_empty($rrule)
    {
        $selector        = "cal_component[cal_rrule=$rrule]";
        $rest_components = Q($selector);
        if (!count($rest_components)) {
            $rrule->delete();
        }
    }
    #endif

    public function index_week_lines_get()
    {
        $form     = Input::form();
        $calendar = O('calendar', $form['calendar']);

        if (!L('ME')->is_allowed_to('列表事件', $calendar)) {
            return;
        }

        $dtstart = (int) $form['dtstart'];
        $dtend   = (int) $form['dtend'];

        if ($dtend < $dtstart || $dtend > $dtstart + 608400) {
            return;
        }

        $lines = (array) Event::trigger('calendar.lines.get', $calendar, $dtstart, $dtend, $form);

        $ldata = [];

        foreach ((array) $lines as $line) {
            if (!$line->time) {
                continue;
            }

            $ldata[] = [
                'id'         => $line->id,
                'time'       => (int) $line->time,
                'color_type' => $line->color_type,
                'view'       => $line->view,
            ];
        }

        Output::$AJAX['lines'] = $ldata;
    }

    public function index_refreshComponent_click()
    {
        $_f   = Input::form();
        $form = json_decode($_f['form'], true);

        if ($form['mode'] == 'week') {
            $component = O('cal_component', $_f['component_id']);
            $component = ORM_Model::refetch($component);
            $rel       = $form['cal_week_rel'];
            $calendar  = $calendar->id ? $calendar : $component->calendar;

            $reserv    = O('eq_reserv', ['component' => $component]);
            $equipment = $component->calendar->parent;
            if ($equipment->control_mode && $reserv->id && $reserv->dtend <= Date::time() && $reserv->status == EQ_Reserv_Model::PENDING) {
                $record         = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();
                $reserv->status = $reserv->get_status(true, $record);
            }

            $current_calendar = O('calendar', $form['calendar_id']);
            if ($component->type == '3') {
                $color = 1;
            }

            $cdata = json_encode([
                'id'      => (int) $component->id,
                'dtStart' => (int) $component->get('dtstart', true),
                'dtEnd'   => (int) $component->get('dtend', true),
                'color'   => $color ?: $reserv->status,
                'content' => $component->render('calendar/component_content', true, ['current_calendar' => $current_calendar, 'mode' => 'week']),
            ]);

            JS::run("(new Q.Calendar.Week($('{$rel}')[0])).getComponent({$cdata}).render();");

            $pop_up = Event::trigger('reserv_success_back_pop_up', $component->calendar->parent);
            
            if ($pop_up) JS::dialog($pop_up, ['title' => I18N::T('equipments', '仪器预约提醒')]);

            if ($_f['merge_component_id']) {
                foreach ((array) explode(',', $_f['merge_component_id']) as $id) {
                    $cdata = json_encode([
                        'id' => $id,
                    ]);
                    JS::run("(new Q.Calendar.Week($('{$rel}')[0])).getComponent({$cdata}).remove();");
                }
            }
        } else {
            JS::refresh();
        }
    }

    public function index_reservComponentFailed_click()
    {
        $form                 = Input::form();

        $class                = '#' . $form['uuid'];
        $parent               = O(H($form['parentName'] ?: 'equipment'), (int) $form['parentId']);
        Output::$AJAX[$class] = [
            'data' => (string) V('calendar/component_form_fail', [
                'errorMsg' => H($form['errorMsg']),
                'parent'   => $parent,
            ]),
            'mode' => 'replace',
        ];
    }

    public function index_socket_auth()
    {
        $form   = Input::form();
        $result = [
            'authed' => false,
        ];

        if (isset($form['code'])) {
            $me     = L('ME');
            $ticket = Calendar::decrypt(base64_decode($form['code']), 'code');
            $ticket = json_decode($ticket, true);
            if ($ticket['id'] == $me->gapper_id || $ticket['id'] == (LAB_ID . '_' . $me->id)) {
                $result = [
                    'authed' => true,
                    'code'   => Calendar::encrypt([
                        'id'    => $me->gapper_id ?: (LAB_ID . '_' . $me->id),
                        'email' => $me->email,
                    ], 'reserve'),
                ];
            }
        }
        Output::$AJAX['result'] = $result;
    }

    private function get_components_cache($uuid)
    {
        $cache = Cache::factory('redis');
        if ($cache->get($uuid)) {
            return $cache->get($uuid);
        } else {
            return false;
        }
    }

    private function set_components_cache($uuid, $data)
    {
        $cache = Cache::factory('redis');
        $cache->set($uuid, $data, 5);
    }

    public function index_permission_check_click($id=0, $mode='week')
    {
        $form = $this->form ? : Form::filter(Input::form());

        $calendar = O('calendar', $form['id']);
        if ($calendar->parent->name() !== 'equipment') {
            return;
        }

        JS::dialog(V('calendar/permission_check', [
            'calendar'=> $calendar
        ]), ['title'=> I18N::T('calendars', '预约"%name"的资格检查', [
            '%name' => $calendar->parent->name
        ])]);
    }
}
