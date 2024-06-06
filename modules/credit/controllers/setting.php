<?php

class Setting_Controller extends Base_Controller
{
}

class Setting_AJAX_Controller extends AJAX_Controller
{

    public function index_notification_setting_submit()
    {
        $form = Input::form();
        if (!$form['type']) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '不支持的消息类型!'));
            JS::refresh();
            exit(0);
        }
        $me = L('ME');

        $measures = Q('credit_measures');
        $message = O('message', $form['mid']);
        $currentMeasure = null;
        foreach ($measures as $measure) {
            if (strpos($message->body, $measure->name)) {
                $currentMeasure = $measure;
                break;
            }
        }

        $setting = O('notification_read_setting', ['source' => $currentMeasure, 'user' => $me]);
        if (!$form['vl']) {
            $setting->delete();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', "设置成功!"));
            JS::refresh();
            exit;
        }
        $setting->user = $me;
        $setting->measure = $measure;
        $setting->source = $measure;
        $setting->type = 0;

        if ($setting->save()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', "设置成功!"));
            JS::refresh();
        } else {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', "设置失败!"));
            JS::refresh();
        }
    }
}
