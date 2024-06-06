<?php

class Billing_Standard
{
    const STATUS_DRAFT = 0;
    const STATUS_PENDDING = 1;
    const STATUS_RECORD = 2;
    const STATUS_CONFIRM = 3;

    public static $STATUS_LABEL = [
        self::STATUS_DRAFT => '未报销',
        self::STATUS_PENDDING => '报销中',
        self::STATUS_RECORD => '已报销',
        self::STATUS_CONFIRM => '待确认',
    ];

    public static $STATUS_COLOR = [
        self::STATUS_DRAFT => '#f93',
        self::STATUS_PENDDING => '#7498e0',
        self::STATUS_RECORD => '#6c9',
        self::STATUS_CONFIRM => '#f93',
    ];

    static function is_accessible($e, $name)
    {
        $me = L('ME');
        $all_user_billing_is_accessible = Config::get('billing_standard.all_user_billing_is_accessible', 0);
        if ($all_user_billing_is_accessible) {
            $e->return_value = true;
            return false;
        }
        if (
            $me->access('管理所有内容')
            || (Q("{$me}<pi lab")->total_count() > 0 && Switchrole::user_select_role() == '课题组负责人')
            || (Q("{$me} lab")->total_count() > 0 && Q("{$me}<pi lab")->total_count() <= 0 && $me->access('管理组内报销'))
        ) {
            $e->return_value = true;
            return false;
        }
        if (Module::is_installed('eq_struct') && Q("{$me}<incharge eq_struct")->total_count() > 0) {
            $e->return_value = true;
            return false;
        }
        if (Q("lab<secretary {$me}")->total_count()) {
            $e->return_value = true;
            return false;
        }
        $e->return_value = false;
        return false;
    }

    static function eq_reserv_prerender_component($e, $view, $form)
    {
        $component = $view->component;

        $form['authorized'] = [
            'label' => I18N::T('billing_standard', '经费卡号'),
            'path' => ['form' => 'billing_standard:view/'],
            'component' => $component,
        ];
        $form['billing_authorized'] = [
            'label' => I18N::T('billing_standard', '经费卡号'),
            'path' => ['form' => 'billing_standard:view/'],
            'component' => $component,
        ];
        $form['#categories']['reserv_info']['items'][] = 'authorized';
        $form['#categories']['reserv_info']['items'][] = 'billing_authorized';
        $e->return_value = $form;
        return TRUE;
    }

    static function component_form_post_submit($e, $component, $form)
    {
        $object = O('eq_reserv', ['component' => $component]);
        if (!$object->id) $object = O('eq_sample', ['component' => $component]);

        $object->fund_card_no = $form['fund_card_no'];
        $fundInfo = self::get_grant_info($object->fund_card_no);
        if ($fundInfo) {
            $object->fund_card_no = $fundInfo['card_no'];
            $object->fund_name = $fundInfo['prot_name'];
            $object->fund_leader_name = $fundInfo['leader_name'];
        }
        $object->save();
    }

    static function eq_sample_prerender_add_form($e, $form, $equipment)
    {
        $me = L('ME');
        $e->return_value .= V('billing_standard:view/eq_sample/add', ['form' => $form]);
    }

    static function eq_sample_prerender_edit_form($e, $sample, $form, $equipment)
    {
        $me = L('ME');
        $e->return_value .= V('billing_standard:view/eq_sample/edit', ['form' => $form, 'sample' => $sample]);
    }

    static function feedback_extra_view($e, $record, $form) {
        $me = L('ME');
        $e->return_value = V('billing_standard:view/eq_record/feedback_extra_view', ['record' => $record, 'form' => $form]);
    }

