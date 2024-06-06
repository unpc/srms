<?php

class Labs_Support
{
    public static function setup()
    {
        if (L('ME')->access('管理所有内容') && !People::perm_in_uno()) {
            Event::bind('admin.columns.tab', ['Labs_Support', '_columns_tab'], 20, 'labs');
            Event::bind('admin.columns.content', ['Labs_Support', '_columns_content'], 20, 'labs');
        }
    }

    public static function _columns_tab($e, $tabs)
    {
        $tabs->add_tab('labs', [
            'url' => URI::url('admin/columns.labs'),
            'title' => I18N::T('equipments', '课题组列表'),
            'weight' => 30,
        ]);
    }

    public static function _columns_content($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $columns = (array) Lab::get('labs_list_show_columns') ?: Config::get('labs.list_default_show_columns');

        if ($form['submit']) {
            try {
                //排序
                $new_columns = [];
                foreach ($form['sort'] as $key) {
                    $new_columns[$key] = $columns[$key];
                    $new_columns[$key]['show'] = isset($form[$key]) && $form[$key] == 'on';
                }
                $columns = $new_columns;
                Lab::set('labs_list_show_columns', $columns);
                Log::add(strtr('[support] %user[%id]修改了课题组列表表头：为%value', [
                    '%user' => L('ME')->name,
                    '%id' => L('ME')->id,
                    '%value' => json_encode($columns),
                ]), 'support');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设置更新成功'));
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设置更新失败'));
            }
        }

        $tabs->content = V('labs:admin/support/labs_columns', ['columns' => $columns]);
    }

    public static function signup_validate_requires($e, $requires, $form)
    {
        foreach (Lab::get('signup_must', []) as $key => $value) {
            if ($value) {
                $requires[$key] = true;
            }
        }
    }
}

