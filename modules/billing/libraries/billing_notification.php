<?php

/**
 * 【通用可配】【上海交通大学医学院免疫所】RQ183304-系统定期给课题组PI发送财务明细
 */
class Billing_Notification {

    static function setup() {
        if (Config::get('billing_center.notification')) {
            Event::bind('department.index.tab', 'Billing_Notification::edit_report_tab');
            Event::bind('department.index.tab.content', 'Billing_Notification::edit_report_content', 0, 'report');
        }
    }

    static function edit_report_tab($e, $tabs) {
        $department = $tabs->department;
        $me = L('ME');
        if ($me->is_allowed_to('修改', $department) || Q("$department $me")->total_count()) {
            $tabs
                ->add_tab('report', [
                        'url'=>URI::url('!billing/department/index.'.$department->id.'.report'),
                        'title'=>I18N::T('billing', '结算通知'),
                        'weight' => 30
                    ]);
        }
    }

    static function edit_report_content($e, $tabs) {
        $department = $tabs->department;
        $form = Form::filter(Input::form());

        $type = 'notification.billing.account.detail.' . $department->id;
        $time_type = 'billing.account.detail.times.' . $department->id;
        $me = L('ME');
        if (!$me->is_allowed_to('修改', $department) && !Q("$department $me")->total_count()) {
            URI::redirect('error/401');
        }

        if ($form['restore']) {
            Lab::set($type, NULL);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '恢复系统默认设置成功'));
        }
        else if ($form['submit']) {
            try {
                if ($form['submit_time']) {
                    $repeat = [];
                    foreach ($form['times'] as $index => $time) {
                        $send_item = [
                            'date' => date('m-d', $time['date']),
                            'date1' => date('m-d', $time['date1']),
                            'date2' => date('m-d', $time['date2']),
                            'description' => $time['description'],
                            'from' => $time['from']
                        ];
                        if (!in_array($send_item, $repeat)) {
                            $times[$index] = $send_item;
                            $repeat[] = $send_item;
                        }
                    }
                    Lab::set($time_type, $times);
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '日期保存成功'));
                }
                else {
                    $form
                        ->validate('title', 'not_empty', I18N::T('billing', '消息标题不能为空！'))
                        ->validate('body', 'not_empty', I18N::T('billing', '消息内容不能为空！'));
                    $vars['form'] = $form;

                    if ($form->no_error) {
                        $config = Lab::get($type, Config::get('notification.billing.account.detail'));
                        $tmp = [
                            'description'=>$config['description'],
                            'strtr'=>$config['strtr'],
                            'title'=>$form['title'],
                            'body'=>$form['body']
                        ];

                        foreach (Lab::get('notification.handlers') as $k=>$v) {
                            if (isset($form['send_by_'.$k])) {
                                $value = $form['send_by_'.$k];
                            }
                            else {
                                $value = 0;
                            }
                            $tmp['send_by'][$k] = $value;
                        }

                        Lab::set($type, $tmp);
                        Log::add(strtr('[billing] %user_name[%user_id]修改了财务管理中的财务结算通知邮件', [
                                    '%user_name' => $me->name,
                                    '%user_id' => $me->id,
                        ]), 'journal');

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '内容修改成功'));
                    }
                }
            }
            catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '保存失败!'));
            }
        }

        $key = Lab::get($type) ? $type : 'notification.billing.account.detail';
        $notification = Notification::preference_views([$key], [], 'billing');
        $times = Lab::get($time_type);

        $tabs->content = V('billing:department/report', [
            'form' => $form,
            'times' => $times,
            'department' => $department,
            'notification' => $notification,
        ]);
    }

    static function lab_saved($e, $lab, $old_data, $new_data) {
        Lab::set('notification.billing.account.detail.email.' . $lab->owner->id, true);
    }

    static function notification_show($e, $notification_keys) {
        if (!Config::get('billing_center.notification')) {
            foreach ($notification_keys as $k => $v) {
                if ($v == 'billing.account.detail') {
                    unset($notification_keys[$k]);
                }
            }
        }

        $e->return_value = $notification_keys;

        return TRUE;
    }

    static function notification_main($type, $user, $account, $start, $end, $from = '', $header = []) {
        if(!$account->id) {
            Log::add("因为[$user->id]该课题组没有财务账号，故不需要发送结算通知", 'mail');
            return;
        }
        $key = Lab::get('notification.' . $type) ? $type : 'billing.account.detail';
        $template = Notification::get_template($key);
        $i18n = $template['i18n_module'] ? : 'application';

        if ($template['send_by']['messages'] == 'on' || $template['send_by']['email'] == 'on'
        || $template['send_by']['messages'][1] || $template['send_by']['email'][1]) {
            $address = $user->get_binding_email();
            //是否允许接收邮件
            $enable_email = Lab::get('notification.billing.account.detail.email.' . $user->id);
            $enable_email = isset($enable_email) ? $enable_email : TRUE;
            $db = Database::factory();
            $ret = $db->value('SELECT `val` FROM `_config` WHERE `key`="%s"', 'notification.billing.account.detail.messages.' . $user->id);
            //是否允许接收信息
            $enable_message = isset($ret) ? substr($ret, -2, 1) : true;

            // 时间内转入、转出金额
            $outcome = $db->value(
                'SELECT SUM(`outcome`) FROM `billing_transaction`'.
                ' WHERE `outcome` > 0'.
                ' AND `account_id` = %u'.
                ' AND `ctime` BETWEEN %u AND %u',
                $account->id, $start, $end);
            $outcome = $outcome ? sprintf("%.2f", $outcome) : 0;
            $income = $db->value(
                'SELECT SUM(`income`) FROM `billing_transaction`'.
                ' WHERE `income` > 0'.
                ' AND `account_id` = %u'.
                ' AND `ctime` BETWEEN %u AND %u',
                $account->id, $start, $end);
            $income = $income ? sprintf("%.2f", $income) : 0;

            $outcome_new = $db->value(
                'SELECT SUM(`outcome`) FROM `billing_transaction`'.
                ' WHERE `outcome` > 0'.
                ' AND `account_id` = %u'.
                ' AND `ctime` > %u',
                $account->id, $end);
            $income_new = $db->value(
                'SELECT SUM(`income`) FROM `billing_transaction`'.
                ' WHERE `income` > 0'.
                ' AND `account_id` = %u'.
                ' AND `ctime` > %u',
                $account->id, $end);
            // 周期结束时账号余额
            $balance_two = sprintf("%.2f", $account->balance + $outcome_new - $income_new);
            // 周期开始时账号余额
            $balance_one = sprintf("%.2f", $balance_two + $outcome - $income);

            if ($enable_message && $template['send_by']['messages']) {
                $params = [
                    '%user' => Markup::encode_Q($user),
                    '%dept' => Markup::encode_Q($account->department),
                    '%dt_one' =>  date('Y年m月d日', $start),
                    '%balance_one' => $balance_one,
                    '%dt_two' => date('Y年m月d日', $end),
                    '%balance_two' => $balance_two,
                    '%income' => $income,
                    '%outcome' => $outcome,
                    '%pay' => sprintf("%.2f", max(0 , 0 - $balance_two)),
                ];

                list($title, $body) = Notification::symbol_to_markup([
                    I18N::T($i18n, $template['title']),
                    I18N::T($i18n, $template['body']),
                ], $params, $user);

                $body = str_replace('附件', '<a class="blue" href="!labs/lab/index.' .
                    $account->lab->id .'.billing_account.transaction">课题组财务明细</a>', $body);

                call_user_func(['Notification_Message', 'send'], NULL, [$user], $title, $body);
            }

            if ($template['send_by']['sms']) {
                $params = [
                    '%user' => Markup::encode_Q($user),
                    '%dept' => Markup::encode_Q($account->department),
                    '%dt_one' =>  date('Y年m月d日', $start),
                    '%balance_one' => $balance_one,
                    '%dt_two' => date('Y年m月d日', $end),
                    '%balance_two' => $balance_two,
                    '%income' => $income,
                    '%outcome' => $outcome,
                    '%pay' => sprintf("%.2f", max(0 , 0 - $balance_two)),
                ];

                list($title, $body) = Notification::symbol_to_markup([
                    I18N::T($i18n, $template['title']),
                    I18N::T($i18n, $template['body']),
                ], $params, $user);

                $body = str_replace('附件', '课题组财务明细', $body);

                call_user_func(['Notification_Sjtu_Scce_SMS', 'send'], NULL, [$user], $title, $body);
            }

            if ($enable_email && $template['send_by']['email']) {
                if (!$address) {
                    Log::add('因为PI没有相应的邮箱地址，故无法正常发送邮件!', 'mail');
                }
                else {
                    $email = new Email;

                    if (!$from) {
                        $from = Config::get('system.email_address');
                    }

                    $email->from($from);

                    $params = [
                        '%user' => new Markup(Markup::encode_Q($user), true),
                        '%dept' => new Markup(Markup::encode_Q($account->department), true),
                        '%dt_one' =>  date('Y年m月d日', $start),
                        '%balance_one' => $balance_one,
                        '%dt_two' => date('Y年m月d日', $end),
                        '%balance_two' => $balance_two,
                        '%income' => $income,
                        '%outcome' => $outcome,
                        '%pay' => max(0 , 0 - $balance_two),
                    ];

                    list($title, $body) = Notification::symbol_to_markup([
                        I18N::T($i18n, $template['title']),
                        I18N::T($i18n, $template['body'])
                    ], $params, $user);

                    $body = str_replace(array("\r\n", "\r", "\n"), '<br />', $body);

                    $transactions = Q("billing_transaction[ctime={$start}~{$end}][account={$account}][income!=0|outcome!=0]:sort(ctime DESC)");

                    $body .=  '<br />' . (string) V('billing:eq_charge_email', ['transactions' => $transactions, 'user' => $user, 'account' => $account]);

                    $email->to($address);
                    // 给相关角色进行抄送
                    $default_roles = Config::get('roles.default_roles');
                    $emails = [];
                    if ($default_roles[ROLE_LAB_STATEMENTS_RECEIVER]) {
                        $users = Q("({$account->lab}, role[weight=" . ROLE_LAB_STATEMENTS_RECEIVER . "]) user[id!={$user->id}]");
                        foreach ($users as $u) {
                            $m = $u->get_binding_email();
                            $m && $emails[] = $m;
                        }
                    }

                    if ($default_roles[ROLE_RESEARCH_INSTITUTE_LEADER]) {
                        $users = Q("role[weight=" . ROLE_RESEARCH_INSTITUTE_LEADER . "] user[id!={$user->id}]");
                        foreach ($users as $u) {
                            $m = $u->get_binding_email();
                            $m && $emails[] = $m;
                        }
                    }

                    $emails = array_unique(array_filter($emails));
                    $header['cc'] = array_merge($header['cc'] ?: [], $emails);
                    if(isset($header['cc']) && !empty($header['cc'])){
                        $email->cc($header['cc']);
                    }
                    $email->subject("课题组平台费用结算明细 ({$account->department->name})");

                    $email->body(NULL, $body);

                    $autoload = ROOT_PATH.'vendor/autoload.php';
                    if(file_exists($autoload)) require_once($autoload);
                    $Excel = new \PHPExcel;
                    $Writer = new \PHPExcel_Writer_Excel5($Excel);
                    $Excel->setActiveSheetIndex(0);
                    $ActSheet = $Excel->getActiveSheet();

                    $title = '财务明细';
                    $ActSheet->setTitle(T($title));

                    $ActSheet->setCellValue('A1', T('使用者'));
                    $ActSheet->setCellValue('B1', T('仪器/财务账号'));
                    $ActSheet->setCellValue('C1', T('转入金额'));
                    $ActSheet->setCellValue('D1', T('转出金额'));
                    $ActSheet->setCellValue('E1', T('时间'));
                    $ActSheet->setCellValue('F1', T('备注'));

                    $i = 2;
                    foreach ($transactions as $transaction) {
                        $ActSheet->setCellValue('A' . $i,
                            strip_tags(new Markup($transaction->description['%user'], true))
                        );
                        if ($transaction->income > 0) {
                            $ActSheet->setCellValue('B' . $i, strip_tags(new Markup($transaction->description['%account'], true)));
                            $ActSheet->setCellValue('C' . $i, Number::currency($transaction->income));
                            $ActSheet->setCellValue('D' . $i, '--');
                        }
                        else {
                            $ActSheet->setCellValue('B' . $i, strip_tags(new Markup($transaction->description['%equipment'], true)));
                            $ActSheet->setCellValue('C' . $i, '--');
                            $ActSheet->setCellValue('D' . $i, Number::currency($transaction->outcome));
                        }
                        $ActSheet->setCellValue('E' . $i, Date::format($transaction->ctime, 'Y/m/d H:i'));
                        $ActSheet->setCellValue('F' . $i, strip_tags(str_replace('<br>', "\r\n", $transaction->description())) ? : '--');
                        $i++;
                    }

                    $name = date('Y.m.d', $start) . '_' . date('Y.m.d', $end);
                    $outputFileName = iconv('utf-8', 'gb2312', "/tmp/{$name}.xls");
                    $Writer->save($outputFileName);
                    $email->attachment($outputFileName);
                    $email->send();
                    @unlink($outputFileName);
                }
            }
        }
    }

    public static function custom_notification_billing_account_detail($e, $receiver, $noti_form)
    {
        $params = $noti_form['params'];

        $department = O('billing_department', $params['department_id']);

        $lab = Q("$receiver lab")->current();
        $account = O("billing_account", ['lab' => $lab, 'department' => $department]);

        $type = 'billing.account.detail.' . $department->id;

        self::notification_main($type, $receiver, $account, Date::get_day_start($params['start_date']), Date::get_day_end($params['end_date']));
        $e->return_value = true;
        return false;
    }
}