    static function feedback_form_submit($e, $record, $form)
    {
        if ($form->no_error && isset($form['fund_card_no'])) {
            $fundInfo = self::get_grant_info($form['fund_card_no']);
            $record->fund_card_no     = $fundInfo['card_no'];
            $record->fund_name        = $fundInfo['prot_name'];
            $record->fund_leader_name = $fundInfo['leader_name'];
            // 如果该使用记录关联了预约
            if($record->reserv->id) {
                $charge = o('eq_charge', ['source' => $record->reserv]);
                // 如果预约选择的经费卡，而反馈的时候根本没选择经费卡
                // 该预约存在预约收费，且未同步到报销 / 或该预约不存在收费
                if (!$charge->id || ($charge->id && !$charge->voucher)) {
                    $record->reserv->fund_card_no     = $fundInfo['card_no'];
                    $record->reserv->fund_name        = $fundInfo['prot_name'];
                    $record->reserv->fund_leader_name = $fundInfo['leader_name'];
                    $record->reserv->save();
                }
                // 该预约存在预约收费，且同步到了报销
                if ($charge->id && $charge->voucher && $record->reserv->fund_card_no != $fundInfo['card_no']) {
                    $form->set_error('fund_card_no', I18N::T('billing_standard', "收费已确认，不可修改经费卡!"));
                }
            }
        }
    }
    
    // glogon 选择经费卡
    static function glogon_logout_record_before_save($e, $record, $data)
    {
        if (isset($data['extra']['fund_card_no'])) {
            $fundInfo = self::get_grant_info($data['extra']['fund_card_no']);
            $record->fund_card_no     = $fundInfo['card_no'];
            $record->fund_name        = $fundInfo['prot_name'];
            $record->fund_leader_name = $fundInfo['leader_name'];
            // 如果该使用记录关联了预约
            if($record->reserv->id) {
                $charge = o('eq_charge', ['source' => $record->reserv]);
                // 如果预约选择的经费卡，而反馈的时候根本没选择经费卡
                // 该预约存在预约收费，且未同步到报销 / 或该预约不存在收费
                if (!$charge->id || ($charge->id && !$charge->voucher)) {
                    $record->reserv->fund_card_no     = $fundInfo['card_no'];
                    $record->reserv->fund_name        = $fundInfo['prot_name'];
                    $record->reserv->fund_leader_name = $fundInfo['leader_name'];
                    $record->reserv->save();
                }
            }
        }
   }

    static function eq_sample_form_submit($e, $sample, $form)
    {
        $sample->fund_card_no = $form['fund_card_no'];
        $fundInfo = self::get_grant_info($sample->fund_card_no);
        if ($fundInfo) {
            $sample->fund_card_no = $fundInfo['card_no'];
            $sample->fund_name = $fundInfo['prot_name'];
            $sample->fund_leader_name = $fundInfo['leader_name'];
        }
    }

