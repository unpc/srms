<?php

class QRCode_Controller extends Controller {

	function app ($id = 0) {
		$me = L('ME');
		if (!class_exists('QRcode', false)) Core::load(THIRD_BASE, 'qrcode', '*');

        $phone = preg_replace('/[^\d]+/', ' ', $me->phone);
        $token = ($me->dto != 0 && $me->dto < time()) ? '' : uniqid();
        $cache = Cache::factory('redis');
        $cache->set("qrcode_{$me->id}", $token, 300);
        
        $note = ($me->dto != 0 && $me->dto < time()) ? 'expired' : '';

        $base_url = Config::get('app.base_url') ? : '';
        $data = sprintf('MECARD:N:%s;EMAIL:%s;TEL:%s;ADR:%s;LAB:%s;TOKEN:%s;ID:%s;YIQIKONG:%s;NOTE:%s;BASE_URL:%s;USER:%s;',
            $me->name, $me->email, $phone, $me->address, LAB_ID, $token, $me->id, !!$me->yiqikong_id ? 1 : 0, $note, $base_url, $me->token);

		header('Expires: Thu, 15 Apr 2100 20:00:00 GMT'); 
		header('Pragma: public');
		header('Cache-Control: max-age=604800');
		header('Content-type: image/png');
		QRcode::png($data, NULL, QR_ECLEVEL_S, 3, 0);
		exit;
	}

}

class QRCode_AJAX_Controller extends AJAX_Controller {

    function index_bind_view () {
        $me = L('ME');

        if (!$me->email) {
            try{
                if (People::perm_in_uno() && $me->gapper_id) {
                    // 获取一次人员信息
                    $remote_user = Gateway::getRemoteUserDetail([
                     'USER_ID' => $me->gapper_id
                    ]);
                    if (isset($remote_user['email']) && $remote_user['email']) {
                        $user = O('gapper_user', ['gapper_id' => $remote_user['id']]);
                        $user->email = $remote_user['email'];
                        $user->save();
                        $me = $user;
                    }
                 }
            } catch (\Exception $e) {
                
            }
        }
        
        if (!$me->email) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('wechat', "您尚未补全电子邮箱信息!"));
            JS::refresh();
            return;
        }

        $name = $me->name;
        $email = $me->email;
        $token = base64_encode($name . '|' . $email);

        // 编译后如果存在反斜杠进行替换
        $token = str_replace(array('+','/'), array('-','_'), $token);

        JS::dialog(V('wechat:show_qrcode', [
            'image_data' => $token . '.' . LAB_ID, 
            'width'=> 200, 
            'height'=> 200
        ]), [
            'title' => I18N::T('wechat', '绑定微信可接收系统相关消息提醒，请扫描下方二维码：'),
            'width' => 400
        ]);
    }

    function index_app_bind_click () {
        $me = L('ME');

        if (!$me->email) {
            try{
                if (People::perm_in_uno() && $me->gapper_id) {
                    // 获取一次人员信息
                    $remote_user = Gateway::getRemoteUserDetail([
                     'USER_ID' => $me->gapper_id
                    ]);
                    if (isset($remote_user['email']) && $remote_user['email']) {
                        $user = O('gapper_user', ['gapper_id' => $remote_user['id']]);
                        $user->email = $remote_user['email'];
                        $user->save();
                        $me = $user;
                    }
                 }
            } catch (\Exception $e) {

            }
        }

        if (!$me->email) {
            try{
                if (People::perm_in_uno() && $me->gapper_id) {
                    // 获取一次人员信息
                    $remote_user = Gateway::getRemoteUserDetail([
                     'USER_ID' => $me->gapper_id
                     ]);
                     if (isset($remote_user['email']) && $remote_user['email']) {
     
                     }
                 }
            } catch (\Exception $e) {

            }
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('wechat', "您尚未补全电子邮箱信息!"));
            JS::refresh();
            return;
        }

        JS::dialog(V('yiqikong:user/qrcode', [
            'width'=> 200, 
            'height'=> 200
        ]), [
            'title' => I18N::T('yiqikong', '请扫描下方二维码：'),
            'width' => 400
        ]);
    }

    function index_app_unbind_click () {
        $me = L('ME');
        JS::dialog(V('yiqikong:user/unbind', ['user' => $me]), ['title' => '解绑提示']);
    }

    function index_app_unbind_submit () {
        $user = L('ME');
        $form = Input::form();
        if ($form['submit']) {
			// 解绑账号
            try {
                $server = Config::get('app.control_user');
                $rest = new REST($server['url']);
                $response = $rest->get("v2/user/node?lab_id=".LAB_ID."&source_id=$user->id");
                if ($user->yiqikong_id) {
                    foreach (Q("user[yiqikong_id={$user->yiqikong_id}][id!=$user->id]") as $u) {
                        $u->yiqikong_id = null;
                        $u->save();
                    }
                }
                if (isset($response['data']) && !count($response['data'])) {
                    $user->yiqikong_id = null;
                    $user->save();
                } else {
                    $response= $rest->delete("v2/user/node/$user->id", [
                        'form_params' => [
                            'source_name' => LAB_ID
                        ],
                    ]);
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('wechat', "解绑成功"));
                JS::refresh();
                return;
            } catch (\Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('wechat', "解绑失败"));
                JS::refresh();
                return;
            }
		}
        JS::dialog(V('yiqikong:user/unbind', ['user' => $me]), ['title' => '解绑提示']);
    }

}
