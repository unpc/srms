<?php

class Support_System
{

    public static function setup($e, $controller, $method, $params)
    {
        Event::bind('admin.support.tab', ['Support_System', '_system_info_tab'], 15, 'system_info');
        Event::bind('admin.support.content', ['Support_System', '_system_info_content'], 15, 'system_info');
    }

    public static function _system_info_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('system_info', [
            'url'    => URI::url('admin/support.system_info'),
            'title'  => I18N::T('support', '系统情况'),
            'weight' => -1,
        ]);
    }

    public static function _system_info_content($e, $tabs)
    {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }

        $select = $tabs->selected;

        $tabs->content = V('support:system_info/layout');
    }

    public static function system_info_stat($e, $output)
    {
        $output[] = (string) V('support:system_info/download_summary');
    }
}
