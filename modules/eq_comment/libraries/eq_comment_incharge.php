<?php

class EQ_Comment_Incharge {

    public static function eq_sample_links_edit($e, $sample, $links, $mode) {
        $me = L('ME');
        
        if ($me->is_allowed_to('评价机主', $sample) && $sample->status == EQ_Sample_Model::STATUS_TESTED && $sample->feedback == 0) {
            $links['comment_incharge'] = [
                'url' => '#',
                'text' => I18N::T('eq_comment', '反馈'),
                'extra'=>'class="blue" q-event="click" q-object="comment_incharge" q-static="'.H(['id' => $sample->id, 'object_type' => 'sample']).'" q-src="'.URI::url('!eq_comment/incharge').'"',
            ];
        }
    }

    public static function comment_incharge_ACL($e, $user, $action, $object, $options) {
        $me = L('ME');
        $comment = O('eq_comment_incharge', ['source' => $object]);

        switch ($action) {
            case '评价机主':
                if ($object->name() == 'eq_record') {
                    $u = $object->user;
                } else if ($object->name() == 'eq_sample') {
                    $u = $object->sender;
                }
                if ($u->id == $me->id || in_array($me->token, Config::get('lab.admin'))) {
                    $e->return_value = TRUE;
                }
                if (Config::get('comment.eq_comment_switch') && $object->name() == 'eq_record' && $object->equipment->require_eq_comment_user !== true) {
                    $e->return_value = FALSE;
                }
                if ($comment->id) {
                    $e->return_value = FALSE;
                }

                break;
            default:
                break;
        }
    }

    public static function eq_record_before_save($e, $record, $new_data) {
        // $comment = O('eq_comment_incharge', ['source' => $record]);
        // if (!$comment->id) {
        //     $record->status = EQ_Record_Model::FEEDBACK_NOTHING;
        //     $record->feedback = '';
        // }

        /**
         * 20191015 Clh #20191967 add !$record->status 
         * 没有反馈的校外用户使用记录加上自动反馈
         * 已经反馈表明用户|机主|管理员想将重新反馈，在这里就被系统自动替换了
         */
        if (!$record->user->token && $record->dtend && !$record->status) {
            $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
            $record->feedback = I18N::T('equipments', '系统自动对校外用户记录进行反馈!');
        }
    }

    public static function eq_sample_before_save($e, $sample) {
        if (!$sample->sender->token) {
            $sample->feedback = 1;
        }
    }

    /*
    机主添加送样不受反馈限制
    public static function sample_form_submit($e, $sample, $form) {
        $user = O('user', $form['sender']);
        if (!$form['id'] && Q("eq_sample[feedback=0][sender=$user]")->total_count()) {
            $form->set_error('count',  I18N::T('eq_comment', '您有未反馈的送样记录, 请反馈后再申请送样!'));
        }
    }
    */

    
    public static function extra_form_validate($e, $equipment, $type, $form) {
        $status = EQ_Sample_Model::STATUS_TESTED;
        $me = L('ME');
        if (!$form['id'] 
            && $type =='eq_sample' 
            && !Equipments::user_is_eq_incharge($me, $equipment)
            && !$me->is_allowed_to('添加送样记录', $equipment)
            && Q("eq_sample[feedback=0][sender=$me][status=$status]")->total_count()) {
            $form->set_error('count',  I18N::T('eq_comment', '您有未反馈的送样记录, 请反馈后再申请送样!'));
        }
    }
    

