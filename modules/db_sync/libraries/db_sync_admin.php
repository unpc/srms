<?php

class Db_Sync_Admin
{
    public static function _secondary_db_sync_content($e, $tabs)
    {
        $subsites = Q('subsite');

        $panel_buttons   = new ArrayIterator;
        $panel_buttons[] = [
            'text'  => I18N::T('db_sync', '添加分站点'),
            'extra' => 'q-object="add_subsite" q-event="click" q-src="' . URI::url('!db_sync/subsite') . '" class="button button_add middle"',
        ];

        $tabs->content = V('db_sync:admin/subsite', [
            'subsites'     => $subsites,
            'panel_buttons' => $panel_buttons,
        ]);
    }
}
