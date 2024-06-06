<?php

class Index_AJAX_Controller extends AJAX_Controller
{

    function index_grants_change()
    {
        if ($GLOBALS['preload']['people.multi_lab']) return;
        $form = Input::form();
        if ($form['user_id']) {
            $component = O('cal_component', $form['component_id']);
            Output::$AJAX["#" . $form['tr_authorized_id']] = [
                'data' => (string)V('billing_standard:view/billing_authorized', [
                    'tr_authorized_id' => $form['tr_authorized_id'],
                    'component' => $component,
                    'user' => O('user', $form['user_id']),
                    'change' => 1,
                ]),
                'mode' => 'replace',
            ];
        }
    }

    function index_add_eq_sample_grants_change()
    {
        if ($GLOBALS['preload']['people.multi_lab']) return;

        $form = Input::form();

        if ($form['user_id']) {
            $user = O('user', $form['user_id']);

            Output::$AJAX["#" . $form['tr_authorized_id']] = [
                'data' => (string)V('billing_standard:view/eq_sample/add_grant', [
                    'tr_authorized_id' => $form['tr_authorized_id'],
                    'user' => $user,
                ]),
                'mode' => 'replace',
            ];
        }
    }

    function index_edit_eq_sample_grants_change()
    {
        if ($GLOBALS['preload']['people.multi_lab']) return;

        $form = Input::form();

        if ($form['user_id']) {
            $sample = O('eq_sample', $form['sample_id']);
            Output::$AJAX["#" . $form['tr_authorized_id']] = [
                'data' => (string)V('billing_standard:view/eq_sample/edit_grant', [
                    'sample' => $sample,
                    'user' => O('user',$form['user_id']),
                ]),
                'mode' => 'replace',
            ];
        }
    }

    public function index_charge_cancel_confirm_click()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $charge = O('eq_charge', $form['id']);
        if (!$me->is_allowed_to('取消确认', $charge)) {
            return;
        }

        if (JS::Confirm('您要取消确认该条计费么？')) {
            try {
                $opt = Config::get('rpc.servers')['billing_standard'];
                $rpc = new RPC($opt['api']);
                $status = $rpc->transaction->cancel($charge->voucher);
            } catch (Exception $e) {
                $status = false;
            } finally {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
                $charge->voucher = 0;
                if ($status && $charge->save()) {
                    Log::add(strtr('[eq_charge_confirm] %user_name[%user_id]取消确认了经费记录[%charge_id]', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%charge_id' => $charge->id,
                    ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '收费取消确认成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '收费取消确认失败!'));
                }
            }
            JS::refresh();
        }
    }

    function index_cancel_transaction_click()
    {
        $me = L('ME');
        $form = Input::form();
        $charge = O('eq_charge', ['voucher' => $form['transaction_id']]);
        if (!$charge->id || !$me->is_lab_pi()) {
            return;
        }

        if (JS::confirm(I18N::T('billing_standard', '确认撤回吗？'))) {
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);

            try {
                $status = $rpc->transaction->cancel($form['transaction_id']);
            } catch (Exception $e) {
                $status = false;
            } finally {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
                $charge->voucher = 0;
                if ($status && $charge->id && $charge->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '撤回成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '撤回失败'));
                }
            }

            JS::refresh();
        }
    }

    function index_edit_transaction_click()
    {
        $me = L('ME');
        $form = Input::form();

        if (!$me->is_lab_pi()) {
            return;
        }

        JS::dialog(V('view/edit_transaction', [
            'form' => $form,
            'q_object' => 'edit_transaction',
            'transaction_ids' => [$form['transaction_id']],
            'fund_id' => $form['fund_id']
        ]), ['title'=>I18N::T('billing_standard', '修改经费卡号')]);
    }

