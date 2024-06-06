<?php

class API_YiQiKong_Training extends API_Common {

    public function update($id, $data) {
        $this->_ready();

        $me = O('user', $data['user_local']);
        if (!$me->id) throw new API_Exception;
        Cache::L('ME', $me);

        $training = O('ue_training', $id);
        if (!$training->id) throw new API_Exception;

        $equipment = $training->equipment;
        if (!$equipment->id || !$equipment->require_training 
        || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            throw new API_Exception('未找到对应信息', 404);
        }
        
        $user = $training->user;
        $training->status = $data['status'];
        if ($data['atime'] != '0000-00-00 00:00:00') {
            $training->atime = strtotime($data['atime']);
        }
        Log::add(strtr('[training] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', [
            '%user_name'=> $me->name, 
            '%user_id'=> $me->id, 
            '%equipment_name'=> $equipment->name, 
            '%equipment_id'=> $equipment->id, 
            '%training_id'=> $training->id
        ]), 'yiqikong');

        Cache::L('YiQiKongTrainingAction', TRUE);
        return $training->touch()->save(); // touch就是更新mtime用的 培训里的通过时间
    }

}