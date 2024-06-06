<?php
class DB_Sync_Vidcam
{
    public static function edit_info_view($e, $form, $vidcam)
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            $e->return_value .= V('db_sync:vidcam/edit.info', [
                'form'   => $form,
                'vidcam' => $vidcam,
            ]);
        }
    }

    public static function post_submit_validate($e, $form)
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            $form->validate('site', 'not_empty', I18N::T('db_sync', '所属站点不能为空!'));
        }

    }

    public static function post_submit($e, $form, $vidcam)
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            $vidcam->site = $form['site'];
        }
    }

    public static function extra_site_column($e, $columns, $vidcam, $form)
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 10,
            ];
        }
    }

    public static function extra_site_row($e, $row, $vidcam)
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            $row['site'] = H(Config::get('site.map')[$vidcam->site]) ?: '--';
        }
    }

    public static function extra_site_selector($e, $selector, $form = [])
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            if (DB_SYNC::is_master()) {
                if ($form['site']) {
                    $selector .= "[site={$form['site']}]";
                }
            } else {
                $selector .= "[site=" . LAB_ID . "]";
            }
        }
        $e->return_value = $selector;
    }

    public static function vidcam_ACL($e, $user, $perms, $vidmon, $options)
    {
        if (DB_SYNC::is_module_unify_manage('vidcam')) {
            $e->return_value = false;
            return false;
        }
    }

    public static function extra_links($e, $vidcam, $links, $mode)
    {
        if (DB_SYNC::is_master() || !DB_SYNC::is_module_unify_manage('vidcam')) {
            return;
        }
        $me     = L('ME');
        $master = Config::get('site.master');
        $url    = $master['host'] . "!vidmon/vidcam/edit.{$vidcam->id}?oauth-sso=db_sync." . LAB_ID;
        switch ($mode) {
            case 'list':
                if ($me->is_allowed_to('修改', $vidcam)) {
                    $links['edit'] = [
                        'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                            'q-object' => 'vidcam_edit',
                            'q-event'  => 'click',
                            'q-static' => ['vidcam_id' => $vidcam->id],
                            'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!vidmon/vidcam', '', true),
                        ]]),
                        'text'  => I18N::T('vidmon', '修改'),
                        'extra' => 'class="blue"',
                    ];
                }
                break;
            case 'view':
                if ($me->is_allowed_to('修改', $vidcam)) {
                    $links['edit'] = [
                        'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                            'q-object' => 'vidcam_edit',
                            'q-event'  => 'click',
                            'q-static' => ['vidcam_id' => $vidcam->id],
                            'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!vidmon/vidcam', '', true),
                        ]]),
                        'text'  => I18N::T('vidmon', '修改'),
                        'extra' => 'class="button button_edit"',
                    ];
                }
                break;
        }
    }
}