    function index_edit_transaction_submit()
    {
        $me = L('ME');
        $form = Input::form();
        $res = true;

        if ($me->is_lab_pi() && $form['fund_id'] && ($form['submit'] == 'edit')) {
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    try {
                        $status = $rpc->transaction->adjustFund($me->id, $key, $form['fund_id']);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        if (!$status) {
                            $res = false;
                        }
                    }
                }
            }
        }

        if ($res) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '修改经费卡号成功!'));
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '修改经费卡号失败!'));
        }

        JS::refresh();
    }

    function index_batch_edit_transaction_submit()
    {
        $me = L('ME');
        $form = Input::form();

        if (!$me->is_lab_pi()) {
            return;
        }

        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);

        if (($form['submit'] == 'cancel') && JS::confirm(I18N::T('billing_standard', '确认批量撤回？'))) {
            $res = true;

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $charge = O('eq_charge', ['voucher' => $key]);
                    if (!$charge->id || !$me->is_lab_pi()) {
                        continue;
                    }

                    try {
                        $status = $rpc->transaction->cancel($key);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
                        $charge->voucher = 0;
                        if (!($status && $charge->id && $charge->save())) {
                            $res = false;
                        }
                    }
                }
            }

            if ($res) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '批量撤回成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '批量撤回失败!'));
            }

            JS::refresh();
        }

        // 批量修改经费卡号
        if (($form['submit'] == 'edit') && !$form['fund_id']) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $transaction_ids[] = $key;
                }
            }

            JS::dialog(V('view/edit_transaction', [
                'q_object' => 'batch_edit_transaction',
                'transaction_ids' => $transaction_ids,
            ]), ['title'=>I18N::T('billing_standard', '批量修改经费卡号')]);
        }

        // 确认批量修改经费卡号
        if (($form['submit'] == 'edit') && $form['fund_id']) {
            $res = true;

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    try {
                        $status = $rpc->transaction->adjustFund($me->id, $key, $form['fund_id']);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        if (!$status) {
                            $res = false;
                        }
                    }
                }
            }

            if ($res) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '批量修改经费卡号成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '批量修改经费卡号失败!'));
            }
    
            JS::refresh();
        }

        // 生成报销单
        if (($form['submit'] == 'create') && !$form['confirm']) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $transaction_ids[] = $key;
                }
            }

            try {
                $res = $rpc->distribution->create($me->id, $transaction_ids);
                
                if ($res['status']) {
                    JS::dialog(V('view/create_distribution', [
                        'form' => $form,
                        'transaction_ids' => $transaction_ids,
                        'preview' => $res['preview']
                    ]), ['title'=>I18N::T('billing_standard', '结算确认')]);
                } else {
                    JS::alert($res['message']);
                }
            } catch (\Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '生成报销单失败'));
                JS::refresh();
            }
        }

        // 确认生成报销单
        if (($form['submit'] == 'create') && $form['confirm']) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $transaction_ids[] = $key;
                }
            }

            try {
                $res = $rpc->distribution->create($me->id, $transaction_ids, true);

                if ($res['status']) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', $res['message']));
                    JS::refresh();
                } else {
                    JS::alert($res['message']);
                }
            } catch (\Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '生成报销单失败'));
                JS::refresh();
            }
        }
    }

    function index_cancel_distribution_click()
    {
        $me = L('ME');
        $form = Input::form();

        if (!$me->is_lab_pi()) {
            return;
        }

        if (JS::confirm(I18N::T('billing_standard', '确认撤回吗？'))) {
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);
            $status = false;

            try {
                $status = $rpc->distribution->cancel($me->id, $form['distribution_id']);
            } catch (Exception $e) {

            }

            if ($status) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '撤回成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '撤回失败'));
            }
            JS::refresh();
        }
    }

    function index_edit_distribution_click()
    {
        $me = L('ME');
        $form = Input::form();

        if (!$me->is_lab_pi()) {
            return;
        }

        JS::dialog(V('view/edit_distribution', [
            'form' => $form,
            'q_object' => 'edit_distribution',
            'distribution_ids' => [$form['distribution_id']],
            'fund_id' => $form['fund_id']
        ]), ['title'=>I18N::T('billing_standard', '修改经费卡号')]);
    }

    function index_edit_distribution_submit()
    {
        $me = L('ME');
        $form = Input::form();
        $res = true;

        if ($me->is_lab_pi() && $form['fund_id'] && ($form['submit'] == 'edit')) {
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    try {
                        $status = $rpc->distribution->adjustFund($me->id, $key, $form['fund_id']);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        if (!$status) {
                            $res = false;
                        }
                    }
                }
            }
        }

        if ($res) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '修改经费卡号成功!'));
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '修改经费卡号失败!'));
        }

        JS::refresh();
    }

    function index_submit_distribution_click()
    {
        $me = L('ME');
        $form = Input::form();

        if (!$me->is_lab_pi()) {
            return;
        }

        if (JS::confirm(I18N::T('billing_standard', '确认提交吗？'))) {
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);
            $status = false;

            try {
                $status = $rpc->distribution->submit($me->id, $form['distribution_id']);
            } catch (Exception $e) {

            }

            if ($status) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '提交成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '提交失败'));
            }
            JS::refresh();
        }
    }

    function index_batch_edit_distribution_submit()
    {
        $me = L('ME');
        $form = Input::form();

        if (!$me->is_lab_pi()) {
            return;
        }

        $opt = Config::get('rpc.servers')['billing_standard'];
        $rpc = new RPC($opt['api']);

        // 批量撤回
        if (($form['submit'] == 'cancel') && JS::confirm(I18N::T('billing_standard', '确认批量撤回？'))) {
            $res = true;

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    try {
                        $status = $rpc->distribution->cancel($me->id, $key);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        if (!$status) {
                            $res = false;
                        }
                    }
                }
            }

            if ($res) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '批量撤回成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '批量撤回失败!'));
            }

            JS::refresh();
        }

        // 批量修改经费卡号
        if (($form['submit'] == 'edit') && !$form['fund_id']) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $distribution_ids[] = $key;
                }
            }

            JS::dialog(V('view/edit_distribution', [
                'q_object' => 'batch_edit_distribution',
                'distribution_ids' => $distribution_ids,
            ]), ['title'=>I18N::T('billing_standard', '批量修改经费卡号')]);
        }

        // 确认批量修改经费卡号
        if (($form['submit'] == 'edit') && $form['fund_id']) {
            $res = true;

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    try {
                        $status = $rpc->distribution->adjustFund($me->id, $key, $form['fund_id']);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        if (!$status) {
                            $res = false;
                        }
                    }
                }
            }

            if ($res) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '批量修改经费卡号成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '批量修改经费卡号失败!'));
            }
    
            JS::refresh();
        }

        // 确认批量提交
        if (($form['submit'] == 'submit') && JS::confirm(I18N::T('billing_standard', '确认批量提交？'))) {
            $res = true;

            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    try {
                        $status = $rpc->distribution->submit($me->id, $key);
                    } catch (Exception $e) {
                        $status = false;
                    } finally {
                        if (!$status) {
                            $res = false;
                        }
                    }
                }
            }

            if ($res) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing_standard', '批量提交成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '批量提交失败!'));
            }

            JS::refresh();
        }
    }
}