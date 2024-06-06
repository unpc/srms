<?php

class CLI_Eq_Struct
{
    public static function update()
    {
        $u = new Upgrader;

        $u->check = function () {
            $db = Database::factory();
            return (bool)$db->value('DESC `eq_struct` `type`');
        };

        //数据库备份
        $u->backup = function () {
            return true;
        };

        $u->upgrade = function () {
            $db = Database::factory();

            $query = "SELECT `_extra`,`id` FROM `eq_struct` ORDER BY `id`";
            $results = $db->query($query)->rows();
            foreach ((array)$results as $res) {
                $extra = json_decode($res->_extra, true);
                $query = "UPDATE `eq_struct` SET `group` = '{$extra['group']}', `token` = {$extra['token']}, `_extra` = NULL
                WHERE `id` = {$res->id}";
                $db->query($query);
            }
        
            // DROP COLUMNs
            $query = "ALTER TABLE `eq_struct` DROP `description`, DROP `type`, DROP `mtime`;";
            $db->query($query);

            Upgrader::echo_success("user 数据升级成功!");
            return true;
        };

        //恢复数据
        $u->restore = function () {
            return true;
        };

        $u->run();
    }

    public static function import() {
        $file = LAB_PATH . 'modules/eq_struct/private/csv/eq_struct.csv';
        $csv = new CSV($file, 'r');
        $csv->read(',');

        while ($row = $csv->read(',')) {
            $token = $row[0];
            $ref_no = $row[4];
            $proj_no = $row[3];
            $struct = O('eq_struct', ['token' => $token]);
            if ($struct->id) {
                $struct->ref_no = $ref_no;
                $struct->proj_no = $proj_no;
                $struct->pch = '030-2018040001';
                $struct->card_no = '05860251000001';
                $struct->save();
            }
        }
    }
}
