<?php

class DB_Sync_Eq_Record
{
    public static function eq_object_links_edit($e, $object, $links, $mode, $ajax_id = null)
    {
        $me = L('ME');
        if (!DB_SYNC::is_module_unify_manage('eq_record')) return;
        if ($me->is_allowed_to('反馈', $object) && !$object->is_locked) {
            $equipment         = $object->equipment;
            $links['feedback'] = [
                'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                    'q-object' => 'feedback_edit',
                    'q-event'  => 'click',
                    'q-static' => ['record_id' => $object->id],
                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!equipments/equipment/index.' . $object->id . '.records', '', true),
                ]]),
                'text'  => I18N::T('equipments', '反馈'),
                'extra' => 'class="blue"',
            ];
        }
        if (isset($links['edit']) && $me->is_allowed_to('修改', $object) && !$object->is_locked) {
            $links['edit'] = [
                'url' => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                    'q-object' => 'record_edit',
                    'q-event'  => 'click',
                    'q-static' => ['record_id' => $object->id],
                    'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!equipments/records/index.' . $object->id, '', true),
                ]]),
                'text' => I18N::T('equipments', '编辑'),
                'extra'=>' class="blue"',
            ];
        }
    }

    public static function extra_site_column($e, $form, $columns, $type)
    {
        if (DB_SYNC::is_module_unify_manage('eq_record')) {
            $columns['site'] = [
                'title'  => I18N::T('db_sync', '所属站点'),
                'filter' => [
                    'form'  => V('db_sync:filters/site', ['site' => $form['site']]),
                    'value' => $form['site'] ? H(Config::get('site.map')[$form['site']]) : null,
                ],
                'nowrap' => true,
                'weight' => 3,
            ];
        }
    }

    public static function extra_site_row($e, $row, $record, $type)
    {
        if (DB_SYNC::is_module_unify_manage('eq_record')) {
            $row['site'] = H(Config::get('site.map')[$record->equipment->site]) ?: '--';
        }
    }

    public static function extra_pre_selector($e, $form, $pre_selectors)
    {
        if (DB_SYNC::is_module_unify_manage('eq_record') && DB_SYNC::is_slave()) {
            $slave_filter = "[site=" . LAB_ID . "]";
            if ($pre_selectors['equipment']) {
                $pre_selectors['equipment'] .= $slave_filter;
            } else {
                $pre_selectors['equipment'] = "equipment" . $slave_filter;
            }
            $e->return_value = $pre_selectors;
            return false;
        }

        if (DB_SYNC::is_module_unify_manage('eq_record') && $form['site']) {
            $slave_filter = "[site={$form['site']}]";
            if ($pre_selectors['equipment']) {
                $pre_selectors['equipment'] .= $slave_filter;
            } else {
                $pre_selectors['equipment'] = "equipment" . $slave_filter;
            }
            $e->return_value = $pre_selectors;
            return false;
        }

        $e->return_value = $pre_selectors;
        return true;
    }

    public static function get_export_record_columns($e, $columns, $type = null)
    {
        if (DB_SYNC::is_module_unify_manage("eq_record")) {
            $columns['site'] = '所属站点';
        }

        $e->return_value = $columns;
        return true;
    }
}
