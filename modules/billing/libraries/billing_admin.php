<?php

class Billing_Admin
{

    public static function setup()
    {

        /*if (L('ME')->access('管理财务中心')) {
            Event::bind('admin.index.tab', 'Billing_Admin::_primary_tab');
        }*/

        Event::bind('lab.notifications.content', 'Billing_Admin::_lab_secondary_refill_content', 10, 'labs');
    }

    static function _primary_tab($e, $tabs) {
        if (!Event::trigger('db_sync.need_to_hidden', 'billing')){
            Event::bind('admin.index.content', 'Billing_Admin::_primary_content', 0, 'billing');

            $tabs->add_tab('billing', [
                'url'=> URI::url('admin/billing'),
                'title'=> I18N::T('billing', '财务管理')
            ]);
        }
    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        Event::bind('admin.billing.content', 'Billing_Admin::_secondary_notification_content', 0, 'notifications');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.billing.tab')
            ->content_event('admin.billing.content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs->select($params[1]);
    }

    public static function admin_billing_tab($e, $tabs)
    {
        $tabs->add_tab('notifications', [
            'url'   => URI::url('admin/billing.notifications'),
            'title' => I18N::T('billing', '通知提醒'),
        ]);
    }

    public static function _secondary_notification_content($e, $tabs)
    {
        $configs = Config::get('notification.billing_conf');

        $vars = [];

        $form = Form::filter(Input::form());

        if (in_array($form['type'], $configs)) {

            if ($form['submit']) {

                $form
                    ->validate('title', 'not_empty', I18N::T('billing', '消息标题不能为空！'))
                    ->validate('body', 'not_empty', I18N::T('billing', '消息内容不能为空！'));
                $vars['form'] = $form;

                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];
                }

                foreach (Lab::get('notification.handlers') as $k => $v) {
                    if (isset($form['send_by_' . $k])) {
                        $value = $form['send_by_' . $k];
                    } else {
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = $value;
                }
                Lab::set($form['type'], $tmp);

                /* 记录日志 */
                $me = L('ME');
                Log::add(strtr('[billing] %user_name[%user_id]修改了系统设置中的财务管理通知邮件', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '内容修改成功'));
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '恢复系统默认设置成功'));
            }
        }

        $tabs->content = Notification::preference_views($configs, $vars, 'billing');
    }

    //labs hooks
    static function _lab_secondary_refill_content($e, $tabs, $sections) {
        if ($GLOBALS['preload']['billing.single_department']) {
            $configs = [
                'notification.billing.refill.unique',
            ];
        } else {
            $configs = [
                'notification.billing.refill',
            ];
        }

        $vars = [];
        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                if (!$form['enable_notification']) {
                    Lab::set('notification.refill', null);
                } else {
                    $form
                        ->validate('balance', 'is_numeric', I18N::T('billing', '最小余额必须是数字！'))
                        ->validate('period', 'number(>0)', I18N::T('billing', '发送提醒邮件的周期必须是大于零的数字！'))
                        ->validate('title', 'not_empty', I18N::T('billing', '消息标题不能为空！'))
                        ->validate('body', 'not_empty', I18N::T('billing', '消息内容不能为空！'));

                    if (!$form['enable_min_credit_per']) {
                        Lab::set('notification.refill.enable_min_credit_per', NULL);
                    }else {
                        // $form->validate('min_credit_per', 'number(>=0)', I18N::T('billing', '最低信用额度占比必须是大于或等于零的数字！'));
                        $form->validate('min_credit_per', 'is_numeric', I18N::T('billing', '最低信用额度占比必须是数字！'));
                    }

                    $vars['form'] = $form;

                    if ($form->no_error) {
                        $config = Lab::get($form['type']) ?: Config::get($form['type']);
                        $tmp = [
                            'description'=>$config['description'],
                            'strtr'=>$config['strtr'],
                            'title'=>$form['title'],
                            'body'=>$form['body'],
                            'balance'=>round($form['balance'], 2),
                            'period'=>$form['period'],
                            'min_credit_per'=>$form['min_credit_per'],
                            'enable_min_credit_per'=>!!$form['enable_min_credit_per'],
                            'enable_balance' => !!$form['enable_balance'],
                            'enable_notification'=>TRUE,
                        ];
                    }
                }

                foreach (Lab::get('notification.handlers') as $k => $v) {
                    if (isset($form['send_by_' . $k])) {
                        $value = $form['send_by_' . $k];
                    } else {
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = $value;
                }

                Lab::set($form['type'], $tmp);

                /* 记录日志 */
                $me = L('ME');
                Log::add(strtr('[billing] %user_name[%user_id]修改了系统设置中的财务管理通知邮件', [
                    '%user_name' => $me->name,
                    '%user_id'   => $me->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '内容修改成功'));
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '恢复系统默认设置成功'));
            }
        }

        $sections[] = Notification::preference_views($configs, $vars, 'labs');
    }
}
