<?php

class Nrii {

    static $push_instruTypes = [
        'center' => 1,
        'device' => 2,
        'equipment' => 4,
        'customs' => 5,
        'record' => 6,
        'service' => 7
    ];

	const ROUTINGKEY_DIRECTORY = 'directory';

    static $serivice_keys = [
        'serviceUrl', // 在线服务平台网址
        'serviceUrlyear', // 在线平台建设年份
        'billNum', // 开放服务发票凭证数量（张）
        'billWorth', // 服务收入总金额（万元）
        'billWorthInstr', // 对外服务收入总金额（万元）
        'intensityFileIdName', // 对外服务收入汇总表（xls格式附件名称）
        'intensityFileUrl', // 对外服务收入汇总表（xls格式附件）
        'instrNum', // 50万元以上仪器总数量（台套）
        'instrWorth', // 50万元以上仪器总原值（万元）
        'specializationFileIdName', // 50万元以上仪器资产明细表（xls格式附件名称）
        'specializationFileUrl', // 50万元以上仪器资产明细表（xls格式附件）
        'remark', // 支撑本单位科技创新成效
        'remarks', // 支撑外单位科技创新成效
        'remarkOne', // 重大科研基础设施开放共享情况
        'remarkTwo' // 管理制度及实验队伍建设情况
    ];

    // 大型科学装置数据字段需要后续补充调整
    static $device_keys = [
        'cname' => 'cname',
        'ename' => 'ename',
        'inner_id' => 'innerId',
        'worth' => 'worth',
        'begin_date' => 'beginDate',
        'address' => 'address',
        'street' => 'street',
        'realm' => 'subject',
        'url' => 'url',
        'technical' => 'technical',
        'function' => 'function',
        'requirement' => 'requirement',
        'service_content' => 'serviceContent',
        'contact' => 'contact',
        'phone' => 'phone',
        'email' => 'email',
        'ename_short' => 'enameShort',
        'competent_dep' => 'competentDep',
        'sup_insname' => 'supInsname',
        'device_category' => 'deviceCategory',
        'construction' => 'construction',
        'approval_dep' => 'approvalDep',
        'video' => 'video',
        'sci_contact' => 'sciContact',
        'sci_position' => 'sciPosition',
        'sci_insname' => 'sciInsname',
        'sci_phone' => 'sciPhone',
        'sci_email' => 'sciEmail',
        'run_contact' => 'runContact',
        'run_insname' => 'runInsname',
        'run_phone' => 'runPhone',
        'run_email' => 'runEmail',
        'fill_position' => 'fillPosition',
        'fill_insname' => 'fillInsname',
        'achievement' => 'achievement',
        'layout_image' => 'layoutImage',
        'key_image' => 'keyImage',
        'experiment_image' => 'experimentImage',
        'organization_file' => 'organizationFile',
        'open_file' => 'openFile',
        'apply_file' => 'applyFile',
        'research_file_one' => 'researchFileOne',
        'research_file_two' => 'researchFileTwo',
        'research_file_three' => 'researchFileThree',
        'research_file_four' => 'researchFileFour',
        'research_file_five' => 'researchFileFive',
    ];


    static $center_keys = [
        'centname' => 'cname',
        'inner_id' => 'innerId',
        'worth' => 'worth',
        'research_area' => 'area',
        'begin_date' => 'establish',
        'instru_num' => 'instruNum',
        'accept' => 'accept',
        'realm' => 'subject',
        'service_content' => 'serviceContent',
        'address' => 'address',
        'equrl' => 'url',
        'contact' => 'contact',
        'phone' => 'phone',
        'email' => 'email',
        'contact_address' => 'location',
        'zip_code' => 'postalcode',
    ];

    // 已经从国家科技部中剔除该类型，暂时保留不进行删除
    static $unit_keys = [
        'unitname',
        'org',
        'category',
        'inner_id',
        'status',
        'begin_date',
        'share_mode',
        'realm',
        'service_url',
        'address',
        'street',
        'function',
        'requirement',
        'service_content',
        'achievement',
        'fee',

        'contact',
        'phone',
        'email',
        'contact_street',
        'zip_code',
    ];

