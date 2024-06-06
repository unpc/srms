<?php
class Current_User_Controller extends Controller {
	function index(){
        //jsonp方式返回登录用户信息
        $current_user = L('ME');
        if(!$_GET['callback'] || !$current_user->id) {
            URI::redirect('/');
        }
        $arr = [
            'name'=>L('ME')->name,
            'img'=>L('ME')->icon('64'),
            'backend' => $backend,
            'id'=>L('ME')->id,
        ];
        $result=json_encode($arr);
        //动态执行回调函数
        $callback=$_GET['callback'];
        echo $callback."($result)";
    }

    function logout(){
        $current_user = L('ME');
        if(!$_GET['callback'] || !$current_user->id) {
            URI::redirect('/');
        }

        Auth::logout();
        if ($current_user->id) {
            Log::add(strtr('[application] %user_name[%user_id]成功登出系统', [
                        '%user_name' => $current_user->name,
                        '%user_id' => $current_user->id,
            ]), 'logon');
            
            Log::add(strtr('[application] %user_name[%user_id]成功登出系统', [
                        '%user_name' => $current_user->name,
                        '%user_id' => $current_user->id,
            ]), 'journal');
        }
        
        //用户自助退出登录后，清空LOGIN_REFERER
        unset($_SESSION['#LOGIN_REFERER']);

        $result=json_encode(TRUE);
        //动态执行回调函数
        $callback=$_GET['callback'];
        echo $callback."($result)";
    }
}
