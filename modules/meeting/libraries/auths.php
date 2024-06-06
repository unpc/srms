<?php

class Auths
{

    public static function setup_view()
    {
        Event::bind('meeting.index.tab', 'Auths::index_auth_tab');
        Event::bind('meeting.index.content', 'Auths::index_auth_tab_content', 0, 'auth');
    }

    public static function index_auth_tab($e, $tabs)
    {
        $meeting = $tabs->meeting;
        if (L('ME')->is_allowed_to('管理授权', $meeting)) {
            if ($meeting->require_auth) {
                $tabs->add_tab('auth', [
                    'url'    => $meeting->url('auth'),
                    'title'  => I18N::T('meeting', '授权'),
                    'weight' => 20,
                ]);
            }
        }
    }

    public static function index_auth_tab_content($e, $tabs)
    {

        $meeting = $tabs->meeting;
        $params  = Config::get('system.controller_params');

        Event::bind('meeting.auth.content', 'Auths::_index_auth_approved', 0, 'approved');
        Event::bind('meeting.auth.content.tool', 'Auths::index_auth_approved_tool', 0, 'approved');

        Event::bind('meeting.auth.content', 'Auths::_index_auth_applied', 0, 'applied');
        Event::bind('meeting.auth.content.tool', 'Auths::index_auth_applied_tool', 0, 'applied');

        $tabs->content                = V('auths/index');
        $tabs->content->tertiary_tabs = Widget::factory('tabs')
            ->add_tab('applied', [
                'url'   => $meeting->url('auth.applied'),
                'title' => I18N::T('meeting', '已申请授权的'),
            ])
            ->add_tab('approved', [
                'url'   => $meeting->url('auth.approved'),
                'title' => I18N::T('meeting', '已通过授权的'),
            ])
            ->content_event('meeting.auth.content')
            ->tool_event('meeting.auth.content.tool')
            ->set('class', 'third_tabs')
            ->set('meeting', $meeting)
            ->select($params[2]);
    }

    public static function _index_auth_approved($e, $tabs)
    {
        $form       = Lab::form();
        $tabs->form = $form;

        $status   = UM_Auth_Model::STATUS_APPROVED;
        $meeting  = $tabs->meeting;
        $selector = "um_auth[meeting={$meeting}][status=$status]";

        if ($form['approved_name']) {
            $approved_name = Q::quote($form['approved_name']);
            $selector = "user[name*=$approved_name|name_abbr^=$approved_name] " . $selector;
        }

        if ($form['ctime_start']) {
            $ctime_start = Q::quote(Date::get_day_start($form['ctime_start']));
            $selector .= "[ctime >= $ctime_start]";
        }

        if ($form['ctime_end']) {
            $ctime_end = Q::quote(Date::get_day_end($form['ctime_end']));
            $selector .= "[ctime <= $ctime_end]";
        }

        /*
        atime不过期相当于期限最大，所以判断开始时间时也应关联到期时间为空的数据
         */
        if ($form['atime_start']) {
            $atime_start = Q::quote(Date::get_day_start($form['atime_start']));
            $selector .= "[atime >= $atime_start | !atime]";
        }

        if ($form['atime_end']) {
            $atime_end = Q::quote(Date::get_day_end($form['atime_end']));
            $selector .= "[atime][atime <= $atime_end]";
        }

        // echo '<pre>';print_r($selector);echo '</pre>';
        $auths = Q($selector);
        // 分页
        $form['selector']      = $selector;
        $_SESSION[$form_token] = $form;
        $start                 = (int) $form['st'];
        $per_page              = 20;
        $start                 = $start - ($start % $per_page);

        $pagination = Lab::pagination($auths, $start, $per_page);

        $tabs->content = V('auths/approved', [
            'auths'      => $auths,
            'pagination' => $pagination,
            'form'       => $form,
        ]);

    }

