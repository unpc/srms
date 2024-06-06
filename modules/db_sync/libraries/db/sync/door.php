<?php
class DB_Sync_Door
{
    public static function edit_info_view($e, $form, $door)
    {
        if (DB_SYNC::is_module_unify_manage('door')) {
            $e->return_value .= V('db_sync:public/site', [
                'form'   => $form,
                'object' => $door,
            ]);
        }
    }

    public static function post_submit_validate($e, $form)
    {
        if (DB_SYNC::is_module_unify_manage('door')) {
            $form->validate('site', 'not_empty', I18N::T('db_sync', '所属站点不能为空!'));
        }
    }

    public static function post_submit($e, $form, $door)
    {
        if (DB_SYNC::is_module_unify_manage('entrance')) {
            $door->site = $form['site'];
        }
    }

    public static function extra_site_column($e, $columns, $door, $form)
    {
        if (DB_SYNC::is_module_unify_manage('door')) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 30,
            ];
        }
    }

    public static function extra_site_row($e, $row, $door)
    {
        if (DB_SYNC::is_module_unify_manage('door')) {
            $row['site'] = H(Config::get('site.map')[$door->site]) ?: '--';
        }
    }

    public static function extra_site_selector($e, $selector, $form = [])
    {
        if (DB_SYNC::is_module_unify_manage('door')) {
            if ($form['site']) {
                $selector .= "[site={$form['site']}]";
            }
        } else {
            $selector .= "[site=" . LAB_ID . "]";
        }
        $e->return_value = $selector;
    }
}
