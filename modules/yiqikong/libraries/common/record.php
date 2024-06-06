<?php

class Common_Record extends Common_Base
{

    public static function create($data)
    {
        /**
         * #20192013 湖南农业
         * 这里 $data['yiqikong_id'] 为空值，被解析成0，取到了id最靠前的一个用户, 下面的时间又解析不了就是0
         * 在一个用户生成使用记录后就又生成了别人一条无始无终的使用记录, 还把使用时段占住了
         * 老师就很焦急
         */
        if (isset($data['user_local']) && $data['user_local']){
            $me = O('user',$data['user_local']);
        }else{
            $me = O('user', ['yiqikong_id' => $data['yiqikong_id']]);
        }
        if (!$me->id) throw new API_Exception;
        Cache::L('ME', $me);
        $equipment = O('equipment', ['yiqikong_id'=> $data['equipment']]);
        if (!$equipment->id) throw new API_Exception;
        $now = Date::time();
        // 增加容错兼容，关闭该仪器所有因意外未关闭的record
        foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $r) {
            if ($r->dtstart==$now) {
                $r->delete();
                continue;
            }
            $r->dtend = $now - 1;
            $r->save();
        }

        $record = O('eq_record');
        $record->user = $me;
        $record->equipment = $equipment;
        $record->status = $data['status'] ? : EQ_record_Model::FEEDBACK_NOTHING;

        /**
         * 这里的时间格式应该是时间戳
         * 但是在湖南农业大学中出现传过来的格式为时间，现在做兼容吧，先把时间转化成时间戳
         */
        if (strpos($data['start_time'],'-')) {
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
        }
        $record->dtstart = $data['start_time'] ? $data['start_time'] : 0;
        $record->dtend = $data['end_time'] ? $data['end_time']: 0;
        $record->samples = $data['samples'];
        $record->feedback = $data['feedback'];
        $record->mtime = time();
        $record->yiqikong_id = $data['yiqikong_record_id'];

        //判断预热和冷却
        $preheatConfig = Equipment_Preheat_Cooling::get_preheat_cooling($equipment);
        if ($preheatConfig->id) {
            $record->preheat = isset($data['preheat']) && $data['preheat'] ? $preheatConfig->preheat : 0;
            $record->cooling = isset($data['cooling']) && $data['cooling'] ? $preheatConfig->cooling : 0;
        } else {
            $record->preheat = 0;
            $record->cooling = 0;
        }

