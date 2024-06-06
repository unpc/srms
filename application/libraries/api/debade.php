<?php

class API_Debade {

    public function _default($data) {

        $hash = $_SERVER['HTTP_X_DEBADE_TOKEN'];
        $secret = Config::get('debade.secret');
        $str = file_get_contents('php://input');
        if ($hash != Debade::hash($str, $secret)) return;

        /*
         * method 通常为
         *    yiqikong/status/
         *    yiqikong/foo/bar
         * 进行 explode , yiqikong 理解为 namespace, status 、foo/bar 为具体函数名称
         */

        list($class, $method) = explode('/', $data['method'], 2);

        //增加 Debade 前缀
        $class = 'Debade_'. ucwords($class);

        // foo/bar ==> action_foo_bar
        $method = 'action_'. str_replace('/', '_', $method);

        $params = $data['params'];

        // 根据$params里边的 labid 重新设定labid 访问相应的数据库

        //进行分发
        if (method_exists($class, $method)) {
            call_user_func([$class, $method], $params);
        }
    }
}
