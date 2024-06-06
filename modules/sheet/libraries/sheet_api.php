<?php

class Sheet_API
{
    private static function log()
    {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', ['%name' => '[sheet API]', '%str' => $str]), 'sheet');
        }
    }

    public static function equipment_sheet($e, $params, $data, $query)
    {
        $equipmentSheetKeys = [
            'name' => 'name',
            'refNo' => 'ref_no',
            'action' => 'action',
            'identity' => 'identity',
            'acceptSample' => 'accept_sample',
            'acceptReserv' => 'accept_reserv',
            'catNo' => 'cat_no',
            'price' => 'price',
            'chargeSettings' => 'charge_info',
            'specification' => 'specification',
            'model' => 'model_no',
            'manuAt' => 'manu_at',
            'manuDate' => 'manu_date',
            'manufacturer' => 'manufacturer',
            'specs' => 'tech_specs',
            'features' => 'features',
            'accessories' => 'configs',
            'contact' => 'contact',
            'reservSettings' => 'open_reserv',
            'owner' => 'incharge',
            'location' => 'location',
            'location2' => 'location2',
            'purchasedDate' => 'purchased_date',
            'atime' => 'atime',
            'controlMode' => 'control_mode',
            'group' => 'group',
            'phone' => 'phone',
            'email' => 'email',
            'id' => 'source_id',
            'site' => 'source_name',
            'icon' => 'source_icon',
        ];

        $location_root = Tag_Model::root('location');
        $group_root = Tag_Model::root('group');
        $rows = $data['equipments'];
        $index = 0;
        $response['total'] = count($rows);
        $response['success'] = 0;
        $response['records'] = [];
        foreach ($rows as $row) {
            self::log('equipment sheet: ' . json_encode($row, true));
            $res = self::validate_equipment_sheet_params($row);
            $res = new ArrayIterator($res);
            Event::trigger('import_equipment_sheet_extra_fields_validate', $res, $row);
            if ($res['message']) {
                $response['records'][$index++] = "第{$index}条记录上传失败, 原因: {$res['message']}";
                continue;
            }

            switch ($row['action']) {
                case 'create':
                    $equipment = O('equipment');
                    foreach ($equipmentSheetKeys as $k => $v) {
                        switch ($v) {
                            case 'contact':
                                // $contact = self::get_user($row[$k]);
                                break;
                            case 'incharge':
                                // $incharge = self::get_user($row[$k]);
                                break;
                            case 'extra':
                                continue;
                                break;
                            case 'source_id':
                            case 'site':
                                if (isset($row[$k])) $equipment->$v = $row[$k];
                                break;
                            case 'location':
                                if ($row['location']) {
                                    $location_parent = $location_root;
                                    foreach ($row['location'] as $location_info) {
                                        $location = O('tag_location', ['name' => $location_info[1]]);
                                        if (!$location->id) {
                                            $location = O('tag_location');
                                            $location->name = $location_info[1];
                                            $location->root = $location_root;
                                            $location->parent = $location_parent;
                                            $location->save();
                                            $location_parent = $location;
                                        }
                                    }
                                    $equipment->location = $location;
                                }else {
                                    $location = O('tag_location');
                                }
                                break;
                            case 'group':
                                if ($row['group']) {
                                    $group_parent = $group_root;
                                    foreach ($row['group'] as $group_info) {
                                        $group = O('tag_group', ['name' => $group_info[1]]);
                                        if (!$group->id) {
                                            $group = O('tag_group');
                                            $group->name = $group_info[1];
                                            $group->root = $group_root;
                                            $group->parent = $group_parent;
                                            $group->save();
                                            $group_parent = $group;
                                        }
                                    }
                                    $equipment->group = $group;
                                }else {
                                    $group = O('tag_group');
                                }
                                break;
                            default:
                                $equipment->$v = $row[$k];
                                break;
                        }
                    }

                    Event::trigger('import_equipment_sheet_extra_fields_save', $equipment, $row);
                    if ($equipment->save()) {
                        // $equipment->connect($incharge, 'incharge');
                        // $incharge->follow($equipment);

                        // $equipment->connect($contact, 'contact');
                        // $contact->follow($equipment);

                        $location_root->disconnect($equipment);
                        $location->connect($equipment);

                        $group_root->disconnect($equipment);
	                    $group->connect($equipment);

                        $response['ids'][$equipment->source_id] = $equipment->id;
                        $response['records'][$index++] = "第{$index}条记录上传成功";
                        $response['success']++;
                        if ($row['id']) $response['ids'][$row['id']] = $equipment->id;//来源站点id => 本站点id
                    } else {
                        $response['records'][$index++] = "第{$index}条记录上传失败, 原因: --";
                        continue;
                    }

                    break;
                case 'update':
                    $equipment = O('equipment', ['identity' => $row['identity'], 'source_name' => $row['site']]);
                    foreach ($equipmentSheetKeys as $k => $v) {
                        switch ($v) {
                            case 'contact':
                                // $contact = self::get_user($row[$k]);
                                break;
                            case 'incharge':
                                // $incharge = self::get_user($row[$k]);
                                break;
                            case 'extra':
                                continue;
                                break;
                            case 'location':
                                if ($row['location']) {
                                    $location_parent = $location_root;
                                    foreach ($row['location'] as $location_info) {
                                        $location = O('tag_location', ['name' => $location_info[1]]);
                                        if (!$location->id) {
                                            $location = O('tag_location');
                                            $location->name = $location_info[1];
                                            $location->root = $location_root;
                                            $location->parent = $location_parent;
                                            $location->save();
                                            $location_parent = $location;
                                        }
                                    }
                                    $equipment->location = $location;
                                }else {
                                    $location = O('tag_location');
                                }
                                break;
                            case 'group':
                                if ($row['group']) {
                                    $equipment->group->id && $equipment->group->disconnect($equipment);
                                    $group_parent = $group_root;
                                    foreach ($row['group'] as $group_info) {
                                        $group = O('tag_group', ['name' => $group_info[1]]);
                                        if (!$group->id) {
                                            $group = O('tag_group');
                                            $group->name = $group_info[1];
                                            $group->root = $group_root;
                                            $group->parent = $group_parent;
                                            $group->save();
                                            
                                        }
                                        $group->parent = $group_parent;
                                        $group->save();
                                        $group->connect($equipment);
                                        $group_parent = $group;
                                    }
                                    $equipment->group = $group;
                                }else {
                                    $group = O('tag_group');
                                }
                                break;
                            default:
                                $equipment->$v = $row[$k];
                                break;
                        }
                    }

                    Event::trigger('import_equipment_sheet_extra_fields_save', $equipment, $row);
                    if ($equipment->save()) {
                        foreach (Q("{$equipment}<incharge user") as $incharge) {
                            $equipment->disconnect($incharge, 'incharge');
                        }

                        foreach (Q("{$equipment}<contact user") as $contact) {
                            $equipment->disconnect($contact, 'contact');
                        }

                        // $equipment->connect($incharge, 'incharge');
                        // $incharge->follow($equipment);

                        // $equipment->connect($contact, 'contact');
                        // $contact->follow($equipment);

                        $location_root->disconnect($equipment);
                        $location->connect($equipment);

                        $group_root->disconnect($equipment);
	                    $group->connect($equipment);

                        $response['ids'][$equipment->source_id] = $equipment->id;
                        $response['records'][$index++] = "第{$index}条记录更新成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条记录更新失败, 原因: --";
                        continue;
                    }

                    break;
                case 'delete':
                    $equipment = O('equipment', ['identity' => $row['identity'], 'site' => $row['site']]);

                    if (!$equipment->id) {
                        $response['records'][$index++] = "第{$index}条记录删除失败, 原因: 未找到该台仪器";
                        continue;
                    }

                    if (Q("eq_record[equipment={$equipment}]")->total_count()
                        || Q("eq_sample[equipment={$equipment}]")->total_count()
                        || Q("eq_reserv[equipment={$equipment}]")->total_count()) {
                        $response['records'][$index++] = "第{$index}条记录删除失败, 原因: 该仪器存在使用/送样/预约等记录，不允许删除";
                        continue;
                    }

                    if ($equipment->delete()) {
                        $response['records'][$index++] = "第{$index}条记录删除成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条记录删除失败, 原因: --";
                        continue;
                    }
                    break;
            }
        }

        $e->return_value = $response;
    }

    public static function equipment_booking_sheet($e, $params, $data, $query)
    {
        $rows = $data['bookings'];
        $index = 0;
        $response['total'] = count($rows);
        $response['success'] = 0;
        $response['records'] = [];
        foreach ($rows as $row) {
            self::log('equipment booking sheet: ' . json_encode($row, true));
            $res = self::validate_equipment_booking_sheet_params($row);
            if ($res['message']) {
                $response['records'][$index++] = "第{$index}条预约记录上传失败, 原因: {$res['message']}";
                continue;
            }

            switch ($row['action']) {
                case 'create':
                case 'update':
                    $action = $row['action'] == 'create' ? '创建' : '更新';
                    $user = self::get_user($row['user'], $row['lab']);
                    $equipment = self::get_equipment($row['equipment']);

                    if (!$equipment->accept_reserv) {
                        $response['records'][$index++] = "第{$index}条预约记录{$action}失败, 原因: 仪器未开启预约功能";
                        continue;
                    }

                    $calendar = O('calendar', ['parent' => $equipment, 'type' => 'eq_reserv']);
                    if (!$calendar->id) {
                        $calendar = O('calendar');
                        $calendar->parent = $equipment;
                        $calendar->type = 'eq_reserv';
                        $calendar->name = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
                        $calendar->save();
                    }
                    $component = O('cal_component', ['identity' => $row['identity']]);
                    $component->calendar = $calendar;
                    $component->organizer = $user;
                    $component->dtstart = substr($row['startTime'], 0, 10);
                    $component->dtend = substr($row['endTime'], 0, 10);
                    $component->organizer = $user;
                    $component->identity = $row['identity'];
                    $component->name = $row['title'];
                    if ($component->save()) {
                        $reserv = O('eq_reserv', ['component' => $component]);
                        $reserv->identity = $component->identity;
                        $reserv->save();
                        $response['records'][$index++] = "第{$index}条预约记录{$action}成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条预约记录{$action}失败, 原因: --";
                        continue;
                    }
                    break;
                case 'delete':
                    $component = O('cal_component', ['identity' => $row['identity']]);

                    if (!$component->id) {
                        $response['records'][$index++] = "第{$index}条预约记录删除失败, 原因: 未找到该条预约记录";
                        continue;
                    }

                    if ($component->delete()) {
                        $response['records'][$index++] = "第{$index}条预约记录删除成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条预约记录删除失败, 原因: --";
                        continue;
                    }
                    break;
            }
        }
        $e->return_value = $response;
    }

    public static function equipment_sample_sheet($e, $params, $data, $query)
    {
        $rows = $data['samples'];
        $index = 0;
        $response['total'] = count($rows);
        $response['success'] = 0;
        $response['records'] = [];
        foreach ($rows as $row) {
            self::log('equipment sample sheet: ' . json_encode($row, true));
            $res = self::validate_equipment_sample_sheet_params($row);
            if ($res['message']) {
                $response['records'][$index++] = "第{$index}条送样记录上传失败, 原因: {$res['message']}";
                continue;
            }

            switch ($row['action']) {
                case 'create':
                case 'update':
                    $action = $row['action'] == 'create' ? '创建' : '更新';
                    $sender = self::get_user($row['user'], $row['lab']);
                    $lab = self::get_lab($row['lab']);
                    $equipment = self::get_equipment($row['equipment']);

                    if (!$equipment->accept_sample) {
                        $response['records'][$index++] = "第{$index}条送样记录{$action}失败, 原因: 仪器未开启送样功能";
                        continue;
                    }

                    $sample = O('eq_sample', ['identity' => $row['identity']]);
                    $sample->identity = $row['identity'];
                    $sample->sender = $sender;
                    $sample->equipment = $equipment;
                    $sample->lab = $lab;
                    $sample->dtsubmit = $row['submitTime'];
                    $sample->count = $row['count'];
                    $sample->status = EQ_Sample_Model::STATUS_TESTED; // 默认为已测试吧，否则页面中不会读取展示
                    if ($sample->save()) {
                        $response['records'][$index++] = "第{$index}条送样记录{$action}成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条送样记录{$action}失败, 原因: --";
                        continue;
                    }
                    break;
                case 'delete':
                    $sample = O('eq_sample', ['identity' => $row['identity']]);

                    if (!$sample->id) {
                        $response['records'][$index++] = "第{$index}条送样记录删除失败, 原因: 未找到该条送样记录";
                        continue;
                    }

                    if ($sample->delete()) {
                        $response['records'][$index++] = "第{$index}条送样记录删除成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条送样记录删除失败, 原因: --";
                        continue;
                    }
                    break;
            }
        }
        $e->return_value = $response;
    }

    public static function equipment_log_sheet($e, $params, $data, $query)
    {
        $rows = $data['logs'];
        $index = 0;
        $response['total'] = count($rows);
        $response['success'] = 0;
        $response['records'] = [];
        foreach ($rows as $row) {
            self::log('equipment log sheet: ' . json_encode($row, true));
            $res = self::validate_equipment_log_sheet_params($row);
            if ($res['message']) {
                $response['records'][$index++] = "第{$index}条使用记录上传失败, 原因: {$res['message']}";
                continue;
            }

            switch ($row['action']) {
                case 'create':
                case 'update':
                    $action = $row['action'] == 'create' ? '创建' : '更新';
                    $user = self::get_user($row['user'], $row['lab']);
                    $lab = self::get_lab($row['lab']);
                    $equipment = self::get_equipment($row['equipment']);

                    $record = O('eq_record', ['identity' => $row['identity']]);
                    $record->identity = $row['identity'];
                    $record->user = $user;
                    $record->equipment = $equipment;
                    $record->dtstart = substr($row['startTime'], 0, 10);
                    $record->dtend = substr($row['endTime'], 0, 10);
                    if ($record->save()) {
                        $response['records'][$index++] = "第{$index}条使用记录{$action}成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条使用记录{$action}失败, 原因: --";
                        continue;
                    }
                    break;
                case 'delete':
                    $record = O('eq_record', ['identity' => $row['identity']]);

                    if (!$record->id) {
                        $response['records'][$index++] = "第{$index}条使用记录删除失败, 原因: 未找到该条使用记录";
                        continue;
                    }

                    if ($record->delete()) {
                        $response['records'][$index++] = "第{$index}条使用记录删除成功";
                        $response['success']++;
                    } else {
                        $response['records'][$index++] = "第{$index}条使用记录删除失败, 原因: --";
                        continue;
                    }
                    break;
            }
        }
        $e->return_value = $response;
    }

    private static function validate_equipment_sheet_params($row)
    {
        $res = [];
        foreach ($row as $key => $value) {
            if ($res['message']) {
                return $res;
            }

            switch ($key) {
                case 'name':
                case 'identity':
                // case 'catNo':
                // case 'model':
                    if (!$value) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'refNo':
                    if ($row['action'] == 'create' && O('equipment', ['ref_no' => $value])->id) {
                        $res['message'] = '仪器在系统中已存在, 无法创建';
                        break;
                    }
                    if (($row['action'] == 'update' || $row['action'] == 'delete') && !O('equipment', ['ref_no' => $value])->id) {
                        $res['message'] = '仪器在系统中不存在, 无法更新或删除';
                    }
                    break;
                case 'owner':
                case 'contact':
                    // if (!$value['identity'] || !$value['name']) {
                        // $res['message'] = $key . '字段不能为空';
                    // }
                    break;
                case 'action':
                    if (!in_array($value, ['create', 'update', 'delete'])) {
                        $res['message'] = '操作类型action未能识别, 无法进行处理';
                    }
                    break;
            }
        }

        return $res;
    }

    private static function validate_equipment_booking_sheet_params($row)
    {
        $res = [];
        foreach ($row as $key => $value) {
            if ($res['message']) {
                return $res;
            }

            switch ($key) {
                case 'title':
                case 'startTime':
                case 'endTime':
                case 'identity':
                    if (!$value) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'user':
                case 'lab':
                case 'equipment':
                    if (!$value['identity'] || !$value['name']) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'action':
                    if (!in_array($value, ['create', 'update', 'delete'])) {
                        $res['message'] = '预约唯一标识不正确, 无法进行处理';
                    }
                    break;
            }
        }

        return $res;
    }

    private static function validate_equipment_sample_sheet_params($row)
    {
        $res = [];
        foreach ($row as $key => $value) {
            if ($res['message']) {
                return $res;
            }

            switch ($key) {
                case 'count':
                case 'submitTime':
                case 'identity':
                    if (!$value) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'user':
                case 'lab':
                case 'equipment':
                    if (!$value['identity'] || !$value['name']) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'action':
                    if (!in_array($value, ['create', 'update', 'delete'])) {
                        $res['message'] = '送样唯一标识不正确, 无法进行处理';
                    }
                    break;
            }
        }

        return $res;
    }

    private static function validate_equipment_log_sheet_params($row)
    {
        $res = [];
        foreach ($row as $key => $value) {
            if ($res['message']) {
                return $res;
            }

            switch ($key) {
                case 'startTime':
                case 'endTime':
                case 'identity':
                    if (!$value) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'user':
                case 'lab':
                    if (!$value['identity'] || !$value['name']) {
                        $res['message'] = $key . '字段不能为空';
                    }
                    break;
                case 'equipment':
                    if (!$value['identity'] || !$value['name']) {
                        $res['message'] = $key . '字段不能为空';
                        break;
                    }
                    $equipment = O('equipment', ['identity' => $value['identity']]);
                    if (!$equipment->id) {
                        $equipment = O('equipment', ['ref_no' => $value['identity']]);
                        if (!$equipment->id) $res['message'] = $value['identity'] . '不存在';
                        break;
                    }
                    break;
                case 'action':
                    if (!in_array($value, ['create', 'update', 'delete'])) {
                        $res['message'] = '送样唯一标识不正确, 无法进行处理';
                    }
                    break;
            }
        }

        return $res;
    }

    private static function get_user($info, $lab_info = [])
    {
        $user = O('user', ['identity' => $info['identity']]);
        if (!$user->id) {
            $user->identity = $info['identity'];
            $user->name = $info['name'];
            $user->phone = $info['phone'] ?: '';
            $user->email = $info['email'] ?: null;
            $user->token = Auth::make_token($info['identity'], Config::get('auth.default_backend'));
            Event::trigger('import_user_sheet_extra_fields_save', $user, $info);
            if ($user->save()) {
                $lab = self::get_lab($lab_info);
                $user->connect($lab);
            } else {
                $user = O('user');
            }
        }

        return $user;
    }

    private static function get_lab($info = [])
    {
        $lab = O('lab', ['identity' => $info['identity']]);
        if (!$lab->id) {
            if ($info['identity']) {
                $lab->identity = $info['identity'];
                $lab->name = $info['name'];
                $lab->atime = Date::time();
                Event::trigger('import_lab_sheet_extra_fields_save', $lab, $info);
                $lab->save();
            } else {
                $lab = O('lab', ['name' => '仪器对接临时课题组']);
                if (!$lab->id) {
                    $lab->name = '仪器对接临时课题组';
                    $lab->atime = Date::time();
                    $lab->save();
                }
            }
        }

        return $lab;
    }

    private static function get_equipment($info)
    {
        $equipment = O('equipment', ['identity' => $info['identity']]);
        if (!$equipment->id) {
            $equipment = O('equipment', ['ref_no' => $info['identity']]);
            if (!$equipment->id) {
                $equipment->identity = $info['identity'];
                $equipment->name = $info['name'];
                Event::trigger('import_equipment_sheet_extra_fields_save', $equipment, $info);
                $equipment->save();
            }
        }

        return $equipment;
    }

    public static function on_eq_charge_before_save($e, $charge, $new_data)
    {
        if (!$charge->source->id) {
            return true;
        }

        if ($charge->source->name() == 'eq_reserv' && $charge->source->component->identity) {
            $no_create_charge = true;
        }

        if ($charge->source->identity) {
            $no_create_charge = true;
        }

        if ($no_create_charge) {
            $e->return_value = false;
            return true;
        }

    }

}
