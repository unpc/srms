<?php

class Biosignature {

    static function on_user_model_update_image ($e, $user) 
    {
        $icon_file = Core::file_exists(PRIVATE_BASE . "icons/user/128/{$user->id}.png", '*');
        $targets = Config::get('biosignature.face');
        $dest = $targets['dir'];
        if (is_dir($dest) && is_file($icon_file)) {
            $name = File::basename($icon_file);
            $dest_path = $dest . '/' . $name;
            @copy($icon_file, $dest_path);
        }
    }
}