    static $equipment_keys = [
        //'eq_id',
        'eq_name' => 'cname',
        'ename' => 'ename',
        //'org',
        'inner_id' => 'innerId',
        'affiliate' => 'instrBelongsType',
        'affiliate_name' => 'instrBelongsName',
        'resource_name' => 'resourceName',
        'class' => 'instrCategory',
        'address' => 'address',
        'street' => 'street',
        'worth' => 'worth',

        'eq_source' => 'instrSource',
        'type_status' => 'type',
        //'status',
        //'share_status',
        'realm' => 'subject',
        'nation' => 'nation',
        'model_no' => 'instrVersion',
        'manufacturer' => 'manufacturer',
        'begin_date' => 'beginDate',

        'technical' => 'technical',
        'function' => 'function',
        'requirement' => 'requirement',
        'fee' => 'fee',
        'service_content' => 'serviceContent',
        //'achievement',
        'service_url' => 'serviceUrl',
        'customs' => 'instrSupervise',
        'run_machine' => 'runMachine', // 年总运行机时
        'service_machine' => 'serviceMachine', // 年服务机时
        'funds' => 'funds', // 主要购置经费来源
        'inside_depart' => 'insideDepart', // 所属单位内部门

        'contact' => 'contact',
        'phone' => 'phone',
        'email' => 'email',
        'contact_address' => 'address',
        'zip_code' => 'postalcode'
    ];

    static $customs_keys = [
        'inner_id' => 'innerId', // 所在单位仪器编号
        'ins_code' => 'insCode', // 所属单位标识
        'declaration_number' => 'declarationNumber', // 进口报关单编号
        'import_date' => 'importDate', // 放行日期
        'item_number' => 'itemNumber', // 仪器设备在进口报关单上的项号
        'form_name'  => 'formname' // 仪器在进口报关单上的名称
    ];

    static function sync_service() {
        $params = [];
		$params['insCode'] = Config::get("nrii")[LAB_ID];

        foreach (self::$serivice_keys as $key) {
            $params[$key] = Lab::get('nrii.service.' . $key);
        }

        // 去除掉nrii中通过YiQiKong中转更新数据的措施直接与国家科技部进行对接。
        // 由于国家科技部还有接口调整，故开发完成后下面报送未联调
        NSoap::push('instru', LAB_ID, self::$push_instruTypes['service'], $params, 'instruInfo');
    }

    static function sync($mode,$eid = 0) {
        if (!in_array($mode, ['device','center','equipment','record'])) return;
        if($mode == 'record'){
            $selector = "nrii_record[nrii_status=1,101,200,201,202,203,204,301]";
        }else{
            $selector = "nrii_{$mode}";
        }
        //如果是大型设备，需要审核之后上传
        if($mode == 'equipment'){
            $shen_status = Nrii_Equipment_Model::SHEN_STATUS_FINISH;
            $selector = "nrii_{$mode}[shen_status={$shen_status}]";
        }
        if($eid){
            $selector = "nrii_{$mode}[id={$eid}]";
        }
        //增加hook，控制上传科技部的范围
        $selector = Event::trigger('nrii.sync.selector', $mode, $selector)?:$selector;
        $qs = Q("$selector");
        $start = $num = 0;
        $step = 10;
        $total = $qs->total_count();
        while ($start <= $total) {

            $nriis = $qs->limit($start, $step);

            foreach ($nriis as $nrii) {
                if ($mode == 'record'){
                    Nrii_Record::sync($nrii);
                }
                else {
                    self::sync_nrii($mode, $nrii);
                }
                if ($num % 500 == 0) {
                    sleep(1);
                }
                $num ++;
                //echo "Push nrii_" . $mode . "[".$nrii->id."]\n";
            }
            $start += $step;
        }
    }

