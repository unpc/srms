<?php

class Equipments_Support
{
    public static function setup()
    {
        if (L('ME')->access('管理所有内容')) {
            Event::bind('admin.columns.tab', ['Equipments_Support', '_columns_tab'], 15, 'equipments');
            Event::bind('admin.columns.content', ['Equipments_Support', '_columns_content'], 15, 'equipments');
        }
    }

    public static function _columns_tab($e, $tabs)
    {
        $tabs->add_tab('equipments', [
            'url' => URI::url('admin/columns.equipments'),
            'title' => I18N::T('equipments', '仪器列表'),
            'weight' => 10,
        ]);
    }

    public static function _columns_content($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $columns = (array) Lab::get('equipments_list_show_columns') ?: Config::get('equipments.list_default_show_columns');

        if ($form['submit']) {
            try {
                //排序
                $new_columns = [];
                foreach ($form['sort'] as $key) {
                    $new_columns[$key] = $columns[$key];
                    $new_columns[$key]['show'] = isset($form[$key]) && $form[$key] == 'on';
                }
                $columns = $new_columns;
                Lab::set('equipments_list_show_columns', $columns);
                Log::add(strtr('[support] %user[%id]修改了仪器列表表头：为%value', [
                    '%user' => L('ME')->name,
                    '%id' => L('ME')->id,
                    '%value' => json_encode($columns),
                ]), 'support');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设置更新成功'));
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设置更新失败'));
            }
        }

        $tabs->content = V('equipments:admin/support/equipment_columns', ['columns' => $columns]);
    }
}

