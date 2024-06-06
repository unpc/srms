<?php

class CLI_Wordpress {

    //SITE_ID=xx LAB_ID=xxx php cli.php wordpress push blogid username password  form(json结构)
    static public function push($blogid, $username, $password, $form) {
        //form 为 json 字符串

        $form = json_decode($form, TRUE);

        $client = new Wordpress($blogid, $username, $password);

        $tmp_object = new CLI_Wordpress();
        if ($form['sync']['users']) $tmp_object->_sync_users($client);
        if ($form['sync']['equipments']) $tmp_object->_sync_equipments($client);
        if ($form['sync']['publications']) $tmp_object->_sync_publications($client, $form['sp_type']);
    }

    // 同步用户
    private function _sync_users($client) {

        $users = Q('user[!hidden]');

        $succeeded_count = 0;
        foreach ($users as $user) {
            $client->insert_or_update_user($user);
        }
    }

    private function _sync_equipments($client) {
        $sync_groups_result = $client->sync_groups();

        $sync_eq_tags_result = $client->sync_eq_tags();

        $equipments = Q('equipment');

        foreach ($equipments as $equipment) {
            $client->insert_or_update_equipment($equipment);
        }
    }

    //默认排序顺序为TIME_DESC
    private function _sync_publications($client, $sp_type = WP_Sync::TYPE_TIME_DESC) {

        switch($sp_type) {
        case WP_Sync::TYPE_TIME_ASC :
            $suffix = ':sort(date A)';
            break;
        case WP_Sync::TYPE_TIME_DESC :
        default :
            $suffix = ':sort(date D)';
            break;
        }

        $selector = 'publication';

        if ($suffix)  $selector .= $suffix;

        $publications = Q($selector);
        foreach ($publications as $publication) {
            $client->insert_or_update_publication($publication);
        }
    }
}
