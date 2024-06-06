<?php

class DB_Sync_Envmon
{
    public static function edit_info_view($e, $form, $envmon)
    {
        $e->return_value .= V('db_sync:envmon/edit.info', [
            'form' => $form,
            'envmon' => $envmon,
        ]);
    }

    public static function post_submit_validate($e, $form)
    {
        $form->validate('site', 'not_empty', I18N::T('db_sync', '所属站点不能为空!'));
    }

    public static function post_submit($e, $form, $envmon)
    {
        $envmon->site = $form['site'];
    }


    public static function extra_site_column($e, $columns, $envmon, $form)
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

    public static function extra_site_row($e, $row, $envmon)
    {
        if (DB_SYNC::is_master()) {
            $row['site'] = H(Config::get('site.map')[$envmon->site]) ?: '--';
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

    public static function envmon_ACL($e, $user, $perms, $vidmon, $options)
    {
        if (DB_SYNC::is_slave()) {
            $e->return_value = false;
            return false;
        }
    }

    public static function extra_links($e, $envmon, $links, $mode)
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('env_node')) {
            return;
        }

        $me = L('ME');
        $master = Config::get('site.master');
        $url = $master['host'] . "!vidmon/envmon/edit.{$envmon->id}?oauth-sso=db_sync." . LAB_ID;
        switch ($mode) {
            case 'index':
                if ($me->is_allowed_to('修改', $envmon)) {
                    $links['edit'] = [
                        'url' => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                            'q-object' => 'edit_node',
                            'q-event' => 'click',
                            'q-static' => ['node_id' => $envmon->id],
                            'q-src' => Event::trigger('db_sync.transfer_to_master_url', '!envmon/node', '', true),
                        ]]),
                        'text' => I18N::T('vidmon', '修改'),
                        'extra' => 'class="blue"'
                    ];
                }
                break;
        }
    }
}
