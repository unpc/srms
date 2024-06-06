<?php

class DB_SYNC
{
    public static $watch_objects = [
        //'user',
        //'lab',
        //'equipment',
        //'billing_department',
        //'door',
        //'message',
        //'vidcam',
        //'patent',
        //'publication',
        //'award',
    ];

    public static function orm_model_call_url($e, $object, $url, $query, $fragment, $op)
    {
        if (self::is_master()) {
            return;
        }
        $master = Config::get('site.master');
        //message reply
        if (in_array($object->name(), self::$watch_objects) && in_array($op, ['edit', 'delete', 'reply'])) {
            $url                = trim($master['host'], '/') . '/' . $url;
            $query['oauth-sso'] = "db_sync." . LAB_ID;
            $query['from_lab']  = LAB_ID;
            $e->return_value    = URI::url($url, $query, $fragment);
        }
    }

    public static function people_list_panel_buttons($e, $panel_buttons, $form_token, $tab)
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('user')) {
            return false;
        }
        $me = L('ME');
        if ($me->is_allowed_to('添加', 'user')) {
            $master = Config::get('site.master');
            $url    = $master['host'] . '!people/profile/add.' . $tab . "?oauth-sso=db_sync." . LAB_ID;

            $panel_buttons[0] = [
                'url'   => $url,
                'text'  => I18N::T('people', '添加新成员'),
                'extra' => 'class="button button_add"',
            ];
        }
        $e->return_value = $panel_buttons;
        return false;
    }

    public static function labs_list_panel_buttons($e, $panel_buttons, $form_token)
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('lab')) {
            return false;
        }
        $me = L('ME');
        if ($me->is_allowed_to('添加', 'lab')) {
            $master = Config::get('site.master');
            $url    = $master['host'] . "!labs/lab/add?oauth-sso=db_sync." . LAB_ID;

            $panel_buttons[0] = [
                'url'   => $url,
                'text'  => I18N::T('labs', '添加实验室'),
                'extra' => 'class="button button_add"',
            ];
        }
        $e->return_value = $panel_buttons;
        return false;
    }

    // db_sync模块开启时，在添加、修改仪器时收集额外信息 lastEditBy lianhui.cao
    public static function equipment_edit_info_view($e, $form, $equipment)
    {
        if (self::is_module_unify_manage('equipment')) {
            $e->return_value .= V('db_sync:equipment/edit.info', [
                'form'      => $form,
                'equipment' => $equipment,
            ]);
        }
        return true;
    }

    public static function equipment_extra_selector($e, $form, $selector)
    {
        if (DB_SYNC::is_slave()) {
            $selector .= '[site=' . LAB_ID . ']';
            $e->return_value = $selector;
        }
    }

    public static function extra_site_column($e, $form)
    {
        $column = [];
        if ($GLOBALS['preload']['billing.single_department']) {
            $column = [
                'site' => [
                    'title'  => I18N::T('db_sync', '所属站点'),
                    'filter' => [
                        'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                        'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                    ],
                    'nowrap' => true,
                    'weight' => 100,
                ],
            ];
        }
        $e->return_value = $column;
        return false;
    }

    public static function extra_site_row($e, $transaction)
    {
        $row = [];
        if ($GLOBALS['preload']['billing.single_department']) {
            $row = [
                'site' => H(Config::get('site.map')[$transaction->site]) ?: '--',
            ];
        }
        $e->return_value = $row;
        return false;
    }

    public static function extra_site_selector($e, $form, $selector)
    {
        $me = L('ME');
        if (DB_SYNC::is_slave()) {
            $selector .= "[site=" . LAB_ID . "]";
        }
        //分站管理员在主站点只能看到自己管理的站点
        if (DB_SYNC::is_master() && Q("$me<incharge subsite")->total_count()) {
            $subsites = Q("$me<incharge subsite")->to_assoc('id', 'ref_no');
            $selector .= "[site=" . implode(',', $subsites) . "]";
        }
        if ($form['site']) {
            $selector .= "[site={$form['site']}]";
        }
        $e->return_value = $selector;
        return false;
    }

    public static function extra_billing_account_select($e)
    {
        $me = L('ME');
        if ($me->access('管理财务中心')) {
            return false;
        }
        if (Q("subsite[ref_no=" . LAB_ID . "] {$me}")->total_count()) {
            $extra_select = [];
        } elseif ($me->access('管理下属课题组财务账号')) {
            $extra_select[] = "{$me->group} lab";
        } elseif ($me->access('管理本课题组财务账号')) {
            $extra_select[] = "lab[group_id={$me->group->id}]";
        }
        $e->return_value = $extra_select;
        return false;
    }

    public static function department_transactions_selector($e, $form)
    {
        $me = L('ME');
        if ($me->access('管理财务中心')) {
            return false;
        }
        if (Q("subsite[ref_no=" . LAB_ID . "] {$me}")->total_count()) {
            $extra_select = '';
        } elseif ($me->access('管理下属课题组财务账号')) {
            $extra_select = "{$me->group} lab";
        } elseif ($me->access('管理本课题组财务账号')) {
            $extra_select = "lab[group_id={$me->group->id}]";
        }
        $e->return_value = $extra_select;
        return false;
    }

    public static function need_to_hidden($e, $table = '')
    {
        // 直接在主站设置，从站不显示
        $e->return_value = self::is_slave() && self::is_module_unify_manage($table);
        return false;
    }

    // 用于判断子站点该模块是否统一管理
    public static function is_module_unify_manage($table = '')
    {
        if (!$table) {
            return false;
        }

        if (self::is_master()) {
            return false;
        }

        return in_array($table, Config::get('db_sync.tables'));
    }

    public static function back_to_slave($e)
    {
        $route = Input::route();
        //$pattern = array_shift(explode('.', $route));
        if (self::is_master()
            && $_SESSION['from_lab']
            && !(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == 'xmlhttprequest')
            && defined('MODULE_ID')
            && !in_array(MODULE_ID, ['oauth', 'calendars', 'nfs', 'nfs_share'])
            && strpos($_SESSION['oauth_sso_referer'], MODULE_ID) === false
            //&& strpos($_SESSION['oauth_sso_referer'], $pattern) === false
        ) {
            $slave     = Config::get('site.slave');
            $slave_url = $slave[$_SESSION['from_lab']]['host'] . $route;
            unset($_SESSION['from_lab']);
            URI::redirect($slave_url);
        }
    }

    public static function message_delete_read_url($e)
    {
        if (DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage('message')) {
            $master          = Config::get('site.master');
            $e->return_value = $master['host'] . "/!messages/message/delete_read?oauth-sso=db_sync." . LAB_ID;
            return false;
        }
    }

    public static function message_batch_action_url($e)
    {
        if (DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage('message')) {
            $master          = Config::get('site.master');
            $e->return_value = $master['host'] . "/!messages/message/batch_action?oauth-sso=db_sync." . LAB_ID;
            return false;
        }
    }

    public static function is_master()
    {
        return Config::get('site.type') == 'master';
    }
    public static function is_slave()
    {
        return Config::get('site.type') == 'slave';
    }

    public static function transfer_to_master_url($e, $url = '', $query = '', $no_oauth = false)
    {
        if (self::is_slave() && self::is_module_unify_manage()) {
            $master = Config::get('site.master');
            if (!$url) {
                $url = Input::route();
            }

            if (is_array($query)) {
                $query = H($query);
            }

            $master_url = "{$master['host']}{$url}?{$query}";
            if (!$no_oauth) {
                $master_url .= "&oauth-sso=db_sync." . LAB_ID;
            }

            $e->return_value = $master_url;
            return false;
        } else {

        }
    }

    public static function equipment_info_api_extra($e, $equipment, $data)
    {
        $data['site'] = $equipment->site;
        return true;
    }

    public static function site_filter($e)
    {
        if (self::is_slave()) {
            $e->return_value = 'equipment[site=' . LAB_ID . ']';
        }
        return false;
    }

    public static function is_subsite_admin($e)
    {
        $me              = L('ME');
        $e->return_value = !!Q("$me<incharge subsite")->total_count();
        return false;
    }

    static function get_signup_href($e, $query = null)
    {
        $query_str = $query ? http_build_query($query) : '';
        //1校n区从站跳到主站注册
        if (self::is_slave() && self::is_module_unify_manage('people')) {
            $href = Config::get('site.master')['host'] . '/!labs/signup?from_lab=' . LAB_ID . '&' . $query_str;
        }else{
            $href = '!labs/signup?' . $query_str;
        }

        $e->return_value = H(URI::url($href));
        return false;
    }

    static function get_lab_signup_href($e, $query = null)
    {
        $query_str = $query ? http_build_query($query) : '';
        //1校n区从站跳到主站注册
        if (DB_SYNC::is_slave() && self::is_module_unify_manage('labs')) {
            $href = Config::get('site.master')['host'] . '/!labs/signup/lab?from_lab=' . LAB_ID . '&' . $query_str;
        }else{
            $href = '!labs/signup/lab?' . $query_str;
        }

        $e->return_value = H(URI::url($href));
        return false;
    }

    public static function jump_master_view($e)
    {
        $route        = Input::route();
        $master       = Config::get('site.master');
        $redirect_url = $master . $route . "?oauth-sso=db_sync.".LAB_ID;
        if (DB_SYNC::is_slave()) {
            $e->return_value = V('db_sync:slave/jump_master', ['redirect_url' => $redirect_url]);
        } else {
            $e->return_value = null;
        }

        return;
    }

    public static function jump_master_url($e, $url = null)
    {
        $route        = Input::route();
        $master       = Config::get('site.master');
        $redirect_url = $master['host'] . $route . "?oauth-sso=db_sync.".LAB_ID;
        if (DB_SYNC::is_slave()) {
            $e->return_value = $redirect_url;
        } else {
            $e->return_value = $url ?: null;
        }

        return;
    }

    /**
     * @param $controller CURRENT::$controller
     * @param disable_links disabled page buttons
     */
    public static function slave_disable_input($e, $controller, $disable_links = [])
    {
        if (DB_SYNC::is_slave() && self::is_module_unify_manage()) {
            $route        = Input::route();
            $master       = Config::get('site.master');
            $redirect_url = $master['host'] . $route . "?oauth-sso=db_sync.".LAB_ID;
            $controller->add_js('db_sync:slave_disable_input');
            if (count($disable_links)) {
                foreach ($disable_links as $value) {
                    $controller->add_js("db_sync:slave_disable_{$value}");
                }
            }
            $e->return_value = V('db_sync:slave/jump_master', ['redirect_url' => $redirect_url]);
        }
    }
}

}
