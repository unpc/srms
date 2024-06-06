<?php

class Sample_Form {

    public static function setup_edit() {
        Event::bind('equipment.edit.tab', 'Sample_Form::eq_element_set_tab');
    }

    public static function eq_element_set_tab($e, $tabs) {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改基本信息', $equipment)) {
            $tabs
                ->add_tab('eq_element', [
                    'url' => $equipment->url('eq_element', null, null, 'edit'),
                    'title' => I18N::T('sample_form', '检测设置'),
                    'weight' => 50
                ]);
            Event::bind('equipment.edit.content', 'Sample_Form::eq_element_set_content', 0, 'eq_element');
        }
    }

    public static function eq_element_set_content($e, $tabs) {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if (!$me->is_allowed_to('修改基本信息', $equipment)) URI::redirect('error/401');

        $elements = Q("eq_element[equipment=$equipment]");

        $ids_remove = $elements->to_assoc('id', 'id');

        $form = Form::filter(Input::form());

        $has_error = FALSE;

        if ($form['submit']) {
            foreach ($form['element'] as $key => $value) {
                unset($ids_remove[$form['element'][$key]['id']]);

                if (!$value['name']) {
                    $form->set_error("element[$key][name]", I18N::T('sample_form', '请填写元素名称!'));
                    $has_error = TRUE;
                }

                if ($form->no_error) {
                    if ($value['id']) {
                        $element = O('eq_element', $value['id']);
                    } else {
                        $element = O('eq_element');
                    }
                    $element->equipment = $equipment;
                    $element->name = $value['name'];
                    $element->price = $value['price'];
                    $element->save();
                }
            }

            if ($ids_remove) {
                $ids = implode(',', $ids_remove);
                Q("eq_element[id=$ids]")->delete_all();
            }

            if (!$has_error) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('sample_form', '检测元素更新成功!'));
            }
        }

        $tabs->content = V('sample_form:eq_element/edit', [
            'form' => $form,
            'equipment' => $equipment,
        ]);

    }

    public static function extra_charge_setting_view($e, $equipment) {
        $me = L('ME');

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            $e->return_value = V('sample_form:charge/setting',[
                'equipment' => $equipment,
            ]);
        }
    }

    public static function extra_charge_setting_content($e, $form, $equipment) {
        $me = L('ME');

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            if ($form['submit']) {
                $sample_form_template = $form['sample_form'];
                $charge_template = $equipment->charge_template;
                $charge_template['sample_form'] = $sample_form_template;
                $equipment->charge_template = $charge_template;
            }
        }
    }

    public static function charge_edit_content_tabs($e, $tabs) {
        $me = L('ME');

		$equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            if ($equipment->charge_template['sample_form'] &&
                $equipment->charge_template['sample_form'] != 'no_charge_sample_form') {
                $tabs->content->third_tabs
                    ->add_tab('sample_form', [
                        'url' => $equipment->url('charge.sample_form', NULL, NULL, 'edit'),
                        'title'  => I18N::T('sample_form', '检测计费'),
                        'weight' => 6,
                    ]);

                Event::bind('equipment.charge.edit.content', 'Sample_Form::edit_charge_sample_form', 6, 'sample_form');
            }
        }
    }

    public static function edit_charge_sample_form($e, $tabs) {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            $form = Form::filter(Input::form());

            $elements = Q("eq_element[equipment=$equipment]");

            if ($form['submit']) {
                foreach ($form['price'] as $id => $price) {
                    $element = O('eq_element', $id);
                    $element->price = $price;
                    $element->save();
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备检测收费信息已更新'));
            }

            $tabs->content = V('sample_form:charge/sample_form', ['equipment' => $equipment, 'elements' => $elements]);
        }
    }

    public static function on_eq_element_saved($e, $element, $old_data, $new_data) {
        $me = L('ME');

        $config = Config::get('rpc.sample_form');

        $rpc = new RPC($config['url']);

        $rpc->set_header([
            "CLIENTID: {$config['client_id']}",
            "CLIENTSECRET: {$config['client_secret']}"
        ]);

        try {
            $data = [
                'id' => $element->id,
                'equipment_id' => $element->equipment->id,
                'name' => $element->name,
                'price' => $element->price,
            ];

            $result = $rpc->element->set($data);

            Log::add(strtr('[sample_form] %user_name[%user_id]将仪器[%equipment_id]的元素%element_name[%element_id]推送至样品检测模块 %result', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_id' => $element->equipment->id,
                '%element_name' => $element->name,
                '%element_id' => $element->id,
                '%result' => $result ? '成功' : '失败',
            ]), 'journal');

            return TRUE;

        } catch (Exception $e){

        }
    }

    public static function on_eq_element_deleted($e, $element) {
        $me = L('ME');

        $config = Config::get('rpc.sample_form');

        $rpc = new RPC($config['url']);

        $rpc->set_header([
            "CLIENTID: {$config['client_id']}",
            "CLIENTSECRET: {$config['client_secret']}"
        ]);

        try {
            $result = $rpc->element->delete($element->id);

            Log::add(strtr('[sample_form] %user_name[%user_id]删除了仪器[%equipment_id]的元素%element_name[%element_id] %result', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_id' => $element->equipment->id,
                '%element_name' => $element->name,
                '%element_id' => $element->id,
                '%result' => $result ? '成功' : '失败',
            ]), 'journal');

            return TRUE;

        } catch (Exception $e){

        }
    }

    static function on_equipment_saved($e, $equipment){
        $me = L('ME');

        $config = Config::get('rpc.sample_form');

        $rpc = new RPC($config['url']);

        $rpc->set_header([
            "CLIENTID: {$config['client_id']}",
            "CLIENTSECRET: {$config['client_secret']}"
        ]);

        try {
            $tag = $equipment->group;
            $group = $tag->id ? [$tag->id => $tag->name] : [] ;
            while($tag->parent->id && $tag->parent->root->id){
                $group += [$tag->parent->id => $tag->parent->name];
                $tag = $tag->parent;
            }
            $root = Tag_Model::root('equipment');
            $incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
            $contacts = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $tags = Q("{$equipment} tag_equipment[root=$root]")->to_assoc('id', 'name');

            $data = [
                'id' => $equipment->id,
                'icon16_url' => $equipment->icon_url('16'),
                'icon32_url' => $equipment->icon_url('32'),
                'icon48_url' => $equipment->icon_url('48'),
                'icon64_url' => $equipment->icon_url('64'),
                'icon128_url' => $equipment->icon_url('128'),
                'iconreal_url' => $equipment->icon_file('real') ? Config::get('system.base_url') . Cache::cache_file($equipment->icon_file('real')) . '?_=' . $equipment->mtime : $equipment->icon_url('128'),
                'url' => $equipment->url(),
                'accept_reserv' => $equipment->accept_reserv,
                'reserv_url' => $equipment->url('reserv'),
                'accept_sample' => $equipment->accept_sample,
                'sample_url' => $equipment->url('sample'),
                'name' => $equipment->name,
                'model_no' => $equipment->model_no,
                'specification' =>$equipment->specification,
                'price' => $equipment->price,
                'manu_at' => $equipment->manu_at,
                'manufacturer' => $equipment->manufacturer,
                'manu_date' => $equipment->manu_date,
                'purchased_date' => $equipment->purchased_date,
                'atime' => $equipment->atime,
                'group' => $group,
                'group_id' => $equipment->group->id ? : 0,
                'cat_no' =>$equipment->cat_no,
                'ref_no' => $equipment->ref_no,
                'location' => $equipment->location,
                'location2' => $equipment->location2,
                'tech_specs' => $equipment->tech_specs,
                'features' => $equipment->features,
                'configs' => $equipment->configs,
                'incharges' => $incharges,
                'contacts' => $contacts,
                'phone' => $equipment->phone,
                'email' => $equipment->email,
                'tags' => join(', ', $tags),
                'billing_dept_id' =>  $equipment->billing_dept_id,
                'charge_rule' => $equipment->ReferChargeRule,
                'require_training' => $equipment->require_training,
                'access_code' => $equipment->access_code,
                'control_mode' => $equipment->control_mode,
                'control_address' => $equipment->control_address,
                'is_using' => $equipment->is_using,
                'is_monitoring' => $equipment->is_monitoring,
                'is_monitoring_mtime' => $equipment->is_monitoring_mtime,
                'current_user' => $equipment->current_user()->name,
                'accept_limit_time' =>  $equipment->accept_limit_time,
                'status' => $equipment->status,
                'ctime' => $equipment->ctime,
                'source' => LAB_ID,
            ];

            $result = $rpc->equipment->set($data);

            Log::add(strtr('[sample_form] %user_name[%user_id]将仪器[%equipment_id]推送至样品检测模块 %result', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_id' => $equipment->id,
                '%result' => $result ? '成功' : '失败',
            ]), 'journal');

            return TRUE;

        } catch (Exception $e){

        }
    }

    public static function charge_get_source($e,$record){
        if($record->id && $record->sample_element->id){
            $e->return_value = $record->sample_element;
        }
    }

    //仪器使用记录显示计费部分
    static function record_description($e, $record, $current_user = null) {
        if(!$record->sample_element->id){
            return true;
        }
        $charge = O("eq_charge", ['source' => $record->sample_element]);
        if (!$charge->id || !$charge->charge_type || $charge->charge_type == 'reserv') $reserv_charge = O('eq_charge', ['source' => $record->reserv]);

        $e->return_value[] = V('eq_charge:record.notes', ['charge'=>$charge, 'reserv_charge'=>$reserv_charge, 'record'=> $record, 'current_user' => $current_user]);
        return false;
    }

}
