<?php
// 服务记录上报国家科技部
class Nrii_Record
{

    static $record_keys = [
        'inner_id'          => 'innerId',
        'amounts'           => 'amounts',
        'service_time'      => 'serviceTime',
        'service_content'   => 'serviceContent',
        'service_way'       => 'serviceWay',
        // 'service_amount' => 'serviceAmount',
        'service_time'      => 'serviceAmount',
        'subject_name'      => 'subjectName',
        'subject_income'    => 'subjectIncome',
        'subject_area'      => 'subjectArea',
        'subject_content'   => 'subjectContent',
        'applicant'         => 'applicant',
        'applicant_phone'   => 'applicantPhone',
        'applicant_email'   => 'applicantEmail',
        'applicant_unit'    => 'applicantUnit',
        'comment'           => 'comment',
        'end_time'          => 'endTime', // 服务结束时间YYYY-MM-DD HH
        'service_type'      => 'serviceType', // 服务对象Int 内部用户、外部用户(120、130)
        'service_direction' => 'serviceDirection', // 服务类型 Int 科学研究、科技开发、教学、其他(135、136、137、138)
        'tax_record'        => 'record', // 补税记录

        'address_type'      => 'adressType', // 是否在单位内使用
        'move_address'      => 'moveAddress', // 对外服务地址
        'service_code'      => 'serviceCode', // 非适用简易程序海关《通知书》编号
        'sign_agreement'    => 'signAgreement', // 本次服务是否签订协议
    ];

    // 将nrii_record直接通过webservice推送至国家科技部
    public static function sync($record)
    {
        if (!$record->id || $record->nrii_status == 100) {
            return;
        }

        $list                  = [];
        $list['insCode']       = Config::get("nrii")[LAB_ID];

        $newList = Event::trigger('sync.nrii.params', $list, 'record', $record);
        if($newList) {
            $list = $newList;
        }

        $list['recordInnerId'] = $record->id;
        $list['auditStatus']   = -1;
        Event::trigger('nrii.equipment.record.push_columns');
        foreach (self::$record_keys as $key => $value) {
            if ($key == 'service_way') {
                $wayList = explode(',', $record->$key);
                foreach ($wayList as $k => $v) {
                    $wayList[$k] = Nrii_Record_Model::$service_way[$v];
                }
                $list[$value] = implode(',', $wayList);
            } elseif ($key == 'subject_income') {
                $list[$value] = Nrii_Record_Model::$subject_income[$record->$key];
            } elseif ($key == 'subject_area') {
                $list[$value] = implode(',', json_decode($record->$key, true));
            } elseif ($key == 'comment') {
                $list[$value] = Nrii_Record_Model::$comment[$record->$key];
            } elseif ($key == 'service_time' || $key == 'end_time') {
                $list[$value] = date('Y-m-d H', $record->$key);
            } elseif ($key == 'address_type' || $key == 'sign_agreement') {
                $list[$value] = (int) $record->$key;
            } elseif ($key == 'service_type') {
                $list[$value] = Nrii_Record_Model::$service_types[$record->$key];
            } elseif ($key == 'service_direction') {
                $list[$value] = Nrii_Record_Model::$service_directions[$record->$key];
            } else {
                $list[$value] = $record->$key;
            }
        }
        $new = Event::trigger('nrii.equipment.record.push_value', $list);
        if ($new && !empty($new)) {
            $list = array_merge($list, $new);
        }
        $record->nrii_status = NSoap::push('instru', LAB_ID, 6, $list, 'instruInfo');
        $record->save();
    }

    // 载入多个仪器数据进入服务记录
    public static function importRecords()
    {
        $eqs   = Q("nrii_equipment[eq_id]");
        $start = $num = 0;
        $step  = 10;
        $total = $eqs->total_count();

        while ($start <= $total) {
            $equipments = $eqs->limit($start, $step);
            foreach ($equipments as $equipment) {
                self::importRecord($equipment);
                $num++;
            }
            $start += $step;
        }
    }

