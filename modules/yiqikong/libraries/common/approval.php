<?php

class Common_Approval extends Common_Base
{

//    const STATUS_PASS = 1;
//    const STATUS_REJECT = 2;

    public static function update($data)
    {
        $approval = O('approval', ['source_name' => $data['object_type'], 'source_id' => $data['source_id']]);
        if ($approval->id) {
            if ($data['approval_opera_user']) {
                $me = O('user', $data['approval_opera_user']);
                if ($me->id) Cache::L('ME', $me);
            }
            if ($approval->source_name == 'eq_sample') {
                // 如果是送样审批，触发了状态变化，需要通知app
                Cache::L('YiQiKongSampleAction', FALSE);
            }
            $data['approval_status'] == parent::STATUS_DONE ? $approval->pass() : $approval->reject();
        }
    }

}
