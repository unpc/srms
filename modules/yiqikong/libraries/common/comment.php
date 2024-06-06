<?php

class Common_Comment extends Common_Base
{
    public static function update($data)
    {
        $type = $data['type'];
        if (!$data['source_id'] || !$data['type'])
            throw new API_Exception(I18N::T('eq_comment', '参数错误'), 1001);

        if (!in_array($type, ['incharge', 'user'])) {
            throw new API_Exception(I18N::T('eq_comment', '不支持的评价类型'), 1104);
        }
        $record = O('eq_record', $data['source_id']);
        if (!$record->id)
            throw new API_Exception(I18N::T('eq_comment', '未找到对应使用记录'), 1105);
        $user = O('user', ['yiqikong_id' => $data['user']]);
        if (!$user->id)
            throw new API_Exception(I18N::T('eq_comment', '未找到对应用户'), 1106);

        $comment = O("eq_comment_{$type}", ['source' => $record]);
        $comment->source = $record;
        $comment->equipment = $record->equipment;
        $comment->user = $user;
        $comment->service_attitude = $data['service_attitude'];
        $comment->service_quality = $data['service_attitude'];
        $comment->technical_ability = $data['service_attitude'];
        $comment->emergency_capability = $data['emergency_capability'];
        $comment->detection_performance = $data['detection_performance'];
        $comment->accuracy = $data['accuracy'];
        $comment->compliance = $data['compliance'];
        $comment->timeliness = $data['timeliness'];
        $comment->sample_processing = $data['sample_processing'];
        $comment->comment_suggestion = $data['comment_suggestion'];
        $comment->obj_dtstart = $record->dtstart;
        $comment->obj_dtend = $record->dtend;
        if ($comment->save()) {
            return true;
        }
        return false;
    }
}