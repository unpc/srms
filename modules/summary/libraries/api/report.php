<?php

/**
 * 消息发送
 */
class API_Report extends Base
{
    public function setReport($report_name, $fill_dtstart, $fill_dtend)
    {
        $this->_ready();

        if (!$report_name) {
            throw new API_Exception(self::$errors[402], 402);
        }

        // $nrii_role = O('role', ['name' => '科技部申报任务相关人员']);
        $users = Q("perm[name=管理申报任务|name=填报仪器数据|name=审核仪器数据] role user");
        foreach ($users as $user) {
            Notification::send('report.message', $user, [
                '%username'     => '尊敬的用户',
                '%fill_dtstart' => Date::format($fill_dtstart, 'Y/m/d'),
                '%fill_dtend'   => Date::format($fill_dtend, 'Y/m/d'),
                '%report_title' => $report_name,
            ]);
        }

        $res['status'] = 'success';
        return $res;
    }
}
