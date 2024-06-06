<?php

class Schedule
{

    const TYPE_LABMEETING  = 0;
    const TYPE_JOURNALCLUB = 1;
    const TYPE_OTHERS      = 2;
    const TYPE_REPORT      = 3;
    public static function get_path($type = 'upload', $str)
    {
        if ($type == 'upload') {
            return LAB_PATH . "upload/cal_component/schedule/" . $str;
        } elseif ($type == 'tmp') {
            return LAB_PATH . "upload/cal_component/schedule/user/" . $str;
        } else {
            return LAB_PATH . $str;
        }
    }

    public static function setup()
    {
        Event::bind('calendar.components.get', 'Schedule::calendar_components_get');
    }

    public static function profile_setup()
    {
        Event::bind('profile.view.tab', 'Schedule::calendar_profile_tab', 10, 'calendar');
    }

    public static function calendar_profile_tab($e, $tabs)
    {
        $user     = $tabs->user;
        $calendar = O('calendar', ['parent' => $user, 'type' => 'schedule']);
        if (!$calendar->id) {
            $calendar->parent = $user;
            $calendar->name   = I18N::T('schedule', '%user的日程安排', ['%user' => $user->name]);
            $calendar->type   = 'schedule';
            $calendar->save();
        }
        if (!L('ME')->is_allowed_to('列表事件', $calendar)) {
            return;
        }

        Event::bind('profile.view.content', 'Schedule::calendar_profile_content', 10, 'calendar');
        $tabs
            ->add_tab('calendar', [
                'url'   => $user->url('calendar'),
                'title' => I18N::HT('schedule', '日程|:tab'),
            ]);
    }

    public static function calendar_profile_content($e, $tabs)
    {
        $user = $tabs->user;

        $calendar = O('calendar', ['parent' => $user, 'type' => 'schedule']);

        $tabs->content = V('schedule:calendar', ['calendar' => $calendar]);
    }

    public static function calendar_components_get($e, $calendar, $components, $dtstart, $dtend)
    {
        if (!$calendar->id || strtolower($calendar->parent->name()) !== 'user' || $calendar->type != 'schedule') {
            return;
        }

        $user       = $calendar->parent;
        $components = Q("cal_component[calendar=$calendar][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)");

        //speaker
        foreach (Q("schedule_speaker[user=$user] cal_component[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D).component") as $c) {
            $components[$c->id] = $c;
        }
        //attendee_user
        foreach (Q("sch_att_user[user=$user] cal_component[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D).component") as $c) {
            $components[$c->id] = $c;
        }
        //attendee_group

        $arr = array_map(function ($a) {
            return $a[0];
        }, (array) $user->group->path);
        $group_ids = implode(',', $arr);

        if ($group_ids) {
            foreach (Q("sch_att_group[group_id=$group_ids] cal_component[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D).component") as $c) {
                $components[$c->id] = $c;
            }
        }
        //attendee_role
        $roles = $user->roles();
        foreach ($roles as $key => $value) {
            foreach (Q("sch_att_role[role_id={$key}] cal_component[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D).component") as $c) {
                $components[$c->id] = $c;
            }
        }

        $vfreebusy = Cal_Component_Model::TYPE_VFREEBUSY; //非预约时段的预约没有必要显示在个人日程中
        foreach (Q("cal_component[organizer=$user][type!={$vfreebusy}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D))") as $c) {
            $components[$c->id] = $c;
        }

        $e->return_value = $components;
    }

    public static function prerender_component($e, $view)
    {
        $component = $view->component;
        $parent    = $component->calendar->parent;
        $pname     = $parent->name();
        if ($pname == 'lab') {

            $form = $view->component_form;

            $form['subtype'] = [
                'label'         => I18N::T('schedule', '日程类型'),
                'default_value' => [
                    'value' => $view->component->subtype,
                    'types' => [
                        Schedule::TYPE_LABMEETING  => I18N::T('schedule', '组会'),
                        Schedule::TYPE_JOURNALCLUB => I18N::T('schedule', '文献讨论'),
                        Schedule::TYPE_REPORT      => I18N::T('schedule', '学术报告'),
                        Schedule::TYPE_OTHERS      => I18N::T('schedule', '其他'),
                    ],
                ],
                'path'          => [
                    'form' => 'schedule:calendar/component_form/',
                    'info' => 'schedule:calendar/component_info/',
                ],
                'weight'        => 9,
            ];

            $form['speaker'] = [
                'label'  => I18N::T('schedule', '报告人'),
                'path'   => [
                    'form' => 'schedule:calendar/component_form/',
                    'info' => 'schedule:calendar/component_info/',
                ],
                'weight' => 10,
            ];
            $form['attendee'] = [
                'label'  => I18N::T('schedule', '参与者'),
                'path'   => [
                    'form' => 'schedule:calendar/component_form/',
                ],
                'weight' => 11,
            ];
            $form['allow_attendee_download_attachments'] = [
                'label'  => null,
                'path'   => [
                    'form' => 'schedule:calendar/component_form/',
                ],
                'weight' => 99,
            ];
            $form['allow_download_attachments'] = [
                'label'  => null,
                'path'   => [
                    'form' => 'schedule:calendar/component_form/',
                    'info' => 'schedule:calendar/component_info/',
                ],
                'weight' => 100,
            ];

            unset($form['organizer']);
            unset($form['type']);

            uasort($form, 'Cal_Component_Model::cmp');

            $view->component_form = $form;
        } elseif ($pname == 'user') {

            $form = $view->component_form;

            $view->component_mode = '';

            $form['allow_download_attachments'] = [
                'label'  => null,
                'path'   => [
                    'info' => 'schedule:calendar/component_info/',
                ],
                'weight' => 100,
            ];
            unset($form['organizer']);

            $view->component_form = $form;

        }
    }

