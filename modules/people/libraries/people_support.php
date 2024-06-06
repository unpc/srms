<?php

class People_Support
{
    public static function setup()
    {
        if (L('ME')->access('管理所有内容') && !People::perm_in_uno()) {
            Event::bind('admin.columns.tab', ['People_Support', '_columns_tab'], 15, 'people');
            Event::bind('admin.columns.content', ['People_Support', '_columns_content'], 15, 'people');
        }
    }

    public static function _columns_tab($e, $tabs)
    {
        $tabs->add_tab('people', [
            'url' => URI::url('admin/columns.people'),
            'title' => I18N::T('equipments', '人员列表'),
            'weight' => 20,
        ]);
    }

    public static function _columns_content($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $columns = (array) Lab::get('people_list_show_columns') ?: Config::get('people.list_default_show_columns');

        if ($form['submit']) {
            try {
                //排序
                $new_columns = [];
                foreach ($form['sort'] as $key) {
                    $new_columns[$key] = $columns[$key];
                    $new_columns[$key]['show'] = isset($form[$key]) && $form[$key] == 'on';
                }
                $columns = $new_columns;
                Lab::set('people_list_show_columns', $columns);
                Log::add(strtr('[support] %user[%id]修改了人员列表表头：为%value', [
                    '%user' => L('ME')->name,
                    '%id' => L('ME')->id,
                    '%value' => json_encode($columns),
                ]), 'support');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设置更新成功'));
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设置更新失败'));
            }
        }

        $tabs->content = V('people:admin/support/people_columns', ['columns' => $columns]);
    }

    public function signup_user_requoires($e, $requires, $user){
        foreach (Lab::get('signup_edit_must', []) as $key => $value) {
            if ($value) {
                $requires[$key] = true;
            }
        }
        $e->return_value = $requires;
        return true;
    }
}