    public static function eq_comment_incharge_save($e, $object, $form) {

        $fields = Config::get('schema.eq_comment_incharge')['fields'];
        unset($fields['equipment']);
        unset($fields['source']);
        unset($fields['user']);
        unset($fields['obj_dtstart']);
        unset($fields['obj_dtend']);

        $me = L('ME');

        if ($me->is_allowed_to('评价机主', $object)) {
            foreach ($fields as $key => $value) {
                if ($key != 'comment_suggestion') {
                    if (!is_numeric($form[$key]) || $form[$key] < 1 || $form[$key] > 5) {
                        $form->set_error($key, '评价填写有误!');
                        break;
                    }
                    if ($form[$key] && $form[$key] < 5 && $form['comment_suggestion'] == '') {
                        $form->set_error('comment_suggestion', '请填写服务评价与建议!');
                        break;
                    }
                }
            }

            if (mb_strlen($form['comment_suggestion']) > 240) {
                $form->set_error('content', '服务评价与建议填写有误, 长度不得大于240!');
            }

            if ($form->no_error) {
                $comment = O('eq_comment_incharge', ['source' => $object]);
                if (!$comment->id) {
                    $comment = O('eq_comment_incharge');
                }
                $comment->equipment = $object->equipment;
                $comment->source = $object;
                if ($object->name() == 'eq_sample') $comment->user = $object->sender;
                else $comment->user = $object->user;
                foreach ($fields as $key => $value) {
                    $comment->$key = $form[$key];
                }
                if ($comment->save()) {
                    if ($object->name() == 'eq_sample') {
                        $object->feedback = 1;
                        $object->save();
                    }
                }
            }
        }
    }

    public static function object_saved($e, $object, $new_data, $old_data) {
        $comment = O('eq_comment_incharge', ['source' => $object]);
        if (!$comment->id) {
            if ($object->name() == 'eq_record' && !$object->user->token
            && $object->status = EQ_Record_Model::FEEDBACK_NORMAL) {
                $comment->user = $object->user;
                $comment->obj_dtend = $object->dtend;
                $comment->obj_dtstart = $object->dtstart;
                $comment->service_attitude = 5;
                $comment->service_quality = 5;
                $comment->technical_ability = 5;
                $comment->emergency_capability = 5;
                $comment->detection_performance = 5;
                $comment->accuracy = 5;
                $comment->compliance = 5;
                $comment->timeliness = 5;
                $comment->sample_processing = 5;
                $comment->save();
            }
            else if ($object->name() == 'eq_sample' && !$object->sender->token
            && $object->feedback = 1) {
                $comment->user = $object->sender;
                $comment->obj_dtend = $object->dtsubmit;
                $comment->obj_dtstart = $object->dtstart;
                $comment->service_attitude = 5;
                $comment->service_quality = 5;
                $comment->technical_ability = 5;
                $comment->emergency_capability = 5;
                $comment->detection_performance = 5;
                $comment->accuracy = 5;
                $comment->compliance = 5;
                $comment->timeliness = 5;
                $comment->sample_processing = 5;
                $comment->save();
            }
            else return TRUE;
        }

        if ($object->name() == 'eq_sample') {
            $comment->user = $object->sender;
            $comment->obj_dtend = $object->dtsubmit;
        } else {
            $comment->user = $object->user;
            $comment->obj_dtend = $object->dtend;
        }
        $comment->obj_dtstart = $object->dtstart;
        $comment->save();
    }

    public static function glogon_switch_to_logout_record_saved($e, $record, $data) {
        if ($data['extra'] && $record->id) {
            $form = $data['extra'];

            $fields = Config::get('schema.eq_comment_incharge')['fields'];
            unset($fields['equipment']);
            unset($fields['source']);
            unset($fields['user']);
            unset($fields['obj_dtstart']);
            unset($fields['obj_dtend']);

            $comment = O('eq_comment_incharge');
            $comment->equipment = $record->equipment;
            $comment->source = $record;
            $comment->user = $record->user;
            foreach ($fields as $key => $value) {
                $comment->$key = $form[$key];
            }
            $comment->obj_dtstart = $record->dtstart;
            $comment->obj_dtend = $record->dtend;
            $comment->save();
        }
    }

    /**
     * 双向评价存在时，不判断评分
     * @param $e
     * @return bool
     */
    public static function need_evaluate_by_source($e, $object,$ignore = ''){
        $e->return_value = $ignore == 'ignore' ? false : true;
        return false;
    }
}
