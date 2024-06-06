<?php

class CLI_Reset {

    static function erase_all () {
        $db = Database::factory();
        $db->empty_database();

        exec('rm -rf /home/disk/*');

        $command = 'SITE_ID='.SITE_ID.' LAB_ID='.LAB_ID.' php '.ROOT_PATH.'cli/create_orm_tables.php';
        exec($command);

        $cache = Cache::factory('redis');
        $cache->flush();

        $user = O('user');
        $token = Auth::normalize('genee|database');
        $user->token = $token;
        $user->email = 'support@geneegroup.com';
        $user->name = '技术支持' ;
        $user->member_type = 0;
        $user->atime = time();
        $password = '83719730';
        $auth = new Auth($token);
        $auth->create($password);
        $user->save();
        $user->connect(Lab_Model::default_lab());
    }
}