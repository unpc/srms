<?php

class API_Equipment_Info extends API_Common {
    
    function get_equipments($start = 0, $step = 100) {
        $this->_ready();
        
        $selector = Event::trigger('equipment.info.api.selector', 'equipment') ? : 'equipment';
        
        $equipments = Q($selector)->limit($start, $step);
        $info = [];

        if (count($equipments)) foreach ($equipments as $equipment) {
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

            $location1 = '';
            if (Config::get('equipment.location_type_select')){
                $ls = Q("{$equipment} tag_location")->to_assoc('id', 'name');
                if (count($ls)) {
                    $location1 .= join(' ', $ls);
                }
            }else{
                $location1 = $equipment->location;
            }

            $data = new ArrayIterator([
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
                'en_name' => $equipment->en_name,
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
                'location' => $location1,
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
                'accounts_date' => $equipment->accounts_date,
                'fund_source' => $equipment->fund_source,
                'get_method' => $equipment->get_method,
                'source' => LAB_ID,
            ]);

            Event::trigger('equipment.info.api.extra', $equipment, $data);

            $info[] = $data->getArrayCopy();;
        }

        return $info;
    }

    function get_equipment($id) {
        $this->_ready();

        $equipment = O('equipment', $id);

        if (!$equipment->id) {
            return [];
        }

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
            'accounts_date' => $equipment->accounts_date,
            'fund_source' => $equipment->fund_source,
            'get_method' => $equipment->get_method,
            'source' => LAB_ID,
        ];

        return $data;
    }

    function get_equipment_status() {
        $this->_ready();

        return EQ_Status_Model::$status;
    }

    function set_equipment_status($params = []) {
        $this->_ready();

        $equipment = O('equipment', $params['equipmentId']);
        if (!$equipment->id) throw new API_Exception(self::$errors[404], 404);

        $now = time();
        if ($params['status'] == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            // ** => 报废
            $status = O('eq_status', [
                'equipment' => $equipment,
                'status' => $equipment->status,
                'dtend' => 0
            ]);

            if ($status->id) {
                $status->dtend = $now;
                $status->save();
            }

            $status = O('eq_status');
            $status->equipment = $equipment;
            $status->dtstart = $now;
            $status->status = $params['status'];

            $sql = "update `eq_record` set status = 1, feedback = '仪器废弃时自动对记录进行反馈!' where equipment_id = '{$equipment->id}'";
            
            ORM_Model::db('eq_record')->query($sql);
            Event::trigger('yiqikong.on_equipment_deleted');
        }
        elseif ($params['status'] == EQ_Status_Model::IN_SERVICE) {
            // 其他 => 正常
            $status = O('eq_status', [
                'equipment' => $equipment,
                'status' => $equipment->status,
                'dtend' => 0
            ]);

            if (!$status->id) throw new API_Exception(self::$errors[404], 404);
            $status->dtend = $now;
        }
        else {
            // 关闭之前的记录
            foreach(Q("eq_status[equipment=$equipment][dtend=0]") as $s) {
                $s->dtend = $now - 1;
                $s->save();
            }
            // 正常 => 其他
            $status = O('eq_status');
            $status->dtstart = $now;
            $status->equipment = $equipment;
            $status->status = $params['status'];
        }

        $status->description = $params['description'];
        $status->save();
        $equipment->status = $params['status'];
        $equipment->save();

        return [
            'id' => $status->id,
            'equipmentId' => $status->equipment->id,
            'status' => $equipment->status,
            'description' => $status->description,
        ];
    }
}
