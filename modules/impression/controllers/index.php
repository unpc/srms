<?php

class Index_Controller extends Layout_Controller {

    function index($module = null) {
        $modules = Config::get('modules');
        if ($modules[$module]['force_eject']) {
            $this->layout->body = V('index', ['module' => $module]);
        }
    }
}

