<?php
class Logs_Admin
{
    public static function setup()
    {
        $me = L('ME');
        if (in_array($me->token, Config::get('log.admin'))) {
            Event::bind('admin.index.tab', 'Logs_Admin::_primary_tab');
        }
    }

    public static function _primary_tab($e, $tabs)
    {
        Event::bind('admin.index.content', 'Logs_Admin::_primary_content', 100, 'logs');
        $tabs->add_tab('logs', [
            'url'    => URI::url('admin/logs'),
            'title'  => I18N::T('logs', '系统日志'),
            'weight' => 160,
        ]);
    }

    public static function _primary_content($e, $tabs)
    {
        $log_path = Config::get('log_path.path');
        $log_list = Logs::log_list($log_path, null);

        $tabs->content = V('logs:list', [
            'path' => $log_path,
            'logs' => $log_list,

        ]);
    }

}