    public static function _index_auth_applied($e, $tabs)
    {

        $form       = Form::filter(Input::form());
        $tabs->form = $form;
        $status     = UM_Auth_Model::STATUS_APPLIED;
        $meeting    = $tabs->meeting;

        $selector = "um_auth[meeting={$meeting}][status=$status]";

        if ($form['approved_name']) {
            $approved_name = Q::quote(trim($form['approved_name']));
            $selector      = "user[name*=$approved_name|name_abbr*=$approved_name] " . $selector;
        }

        $auths = Q($selector);

        // 分页
        $start    = (int) $form['st'];
        $per_page = 20;
        $start    = $start - ($start % $per_page);
        if ($start > 0) {
            $last = floor($auths->total_count() / $per_page) * $per_page;
            if ($last == $auths->total_count()) {
                $last = max(0, $last - $per_page);
            }

            if ($start > $last) {
                $start = $last;
            }

            $auths = $auths->limit($start, $per_page);
        } else {
            $auths = $auths->limit($per_page);
        }
        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'    => $start,
            'per_page' => $per_page,
            'total'    => $auths->total_count(),
        ]);
        $tabs->content = V('auths/applied', [
            'pagination' => $pagination,
            'auths'      => $auths,
            'form'       => $form,
        ]);

    }

    public static function index_auth_applied_tool($e, $tabs)
    {
        $form    = $tabs->form;
        $columns = [
            'approved_name' => [
                'title'  => I18N::T('people', '姓名'),
                'filter' => [
                    'form'  => V('meeting:auth_table/filters/approved_name', ['approved_name' => $form['approved_name']]),
                    'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                ],
                'nowrap' => true,
            ],
            'contact_info'  => [
                'title'  => I18N::T('people', '联系方式'),
                'nowrap' => true,
            ],
            'address'       => [
                'title'  => I18N::T('people', '地址'),
                'nowrap' => true,
            ],
            'rest'          => [
                'nowrap' => true,
                'align'  => 'right',
            ],
        ];

        $tabs->columns    = $columns;
        $tabs->search_box = V('application:search_box', ['is_offset' => false, 'top_input_arr' => ['approved_name'], 'columns' => $columns]);
    }

    public static function index_auth_approved_tool($e, $tabs)
    {
        $form = $tabs->form;
        if ($form['ctime_start'] || $form['ctime_end']) {
            $form['ctime'] = true;
        }

        if ($form['atime_start'] || $form['atime_end']) {
            $form['ctime'] = true;
        }

        $columns = [
            'approved_name' => [
                'title'  => I18N::T('meeting', '名称'),
                'filter' => [
                    'form'  => V('meeting:auth_table/filters/approved_name', ['approved_name' => $form['approved_name']]),
                    'value' => $form['approved_name'] ? H($form['approved_name']) : null,
                ],
                'nowrap' => true,
            ],
            'contact_info'  => [
                'title'  => I18N::T('meeting', '联系方式'),
                'nowrap' => true,
            ],
            'address'       => [
                'title'  => I18N::T('meeting', '地址'),
                'nowrap' => true,
            ],
            'ctime'         => [
                'title'  => I18N::T('meeting', '通过时间'),
                'filter' => [
                    'form'  => V('meeting:auth_table/filters/approved_date', [
                        'start'   => 'ctime_start',
                        'end'     => 'ctime_end',
                        'dtstart' => $form['ctime_start'],
                        'dtend'   => $form['ctime_end'],
                    ]),
                    'value' => $form['ctime'] ? H($form['ctime']) : null,
                    'field' => 'ctime_start,ctime_end',
                ],
                'nowrap' => true,
            ],
            'atime'         => [
                'title'  => I18N::T('meeting', '过期时间'),
                'filter' => [
                    'form'  => V('meeting:auth_table/filters/approved_date', [
                        'start'   => 'atime_start',
                        'end'     => 'atime_end',
                        'dtstart' => $form['atime_start'],
                        'dtend'   => $form['atime_end'],
                    ]),
                    'value' => $form['atime'] ? H($form['atime']) : null,
                    'field' => 'atime_start,atime_end',
                ],
                'nowrap' => true,
            ],
            'rest'          => [
                'nowrap' => true,
                'align'  => 'right',
            ],
        ];

        $tabs->columns    = $columns;
        
        $panel_buttons[] = [
            'tip'   => I18N::T('meeting', '添加授权'),
            'text' => I18N::T('status', '添加授权'),
            'extra' => 'q-event="click" q-object="add_approved_tag" q-static=' . H(["meeting_id" => $tabs->meeting->id]) . ' class="button button_add" q-src="' . H(URI::url('!meeting/auth')) . '" ',
        ];
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['approved_name'], 'columns' => $columns]);
    }

}
