<?php

class Support_Reset
{

    static function setup($e, $controller, $method, $params) {
        //主从站点不开启'系统相关'
        if (Module::is_installed('uno')) return;
        if (!Module::is_installed('db_sync')) {
            Event::bind('admin.support.tab', ['Support_Reset', '_system_reset_tab'], 15, 'system_reset');
            Event::bind('admin.support.content', ['Support_Reset', '_system_reset_content'], 15, 'system_reset');
        }
    }

    public static function _system_reset_tab($e, $tabs)
    {
        if (Module::is_installed('uno')) return;
        $me = L('ME');
        $tabs->add_tab('system_reset', [
            'url'    => URI::url('admin/support.system_reset'),
            'title'  => I18N::T('support', '系统相关'),
            'weight' => 100,
        ]);
    }

    public static function _system_reset_content($e, $tabs)
    {
        if (Module::is_installed('uno')) return;
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }

        $select = $tabs->selected;

        $tabs->content = V('support:system_reset');
    }

}