        Cache::L('YiQiKongRecordAction', TRUE);
        if ($record->save()) {
            if (!$record->dtend) {
                $equipment->is_using = TRUE;
                $equipment->save();
            }

            if (isset($data['extra_fields'])) {
                $record->extra_fields = $data['extra_fields'];
                $extra_value = O('extra_value', ['object' => $record]);
                $extra_value->values_json = json_encode($data['extra_fields']);
                $extra_value->object = $record;
                $extra_value->save();
            }

            $res = [
                'id' => $record->id,
                'user_name' => $record->user->name,
                'user_local' => $record->user->id,
                'group_name' => $record->user->group->name,
                'preheat' => $record->preheat,//重新更新回app，保证一切以lims为准
                'cooling' => $record->cooling,//重新更新回app，保证一切以lims为准
            ];

            return $res;
        }
        return FALSE;
    }

    public static function update($id, $data)
    {
        if (isset($data['user_local']) && $data['user_local']){
            $me = O('user',$data['user_local']);
        }else{
            $me = O('user', ['yiqikong_id' => $data['yiqikong_id']]);
        }
        if (!$me->id) throw new API_Exception;

        Cache::L('ME', $me);

        $equipment = O('equipment', ['yiqikong_id'=> $data['equipment']]);
        if (!$equipment->id) throw new API_Exception;
        $record = O('eq_record', $id);
        if (!$record->id) throw new API_Exception;
        $record->user = $me;
        $record->equipment = $equipment;
        /**
         * #20191778 湖南农业大学无法添加使用记录
         */
        if (strpos($data['start_time'],'-')) {
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
        }

        isset($data['status']) && $record->status = $data['status'];
        isset($data['start_time']) && $record->dtstart = $data['start_time'] ? $data['start_time'] : 0;
        isset($data['end_time']) && $record->dtend = $data['end_time'] ? $data['end_time'] : 0;

        // 传递了样品数，并且不为0, 都以传递的样品数为准
        if (isset($data['samples']) && $data['samples']) {
            $record->samples = $data['samples'];
        }
        $show_samples = (int)Config::get('equipment.feedback_show_samples', 0);
        if ($show_samples && isset($data['samples']) && !$data['samples']) {
            // 反馈显示样品数 传递了样品数，并且为0 
            if (Config::get('eq_record.must_samples')) {
                $record->samples = Config::get('eq_record.record_default_samples');
            } else {
                if((isset($data['feedback']) && $data['status'] != 0)) {
                    // app 端反馈，以反馈为主
                    $record->samples = $data['samples'];
                } else if((isset($data['feedback']) && $data['status'] == 0)) {
                    // 未反馈，以预约为主
                    if ($record->reserv->id) {
                        if (!$record->samples || $record->samples == 1 || 
                        !Config::get('equipment.feedback_show_samples', 0)) 
                        $record->samples = (int) $record->reserv->count;
                    } else {
                        $record->samples = Config::get('eq_record.record_default_samples');
                        if (!$record->reserv->id && $record->dtend) {
                            $reserv = Q("eq_reserv[equipment={$record->equipment}][user={$record->user}][dtstart=$record->dtstart~$record->dtend|dtstart~dtend=$record->dtstart][dtend!=$record->dtstart]:sort(dtstart A):limit(1)")->current();
                            if ($reserv->id) {
                                if (!$record->samples || $record->samples == 1 || !Config::get('equipment.feedback_show_samples', 0)) 
                                $record->samples = (int) $reserv->count;
                            }
                        } 
                    }
                } 
            }
        }
        if (!$show_samples && isset($data['samples']) && !$data['samples']) {
            // 反馈不显示样品数 传递了样品数，并且为0
            if (Config::get('eq_record.must_samples')) {
                $record->samples = Config::get('eq_record.record_default_samples');
            } else {
                if ($record->reserv->id) {
                    if (!$record->samples || $record->samples == 1 || 
                    !Config::get('equipment.feedback_show_samples', 0)) $record->samples = (int) $record->reserv->count;
                } else {
                    $record->samples = Config::get('eq_record.record_default_samples');
                    if (!$record->reserv->id && $record->dtend) {
                        $reserv = Q("eq_reserv[equipment={$record->equipment}][user={$record->user}][dtstart=$record->dtstart~$record->dtend|dtstart~dtend=$record->dtstart][dtend!=$record->dtstart]:sort(dtstart A):limit(1)")->current();
                        if ($reserv->id) {
                            if (!$record->samples || $record->samples == 1 || !Config::get('equipment.feedback_show_samples', 0)) 
                            $record->samples = (int) $reserv->count;
                        }
                    } 
                }
            }
        }
        
        isset($data['feedback']) && $record->feedback = $data['feedback'];
        isset($data['is_locked']) && $record->is_locked = $data['is_locked'];
        if (isset($data['extra_fields'])) {
            $record->extra_fields = $data['extra_fields'];
            $extra_value = O('extra_value', ['object' => $record]);
            $extra_value->values_json = json_encode($data['extra_fields']);
            $extra_value->object = $record;
            $extra_value->save();
        }
        isset($data['agent_id']) && $record->agent = O('user', $data['agent_id']);
        isset($data['use_type']) && Config::get('equipment.enable_use_type') && $record->use_type = $data['use_type'];

        //判断预热和冷却
        $preheatConfig = Equipment_Preheat_Cooling::get_preheat_cooling($equipment);
        if ($preheatConfig->id) {
            isset($data['preheat']) && $record->preheat = $data['preheat'];
            isset($data['cooling']) && $record->cooling = $data['cooling'];
        } else {
            $record->preheat = 0;
            $record->cooling = 0;
        }

        Cache::L('YiQiKongRecordAction', TRUE);

        if ($record->dtend) {
            $equipment->is_using = FALSE;
            $equipment->save();
        }
        if ($record->save()){
            $res = [
                'id' => $record->id,
                'user_name' => $record->user->name,
                'user_local' => $record->user->id,
                'group_name' => $record->user->group->name,
                'preheat' => $record->preheat,//重新更新回app，保证一切以lims为准
                'cooling' => $record->cooling,//重新更新回app，保证一切以lims为准
            ];

            return $res;
        }
        return false;
    }

    public static function delete($id, $data)
    {
        if (isset($data['user_local']) && $data['user_local']){
            $me = O('user',$data['user_local']);
        }else{
            $me = O('user', ['yiqikong_id' => $data['yiqikong_id']]);
        }
        if (!$me->id) throw new API_Exception;
        Cache::L('ME', $me);

        $record = O('eq_record', $id);
        if (!$record->id) throw new API_Exception;

        Cache::L('YiQiKongSampleAction', TRUE);
        return $record->delete();
    }

}
