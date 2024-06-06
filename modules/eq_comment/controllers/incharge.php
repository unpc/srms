<?php

class Incharge_AJAX_Controller extends AJAX_Controller {

    public function index_comment_incharge_click() {
        $form = Form::filter(Input::form());

        switch ($form['object_type']) {
            case 'record':
                $object = O('eq_record', $form['id']);
                break;
            case 'sample':
                $object = O('eq_sample', $form['id']);
                break;
            default: 
                $object = null; 
        }
        $comment = O('eq_comment_incharge', ['source' => $object]);

        JS::dialog(V('comment/comment.incharge', [
            'object' => $object,
            'form' => $form,
        ]), [
            'width' => 370, 
            'title' => '使用评价'
        ]);
    }

    public function index_comment_incharge_submit() {
		$me = L('ME');
        $form = Form::filter(Input::form());
        switch ($form['object_type']) {
            case 'record':
                $object = O('eq_record', $form['object_id']);
                break;
            case 'sample':
                $object = O('eq_sample', $form['object_id']);
                break;
            default: 
                $object = null; 
        }

		if (!$form['submit'] || !$object->id || !$me->is_allowed_to('评价机主', $object)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_comment', '您无权进行操作!')); 
            JS::refresh();
            return;
        };

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
                        $form->set_error($key, '请填写服务评价与建议!');
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
                $comment->user = $object->sender;
                foreach ($fields as $key => $value) {
                    $comment->$key = $form[$key];
                }

                if ($comment->save()) {
                    $object->feedback = 1;
                    $object->save();

                    Log::add(strtr('[eq_comment] %user_name[%user_id] 反馈了ssyh1记录[%object_type][%object_id]', [
                        '%user_name' => $me->name, 
                        '%user_id' => $me->id, 
                        '%object_type%' => $form['object_type'],
                        '%object_id' => $object->id,
                    ]), 'journal');
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_comment', '反馈成功!')); 
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_comment', '反馈失败!'));
                }

                JS::refresh();
            } else {
                JS::dialog(V('comment/comment.incharge', [
                    'form' => $form,
                    'object' => $object,
                ]), [
                    'width' => 370, 
                    'title' => '使用评价'
                ]);
                return;
            }
        }
    }
}