    public static function component_form_submit($e, $form, $component)
    {

        $parent = $component->calendar->parent;
        $pname  = $parent->name();
        if ($pname == 'lab') {

            $speakers        = (array) json_decode($form['speakers'], true);
            $attendee_users  = (array) json_decode($form['attendee_users'], true); //todo 自定的参与者不能添加，有可能影响用户判断
            $attendee_groups = (array) json_decode($form['attendee_groups'], true);
            $attendee_roles  = (array) json_decode($form['attendee_roles'], true);

            if ($speakers) {
                $component->speakers = $form['speakers'];
            } else {
                $form->set_error('speakers', I18N::T('schedule', '报告人不能为空!'));
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('schedule', '报告人不能为空!'));
            }

            if (!$form->no_error) {
                $e->return_value = false;
                return false;
            }

            $component->subtype = $form['subtype'];

            if ($form['attendee_type'] == 'part') {

                $can_submit = false;
                if ($form['user'] == 'on' && $attendee_users) {
                    $component->attendee_users = $form['attendee_users'];
                    $can_submit                = true;
                } else {
                    $component->attendee_users = $form['attendee_users'] = null;
                    Q("sch_att_user[component={$component}]")->delete_all();
                }

                if ($form['roles'] == 'on' && $attendee_roles) {
                    $component->attendee_roles = $form['attendee_roles'];
                    $can_submit                = true;
                } else {
                    $attendee_role             = false;
                    $component->attendee_roles = $form['attendee_roles'] = null;
                    Q("sch_att_role[component={$component}]")->delete_all();
                }

                if ($form['group'] == 'on' && $attendee_groups) {
                    $component->attendee_groups = $form['attendee_groups'];
                    $can_submit                 = true;
                } else {
                    $attendee_group             = false;
                    $component->attendee_groups = $form['attendee_groups'] = null;
                    Q("sch_att_group[component={$component}]")->delete_all();
                }

                if (!$can_submit) {
                    $form->set_error('attendee', I18N::T('schedule', '参与者不能为空!'));
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('schedule', '参与者不能为空!'));
                    $e->return_value = false;
                    return false;
                }

                $component->attendee_type = 'part';
            } else {
                $component->attendee_type   = 'all';
                $component->attendee_users  = $form['attendee_users']  = null;
                $component->attendee_roles  = $form['attendee_roles']  = null;
                $component->attendee_groups = $form['attendee_groups'] = null;
                Q("sch_att_user[component={$component}]")->delete_all();
                Q("sch_att_role[component={$component}]")->delete_all();
                Q("sch_att_group[component={$component}]")->delete_all();
            }

            $component->allow_download_attachments = $form['allow_download_attachments'] == 'on' ? true : false;

