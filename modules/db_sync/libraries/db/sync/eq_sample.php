<?php

class DB_Sync_Eq_Sample
{
    public static function extra_site_column($e, $form, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('eq_sample')) {
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

    public static function extra_site_row($e, $row, $sample)
    {
        if (DB_SYNC::is_module_unify_manage('eq_sample')) {
            $row['site'] = H(Config::get('site.map')[$sample->equipment->site]) ?: '--';
        }
    }

    public static function sample_search_filter_submit($e, $selector, $form, $pre_selectors)
    {
        if (DB_SYNC::is_slave() && DB_SYNC::is_module_unify_manage('eq_sample')) {
            $slave_filter = "[site=" . LAB_ID . "]";
            if ($pre_selectors['equipment']) {
                $pre_selectors['equipment'] .= $slave_filter;
            } else {
                $pre_selectors['equipment'] = "equipment" . $slave_filter;
            }
            $e->return_value = $selector;
            return false;
        }

        if (DB_SYNC::is_module_unify_manage('eq_sample') && $form['site']) {
            $slave_filter = "[site={$form['site']}]";
            if ($pre_selectors['equipment']) {
                $pre_selectors['equipment'] .= $slave_filter;
            } else {
                $pre_selectors['equipment'] = "equipment" . $slave_filter;
            }
            $e->return_value = $selector;
            return false;
        }

        $e->return_value = $selector;
        return true;
    }

    public static function get_export_sample_columns($e, $columns)
    {
        if (DB_SYNC::is_module_unify_manage('eq_sample')) {
            $columns['site'] = '所属站点';
        }

        $e->return_value = $columns;
        return true;
    }

    public static function eq_sample_links_edit($e, $sample, $links, $mode)
    {
        $me = L('ME');
        if (!DB_SYNC::is_module_unify_manage('eq_sample')) return;
        if (isset($links['update']) && $me->is_allowed_to('修改', $sample)) {
            $links['update'] = [
                'url' => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                    'q-object' => 'edit_sample',
                    'q-event'  => 'click',
                    'q-static' => ['id' => $sample->id],
                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!eq_sample/index', '', true),
                ]]),
                'text' => I18N::T('eq_sample', '编辑'),
                'extra'=>' class="blue"',
            ];
        }
    }
}
