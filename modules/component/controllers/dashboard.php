<?php

class Dashboard_Controller extends Layout_Controller {

	public function index() {
        $me = L('ME');

        if (!$me->is_allowed_to('查看', 'dashboard')) {
            URI::redirect('error/401');
        }

        $token = Config::get('dashboard.base.token');
        if (!$token) {
            $token = uniqid();
            $cache = Cache::factory('redis');
            $cache->set($token, $me->id, 3600);
        }

        $src = URI::url(Config::get('dashboard.base.src'), ['token' => $token]);

        $this->layout->body = V('component:dashboard/index', ['src' => $src]);
        
    }
}