            $component->allow_attendee_download_attachments = $form['allow_attendee_download_attachments'] == 'on' ? true : false;

        }
    }

    public static function component_content_render($e, $component, $current_calendar = null)
    {
        $calendar = $component->calendar;
        $parent   = $calendar->parent;
        if ($calendar->id &&
            ($parent->name() == 'lab' || $parent->name() == 'user')
        ) {
            $e->return_value = V('schedule:calendar/component_content', ['component' => $component, 'current_calendar' => $current_calendar]);
            return false;
        } else if ($calendar->id && $parent->name() == 'user') {
            $e->return_value = V('schedule:calendar/user/component_content', ['component' => $component, 'current_calendar' => $current_calendar]);
            return false;
        }
    }

    public static function component_icon_present($e, $component)
    {
        $parent = $component->calendar->parent;
        if ($parent->id && ($parent->name() == 'lab' || $parent->name() == 'user')) {
            $path  = Schedule::get_path('upload', $component->id . '/');
            $files = Schedule::get_files($path);
            if (count($files)) {
                $e->return_value = V('schedule:calendar/component_icon');
            }
        }
    }

    public static function cal_component_get_color($e, $component, $calendar)
    {
        $cal_type    = $calendar->type;
        $parent_name = $calendar->parent->name();

        $return = null;
        if ($parent_name == 'lab') {
            if ($component->type == Cal_Component_Model::TYPE_VFREEBUSY) {
                $return = 7;
            } else {
                $return = (int) ($component->organizer->id % 6);
            }
        } elseif ($parent_name == 'user' && $cal_type == 'schedule') {
            if ($component->type == Cal_Component_Model::TYPE_VFREEBUSY) {
                $return = 7;
            } else {
                $return = (int) ($component->calendar->parent->id % 6);
            }
        }
        return;
    }

    public static function on_cal_component_saved($e, $component, $old, $new)
    {
        $me            = L('ME');
        $parent        = $component->calendar->parent;
        $new_component = (bool) $new['id'];

        if ($parent && ($parent->name() == 'lab' || $parent->name() == 'user')) {

            //Log::add
            if ($new_component) {
                $log = sprintf('[schedule] %s[%d] 于 %s 成功创建 %s[%d] 的日程安排[%d]', $me->name, $me->id, Date::format(Date::time()), $parent->name, $parent->id, $component->id);
            } else {
                $log = sprintf('[schedule] %s[%d] 于 %s 成功修改 %s[%d] 的日程安排[%d]', $me->name, $me->id, Date::format(Date::time()), $parent->name, $parent->id, $component->id);
            }

            Log::add($log, 'common');

            if ($component->id && isset($old['calendar'])) {
                $file_path = Schedule::get_path('tmp', $me->id . '/foobar');
                $old_path  = Schedule::get_path('tmp', $me->id . '/');
                File::check_path($file_path);
                $path     = Schedule::get_path('upload', $component->id . '/foobar');
                $new_path = Schedule::get_path('upload', $component->id . '/');
                File::check_path($path);
                rename($old_path, $new_path);
            }
            /*  speaker */
            $speakers          = (array) json_decode($new['speakers'], true); //新
            $schedule_speakers = Q("schedule_speaker[component={$component}]")->to_assoc('user_id', 'name'); //旧
            $s_diff            = array_diff($speakers, $schedule_speakers);
            $s_delete          = array_diff($schedule_speakers, $speakers);
            if ($s_diff) { //个别用户有变化
                foreach ($s_diff as $key => $value) { //增加新加的用户
                    $user                        = O('user', $key);
                    $schedule_speaker            = O('schedule_speaker');
                    $schedule_speaker->user      = $user;
                    $schedule_speaker->component = $component;
                    $schedule_speaker->name      = $value;
                    $schedule_speaker->save();
                }
            }
            if ($s_delete) {
                foreach ($s_delete as $key => $value) { //删除没了的用户
                    if ($key == 0) {
                        Q("schedule_speaker[component={$component}][name=$value]")->delete_all();
                    } else {
                        $user = O('user', $key);
                        Q("schedule_speaker[component={$component}[user={$user}]]")->delete_all();
                    }
                }
            }

            if ($component->attendee_type == 'all') {
                $labs = Q("$me lab");
                foreach ($labs as $lab) {
                    $users = Q("$lab user");
                    foreach ($users as $user) {
                        $sch_att_user            = O('sch_att_user');
                        $sch_att_user->user      = $user;
                        $sch_att_user->component = $component;
                        $sch_att_user->name      = $user->name;
                        $sch_att_user->save();
                    }
                }
            } else {
                /* attendee_users */

                $new_sch_att_users = (array) json_decode($new['attendee_users'], true); //新
                $sch_att_users     = (array) Q("sch_att_user[component={$component}]")->to_assoc('user_id', 'name'); //旧
                $a_diff            = array_diff($new_sch_att_users, $sch_att_users);
                $a_delete          = array_diff($sch_att_users, $new_sch_att_users);
                if ($a_diff) { //个别用户有变化
                    foreach ($a_diff as $key => $value) { //增加新加的用户
                        $user                    = O('user', $key);
                        $sch_att_user            = O('sch_att_user');
                        $sch_att_user->user      = $user;
                        $sch_att_user->component = $component;
                        $sch_att_user->name      = $value;
                        $sch_att_user->save();
                    }
                }
                if ($a_delete) {
                    foreach ($a_delete as $key => $value) { //删除没了的用户
                        if ($key == 0) {
                            Q("sch_att_user[component={$component}][name=$value]")->delete_all();
                        } else {
                            $user = O('user', $key);
                            Q("sch_att_user[component={$component}[user={$user}]]")->delete_all();
                        }
                    }
                }

                /* attendee_roles */
                $old_role = Q("sch_att_role[component={$component}]")->to_assoc('id', 'role_id');
                $new_role = array_flip((array) json_decode($new['attendee_roles'], true));
                $in_role  = array_diff($new_role, $old_role);
                $de_role  = array_diff($old_role, $new_role);

                foreach ($in_role as $key => $value) {
                    $sch_att_role            = O('sch_att_role');
                    $sch_att_role->role_id   = $value;
                    $sch_att_role->component = $component;
                    $sch_att_role->save();
                }
                foreach ($de_role as $key => $value) {
                    O('sch_att_role', ['role_id' => $value, 'component_id' => $component->id])->delete();
                }

                /* attendee_groups */
                $old_group = Q("sch_att_group[component={$component}]")->to_assoc('id', 'group_id');
                $new_group = array_flip((array) json_decode($new['attendee_groups'], true));
                $in_group  = array_diff($new_group, $old_group);
                $de_group  = array_diff($old_group, $new_group);
                foreach ($in_group as $key => $value) {
                    $sch_att_group            = O('sch_att_group');
                    $sch_att_group->group_id  = $value;
                    $sch_att_group->component = $component;
                    $sch_att_group->save();
                }
                foreach ($de_group as $key => $value) {
                    O('sch_att_group', ['group_id' => $value, 'component_id' => $component->id])->delete();
                }
            }

            /* notification */
            $dtstart           = $component->dtstart;
            $dtend             = $component->dtend;
            $description       = $component->description;
            $name              = $component->name;
            $organizer         = $component->organizer;
            $schedule_speakers = Q("schedule_speaker[component={$component}]")->to_assoc('user_id', 'name');
            //   发送给attendee

            $difference = array_diff_assoc($new, $old);
            $arr        = array_keys($difference);
            switch ($parent->name()) {
                //以下为个人日程安排
                case 'user':
                    //   organizer
                    Notification::send('schedule.user.add_event.to_organizer', $organizer, [
                        '%user'        => Markup::encode_Q($organizer),
                        '%name'        => $name,
                        '%description' => $description,
                        '%dtstart'     => Date::format($dtstart),
                        '%dtend'       => Date::format($dtend),
                        '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),
                    ]);
                    break;
                case 'lab':
                    //以下为课题组日程安排

                    // 准备 params
                    $markup_speakers = [];
                    foreach ($schedule_speakers as $k => $speaker) {
                        if ($k > 0) {
                            $speaker           = O('user', $k);
                            $markup_speakers[] = Markup::encode_Q($speaker);
                        }

                    }
                    $markup_organizer = Markup::encode_Q($organizer);
                    $speakers         = implode(', ', $markup_speakers);
                    if (Module::is_installed('meeting')) {
                        if ($component->me_room_id) {
                            $meeting      = O('meeting', $component->me_room_id);
                            $mark_meeting = $meeting->id ? Markup::encode_Q($meeting) : '';
                        }
                    } else {
                        $mark_meeting = I18N::T('schedule', '无|:no_meeting');
                    }

                    // 发送 notification
                    if ($component->attendee_type == 'all') {
                        // 对所有人发信

                        $labs = Q("$me lab");
                        foreach ($labs as $lab) {
                            $users = Q("$lab user");
                            foreach ($users as $user) {
                                $sch_att_user            = O('sch_att_user');
                                $sch_att_user->user      = $user;
                                $sch_att_user->component = $component;
                                $sch_att_user->name      = $user->name;
                                $sch_att_user->save();
                            }
                        }
                        Notification::send('schedule.lab.add_event.to_people', $lab, [
                            '%user'        => $markup_organizer,
                            '%name'        => $name,
                            '%organizer'   => $markup_organizer,
                            '%speaker'     => $speakers,
                            '%description' => $description,
                            '%dtstart'     => Date::format($dtstart),
                            '%dtend'       => Date::format($dtend),
                            '%meeting'     => $mark_meeting,
                            '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),
                        ]);

                    } else {

                        $batch_id = Notification::start_batch();

                        // 发给 组织者
                        Notification::send('schedule.lab.add_event.to_people', $organizer, [
                            '%user'        => $markup_organizer,
                            '%name'        => $name,
                            '%organizer'   => $markup_organizer,
                            '%speaker'     => $speakers,
                            '%description' => $description,
                            '%dtstart'     => Date::format($dtstart),
                            '%dtend'       => Date::format($dtend),
                            '%meeting'     => $mark_meeting,
                            '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),
                        ], null, $batch_id);

                        // 发给 主讲人
                        foreach ($schedule_speakers as $key => $value) {
                            $speaker = O('user', $key);
                            Notification::send('schedule.lab.add_event.to_people', $speaker, [
                                '%user'        => Markup::encode_Q($speaker),
                                '%name'        => $name,
                                '%organizer'   => $markup_organizer,
                                '%speaker'     => $speakers,
                                '%description' => $description,
                                '%dtstart'     => Date::format($dtstart),
                                '%dtend'       => Date::format($dtend),
                                '%meeting'     => $mark_meeting,
                                '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),

                            ], null, $batch_id);
                        }

                        // 发给 参与者
                        $attendees = (array) json_decode($new['attendee_users'], true);
                        $groups    = (array) json_decode($new['attendee_groups'], true);
                        $roles     = (array) json_decode($new['attendee_roles'], true);

                        /// 参与者 - 人
                        foreach ($attendees as $key => $value) {
                            $attendee = O('user', $key);
                            Notification::send('schedule.lab.add_event.to_people', $attendee, [
                                '%user'        => Markup::encode_Q($speaker),
                                '%name'        => $name,
                                '%organizer'   => $markup_organizer,
                                '%speaker'     => $speakers,
                                '%description' => $description,
                                '%dtstart'     => Date::format($dtstart),
                                '%dtend'       => Date::format($dtend),
                                '%meeting'     => $mark_meeting,
                                '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),

                            ], null, $batch_id);
                        }

                        /// 参与者 - 组织机构
                        foreach ($groups as $key => $value) {
                            $attendee_group = O('tag_group', $key);
                            Notification::send('schedule.lab.add_event.to_people', $attendee_group, [
                                '%user'        => Markup::encode_Q($speaker),
                                '%name'        => $name,
                                '%organizer'   => $markup_organizer,
                                '%speaker'     => $speakers,
                                '%description' => $description,
                                '%dtstart'     => Date::format($dtstart),
                                '%dtend'       => Date::format($dtend),
                                '%meeting'     => $mark_meeting,
                                '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),

                            ], null, $batch_id);
                        }

                        /// 参与者 - 角色
                        foreach ($roles as $key => $value) {
                            $attendee_role = O('role', $key);
                            Notification::send('schedule.lab.add_event.to_people', $attendee_role, [
                                '%user'        => Markup::encode_Q($speaker),
                                '%name'        => $name,
                                '%organizer'   => $markup_organizer,
                                '%speaker'     => $speakers,
                                '%description' => $description,
                                '%dtstart'     => Date::format($dtstart),
                                '%dtend'       => Date::format($dtend),
                                '%meeting'     => $mark_meeting,
                                '%link'        => URI::url('!schedule/index', ['st' => $dtstart]),
                            ], null, $batch_id);
                        }

                        Notification::finish_batch($batch_id);
                    }

                    break;
            }

            if ($new['id'] && !$old['id']) {
                //旧值不存在说明为新增。同步上传的文件到$note的目录
                $old_path = NFS::get_path(O('cal_component'), '', 'attachments', true);
                $new_path = NFS::get_path($component, '', 'attachments', true);
                NFS::move_files($old_path, $new_path);
            }

        }
    }

    public static function on_cal_component_deleted($e, $component)
    {

        if ($component->calendar->parent instanceof Lab_Model) {
            $path = Schedule::get_path('upload', $component->id . "/");
            File::rmdir($path);
            $attendee_roles    = [];
            $attendee_groups   = [];
            $dtstart           = $component->dtstart;
            $dtend             = $component->dtend;
            $description       = $component->description;
            $name              = $component->name;
            $schedule_speakers = Q("schedule_speaker[component={$component}] user")->to_assoc('id', 'name');
            $organizer         = $component->organizer;

            // 准备 params
            $markup_speakers = [];
            foreach ($schedule_speakers as $k => $speaker) {
                if ($k > 0) {
                    $speaker           = O('user', $k);
                    $markup_speakers[] = Markup::encode_Q($speaker);
                }

            }
            $markup_organizer = Markup::encode_Q($organizer);
            $speakers         = implode(', ', $markup_speakers);
            if (Module::is_installed('meeting')) {
                if ($component->me_room_id) {
                    $meeting      = O('meeting', $component->me_room_id);
                    $mark_meeting = $meeting->id ? Markup::encode_Q($meeting) : '';
                }
            } else {
                $mark_meeting = I18N::T('schedule', '无|:no_meeting');
            }

            $attendees = [];
            if ($component->attendee_type == 'all') {
                $labs = Q("$me lab");
                foreach ($labs as $lab) {
                    $users = Q("$lab user");
                    foreach ($users as $user) {
                        $sch_att_user            = O('sch_att_user');
                        $sch_att_user->user      = $user;
                        $sch_att_user->component = $component;
                        $sch_att_user->name      = $user->name;
                        $sch_att_user->save();
                    }
                }
                Notification::send('schedule.lab.delete_event.to_people', $lab, [
                    '%user'          => $markup_organizer,
                    '%name'          => $name,
                    '%organizer'     => $markup_organizer,
                    '%speaker'       => $speakers,
                    '%description'   => $description,
                    '%dtstart'       => Date::format($dtstart),
                    '%dtend'         => Date::format($dtend),
                    '%meeting'       => $mark_meeting,
                    '%cancel_reason' => $component->cancel_reason,
                    '%link'          => URI::url('!schedule/index', ['st' => $dtstart]),
                ]);
            } else {
                $batch_id = Notification::start_batch();

                // 发送给组织者
                Notification::send('schedule.lab.delete_event.to_people', $organizer, [
                    '%user'          => $markup_organizer,
                    '%name'          => $name,
                    '%organizer'     => $markup_organizer,
                    '%speaker'       => $speakers,
                    '%description'   => $description,
                    '%dtstart'       => Date::format($dtstart),
                    '%dtend'         => Date::format($dtend),
                    '%meeting'       => $mark_meeting,
                    '%cancel_reason' => $component->cancel_reason,
                    '%link'          => URI::url('!schedule/index', ['st' => $dtstart]),
                ], null, $batch_id);
                // 发送给演讲者
                foreach ($schedule_speakers as $key => $value) {
                    $speaker = O('user', $key);
                    Notification::send('schedule.lab.delete_event.to_people', $speaker, [
                        '%user'          => $markup_organizer,
                        '%name'          => $name,
                        '%organizer'     => $markup_organizer,
                        '%speaker'       => $speakers,
                        '%description'   => $description,
                        '%dtstart'       => Date::format($dtstart),
                        '%dtend'         => Date::format($dtend),
                        '%meeting'       => $mark_meeting,
                        '%cancel_reason' => $component->cancel_reason,
                        '%link'          => URI::url('!schedule/index', ['st' => $dtstart]),
                    ], null, $batch_id);
                }

                // 发送给参与者
                foreach (Q("sch_att_user[component={$component}] user") as $user) {
                    Notification::send('schedule.lab.delete_event.to_people', $user, [
                        '%user'          => $markup_organizer,
                        '%name'          => $name,
                        '%organizer'     => $markup_organizer,
                        '%speaker'       => $speakers,
                        '%description'   => $description,
                        '%dtstart'       => Date::format($dtstart),
                        '%dtend'         => Date::format($dtend),
                        '%meeting'       => $mark_meeting,
                        '%cancel_reason' => $component->cancel_reason,
                        '%link'          => URI::url('!schedule/index', ['st' => $dtstart]),
                    ], null, $batch_id);
                }

                foreach (Q("sch_att_role[component={$component}] role") as $role) {
                    Notification::send('schedule.lab.delete_event.to_people', $role, [
                        '%user'          => $markup_organizer,
                        '%name'          => $name,
                        '%organizer'     => $markup_organizer,
                        '%speaker'       => $speakers,
                        '%description'   => $description,
                        '%dtstart'       => Date::format($dtstart),
                        '%dtend'         => Date::format($dtend),
                        '%meeting'       => $mark_meeting,
                        '%cancel_reason' => $component->cancel_reason,
                        '%link'          => URI::url('!schedule/index', ['st' => $dtstart]),
                    ], null, $batch_id);
                }

                foreach (Q("sch_att_group[component={$component}] tag") as $group) {
                    Notification::send('schedule.lab.delete_event.to_people', $group, [
                        '%user'          => $markup_organizer,
                        '%name'          => $name,
                        '%organizer'     => $markup_organizer,
                        '%speaker'       => $speakers,
                        '%description'   => $description,
                        '%dtstart'       => Date::format($dtstart),
                        '%dtend'         => Date::format($dtend),
                        '%meeting'       => $mark_meeting,
                        '%cancel_reason' => $component->cancel_reason,
                        '%link'          => URI::url('!schedule/index', ['st' => $dtstart]),
                    ], null, $batch_id);
                }

                Notification::finish_batch($batch_id);
            }

            //删掉与component相关的speakers
            Q("schedule_speaker[component={$component}]")->delete_all();
            Q("sch_att_attendee[component={$component}]")->delete_all();
            Q("sch_att_role[component={$component}]")->delete_all();
            Q("sch_att_group[component={$component}]")->delete_all();
            Q("schedule_type[component={$component}]")->delete_all();
        }
    }

    public static function &get_files($path)
    {
        $files  = [];
        $handle = @opendir($path);
        if ($handle) {
            while ($file = (readdir($handle))) {
                if ($file[0] == ".") {
                    continue;
                }

                $files[] = $file;
            }
            closedir($handle);
        }
        return $files;
    }

    public static function postrender_component($e, $view, $output)
    {
        $component = $view->component;
        $parent    = $component->calendar->parent;
        $me        = L('ME');
        if ($parent->name() == 'lab' || $parent->name() == 'user') {
            if ($component->id) {
                $path      = Schedule::get_path('upload', $component->id . '/foobar');
                $file_path = Schedule::get_path('upload', $component->id . '/');
                File::check_path($path, 0755);
            }
            $e->return_value = $output . ((string) V('schedule:calendar/component_form/attachments',
                [
                    'component' => $component,
                    'path'      => $file_path,
                ]));
        }

    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }
        //取消现默认赋予给pi的权限
