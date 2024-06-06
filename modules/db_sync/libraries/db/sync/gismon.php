<?php
class DB_Sync_Gismon
{
    public static function post_submit_validate($e, $form)
    {
        $form->validate('site', 'not_empty', I18N::T('db_sync', '所属站点不能为空!'));
    }

    public static function post_submit($e, $form, $build)
    {
        $build->site = $form['site'];
    }

    public static function edit_info_view($e, $form, $build)
    {
        $e->return_value .= V('db_sync:gismon/building/edit.info', [
            'form' => $form,
            'build' => $build,
        ]);
    }

    public static function extra_site_column($e, $columns, $build, $form)
    {
        if (DB_SYNC::is_master()) {
            $columns['site'] = [
                'title' => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form' => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null
                ],
                'nowrap' => true,
                'weight' => 10,
            ];
        }
    }

    public static function extra_site_row($e, $row, $build)
    {
        if (DB_SYNC::is_master()) {
            $row['site'] = H(Config::get('site.map')[$build->site]) ?: '--';
        }
    }


    public static function extra_site_selector($e, $selector, $form = [])
    {
        if (DB_SYNC::is_master()) {
            if ($form['site']) {
                $selector .= "[site={$form['site']}]";
            }
        } else {
            $selector .= "[site=" . LAB_ID . "]";
        }
        $e->return_value = $selector;
    }

    public static function extra_links($e, $build, $links, $mode)
    {
        if (DB_SYNC::is_master()) {
            return;
        }
        $me = L('ME');
        switch ($mode) {
            case 'view':
                if ($me->is_allowed_to('修改', $build)) {
                    $links['edit'] = [
                        'url' => Event::trigger('db_sync.transfer_to_master_url', "!gismon/building/edit." . $build->id) ?:
                            URI::url("!gismon/building/edit." . $build->id),
                        'text' => I18N::T('gismon', '修改'),
                        'extra' => 'class="button button_edit"',
                    ];

                }
                break;
            case 'index':
            default:
                if ($me->is_allowed_to('修改', $build)) {
                    $links['edit'] = [
                        'url' => Event::trigger('db_sync.transfer_to_master_url', "!gismon/building/edit." . $build->id) ?:
                            URI::url("!gismon/building/edit." . $build->id),
                        'text' => I18N::T('gismon', '修改'),
                        'extra' => 'class="blue"',
                    ];

                }
        }
    }

    public static function extra_data($e, $data)
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('gis_device')) {
            return;
        }
        $route = Input::route();
        $master = Config::get('site.master');
        $data["url"] = $master['host'] . $route . "?oauth-sso=db_sync." . LAB_ID;
        $data["submit_url"] = $master['host'] . $route;
        $e->return_value = $data;
        return false;
    }

    public static function device_pickup_url($e, $data = [])
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('gis_device')) {
            return;
        }
        $route = Input::route();
        $master = Config::get('site.master');
        $data['url'] = $master['host'] . $route . "?oauth-sso=db_sync." . LAB_ID;
        $data['submit_url'] = $master['host'] . $route;
        $e->return_value = $data;
        return false;
    }


    public static function device_move_url($e, $data = [])
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('gis_device')) {
            return;
        }
        $route = Input::route();
        $master = Config::get('site.master');
        $route = str_replace("device", "building", $route);
        $data['url'] = $master['host'] . $route . "?oauth-sso=db_sync." . LAB_ID;
        $data['submit_url'] = $master['host'] . $route;
        $e->return_value = $data;
        return false;
    }

    public static function device_is_not_display($e, $object)
    {
        if (!DB_SYNC::is_master()) {
            switch ($object->name()) {
                case 'equipment':
                    if ($object->site != LAB_ID)
                        $e->return_value = true;
                    break;
            }
        }
    }

    public static function extra_device_site_selector($e, $selector, $form = [])
    {
        if (DB_SYNC::is_master()) {
            if ($form['site']) {
                $selector .= "[site={$form['site']}]";
            }
        } else {
            $selector .= "[site=" . LAB_ID . "]";
        }
        $e->return_value = $selector;
    }
}
