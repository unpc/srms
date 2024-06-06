<?php
class CLI_Eq_Sample
{
    public static function update_history()
    {
        $db = Database::factory();
        $db->query('UPDATE `eq_sample` SET `status` = 6 WHERE `status`=1');
    }
}