    // 载入单个仪器的服务记录
    public static function importRecord($equipment)
    {
        if (!$equipment->eq_id) {
            return;
        }

        $inner_id = $equipment->inner_id;

        $equipmentOrigin = O("equipment", $equipment->eq_id);
        // 每条使用记录对应一条服务记录WHERE `equipment_id` = {$equipment->eq_id} AND is_locked = 1) a
        $db      = Database::factory();
        $sources = ['eq_record', 'eq_sample'];
        foreach ($sources as $source_name) {
            switch ($source_name) {
                case 'eq_record':
                    $SQL = "SELECT `a`.`feedback`, `a`.`dtstart`, `a`.`dtend`, `a`.`id`, `a`.`project_id`, `a`.`user_id`,
                        `b`.`count`, `b`.`record_id`,
                        `c`.`name` AS `proj_name`, `c`.`_extra` AS `project_extra`,
                        `d`.`name` AS `user_name`, `d`.`phone`, `d`.`email`,
                        `e`.`amount`,
                        `f`.`name` AS `tag_name`
                        FROM `eq_record` AS `a`
                        LEFT OUTER JOIN `eq_sample` AS `b` ON (`a`.`id` = `b`.`record_id`)
                        LEFT OUTER JOIN `lab_project` AS `c` ON (`a`.`id` = `c`.`id`)
                        LEFT OUTER JOIN `user` AS `d` ON (`a`.`user_id` = `d`.`id`)
                        LEFT OUTER JOIN `eq_charge` AS `e` ON (`a`.`id` = `e`.`source_id`)
                        LEFT OUTER JOIN `tag_group` AS `f` ON (`d`.`group_id` = `f`.`id`)
                        WHERE `a`.`equipment_id` = {$equipment->eq_id}";
                    break;
                case 'eq_sample':
                    $SQL = "SELECT `a`.`dtstart`, `a`.`dtend`, `a`.`id`, `a`.`project_id`, `a`.`sender_id` AS `user_id`,
                        `a`.`count`,
                        `c`.`name` AS `proj_name`, `c`.`_extra` AS `project_extra`,
                        `d`.`name` AS `user_name`, `d`.`phone`, `d`.`email`,
                        `e`.`amount`,
                        `f`.`name` AS `tag_name`
                        FROM `eq_sample` AS `a`
                        LEFT OUTER JOIN `lab_project` AS `c` ON (`a`.`id` = `c`.`id`)
                        LEFT OUTER JOIN `user` AS `d` ON (`a`.`sender_id` = `d`.`id`)
                        LEFT OUTER JOIN `eq_charge` AS `e` ON (`a`.`id` = `e`.`source_id` AND `e`.`source_name` = '{$source_name}')
                        LEFT OUTER JOIN `tag_group` AS `f` ON (`d`.`group_id` = `f`.`id`)
                        WHERE `a`.`equipment_id` = {$equipment->eq_id} AND `a`.`status` = " . EQ_Sample_Model::STATUS_TESTED . " AND `a`.`record_id` = 0";
                    break;
            }
            $ret = $db->query($SQL);

            while ($value = $ret->row()) {
                // 不同站点的准入规则不同
                if (Event::trigger('nrii_record.filter.extra')) {
                    continue;
                }

                $source = O($source_name, $value->id);
                $record = O("nrii_record", ['source' => $source]);
                if ($record->id) {
                    $user                    = $source_name == 'eq_record' ? O("user", $value->user_id) : O("user", $value->sender_id);
                    $record->user            = $user;
                    $record->applicant       = $value->user_name;
                    $record->applicant_phone = $value->phone;
                    $record->applicant_email = $value->email;
                    $record->applicant_unit  = $record->applicant_unit ?: $value->tag_name;
                    $record->amounts         = $value->amount ?: 0;
                    $record->service_time    = round(($value->dtend - $value->dtstart) / 3600, 1); // 服务机时(小时)
                    $record->start_time      = $value->dtstart;
                    $record->end_time        = $value->dtend;
                    $record->source          = $source;
                    $record->save();
                } else {
                    $project_extra = json_decode($value->project_extra, true);
                    $project_des   = $project_extra['description'];

                    $record->inner_id     = $inner_id;
                    $record->amounts      = $value->amount ?: 0;
                    $record->service_time = round(($value->dtend - $value->dtstart) / 3600, 1); // 服务机时(小时)
                    // $record->service_content = '';
                    if ($value->record_id) {
                        $record->service_way  = 3; //委托共享
                        $record->service_time = $value->count;
                    } elseif ($equipmentOrigin->require_training) {
                        $record->service_way  = 2; //技术共享
                        $record->service_time = round(($value->dtend - $value->dtstart) / 3600, 1);
                    } else {
                        $record->service_way  = 1; //占用共享
                        $record->service_time = round(($value->dtend - $value->dtstart) / 3600, 1);
                    }
                    $record->subject_name    = $value->proj_name ?: '无';
                    $record->subject_area    = $equipment->realm;
                    // $record->subject_content = $project_des;
                    $record->applicant       = $value->user_name;
                    $record->applicant_phone = $value->phone;
                    $record->applicant_email = $value->email;
                    $record->applicant_unit  = $record->applicant_unit ?: $value->tag_name;

                    $user            = O("user", $value->user_id);
                    $recordOrigin    = O("eq_record", $value->id);
                    $record->nrii_eq = $equipment;
                    $record->user    = $user;
                    $record->record  = $recordOrigin;

                    $record->eq_name      = $equipment->eq_name;
                    $record->start_time   = $value->dtstart;
                    $record->end_time     = $value->dtend;
                    $record->service_type = Event::trigger('nrii_record.get_service_type', $user);
                    $record->source       = $source;
                    // $record->service_direction = 0;

                    // 临时赋予默认值
                    $record->address_type   = 0;
                    $record->move_address   = '';
                    $record->service_code   = '';
                    $record->sign_agreement = 0;

                    $record->save();
                }
            }
        }
    }
}
