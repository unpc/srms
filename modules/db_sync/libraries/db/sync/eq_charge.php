<?php

class DB_Sync_Eq_Charge
{
    public static function extra_site_column($e, $form, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('eq_charge')) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 15,
            ];
        }
    }

    public static function extra_site_row($e, $row, $sample)
    {
        if (DB_SYNC::is_module_unify_manage('eq_charge')) {
            $row['site'] = H(Config::get('site.map')[$sample->equipment->site]) ?: '--';
        }
    }

    public static function get_export_charge_columns($e, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('eq_charge')) {
            $columns['site'] = '所属站点';
        }

        $e->return_value = $columns;
        return true;
    }
}
