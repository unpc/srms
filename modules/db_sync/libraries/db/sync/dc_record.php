<?php

class DB_Sync_Dc_Record
{
    public static function extra_site_column($e, $columns, $dc_record, $form)
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

    public static function extra_site_row($e, $row, $dc_record)
    {
        if (DB_SYNC::is_master()) {
            $row['site'] = H(Config::get('site.map')[$dc_record->door->site]) ?: '--';
        }
    }

    public static function extra_site_selector($e, $selector, $form = [])
    {
        if (DB_SYNC::is_master()) {
            if ($form['site']) {
                $selector = "door[site={$form['site']}] " . $selector;
            }
        } else {
            $selector = "door[site=" . LAB_ID . "] " . $selector;
        }
        $e->return_value = $selector;
    }

    public static function dc_record_links_edit($e, $object, $links, $mode)
    {
        $me = L('ME');
        if (Db_Sync::is_slave()) {
            if ($me->is_allowed_to('删除', $object)) {
                $links['delete'] = [
                    'url'   => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                        'q-object' => 'delete_record',
                        'q-event'  => 'click',
                        'q-static' => ['id' => $object->id, 'uniqid' => uniqid()],
                        'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!entrance/dc_record/index', '', true),
                    ]]),
                    'text'  => I18N::T('entrance', '删除'),
                    'extra' => 'class="blue"',
                    // 'extra' => 'class="blue" q-object="delete_record" q-event="click" q-static="' . H(['id' => $this->id, 'uniqid' => $uniqid]) . '" q-src="' . H(URI::url('!entrance/dc_record/index')) . '"',
                ];
            }

            if (!Q("subsite[ref_no={$object->door->site}]<incharge {$user}")->total_count()) {
                unset($links['delete']);
            }
        }
    }

}
