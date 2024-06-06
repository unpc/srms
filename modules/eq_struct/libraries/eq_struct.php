<?php

class Eq_Struct
{
    public static function secondary_tabs($e, $secondary_tabs)
    {
        $me = L('ME');
        if ($me->access('管理所有内容')) {
            $secondary_tabs->add_tab('struct', [
                'url' => URI::url('admin/equipment.struct'),
                'title'=> I18N::T('eq_struct', '仪器入账管理'),
            ]);
        }

        Event::bind('admin.equipment.content', 'Eq_Struct::_struct_content', 0, 'struct');
    }

    public static function _struct_content($e, $tabs)
    {
        $form = Lab::form();
        // 可按照平台编号、平台名称、所属单位、项目编号、财务收费账户进行搜索
        $pre_selector = new ArrayIterator;
        $selector = 'eq_struct';
        $where = new ArrayIterator;
        if ($form['ref_no']) {
            $where['ref_no'] = "[ref_no*={$form['ref_no']}]";
        }
        if ($form['name']) {
            $where['name'] = "[name*={$form['name']}]";
        }
        $groupRoot = Tag_Model::root('group');
        if ($form['group'] && $groupRoot->id != $form['group']) {
            $where['group'] = "[group*={$form['group']}]";
        }
        if ($form['proj_no']) {
            $where['proj_no'] = "[proj_no*={$form['proj_no']}]";
        }
        if ($form['card_no']) {
            $where['card_no'] = "[card_no*={$form['card_no']}]";
        }
        Event::trigger('eq_struct.extra.selector', $form, $selector, $pre_selector, $where);

        $selector = join(' ', (array)$pre_selector) . ' ' . $selector . join('', (array)$where);
        $eq_structs = Q($selector);
        $panel_buttons = [];
        $panel_buttons[] = [
            'text' => I18N::T('eq_struct', '添加'),
            'extra' => 'q-object="add" q-event="click" q-src="' . URI::url('!eq_struct/index') .
                    '" class="button button_add"'
        ];

        $start = (int) $form['st'];
        $per_page = 50;
        $pagination = Lab::pagination($eq_structs, $start, $per_page);
        $tabs->content = V('eq_struct:admin/table/index', [
            'form' => $form,
            'eq_structs' => $eq_structs,
            'pagination' => $pagination,
            'panel_buttons' => $panel_buttons
        ]);
    }

    public static function equipment_post_submit_validate($e, $form)
    {
        $me = L('ME');
        if (Config::get('eq_struct.require.struct', false)
            && $me->is_allowed_to('修改仪器入账', 'equipment')) {
            if (empty($form['Struct'])) {
                $form->set_error('Struct', I18N::T('equipments', '仪器隶属机组不能为空!'));
            }
            return TRUE;
        }
    }

    public static function equipment_post_submit($e, $form, $equipment)
    {
        $me = L('ME');
        if ($me->is_allowed_to('修改仪器入账', $equipment)) {
            $me = L('ME');
            if ((int)$form['Struct']) {
                $struct = O('eq_struct', (int)$form['Struct']);
                $equipment->struct = $struct;
                Log::add(strtr('[eq_struct] %user_name[%user_id]将仪器%eq_name[%eq_id]隶属平台设置为%struct_name[%struct_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%eq_name' => $equipment->name,
                    '%eq_id' => $equipment->id,
                    '%struct_name' => $struct->name,
                    '%struct_id' => $struct->id
                ]), 'journal');
            } else {
                $struct = O('eq_struct');
                $equipment->struct = $struct;
                Log::add(strtr('[eq_struct] %user_name[%user_id]将仪器%eq_name[%eq_id]隶属平台设置为空', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%eq_name' => $equipment->name,
                    '%eq_id' => $equipment->id
                ]), 'journal');
            }
        }
    }

    public static function equipment_ACL($e, $user, $perm_name, $object, $options)
    {
        if ($object->id && $object->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            $e->return_value = false;
            return;
        }
        switch ($perm_name) {
            case '修改仪器入账':
                $e->return_value = true;
                break;
        }
        return;
    }
}
