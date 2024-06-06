<?php

class DB_Sync_Eq_Reserv
{
    public static function extra_site_column($e, $form, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('eq_reserv')) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 5,
            ];
        }
    }

    public static function extra_site_row($e, $row, $reserv)
    {
        if (DB_SYNC::is_module_unify_manage('eq_reserv')) {
            $row['site'] = H(Config::get('site.map')[$reserv->equipment->site]) ?: '--';
        }
    }

    public static function reserv_search_filter_submit($e, $form, $selector, $pre_selector)
    {
        if (DB_SYNC::is_module_unify_manage('eq_reserv') && DB_SYNC::is_slave()) {
            $slave_filter = "[site=" . LAB_ID . "]";
            if ($pre_selector['equipment']) {
                $pre_selector['equipment'] .= $slave_filter;
            } else {
                $pre_selector['equipment'] = "equipment" . $slave_filter;
            }
            $e->return_value = $selector;
            return false;
        }

        if (DB_SYNC::is_module_unify_manage('eq_reserv') && $form['site']) {
            $slave_filter = "[site={$form['site']}]";
            if ($pre_selector['equipment']) {
                $pre_selector['equipment'] .= $slave_filter;
            } else {
                $pre_selector['equipment'] = "equipment" . $slave_filter;
            }
            $e->return_value = $selector;
            return false;
        }

        $e->return_value = $selector;
        return true;
    }

    public static function get_export_reserv_columns($e, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('eq_reserv')) {
            $columns['site'] = '所属站点';
        }

        $e->return_value = $columns;
        return true;
    }
}
