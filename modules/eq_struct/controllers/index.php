<?php

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_add_click()
    {
        $me = L('ME');
        if (!$me->access('管理所有内容')) {
            JS::refresh();
        }
        JS::dialog(V('eq_struct:add', []), [
            'title' => I18N::T('eq_struct', '添加平台')
        ]);
    }

    public function index_add_submit()
    {
        $me = L('ME');
        if (!$me->access('管理所有内容')) {
            JS::refresh();
        }
        $form = Form::filter(Input::form());

        try {
            $struct = O('eq_struct');
            self::_validate($form, $struct);
            $columns = Config::get('eq_struct.edit_columns');
            foreach($columns as $key => $value) {
                $struct->$key = $form[$key];
            }

            if ($struct->save()) {
                Event::trigger('eq_struct.form.submit', $form, $struct);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_struct', '新增平台信息成功!'));

                Log::add(strtr('[eq_struct] %user_name[%user_id]新增了平台信息%params', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%params' => json_encode($form, JSON_UNESCAPED_UNICODE)
                ]), 'journal');
            }
            JS::refresh();
        } catch (Error_Exception $e) {
            JS::dialog(V('eq_struct:add', [
                'form' => $form
            ]), [
                'title' => I18N::T('eq_struct', '添加平台')
            ]);
        }
    }

    public function index_edit_click()
    {
        $me = L('ME');
        if (!$me->access('管理所有内容')) {
            JS::refresh();
        }
        $form = Input::form();
        $struct = O('eq_struct', $form['id']);
        if (!$struct->id) {
            JS::refresh();
        }
        JS::dialog(V('eq_struct:edit', [
            'struct' => $struct,
            'form' => $form,
        ]), [
            'title' => I18N::T('eq_struct', '编辑平台')
        ]);
    }


    public function index_edit_submit()
    {
        $me = L('ME');
        if (!$me->access('管理所有内容')) {
            JS::refresh();
        }
        $form = Form::filter(Input::form());
        $struct = O('eq_struct', $form['id']);
        if (!$struct->id) {
            JS::refresh();
        }

        try {
            $struct = O('eq_struct', $form['id']);
            self::_validate($form, $struct);

            $columns = Config::get('eq_struct.edit_columns');
            foreach($columns as $key => $value) {
                $struct->$key = $form[$key];
            }

            if ($struct->save()) {
                Event::trigger('eq_struct.form.submit', $form, $struct);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_struct', '编辑平台信息成功!'));
                Log::add(strtr('[eq_struct] %user_name[%user_id]编辑了平台信息%params', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%params' => json_encode($form, JSON_UNESCAPED_UNICODE)
                ]), 'journal');
            }
            JS::refresh();
        } catch (Error_Exception $e) {
            JS::dialog(V('eq_struct:edit', [
                'struct' => $struct,
                'form' => $form,
            ]), [
                'title' => I18N::T('eq_struct', '编辑平台')
            ]);
        }
    }

    public function index_delete_click()
    {
        $me = L('ME');
        if (!$me->access('管理所有内容')) {
            JS::refresh();
        }
        if(JS::confirm(T('您确定要删除吗?删除后不可恢复!'))){
            $form = Input::form();
            $struct = O('eq_struct', $form['id']);
            $connect_eqs = Q("{$struct}<struct equipment")->total_count();

            if (!$struct->id) {
                JS::refresh();
            }
            if ($connect_eqs) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_struct', '该账户已关联仪器，不可删除!'));
                JS::refresh();
            } elseif ($struct->delete()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_struct', '删除平台信息成功!'));
                Log::add(strtr('[eq_struct] %user_name[%user_id]删除了平台信息[%struct_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%struct_id' => $struct->id
                ]), 'journal');
            }
            JS::refresh();
        }
    }

    private static function _validate($form, $struct)
    {
        $columns = Config::get('eq_struct.edit_columns');
        foreach ($columns as $key => $value) {
            if (!$value['require']) {
                continue;
            }
            if (!$form[$key]) {
                $form->set_error($key, I18N::T('eq_struct', "请填写{$value['name']}!"));
            }
        }

        if ($columns['ref_no']['require'] && $columns['proj_no']['require']) {
            $exist_struct = O('eq_struct', [
                'ref_no' => $form['ref_no'],
                'proj_no' => $form['proj_no'],
            ]);
            if (($form['id'] && $exist_struct->id && $exist_struct->id != $form['id'])
            || (!$form['id'] && $exist_struct->id)) {
                $form->set_error('ref_no', I18N::T('eq_struct', '已存在平台编号、项目编号相同的平台信息!'));
                $form->set_error('proj_no', I18N::T('eq_struct', '已存在平台编号、项目编号相同的平台信息!'));
            }
        }
        Event::trigger('eq_struct.form.validate', $form, $struct);

        if (!$form->no_error) {
            throw new Error_Exception;
        }
    }
}
