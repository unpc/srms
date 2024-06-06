<?php

class QRCode_Controller extends Controller {

    function equipment($id = 0) {

        Core::load(THIRD_BASE, 'qrcode', '*');

        if ($id) {
            $data = Config::get('wechat.wechat_equipment_url').$id;
            //加上 equipment_id 给小程序扫码获取仪器id用 [因小程序获取仪器详情的接口不支持yiqikong id]
            $equipment = O('equipment', ['yiqikong_id' => $id]);
            $data .= "?equipment_id={$equipment->id}";
        }
        else {
            $data = 'ACCESS DENIED';
        }

        header('Expires: Thu, 15 Apr 2100 20:00:00 GMT');
        header('Pragma: public');
        header('Cache-Control: max-age=604800');
        header('Content-type: image/png');
        QRcode::png($data, NULL, QR_ECLEVEL_M, 3.3,0);
        exit;
    }

    function equipment_export($id = 0) {

        Core::load(THIRD_BASE, 'qrcode', '*');
        $equipment = O('equipment', $id);
        $data = Config::get('wechat.wechat_equipment_url').$equipment->yiqikong_id;
        $pic_file = 'eq'.$id.'.png';
        QRcode::png($data, $pic_file, QR_ECLEVEL_M, 10,0);

        $image = Image::load($pic_file);
        $image->resize(660, 660);

        $image->crop(-10, -10, 680, 990);

        $font = Core::file_exists(PRIVATE_BASE.'fonts/SourceHanSansK-Normal.ttf');

        $name = $equipment->name;

        $arr = [];
        $j = 0;
        $i = 0;

        for($i = 0; $i < mb_strlen($name); $i += 1) {
            $item = mb_substr($equipment->name, $i, 1);
            switch(strlen($item)) {
                case 3 : //中文
                    $dl = 2;
                    break;
                case 1 : //英文
                    $dl = 1;
                    break;
            }

            $l += $dl;
            $arr[$j] .= $item;

            if ($l >= 28) {
                $j ++;
                $l = 0;
            }
        }

        $posy = 690;

        foreach($arr as $a) {
            $image->text($a, 10, $posy, $font, 35);
            $posy += 58;
        }

        $image->text($equipment->ref_no, 10, $posy + 8, $font, 35);

        $image->text(Config::get('page.title_default'), 10, $posy + 60, $font, 30);

        $image->background_color('#FFFFFF');

        $image->show();

        @File::delete($pic_file);
    }

    function get_qrcode($data, $lab_id) {
        // 将之前data中替换的'/'替换回去
        $data = urldecode(Config::get('wechat.wechat_userbind_url').$data.'/'.$lab_id);
        Core::load(THIRD_BASE, 'qrcode', '*');

        header('Expires: Thu, 15 Apr 2100 20:00:00 GMT');
        header('Pragma: public');
        header('Cache-Control: max-age=604800');
        header('Content-type: image/png');
        QRcode::png($data, NULL, QR_ECLEVEL_M, 10,0);
        exit;
    }
}

class QRCode_AJAX_Controller extends AJAX_Controller {

    function index_bind_click($id = 0) {

        if($id) {
            $user = O('user', $id);

            if (!$user->email) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('wechat', "您尚未补全电子邮箱信息!"));
                JS::refresh();
                return;
            }

            /*switch($user->wechat_bind_status) {
                case Wechat::BIND_STATUS_NOT_YET :*/

            $name = $user->name;
            $email = $user->email;
            $token = base64_encode($name . '|' . $email);

            // 编译后如果存在反斜杠进行替换
            $token = str_replace(array('+','/'), array('-','_'), $token);

            JS::dialog(V('wechat:show_qrcode', ['image_data'=> $token.'.'.LAB_ID, 'width'=> 200, 'height'=> 200]), ['title'=>I18N::T('wechat', '请使用微信扫描二维码')]);
                /*    break;
                case Wechat::BIND_STATUS_SUCCESS :
                    if (JS::confirm(I18N::T('wechat', '您确定对微信账号解除绑定吗?'))) {
                        //unbind 的时候还需要同步到远程
                        $user->wechat_unbind(TRUE);

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('wechat', '解绑成功!'));
                        JS::refresh();
                    }
            }*/
        }
        else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('wechat', "您的绑定信息异常, 请联系客服完善账号信息! \n\n客服电话: 400-617-5664"));
            JS::refresh();
        }
    }
}
