<?php

class Common_Sample
{

    public static function create($data)
    {
        $me = $data['user_local'] ? O('user', $data['user_local']): O('user', ['yiqikong_id' => $data['user']]);
        if (!$me->id) $me = Yiqikong_User::make_user($data);
        if (!$me->id) throw new API_Exception;

        $equipment = O('equipment', ['yiqikong_id' => $data['equipment']]);
        if (!$equipment->id) throw new API_Exception;
        if (!$equipment->accept_sample) throw new API_Exception;

        if (!$me->is_allowed_to('添加送样请求', $equipment) && !$me->is_allowed_to('添加送样记录', $equipment)) {
            error_log(join(',', Lab::messages('sample')));
            throw new API_Exception(join(',', Lab::messages('sample')));
        }
        // 自定义表单验证 拥有特殊自定义表单的送样要验证 一期不做所以传空
        $form = Form::filter([]);
        $form['extra_fields'] = $data['extra_fields'];
        Extra::validate_extra_value(null, $equipment, 'eq_sample', $form);
        if (!$form->no_error) throw new API_Exception(join(' ', array_column($form->errors, 0)));
        $sample = O('eq_sample');
        $sample->sender = $me;

        $operator = $data['user_local'] ? O('user', $data['user_local']): O('user', ['yiqikong_id' => $data['operator']]);
        if ($operator->id) $sample->operator = $operator;
        $sample->lab = Q("{$sample->sender} lab")->current();
        $sample->equipment = $equipment;
        $sample->status = $data['status'] ?: EQ_Sample_Model::STATUS_APPLIED;
        $sample->dtstart = $data['start_time'] ?: 0;
        $sample->dtend = $data['end_time'] ?: 0;
        $sample->dtpickup = $data['pickup_time'] ?: 0;
        $sample->dtsubmit = $data['submit_time'] ?: time();
        $sample->count = (int)max($data['samples'], 1);
        $sample->success_samples = $data['success_samples'] ?: 0;
        $sample->note = $data['note'];
        $sample->description = $data['description'];
        $sample->yiqikong_id = $data['yiqikong_id'];
        $sample->extra_fields = $data['extra_fields'];

        Event::trigger('eq_sample.sample.extra.fields.change', $sample);

        Cache::L('YiQiKongSampleAction', TRUE);
        if ($sample->save()) {
            $extra_value = O('extra_value', ['object' => $sample]);
            if (!$extra_value->id) $extra_value->object = $sample;
            $extra_value->values = $data['extra_fields'];
            $extra_value->save();
            $lab = Q("$sample->sender lab")->current();
            $res = [
                'id' => $sample->id,
                'user_name' => $sample->sender->name,
                'lab_name' => $lab->name,
                'lab_id' => $lab->id,
                'user_local' => $sample->sender->id,
                'group_name' => $sample->sender->group->name,
            ];
            return $res;
        }
        return FALSE;
    }

    public static function update($id, $data)
    {
        $me = $data['user_local'] ? O('user', $data['user_local']): O('user', ['yiqikong_id' => $data['user']]);
        if (!$me->id) throw new API_Exception;

        Cache::L('ME', $me);

        $sample = O('eq_sample', $id);
        if (!$sample->id) throw new API_Exception;

        if (!isset($data['record_source_id']) && !$me->is_allowed_to('修改', $sample)) {
            throw new API_Exception(join(',', Lab::messages('sample')));
        }

        // 自定义表单验证 拥有特殊自定义表单的送样要验证 一期不做所以传空
        $form = Form::filter([]);
        $form['extra_fields'] = $data['extra_fields'];
        Extra::validate_extra_value(null, $sample->equipment, 'eq_sample', $form);
        if (!$form->no_error) throw new API_Exception(join(' ', array_column($form->errors, 0)));

        $sample->sender = $me;
        $operator = $data['user_local'] ? O('user', $data['user_local']): O('user', ['yiqikong_id' => $data['operator']]);
        if ($operator->id) $sample->operator = $operator;
        $sample->lab = Q("{$sample->sender} lab")->current();
        $sample->equipment = $sample->equipment;
        $sample->status = $data['status'] ?: EQ_Sample_Model::STATUS_APPLIED;
        $sample->dtstart = $data['start_time'] ?: 0;
        $sample->dtend = $data['end_time'] ?: 0;
        $sample->dtpickup = $data['pickup_time'] ?: 0;
        $sample->dtsubmit = $data['submit_time'] ?: 0;
        $sample->count = (int)max($data['samples'], 1);
        $sample->success_samples = $data['success_samples'] ?: 0;
        $sample->note = $data['note'];
        $sample->description = $data['description'];
        $sample->extra_fields = $data['extra_fields'];
        Event::trigger('eq_sample.sample.extra.fields.change', $sample);
        if (isset($data['record_source_id'])) {
            foreach (Q("$sample eq_record") as $re) {
                $sample->disconnect($re);
            }
            foreach (explode(',', ($data['record_source_id'])) as $record_source_id) {
                $record = O('eq_record', $record_source_id);
                $sample->connect($record);
            }
        }
        if ($data['app_approval'])
            $sample->app_approval = $data['app_approval'];

        Cache::L('YiQiKongSampleAction', TRUE);
        if ($sample->save()) {
            $extra_value = O('extra_value', ['object' => $sample]);
            if (!$extra_value->id) $extra_value->object = $sample;
            $extra_value->values = $data['extra_fields'];
            $extra_value->save();
            $lab = Q("$sample->sender lab")->current();
            $res = [
                'id' => $sample->id,
                'user_name' => $sample->sender->name,
                'user_local' => $sample->sender->id,
                'lab_name' => $lab->name,
                'lab_id' => $lab->id,
                'group_name' => $sample->sender->group->name,
            ];
            return $res;
        };
        return false;
    }

    public static function delete($id, $data)
    {
        $me = $data['user_local'] ? O('user', $data['user_local']): O('user', ['yiqikong_id' => $data['operator']]);
        if (!$me->id) throw new API_Exception;
        Cache::L('ME', $me);

        $sample = O('eq_sample', $id);
        if (!$sample->id) throw new API_Exception;

        Cache::L('YiQiKongSampleAction', TRUE);
        return $sample->delete();
    }

}
