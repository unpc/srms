<?php

class DB_Sync_Training
{
    public static function extra_site_column($e, $form, $columns)
    {
        if (DB_SYNC::is_master()) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 25,
            ];
        }
    }

    public static function extra_site_row($e, $row, $equipment)
    {
        if (DB_SYNC::is_master()) {
            $row['site'] = H(Config::get('site.map')[$equipment->site]) ?: '--';
        }
    }

}
