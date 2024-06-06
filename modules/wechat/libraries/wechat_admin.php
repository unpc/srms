<?php

/*
class Wechat_Admin {

    static function setup(){
        if(L('ME')->is_allowed_to('管理角色', 'user')){
            Event::bind('admin.preferences.tab', 'Wechat_Admin::_edit_wechat_tab');
        }
    }

    static function _edit_wechat_tab($e, $tabs) {
        Event::bind('admin.preferences.content', 'Wechat_Admin::_edit_wechat_tab_content', 0, 'wechat');
        $tabs
        ->add_tab('wechat', array(
            'url'=> URI::url('admin/preferences.wechat'),
            'title'=>I18N::T('wechat', '微信设置'),
        ));
    }

    static function _edit_wechat_tab_content($e, $tabs){
        $form = Form::filter(Input::form());
        if(Input::form('submit')){
            $form
                ->validate('appID', 'not_empty', T('AppID不能为空!'))
                    ->validate('Secret', 'not_empty', T('Secret不能为空!'));

            if($form->no_error){
                Lab::set('Wechat.AppID', $form['appID']);
                Lab::set('Wechat.Secret', $form['Secret']);

                //完成平台注册
                //TODO:生成微信Secret
                $str = null;
                $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
                $max = strlen($strPol)-1;

                for($i=0;$i<20;$i++){
                    $str.=$strPol[rand(0,$max)];
                }
                $secretForWechat = $str;
                Lab::set('Wechat.WechatSecret', $secretForWechat);
                $wechat_api = Config::get('wechat.wechat_url');
                try {
                    $rpc = new RPC($wechat_api);
                    if ($rpc->platform->authorize(Lab::get('Wechat.AppID'), Lab::get('Wechat.Secret'))) {
                        $id = $rpc->platform->finishRegister($secretForWechat);
                        Lab::set('Wechat.PlatformID', $id);
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('wechat', '微信平台绑定成功!'));
                }
                catch(RPC_Exception $e) {
                    Lab::message(Lab::MESSAGE_NORMAL, $e->getMessage());
                }
            }
        }
        if(Input::form('unregister')) {
            $wechat_api = Config::get('wechat.wechat_url');
            try {
                $rpc = new RPC($wechat_api);
                if ($rpc->platform->authorize(Lab::get('Wechat.AppID'), Lab::get('Wechat.Secret'))) {
                    $rpc->platform->unregister();
                }
                Lab::set('Wechat.AppID', null);
                Lab::set('Wechat.Secret', null);
                Lab::set('Wechat.WechatSecret', null);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('wechat', '微信平台解绑定成功!'));
            }
            catch(RPC_Exception $e) {
                Lab::message(Lab::MESSAGE_NORMAL, $e->getMessage());
            }
        }
        $tabs->content = V('wechat:wechat_setting', array('form'=>$form));
    }
}
*/
