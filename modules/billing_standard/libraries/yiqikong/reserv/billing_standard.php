<?php

class Yiqikong_Reserv_Billing_Standard
{
    static function equipment_reserv_extra_fields ($e, $extra_fields, $equipment) {

        if(Config::get('eq_charge.preview_calcluate', false)){
            $extra_fields['预估收费']['calculate_amount'] = [
                'adopted' => null,
                'default' => "",
                'params' => "",
                "remarks" => "",
                "required" => 0,
                "title" => "预估收费",
                "type" => "20",//定义成数值20=button+text,且more_data=1则事件获取数据
                "params_sources" => ['source' => 'lims_rpc', 'more_data' => 1 ,'key' => 'calculate_amount','button_text' => '收费预估']
            ];    
        }
        
        // 字段内容也自定义表单一致 额外增加字段 params_sources 备选项来源*/
        if(Config::get('billing_standard.exist_fund', false)){
            $extra_fields['经费信息']['fund_card_no'] = [
                'adopted' => null,
                'default' => "",
                'params' => "",
                "remarks" => "选择要使用的经费卡",
                "required" => 0,
                "title" => "经费卡号",
                "type" => "6",
                "params_sources" => ['source' => 'lims_rpc', 'key' => 'fund_card_no']
            ];
            return false;
        }

    }

    static function equipment_reserv_extra_fields_value ($e, $sources, $params) {
        // 字段内容也自定义表单一致 额外增加字段 params_sources 备选项来源*/
        if($sources == "fund_card_no" && Config::get('billing_standard.exist_fund', false)){
            if (!isset($params['user_local'])) {
                $e->return_value = [];
                return false;
            }
            $user = O('user', $params['user_local']);
            if (!$user->id) {
                $e->return_value = [];
                return false;
            }
            $e->return_value = Billing_Standard::get_grants($user);
            return false;
        }
        if($sources == "calculate_amount"){
            $form = json_decode($params['form'],true);
            $user = O('user', $form['user_local']);
            $equipment = O('equipment', ['yiqikong_id' => $form['equipment']]);
            $return = ['total'=>1,'data'=>0];
            if($user->id && $equipment->id){
                $incharge_fee = Lab::get('eq_charge.incharges_fee') == 'on';
                if ($user->id && $user->is_allowed_to('管理使用', $equipment) && $incharge_fee) {
                }else{
                    $settings = $equipment->charge_template;
                    if($form['type'] == 'sample'){
                        $object = O('eq_sample');
                        $object->sender = $user;
                        $object->count = $form['samples'];
                        $object->equipment = $equipment;
                        $object->dtpickup = $form['pickup_time'];
                        $object->dtstart = $form['start_time'];
                        $object->dtend = $form['end_time'];
                        $object->dtsubmit = $form['dtsubmit'];

                        //通用渲染当前送样对象，用于提供计费需要的虚属性
                        Event::trigger('eq_charge.sample_render',$object,(array)$form,$equipment);

                    }
                    if($form['type'] == 'reserv'){
                        $object = NULL;
                        if ( $settings['record'] ) {
                            $record = O('eq_record');
                            $record->user = $user;
                            $record->equipment = $equipment;
                            $record->dtstart = min($form['start_time'], $form['end_time']);
                            $record->dtend = max($form['start_time'], $form['end_time']);
                            $record->voucher = $form['voucher'];
                            $record->samples = max((int)$form['count'], Config::get('eq_record.record_default_samples'));
                            $object = $record;
                        }
                        else {
                            $reserv = O('eq_reserv');
                            $reserv->user = $user;
                            $reserv->equipment = $equipment;
                            $reserv->dtstart = min($form['start_time'], $form['end_time']);
                            $reserv->dtend = max($form['start_time'], $form['end_time']);
                            $reserv->description = $form['description'];
                            $reserv->voucher = $form['voucher'];
                            $object = $reserv;
                        }
                    }

                    $charge = O('eq_charge');
                    $charge->source = $object;

                    //自定义送样表单传入供lua计算
                    if (Module::is_installed('extra')) {
                        $charge->source->extra_fields = (array)$form['extra_fields'];
                    }

                    $lua = new EQ_Charge_LUA($charge);
                    $result = $lua->run(['fee']);
                    $fee = '¥' . round($result['fee'], 2);
                    $return = ['total'=>1,'data'=>$fee];
                }
            }
            $e->return_value = $return;
            return false;
        }
    }

    public static function yiqikong_component_validate($e, $user, $equipment, $params, $reserv = null)
    {
        preg_match('/.*\((?P<fund_no>.*)\)/', $params['extra_fields']['fund_card_no'], $matches);
        $fund_card_no = $matches['fund_no'];
        $me = $user;
        $lab = Q("{$me} lab")->current();

        $must_select_fund = Config::get('billing_standard.must_select_fund', 0) &&
            !Event::trigger('billing_standard.not_must_select_fund', $user);

        if ($must_select_fund && !$fund_card_no) {
            throw new Error_Exception(I18N::T('billing', "请选择经费卡号!"));
            return FALSE;
        }

        $no_fee = false;
        if (!$equipment->charge_template['reserv'] && !$equipment->charge_template['record']) {
            $no_fee = true;
        } else {
            $fee = 0;
            if (
                $equipment->charge_template['reserv'] == 'only_reserv_time' ||
                $equipment->charge_template['reserv'] == 'custom_reserv' ||
                $equipment->charge_template['reserv'] == 'time_reserv_record'
            ) {
                $fee = EQ_Charge_Assist::reserv_fee([
                    'organizer' => $user->id,
                    'equipment_id' => $equipment->id,
                    'dtstart' => strtotime($params['dtstart']),
                    'dtend' => strtotime($params['dtend']) - 1,
                ]);
            }
            if (
                $equipment->charge_template['record'] == 'record_time' ||
                $equipment->charge_template['record'] == 'record_times'
            ) {
                $fee = EQ_Charge_Assist::record_fee([
                    'user_id' => $user->id,
                    'equipment_id' => $equipment->id,
                    'dtstart' => strtotime($params['dtstart']),
                    'dtend' => strtotime($params['dtend']) - 1,
                ]);
            }
            // 如果是编辑，则需将老的计费扣除
            if ($reserv->id) {
                $old_charge = O('eq_charge', ['source' => $reserv]);
                if ($old_charge->id) {
                    $fee -= $old_charge->amount;
                }
            }
        }

        $must_check_balance = Config::get('billing_standard.must_check_balance', 0);
        if ($must_check_balance && !$fund_card_no && !$no_fee) {
            $fee = round($fee, 2);

            try {
                $opt = Config::get('rpc.servers')['billing_standard'];
                $rpc = new RPC($opt['api']);
                $balance = $rpc->Authorized->getAvailableAmount($user->id, $fund_card_no);
            } catch (Exception $e) {
                $balance = 0;
            } finally {
                if ($balance - $fee < 0) {
                    throw new Error_Exception(I18N::T('billing_standard', "所选经费卡额度不足，请更换经费卡或联系老师追加冻结金额!"));
                    return FALSE;
                }
            }
        }
    }

    public static function yiqikong_component_submit($e, $user, $equipment, $params, $reserv = null)
    {
        preg_match('/.*\((?P<fund_no>.*)\)/', $params['extra_fields']['fund_card_no'], $matches);
        $fund_card_no = $matches['fund_no'];
        if ($fund_card_no) {
            $reserv->fund_card_no = $fund_card_no;
            $reserv->save();
        }
    }
}

