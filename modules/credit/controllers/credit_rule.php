<?php

class Credit_Rule_Controller extends Base_Controller
{
    public function delete($id = null)
    {
        if (L('ME')->access('管理所有内容') && $id) {
            $credit_rule = O('credit_rule', $id);
            if ($credit_rule->id > 0) {
                /**
                 * 已经产生信用明细的记分事件不允许删除, 可以将分值调整为0从而不触发该事件
                 */
                if (Q("{$credit_rule} credit_record")->total_count()) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::HT('credit', '计分规则 %name 已生成信用明细, 不允许删除! 您可以取消勾选该事件, 之后将会忽略该事件!', ['%name' => $credit_rule->name]));
                    URI::redirect(URI::url('admin/credit.rule'));
                    exit(0);
                }

                if ($credit_rule->delete()) {
                    /* 记录日志 */
                    Log::add(strtr('[credit_rule] %user_name[%user_id]删除了计分规则%credit_rule_name[%credit_rule_id]', [
                        '%user_name'        => L('ME')->name,
                        '%user_id'          => L('ME')->id,
                        '%credit_rule_name' => $credit_rule->name,
                        '%credit_rule_id'   => $credit_rule->id,
                    ]), 'credit');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('credit', '计分规则 %name 删除成功!', ['%name' => $credit_rule->name]));
                }
            }
        }
        URI::redirect(URI::url('admin/credit.rule'));
    }
}

class Credit_Rule_AJAX_Controller extends AJAX_Controller
{
    public function index_get_credit_item_click()
    {
        $form                                                       = Input::form();
        Output::$AJAX['#' . $form['container_id'] . ' > div:eq(0)'] = [
            'data' => (string) V('credit:admin/credit/relate_view', ['key' => $form['key']]),
            'mode' => 'replace',
        ];
    }

    public function index_credit_edit_click()
    {
        JS::dialog(
            V('credit:admin/credit/item', [
            'form' => $form,
        ]),
            ['title' => I18N::T('credit', '添加自定义计分规则')]
        );
    }

    public function index_modify_credit_submit()
    {
        $me   = L('ME');
        $form = [];
        $data = [];

        //form是通过jquery serialize而来, 所以需要进行如下处理
        foreach (explode('&', urldecode(Input::form('form'))) as $form_item) {
            list($key, $value) = explode('=', $form_item);
            $form[$key]        = $value;

            $arr = explode('_', $key);
            if (!$arr[1]) {
                continue;
            }

            if ($arr[0] == 'score') {
                $data[$arr[1]]['score'] = $value;
            }

            if ($arr[0] == 'id') {
                $data[$arr[1]]['is_disabled'] = $value;
            }
        }

        foreach ($data as $k => $v) {
            $credit = O('credit_rule', $k);
            if (!$credit->id) {
                continue;
            }
            $credit->score       = $v['score'] ?: 0;
            $credit->is_disabled = $v['is_disabled'] ? Credit_Rule_Model::ENABLED : Credit_Rule_Model::DISABLED;
            $credit->save();
        }

        Output::$AJAX['#' . $form['message_uniqid']] = [
            'data' => (string) V('credit:admin/credit/message'),
            'mode' => 'append',
        ];
    }

    public function index_credit_edit_submit()
    {
        $me   = L('ME');
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('credit', '请填写计分事件!'));
            $form->validate('name', 'length(0,30)', I18N::T('credit', '计分事件不能超过30个字!'));
            $form->validate('score', 'not_empty', I18N::T('credit', '请填写计分值!'));
            $form->validate('score', 'number(>0)', I18N::T('credit', '请填写合法的计分值!'));

            $credit = O('credit_rule', ['name' => $form['name'], 'type' => $form['type']]);

            if ($credit->id) {
                $form->set_error('name', I18N::T('credit', '计分事件重复, 请重新填写'));
            }

            if ($form->no_error) {
                $credit->ref_no      = uniqid();
                $credit->type        = $form['type'];
                $credit->name        = $form['name'];
                $credit->score       = $form['score'];
                $credit->hidden      = Credit_Rule_Model::NOT_HIDE_ITEMS;
                $credit->is_custom   = Credit_Rule_Model::STATUS_CUSTOM;
                $credit->is_disabled = Credit_Rule_Model::DISABLED;

                if ($credit->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('credit', '添加规则成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('credit', '添加规则失败!'));
                }
                JS::refresh();
            } else {
                JS::dialog(
                    V('credit:admin/credit/item', [
                    'form' => $form,
                ]),
                    ['title' => I18N::T('credit', '添加自定义计分规则')]
                );
            }
        }
    }
}
