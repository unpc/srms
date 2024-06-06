<?php

class Impression {

    static function setup() {
        $modules = Config::get('modules');
        foreach ($modules as $module => $value) {
            if (isset($value['force_eject']) && $value['force_eject']) {
                Event::bind($value['trigger'], 'Impression::'.$value['callback']);
            } else {
                
            }
        }
    }

    static function equipment_impression($e, $user) {
        $count = Q("eq_record[has_impression=0][user={$user}]")->total_count();
        if ($count) {
            $path = (defined('MODULE_ID') ? '!'.MODULE_ID.'/' :'').Config::get('system.controller_path').'/'.Config::get('system.controller_method');
            if($path !== '!people/index/password' && MODULE_ID !== 'impression' && Input::arg(0) !== 'logout'){
                URI::redirect('!impression/index/index.equipment');
            }
        }
    }
}
