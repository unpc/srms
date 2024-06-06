<?php

class Support_Data
{

    static function setup($e, $controller, $method, $params) {
        //主从站点不开启'数据删除'
        if (!Module::is_installed('db_sync')){
            Event::bind('admin.support.tab', ['Support_Data', '_data_delete_tab'], 15, 'data_delete');
            Event::bind('admin.support.content', ['Support_Data', '_data_delete_content'], 15, 'data_delete');
        }
    }

    public static function _data_delete_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('data_delete', [
            'url'    => URI::url('admin/support.data_delete'),
            'title'  => I18N::T('support', '数据删除'),
            'weight' => 0,
        ]);
    }

    public static function _data_delete_content($e, $tabs)
    {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }

        $select = $tabs->selected;

        $tabs->content = V('support:data_delete');
    }

}