//        if (Q("$user<pi lab")->total_count()) {
//            $perms['管理负责实验室的日程安排'] = 'on';
//            $perms['管理所有成员的日程安排']    = 'on';
//            $perms['管理负责实验室的日程附件'] = 'on';
//            $perms['管理所有成员的日程附件']    = 'on';
//            $perms['查看负责实验室的日程安排'] = 'on';
//            $perms['查看所有成员的日程安排']    = 'on';
//            $perms['查看负责实验室的日程附件'] = 'on';
//            $perms['查看所有成员的日程附件']    = 'on';
//        }
    }

    public static function get_schedule_component_ids($e, $calendar)
    {
        $parent = $calendar->parent;
        if ($parent->name() == 'meeting') {

            $e->return_value = $parent->id;
            return;
        } elseif ($parent->name() == 'user' && $calendar->type == 'me_incharge') {
            //会议室负责人所有负责会议室关联的日程
            $me_room_ids = Q("$parent<incharge meeting")->to_assoc('id', 'id');

            $mids = implode(',', $me_room_ids);

            $e->return_value = $mids;
            return;
        } elseif ($calendar->type == 'all_meetings') {
            //所有关联了会议室的日程
            $me_room_ids = Q("meeting")->to_assoc('id', 'id');

            $mids = implode(',', $me_room_ids);

            $e->return_value = $mids;
            return;
        }
    }

    public static function schedule_newsletter_content($e, $user)
    {
        $templates = Config::get('newsletter.template');
        $dtstart   = strtotime(date('Y-m-d'));
        $dtend     = Date::next_time($dtstart);

        $db = Database::factory();

        $template = $templates['schedule']['schedule_calendar_count'];
        $sql      = "SELECT id FROM `calendar` WHERE parent_name='user' AND parent_id=%d AND type='schedule'";
        $query    = $db->query($sql, $user->id);
        if ($query) {
            $calendars = $query->rows();
            $num       = 0;
            foreach ($calendars as $calendar) {
                $calendar   = O('calendar', $calendar->id);
                $components = Event::trigger('calendar.components.get', $calendar, null, $dtstart, $dtend);
                $num += count($components);
            }
            if ($num > 0) {
                $str .= V('schedule:newsletter/schedule_calendar_count', [
                    'count'    => count($components),
                    'template' => $template,
                ]);
            }
        }

        $template = $templates['schedule']['schedule_lab_component'];
        $sql      = "SELECT COUNT(*)  FROM `cal_component` WHERE `calendar_id` = (SELECT id FROM `calendar` WHERE parent_name='lab' AND parent_id=%d) AND `dtstart`>%d AND `dtend`<%d";
        $count    = $db->value($sql, $user->id, $dtstart, $dtend);
        if ($count > 0) {
            $str .= V('schedule:newsletter/schedule_lab_component', [
                'count'    => $count,
                'template' => $template,
            ]);
        }

        $template = $templates['schedule']['schedule_lab_info'];
        $sql      = "SELECT id,me_room_id,subtype,dtstart,dtend  FROM `cal_component` WHERE `calendar_id` = (SELECT id FROM `calendar` WHERE parent_name='lab' AND parent_id=%d) AND `dtstart`>%d AND `dtend`<%d";
        $query    = $db->query($sql, $user->id, $dtstart, $dtend);
        if ($query) {
            $components = $query->rows();
            foreach ($components as $component) {
                $str .= V('schedule:newsletter/schedule_lab_info', [
                    'component' => $component,
                    'template'  => $template,
                ]);
            }
        }

        if (strlen($str) > 0) {
            $e->return_value = $str;
        }
    }
    public static function empty_schedule_message($e, $calendar)
    {
        if ($calendar->type == 'schedule' && $calendar->parent_name == 'user') {
            $e->return_value = I18N::T('schedule', '没有符合要求的日程');
            return true;
        } else if ($calendar->parent_name == 'lab') {
            $e->return_value = I18N::T('schedule', '没有符合要求的课题组日程');
            return true;
        }
    }

    //form为Form::filter()后对象
    public static function component_form_before_delete($e, $form, $component)
    {
        $parent = $component->calendar->parent;

        if ($parent->name() == 'lab') {

            //如果不存在cancel_reason
            if (!$form['cancel_reason']) {
                //属于第二次提交
                if ($form['delete_flag']) {
                    $form->set_error('cancel_reason', I18N::T('schedule', '请填写取消原因'));
                }
                JS::dialog(V('schedule:calendar/cancel_reason', ['component' => $component, 'form_token' => $form_token, 'form' => $form]), ['title' => I18N::T('schedule', '请填写取消原因|:title')]);
                $e->return_value = true;
                return false;

            } else {
                $component->cancel_reason = $form['cancel_reason'];
            }
        }
    }

    public static function calendar_insert_component_title($e, $calendar)
    {
        if ($calendar->type == 'schedule') {
            if ($calendar->parent->name() == 'lab') {
                $e->return_value = I18N::T('schedule', '添加实验室日程');
            } else if ($calendar->parent->name() == 'user') {
                $e->return_value = I18N::T('schedule', '添加我的日程');
            }
        }
    }

    public static function meeting_export_columns_print($e, $component, $type)
    {
        if ($component->calendar->type == 'schedule') {
            switch ($type) {
                case 'reserv_type':
                    switch ($component->subtype) {
                        case 0:
                            $return = I18N::T('schedule', '组会');
                            break;
                        case 1:
                            $return = I18N::T('schedule', '文献讨论');
                            break;
                        case 2:
                            $return = I18N::T('schedule', '其他');
                            break;
                        case 3:
                            $return = I18N::T('schedule', '学术报告');
                            break;
                    }
                    break;
                case 'speakers':
                    $return = H(join(', ', json_decode($component->speakers, true)));
                    break;
                case 'attendee':
                    $return = '';
                    if ($component->attendee_type == 'all') {
                        $return = I18N::T('schedule', '全部成员');
                    } else {
                        if ($component->attendee_groups) {
                            $return .= ' ' . I18N::T('schedule', '组织机构: ');
                            $return .= join(',', json_decode($component->attendee_groups, true));
                        }

                        if ($component->attendee_roles) {
                            $return .= ' ' . I18N::T('schedule', '角色: ');
                            $return .= join(',', json_decode($component->attendee_roles, true));
                        }

                        if ($component->attendee_users) {
                            $return .= ' ' . I18N::T('schedule', '个别用户: ');
                            $return .= join(',', json_decode($component->attendee_users, true));
                        }
                    }
                    break;
            }

            $e->return_value = trim($return);
            return false;
        }
    }

    public static function meeting_export_columns_csv($e, $component, $type)
    {
        if ($component->calendar->type == 'schedule') {
            switch ($type) {
                case 'reserv_type':
                    switch ($component->subtype) {
                        case 0:
                            $return = I18N::T('schedule', '组会');
                            break;
                        case 1:
                            $return = I18N::T('schedule', '文献讨论');
                            break;
                        case 2:
                            $return = I18N::T('schedule', '其他');
                            break;
                        case 3:
                            $return = I18N::T('schedule', '学术报告');
                            break;
                    }
                    break;
                case 'speakers':
                    $return = H(join(', ', json_decode($component->speakers, true)));
                    break;
                case 'attendee':
                    $return = '';
                    if ($component->attendee_type == 'all') {
                        $return = I18N::T('schedule', '全部成员');
                    } else {
                        if ($component->attendee_groups) {
                            $return .= ' ' . I18N::T('schedule', '组织机构: ');
                            $return .= join(',', json_decode($component->attendee_groups, true));
                        }

                        if ($component->attendee_roles) {
                            $return .= ' ' . I18N::T('schedule', '角色: ');
                            $return .= join(',', json_decode($component->attendee_roles, true));
                        }

                        if ($component->attendee_users) {
                            $return .= ' ' . I18N::T('schedule', '个别用户: ');
                            $return .= join(',', json_decode($component->attendee_users, true));
                        }
                    }
                    break;
            }

            $e->return_value = trim($return);
            return false;
        }
    }
}