    public static function get_grants($user, $component = [])
    {
        if (!$user->id) return [];
        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);
        try {
            $funds = $rpc->Authorized->getAuthorizeds($user->id);
        } catch (\Exception $e) {
            error_log('获取经费卡异常');
        }
        return $funds;
    }

    public static function get_grant_info($card_no)
    {
        if (!$card_no) return [];
        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);
        try {
            $fund = $rpc->Authorized->getAuthorizedInfo($card_no);
        } catch (\Exception $e){
            error_log('获取经费卡详情异常');
        }
        return $fund;
    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }
        $lab = Q("$user lab")->current();
        if ($lab->id && $lab->owner->id == $user->id) {
            $perms['管理组内报销'] = 'on';
        }
    }

    public static function eq_charge_confirmed($e, $charge)
    {

        if ($charge->confirm != EQ_Charge_Confirm_Model::CONFIRM_INCHARGE) {
            return;
        }
        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);

        try {
            $struct = $charge->equipment->struct;
            $params = [
                'user'               => $charge->user->id,
                'lab'                => $charge->lab->id,
                'amount'             => 0 - $charge->amount,
                'account'            => $charge->transaction->account->id,
                'confirm_user'       => L('ME')->id,
                'confirm_time'       => Date::format(Date::time()),
                'object'             => (string) $charge->source,
                'equipment'          => $charge->equipment->id,
                'lims_transacion_id' => Module::is_installed('billing') ? $charge->transaction->id : $charge->id,
                'description'        => $charge->description,
                'struct'             => [
                    'id' => $struct->id,
                    'struct_id' => $struct->id,
                    'struct_name' => $struct->name,
                    'struct_card_no' => $struct->card_no,
                    'struct_proj_no' => $struct->proj_no,
                ],
                'fund_card_no'       => $charge->source->fund_card_no,
                'charge_duration_blocks'  => $charge->charge_duration_blocks,
                'ctime' => $charge->ctime,
            ];

            $params['samples'] = 0;
            switch ($charge->source->name()) {
                case 'eq_sample':
                    $params['samples'] = $charge->source->count;
                    break;
                case 'eq_record':
                    $params['samples'] = $charge->source->samples;
                    break;
                case 'eq_reserv':
                    foreach (Q("eq_record[reserv={$charge->source}]") as $record) {
                        $params['samples'] += $record->samples;
                    }
                    break;
            }

            if ($charge->voucher) {
                $params['voucher'] = $charge->voucher;
            }

            $params = Event::trigger('billing_standard.charge_save_params', $params, $struct, $charge) ?: $params;
            $result = $rpc->transaction->save($params);

            if (!$result) {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '收费确认失败!'));
            } else {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '收费确认成功!'));
                $charge->voucher = $result;
            }
        } catch (Exception $e) {
            $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '收费确认失败!'));
        } finally {
            $charge->save();
        }
    }

    public static function eq_charge_saved($e, $charge, $old, $new)
    {

        if (Module::is_installed('billing')) {
            return true;
        }

        //确认收费不再推送
        if ($charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_INCHARGE) return true;

        if ($old['confirm'] != $new['confirm']) return true;

        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);

        try {

            $struct = $charge->equipment->struct;
            $params = [
                'user'               => $charge->user->id,
                'lab'                => $charge->lab->id,
                'amount'             => 0 - $charge->amount,
                'object'             => (string) $charge->source,
                'equipment'          => $charge->equipment->id,
                'lims_transacion_id' => $charge->id,
                'description'        => $charge->description,
                'pay_type'           => $charge->pay_type,
                'ctime' => $charge->ctime,
                'struct'             => [
                    'id' => $struct->id,
                    'struct_id' => $struct->id,
                    'struct_name' => $struct->name,
                    'struct_card_no' => $struct->card_no,
                    'struct_proj_no' => $struct->proj_no,
                ],
                'fund_card_no'       => $charge->source->fund_card_no,
            ];

            $params['samples'] = 0;
            switch ($charge->source->name()) {
                case 'eq_sample':
                    $params['samples'] = $charge->source->count;
                    break;
                case 'eq_record':
                    $params['samples'] = $charge->source->samples;
                    break;
                case 'eq_reserv':
                    foreach (Q("eq_record[reserv={$charge->source}]") as $record) {
                        $params['samples'] += $record->samples;
                    }
                    break;
            }

            if ($charge->voucher) {
                $params['voucher'] = $charge->voucher;
            }

            $params = Event::trigger('billing_standard.charge_save_params', $params, $struct, $charge) ?: $params;
            $result = $rpc->transaction->save($params);

            if ($result) {
                Database::factory()->query('UPDATE eq_charge set voucher = ' . $result . ' WHERE id = ' . $charge->id);
            }
        } catch (Exception $e) {
        }
    }

    public static function on_eq_charge_deleted($e, $charge)
    {
        if (Module::is_installed('billing')) {
            return true;
        }

        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);

        try {
            $rpc->transaction->delete($charge->id);
        } catch (Exception $e) {
        }

    }

    public static function charge_table_list_columns($e, $form, $columns)
    {
        $columns['structnum'] = [
            'title' => I18N::T('billing_standard', '入账账号'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/structnum', ['form' => $form]),
                'value' => $form['structnum'] ? $form['structnum'] : null,
            ],
            'weight' => 20
        ];
        $columns['confirm'] = [
            'title' => I18N::T('billing_standard', '确认状态'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('eq_charge_confirm:charges_table/filters/confirm', ['form' => $form]),
                'value' => $form['confirm'] != -1 ? EQ_Charge_Confirm_Model::confirm($form['confirm']) : NULL
            ],
            'weight' => 20
        ];
        $columns['bl_status'] = [
            'title' => I18N::T('billing_standard', '报销状态'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/bl_status', ['form' => $form]),
                'value' => $form['bl_status'] != -1 ? Billing_Standard::$STATUS_LABEL[$form['bl_status']] : null,
            ],
            'weight' => 20
        ];
        $columns['serialnum'] = [
            'title' => I18N::T('billing_standard', '报销单号'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/serialnum', ['form' => $form]),
                'value' => $form['serialnum'] ? $form['serialnum'] : null,
            ],
            'weight' => 20
        ];
        $columns['vouchernum'] = [
            'title' => I18N::T('billing_standard', '凭证号'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/vouchernum', ['form' => $form]),
                'value' => $form['vouchernum'] ? $form['vouchernum'] : null,
            ],
            'weight' => 20
        ];
        if ($form['dtstart_completetime'] && $form['dtend_completetime']) {
            $form['completetime'] = H(date('Y/m/d', $form['dtstart_completetime'])) . '~' . H(date('Y/m/d', $form['dtend_completetime']));
        } elseif ($form['dtstart_completetime']) {
            $form['completetime'] = H(date('Y/m/d', $form['dtstart_completetime'])) . '~' . I18N::T('achievements', '最末');
        } elseif ($form['dtend_completetime']) {
            $form['completetime'] = I18N::T('achievements', '最初') . '~' . H(date('Y/m/d', $form['dtend_completetime']));
        }
        $columns['completetime'] = [
            'title' => I18N::T('billing_standard', '报销时间'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form'  => V('billing_standard:charges_table/filters/completetime', [
                    'dtstart_completetime' => H($form['dtstart_completetime']),
                    'dtend_completetime' => H($form['dtend_completetime']),
                ]),
                'value' => $form['completetime'] ? H($form['completetime']) : null,
                'field' => 'dtstart_completetime,dtend_completetime',
            ],
            'weight' => 20
        ];
        foreach (Config::get('eq_charge.billing_list_columns.eq_charge') as $k => $c){
            if (!$c) unset($columns[$k]);
        }
    }

    public static function charge_table_list_row($e, $row, $charge)
    {
        $row['structnum'] = V('billing_standard:charges_table/data/structnum', ['charge' => $charge]);
        $row['confirm'] = V('eq_charge_confirm:charges_table/data/confirm', ['c' => $charge]);
        $row['bl_status'] = V('billing_standard:charges_table/data/bl_status', ['charge' => $charge]);
        $row['serialnum'] = V('billing_standard:charges_table/data/serialnum', ['charge' => $charge]);
        $row['completetime'] = V('billing_standard:charges_table/data/completetime', ['charge' => $charge]);
        $row['vouchernum'] = V('billing_standard:charges_table/data/vouchernum', ['charge' => $charge]);
        $e->return_value = $row;
    }

    public static function lab_charges_table_list_row($e, $form, $columns, $lab)
    {
        $columns['structnum'] = [
            'title' => I18N::T('billing_standard', '入账账号'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/structnum', ['form' => $form]),
                'value' => $form['structnum'] ? $form['structnum'] : null,
            ],
            'weight' => 20
        ];
        $columns['confirm'] = [
            'title' => I18N::T('billing_standard', '确认状态'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('eq_charge_confirm:charges_table/filters/confirm', ['form' => $form]),
                'value' => $form['confirm'] != -1 ? EQ_Charge_Confirm_Model::confirm($form['confirm']) : NULL
            ],
            'weight' => 20
        ];
        $columns['bl_status'] = [
            'title' => I18N::T('billing_standard', '报销状态'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/bl_status', ['form' => $form]),
                'value' => $form['bl_status'] != -1 ? billing_standard::$STATUS_LABEL[$form['bl_status']] : null,
            ],
            'weight' => 20
        ];
        $columns['serialnum'] = [
            'title' => I18N::T('billing_standard', '报销单号'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/serialnum', ['form' => $form]),
                'value' => $form['serialnum'] ? $form['serialnum'] : null,
            ],
            'weight' => 20
        ];
        $columns['vouchernum'] = [
            'title' => I18N::T('billing_standard', '凭证号'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form' => V('billing_standard:charges_table/filters/vouchernum', ['form' => $form]),
                'value' => $form['vouchernum'] ? $form['vouchernum'] : null,
            ],
            'weight' => 20
        ];

        if ($form['dtstart_completetime_check'] && $form['dtend_completetime_check']) {
            $form['completetime'] = H(date('Y/m/d', $form['dtstart_completetime'])) . '~' . H(date('Y/m/d', $form['dtend_completetime']));
        } elseif ($form['dtstart_completetime_check']) {
            $form['completetime'] = H(date('Y/m/d', $form['dtstart_completetime'])) . '~' . I18N::T('achievements', '最末');
        } elseif ($form['dtend_completetime_check']) {
            $form['completetime'] = I18N::T('achievements', '最初') . '~' . H(date('Y/m/d', $form['dtend_completetime']));
        }
        $columns['completetime'] = [
            'title' => I18N::T('billing_standard', '报销时间'),
            'align' => 'left',
            'nowrap' => true,
            'filter' => [
                'form'  => V('billing_standard:charges_table/filters/completetime', [
                    'dtstart_completetime_check' => H($form['dtstart_completetime_check']),
                    'dtend_completetime_check' => H($form['dtend_completetime_check']),
                    'dtstart_completetime' => H($form['dtstart_completetime']),
                    'dtend_completetime' => H($form['dtend_completetime']),
                ]),
                'value' => $form['completetime'] ? H($form['completetime']) : null,
                'field' => 'dtstart_completetime_check,dtstart_completetime,dtend_completetime_check,dtend_completetime',
            ],
            'weight' => 20
        ];
        foreach (Config::get('eq_charge.billing_list_columns.eq_charge') as $k => $c){
            if (!$c) unset($columns[$k]);
        }
    }

    public static function lab_charges_list_columns($e, $row, $charge, $lab)
    {
        $row['structnum'] = V('billing_standard:charges_table/data/structnum', ['charge' => $charge]);
        $row['confirm'] = V('eq_charge_confirm:charges_table/data/confirm', ['c' => $charge]);
        $row['bl_status'] = V('billing_standard:charges_table/data/bl_status', ['charge' => $charge]);
        $row['serialnum'] = V('billing_standard:charges_table/data/serialnum', ['charge' => $charge]);
        $row['completetime'] = V('billing_standard:charges_table/data/completetime', ['charge' => $charge]);
        $row['vouchernum'] = V('billing_standard:charges_table/data/vouchernum', ['charge' => $charge]);
        $e->return_value = $row;
    }

    //构造报销状态筛选sql
    public static function eq_charge_primary_content_selector($e, $form, $selector, $pre_selector)
    {
        if (isset($form['confirm']) && $form['confirm'] != -1) {
            $selector .= "[confirm={$form['confirm']}]";
        }
        if (isset($form['bl_status']) && $form['bl_status'] != -1) {
            $selector .= "[bl_status={$form['bl_status']}]";
        }
        if ($form['serialnum']) {
            $selector .= "[serialnum={$form['serialnum']}]";
        }
        if ($form['dtstart_completetime_check'] && $form['dtstart_completetime']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtstart_completetime']));
            $selector .= "[completetime>=$dtstart]";
        }
        if ($form['dtend_completetime_check'] && $form['dtend_completetime']) {
            $dtend = Q::quote(Date::get_day_end($form['dtend_completetime']));
            $selector .= "[completetime<=$dtend]";
        }
        if ($form['vouchernum']) {
            $selector .= "[vouchernum={$form['vouchernum']}]";
        }
        if ($form['structnum']) {
            $selector .= "[structnum={$form['structnum']}]";
         }
        $e->return_value = $selector;
    }


    static function eq_charge_links($e, $charge, $links, $mode)
    {
        $me = L('ME');

        if (
            $charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_PENDDING
            && $me->is_allowed_to('确认', $charge)
        ) {
            $links['confirm'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">' . I18N::T('eq_charge', '确认') . '</span>',
                'tip' => I18N::T('eq_charge', '确认'),
                'extra' => 'class="blue" q-src="' . URI::url('!eq_charge_confirm/confirm') .
                    '" q-static="id=' . $charge->id . '" q-event="click" q-object="charge_confirm"',
            ];
        }

        if (
            $charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_PENDDING
            && $charge->source->name() == 'eq_sample'
            && $me->is_allowed_to('修改', $charge->source)
        ) {
            $links['update'] = [
                'url'   => null,
                'tip'   => I18N::T('eq_sample', '编辑'),
                'text'   => I18N::T('eq_sample', '编辑'),
                'extra' => 'class="blue" q-object="edit_sample" q-event="click" ' .
                    'q-static="' . H(['id' => $charge->source->id]) . '" q-src="' . URI::url('!eq_sample/index') . '"'
            ];
        }

        if (
            $charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_PENDDING
            && $charge->source->name() == 'eq_record'
            && $me->is_allowed_to('修改', $charge->source)
        ) {

            $links['update'] = [
                'url'   => null,
                'tip'   => I18N::T('eq_record', '编辑'),
                'text'   => I18N::T('eq_record', '编辑'),
                'extra' => ' class="blue" q-event="click" q-object="record_edit" q-static="record_id=' . $charge->source->id . '" q-src="' . URI::url('!equipments/records/index.' . $charge->source->id) . '"',
            ];
        }

        if (
            $charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_INCHARGE
            && $me->is_allowed_to('取消确认', $charge)
        ) {
            $links['unconfirm'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">' . I18N::T('billing_standard', '取消确认') . '</span>',
                'tip' => I18N::T('eq_charge', '取消确认'),
                'extra' => 'class="blue" q-src="' . URI::url('!billing_standard/index') .
                    '" q-static="id=' . $charge->id . '" q-event="click" q-object="charge_cancel_confirm"',
            ];
        }
    }

    static function charge_confirm_ACL($e, $user, $action, $charge, $options)
    {
        switch ($action) {
            case '取消确认':
                if (
                    Config::get('eq_charge_confirm.cancel_confirm') &&
                    $user->is_allowed_to('确认', $charge)
                ) {
                    try {
                        $opt = Config::get('rpc.servers')['billing_standard'];
                        $rpc = new RPC($opt['api']);
                        $status = $rpc->transaction->canCancel($charge->voucher);
                    } catch (Exception $e) {
                        $status = ['status' => false, 'msg' => "网络异常"];
                    } finally {
                        if ($status['status']) {
                            $e->return_value = TRUE;
                            return FALSE;
                        }
                    }
                }
                break;
            default:
                break;
        }
    }

    public static function charges_search_box_extra_view($e, $form, $obj, $type = '')
    {
        $pre_selector = [];
        if ($obj->id) {
            switch ($type) {
                case 'incharge':
                    $pre_selector[base]   = " $obj equipment.incharge ";
                    break;
                case 'group' :
                    $pre_selector[] = " {$obj->group} equipment ";
                    break;
                case 'all':
                    break;
                default:
                    $pre_selector[] = $obj;
                    break;
            }
        }
        $confirm_balance = (float)Q("( " . join(', ', $pre_selector) . " ) " . "eq_charge[confirm]")->sum('amount');
        $bl_balance = (float)Q("( " . join(', ', $pre_selector) . " ) " . "eq_charge[confirm][bl_status=" . Billing_Standard::STATUS_RECORD . ']')->sum('amount');
        $no_balance = $confirm_balance - $bl_balance;
        $extra_title = I18N::T(
            'equipments',
            "总收费 <span class='blue'>$confirm_balance</span> 元、已报销 <span class='blue'>$bl_balance</span> 元、未报销 <span class='blue'>$no_balance</span> 元"
        );
        $e->return_value = '<div class="adj statistics middle">' . $extra_title . '</div>';
    }

    static function not_must_select_fund($e, $user)
    {
        // return FALSE 表示必填  return TRUE 表示不必填 
        $must_select_fund_group = Config::get('billing_standard.must_select_fund_group', []);
        $not_must_select_fund_group = Config::get('billing_standard.not_must_select_fund_group', []);
        // 如果必填和不必填的组织机构都为空，默认所有人必填
        // 如果两个值都填了，优先按照不必填的组织机构
        if (!count($must_select_fund_group) && !count($not_must_select_fund_group)) {
            $e->return_value = FALSE;
            return TRUE;
        }
        // 不必填的组织机构
        if (count($not_must_select_fund_group)) {
            $root = Tag_Model::root('group');
            foreach ($not_must_select_fund_group as $groupName) {
                if (!empty($groupName)) {
                    $group = O('tag_group', ['name' => $groupName, 'root' => $root]);
                    if (Q("$group $user")->total_count()) {
                        $e->return_value = TRUE;
                        return TRUE;
                    }
                }
            }
            $e->return_value = FALSE;
            return TRUE;
        }
        // 必填的组织机构
        if (count($must_select_fund_group)) {
            $root = Tag_Model::root('group');
            foreach ($must_select_fund_group as $groupName) {
                if (!empty($groupName)) {
                    $group = O('tag_group', ['name' => $groupName, 'root' => $root]);
                    if (Q("$group $user")->total_count()) {
                        $e->return_value = FALSE;
                        return TRUE;
                    }
                }
            }
            $e->return_value = TRUE;
            return TRUE;
        }
    }

    static function extra_form_validate($e, $equipment, $type, $form)
    {
        $me = L('ME');

        $lab = $form['project_lab'] ? O('lab', $form['project_lab']) : Q("{$me} lab")->current();
        switch ($type) {
            case 'eq_sample':
                $user = O('user', $form['sender']); // 代开
                $user = $user->id ? $user : L('ME');
                break;
            case 'eq_reserv':
                $user = O('user', $form['organizer'] ?: $form['currentUserId']); // 代开
                $user = $user->id ? $user : L('ME');
            default:
                $user = L('ME');
        }

        $must_select_fund = Config::get('billing_standard.must_select_fund', 0) &&
            !Event::trigger('billing_standard.not_must_select_fund', $user);

        if ($must_select_fund) {
            switch ($type) {
                case 'eq_sample':
                    if (!$form['fund_card_no']) {
                        $form->set_error('fund', I18N::T('billing', "请选择经费卡号!"));
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                    break;
                case 'eq_reserv':
                    if (!$form['fund_card_no']) {
                        $form->set_error('fund', I18N::T('billing', "请选择经费卡号!"));
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                    break;
                case 'use':
                    break;
            }
        }

        $no_fee = false;
        switch ($type) {
            case 'eq_sample':
                if (!$equipment->charge_template['sample']) {
                    $no_fee = true;
                } else {
                    $fee = EQ_Charge_Assist::sample_fee($form);
                    // 如果是编辑，则需将老的计费扣除
                    if ($form['id']) {
                        $source = O('eq_sample', $form['id']);
                        $eq_charge = O('eq_charge', ['source' => $source]);
                    }
                }
                break;
            case 'eq_reserv':
                if (!$equipment->charge_template['reserv'] && !$equipment->charge_template['record']) {
                    $no_fee = true;
                } else {
                    $fee = 0;
                    if (isset($equipment->charge_template['reserv'])) {
                        $fee += EQ_Charge_Assist::reserv_fee($form);
                    }
                    if (isset($equipment->charge_template['record'])) {
                        $record = O('eq_record');
                        $record->equipment = $equipment;
                        $record->user = $user;
                        $record->dtstart = $form['dtstart'];
                        $record->dtend = $form['dtend'];
                        $record->samples = $form['count'];
                        $charge = O('eq_charge');
                        $charge->source = $record;

                        $charge->user = $user;
                        $charge->lab = $lab;
                        $charge->equipment = $equipment;

                        if ($form['charge_tags']) {
                            $tags = [];
                            foreach ((array) $form['charge_tags'] as $k => $v) {
                                if ($v['checked'] == 'on') {
                                    $k = rawurldecode($k);
                                    $tags[$k] = $v['value'];
                                }
                            }
                        }
                        $charge->charge_tags = $tags;

                        $lua = new EQ_Charge_LUA($charge);
                        $result = $lua->run(['fee']);
                        $fee += $result['fee'];
                    }
                    // 如果是编辑，则需将老的计费扣除
                    if ($form['component_id']) {
                        $source = O('eq_reserv', ['component_id' => $form['component_id']]);
                        $eq_charge = O('eq_charge', ['source' => $source]);
                    }
                }
                break;
            case 'use':
                $no_fee = true;
                break;
        }

        $must_check_balance = Config::get('billing_standard.must_check_balance', 0);
        if ($must_check_balance && $form['fund_card_no'] && !$no_fee) {
            $fee = round($fee, 2);

            try {
                $opt = Config::get('rpc.servers')['billing_standard'];
                $rpc = new RPC($opt['api']);
                $balance = $rpc->Authorized->getAvailableAmount($user->id, $form['fund_card_no']);
            } catch (Exception $e) {
                $balance = 0;
            } finally {
                if ($balance - $fee < 0) {
                    $form->set_error('fund_card_no', I18N::T('billing_standard', "所选经费卡额度不足，请更换经费卡或联系老师追加冻结金额"));
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }

        $must_check_account = Config::get('billing_standard.must_check_account', 0);
        if ($must_check_account && !$no_fee) {
            $fee = round($fee, 2);

            try {
                $opt = Config::get('rpc.servers')['billing_standard'];
                $rpc = new RPC($opt['api']);
                $balance = $rpc->Account->getBalanceByLab($lab->id);
            } catch (Exception $e) {
                $balance = 0;
            } finally {
                if ($balance['total'] < 0) {
                    $form->set_error('project_lab', I18N::T('billing_standard', "可用余额不足, 请联系管理员充值或追加信用额度"));
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
        $e->return_value = FALSE;
        return;
    }

    static function isOutside($user)
    {
        $outside_school_group = Config::get('billing_standard.outside_school_group', []);
        if (count($outside_school_group)) {
            $root = Tag_Model::root('group');
            foreach ($outside_school_group as $groupName) {
                if (!empty($groupName)) {
                    $group = O('tag_group', ['name' => $groupName,'root' => $root]);
                    if (Q("$group $user")->total_count()) {
                        return TRUE;
                    }
                }
            }
        }
        if (!$user->token) {
            return TRUE;
        }
        return FALSE; 
    }

    static function charge_export($e, $charge, $valid_columns, $data)
    {
        if (array_key_exists('confirm', $valid_columns)) {
            $data[] = EQ_Charge_Confirm_Model::confirm($charge->confirm);
        }
        if (array_key_exists('bl_status', $valid_columns)) {
            $data[] = Billing_Standard::$STATUS_LABEL[$charge->bl_status];
        }
        if (array_key_exists('serialnum', $valid_columns)) {
            $data[] = I18N::HT('eq_charge', $charge->serialnum);
        }
        if (array_key_exists('structnum', $valid_columns)) {
            $data[] = I18N::HT('eq_charge', $charge->structnum);
        }
        if (array_key_exists('completetime', $valid_columns)) {
            $data[] = $charge->completetime ? Date::format($charge->completetime) : '--';
        }
        if (array_key_exists('vouchernum', $valid_columns)) {
            $data[] = $charge->vouchernum ? : '--';
        }
        if (array_key_exists('pay_method', $valid_columns)) {
            $data[] = Billing_Standard::$pay_methods[$charge->pay_method] ? : '--';
        }
        $e->return_value = $data;
    }

    static function equipment_billing_department($e, $equipment, $params)
    {
        if (!Module::is_installed('billing')) {
            $e->return_value = true;
            return false;
        }
    }
}
