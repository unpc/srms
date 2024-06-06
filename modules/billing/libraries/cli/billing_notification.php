<?php
/**
 * 【通用可配】【上海交通大学医学院免疫所】RQ183304-系统定期给课题组PI发送财务明细
 */
class CLI_Billing_Notification {

    static function send() {
        if (!Config::get('billing_center.notification')) return;
        $now = Date::get_day_start();
        foreach (Q("lab[atime>0]") as $lab) {
            $user = $lab->owner;
            $accounts = Q("billing_account[lab=$lab]");

            foreach ($accounts as $account) {

                //获取财物部门发送时间
                $times = Lab::get('billing.account.detail.times.' . $account->department->id);

                //财物部门相应消息模板
                $type = 'billing.account.detail.' . $account->department->id;

                if ($times) foreach ($times as $index => $value) {
                    if (date('m-d', $now) == $value['date']) {
                        // 根据设置得到结算周期开始、结束时间
                        //将year的变量循环放到循环内
                        $year = date('Y');

                        $year = strtotime($year . '-' . $value['date2']) <= $now ? $year : $year - 1;
                        $end = Date::get_day_end(strtotime($year . '-' .$value['date2']));
                        $start = strtotime($year . '-' .$value['date1']) > $end ?
                            strtotime($year - 1 . '-' .$value['date1']) :
                            strtotime($year . '-' .$value['date1']);

                        $cc = Event::trigger('billing_transaction.get_reveiver',$account);
                        Billing_Notification::notification_main($type, $user, $account, $start, $end, $value['from'],['cc'=>$cc]);
                    }
                }
            }
        }
    }

    static function auto_send() {
        if (!Config::get('billing_center.notification')) return FALSE;

        $params = func_get_args();

        $department_id = $params[0];
        $start = $params[1];
        $end = $params[2];
        $me_id = $params[4];
        $me = O('user', $me_id);

        if ($params[3] == 'all_pi') {
            $labs = Q('lab[atime>0]')->to_assoc('id', 'id');

        } elseif ($params[3] == 'role'){
            $roles = json_decode($params[4], true);
            $me_id = $params[5];
            $me = O('user', $me_id);
            $labs = [];
            foreach ($roles as $rid => $rname){
                $role = O('role',$rid);
                $roleusers = Q("{$role} user");
                foreach ($roleusers as $roleuser){
                    $lab = Q("{$roleuser} lab")->current();
                    $labs[$roleuser->id] = $lab->id;
                }
            }
        } else {
            $labs = json_decode($params[3], true);
        }

        $department = O('billing_department', $department_id);

        if (!$department->id ||
            !$start ||
            !$end ||
            !count($labs)) {
            return FALSE;
        }

        if ($params[3] == 'role'){
            foreach ($labs as $uid => $lid) {
                $user = O('user',$uid);
                $account = O("billing_account", ['lab_id' => $lid, 'department' => $department]);
                $type = 'billing.account.detail.' . $department->id;

                /**
                 * binding_email发不出去..?
                 */
                // Billing_Notification::notification_main($type, $user, $account, Date::get_day_start($start), Date::get_day_end($end), $me->get_binding_email());
                Billing_Notification::notification_main($type, $user, $account, Date::get_day_start($start), Date::get_day_end($end));
            }
        }else{
            foreach ($labs as $l) {
                $lab = O('lab', $l);
                $user = $lab->owner;
                $account = O("billing_account", ['lab' => $lab, 'department' => $department]);
                $type = 'billing.account.detail.' . $department->id;

                /**
                 * binding_email发不出去..?
                 */
                // Billing_Notification::notification_main($type, $user, $account, Date::get_day_start($start), Date::get_day_end($end), $me->get_binding_email());
                Billing_Notification::notification_main($type, $user, $account, Date::get_day_start($start), Date::get_day_end($end));
            }
        }
        return true;
    }

    static function set_pi_email_enable() {
        foreach (Q("lab[atime>0]") as $lab) {
            $user = $lab->owner;
            Lab::set('notification.billing.account.detail.email.' . $user->id, true);
        }
    }

}