    static private function sync_nrii($mode, $nrii) {
        if (!in_array($mode, ['device','center','equipment'])) return;
        if (!$nrii->id) return;
        $id = $nrii->id;

        $params = [];
        // $params['source_name'] = LAB_ID;
        $params['insCode'] = Config::get('nrii', [])[LAB_ID];

        $newParams = Event::trigger('sync.nrii.params', $params, $mode, $nrii);
        if($newParams) {
            $params = $newParams;
        }

        //图标字段
        if ($mode == 'equipment') {
            $equipment = O('equipment', $nrii->eq_id);
            if ($equipment->id && $equipment->icon_file(128)) {
                $icon_url = $equipment->icon_url('128');
            }
            else {
                $icon_url = Config::get('system.base_url').'images/equipment.jpg';
            }
        }
        else {
            $icon_file = Core::file_exists(PRIVATE_BASE.'icons/nrii_' . $mode . '/128/'.$id.'.png', '*');
            if (!$icon_file) $icon_file = Core::file_exists(PRIVATE_BASE.'icons/nrii_' . $mode . '/128.png', '*');
            if (!$icon_file) $icon_file = Core::file_exists(PRIVATE_BASE.'icons/128.png', '*');
            if ($icon_file) $icon_url = Config::get('system.base_url').'icon/nrii_' . $mode . '.'.$id.'.128';
        }
        $params['image'] = $icon_url ? : '无';
        $keyName = $mode . '_keys';

        Event::trigger('nrii.equipment.push_columns');
        foreach (self::$$keyName as $key => $value) {
            // 如果是这三类字段，通用均需要做调整
            if ($key == 'begin_date') {
                $params[$value] = date('Y-m-d', $nrii->$key);
                continue;
            }
            // 2019.2.14 UNPC 数据校准容错，避免上传到Nrii会出现字段不存在、字段类型错误等问题
            switch ($mode) {
                case 'equipment':
                    if ($key == 'realm' || $key == 'funds' ) {
                        $subjects = @json_decode($nrii->$key, true);
                        $params[$value] = implode(', ', $subjects);
                        break;
                    }
                    if ($key == 'service_machine') {
                        $params[$value] = (float)$nrii->service_machine;
                        break;
                    }
                    if ($key == 'run_machine') {
                        $params[$value] = (float)$nrii->run_machine;
                        break;
                    }
                    if ($key == 'eq_source') {
                        $params[$value] = Nrii_Equipment_Model::$eq_source[(int)$nrii->eq_source];
                        break;
                    }
                    if ($key == 'type_status') {
                        $params[$value] = Nrii_Equipment_Model::$type_status[(int)$nrii->type_status];
                        break;
                    }
                    if ($key == 'affiliate') {
                        if ($nrii->affiliate == Nrii_Equipment_Model::AFFILIATE_NONE || !$nrii->affiliate) {
                            $params[$value] = '无';
                        }
                        else {
                            $params[$value] = (int) $nrii->$key;
                        }
                        break;
                    }
                    if ($key == 'affiliate_name') {
                        if ($nrii->affiliate == Nrii_Equipment_Model::AFFILIATE_NONE) {
                            $params[$value] = '无';
                        }
                        else if (in_array($nrii->affiliate, Nrii_Equipment_Model::$affiliate_resource_type)) {
                            $params[$value] = '无';
                        }
                        else {
                            $params[$value] = $nrii->$key;
                        }
                        break;
                    }
                    if ($key == 'resource_name') {
                        if (in_array($nrii->affiliate, Nrii_Equipment_Model::$affiliate_resource_type)) {
                            $params[$value] = $nrii->affiliate_name;
                        }
                        else {
                            $params[$value] = '无';
                        }
                        break;
                    }
                    // 如果是单台套、仪器分类为其他，则转换成990000）
                    if ($key =='class' && $nrii->$key == '999999') {
                        $params[$value] = '990000';
                        break;
                    }
                    if ($key == 'inside_depart') {
                        $params[$value] = ( $nrii->$key ?: $nrii->org ) ?: '无';
                        break;
                    }
                    if ($key == 'fee') {
                        $params[$value] = $nrii->$key ?: '无';
                        break;
                    }
                    if ($key == 'customs') break;
                    if($key == 'share_status'){
                        $params[$value] = Nrii_Equipment_Model::$share_status[$nrii->$key];
                        break;
                    }
                    if($key == 'status'){
                        $params[$value] = Nrii_Equipment_Model::$status[$nrii->$key];
                        break;
                    }
                    if ($key == 'worth') {
                        $params[$value] = (double)round($nrii->$key, 2);
                        break;
                    }
                case 'device':
                    if ($key == 'realm') {
                        $subjects = @json_decode($nrii->$key, true);
                        $params[$value] = implode(', ', $subjects);
                        break;
                    }
                    if ($key == 'device_category') {
                        $params[$value] = Nrii_Device_Model::$device_category[$nrii->device_category];
                        break;
                    }
                    if ($key == 'construction') {
                        $params[$value] = Nrii_Device_Model::$construction[$nrii->construction];
                        break;
                    }
                case 'unit':
                case 'center':
                    if ($key == 'realm') {
                        $subjects = @json_decode($nrii->$key, true);
                        $params[$value] = implode(', ', $subjects);
                        break;
                    }
                    // 将数据库中存储的编号转换成文字,下同
                    if ($key == 'address') {
                        if($nrii->address){
                            $names = Nrii_Address::get_name($nrii->address);
                            $params['province'] = $names['province'];
                            $params['city'] = $names['city'];
                            $params['county'] = $names['county'];
                        }else{
                            $names = $nrii->address_info;
                            $params['province'] = $names['province'];
                            $params['city'] = $names['city'];
                            $params['county'] = $names['county'];
                        }

                        break;
                    }
                default:
                    $params[$value] = $nrii->$key;
                    break;
            }
        }
        //对字段进行重新渲染
        $params = Event::trigger('nrii.equipment.push_columns_update',$params,$nrii) ?: $params;

        $params['auditStatus'] = -1;

        //如果是单台套补充海关信息和服务记录
        $pushCustoms = false;//标识是否需要推送海关信息
        if ($mode == 'equipment') {
            $customsParams = [];
            $params['instrSupervise'] = '否';
            $customs = O('nrii_customs', $nrii->customs->id);
            if ($customs->id){
                $params['instrSupervise'] = '是';
                Event::trigger('nrii.equipment.customs.push_columns');
                foreach (self::$customs_keys as $key => $value) {
                    if ($key == 'import_date') {
                        $customsParams[$value] = date('Y-m-d', $customs->$key);
                        continue;
                    }
                    if ($key == 'share') {
                        $customsParams[$value] = $customs->$key ? '是' : '否';
                        continue;
                    }
                    if ($key == 'fees_approved') {
                        $customsParams[$value] = $customs->$key ? '是' : '否';
                        continue;
                    }
                    if ($key == 'auditStatus') {
                        $customsParams[$value] = -1;
                        continue;
                    }
                    $customsParams[$value] = $customs->$key;

                }
                $pushCustoms = true;
            }
        }
        // 2019.6.17 早上做数据对接推送的时候返回了一个achievement不能为空的错误，与文档不符， 随后与国家科技部人员联系后告知临时做个传入处理。
        if($mode !='device') $params['achievement'] = '无';

        // 2019.2.14 UNPC 情人节大换样, 去除掉nrii中通过YiQiKong中转更新数据的措施直接与国家科技部进行对接
        $nrii->nrii_status = NSoap::push('instru', LAB_ID, self::$push_instruTypes[$mode], $params, 'instruInfo');
        $nrii->save();
        //这里需要先推仪器再推海关。所以这块我改了通用。
        if($pushCustoms && !empty($customsParams)){
            $customs->nrii_status = NSoap::push('instru', LAB_ID, self::$push_instruTypes['customs'], $customsParams, 'instruInfo');
            $customs->save();
        }
    }

    //Nrii模块开启时，在添加、修改仪器时收集额外信息
    static function equipment_edit_info_view($e, $form, $equipment) {
        $e->return_value .= V('nrii:equipment/edit.info', [
                    'form' => $form,
                    'equipment' => $equipment,
                    'add' => true
                ]);
        return TRUE;
    }

    static function equipment_edit_info_view_dialog($e, $form, $equipment) {
        $e->return_value .= V('nrii:equipment/edit.info', [
                    'form' => $form,
                    'equipment' => $equipment,
                ]);
        return TRUE;
    }

    static function equipment_post_submit_validate($e, $form) {
        return TRUE;
    }

    static function equipment_post_submit($e, $form, $equipment) {
        $equipment->nrii_share = (int)$form['nrii_share'];
    }
}
