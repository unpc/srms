<?php

class CLI_Db_Sync
{
    public static function __index()
    {
        echo "Available commands:" . PHP_EOL;
        echo "  refresh_subsite_status" . PHP_EOL;
        echo PHP_EOL;
    }

    public static function refresh_subsite_status()
    {
        $slaves = Config::get('site.slave');
        foreach ($slaves as $slave) {
            $subsite = O('subsite', ['ref_no' => $slave['name']]);
            if (!$subsite->id) {
                continue;
            }

            try {
                $db  = Database::factory($slave['name']);
                $sql = 'show slave status';
                $res = $db->query($sql);
                $row = $res ? $res->row('assoc') : [];
                if ($row['Slave_IO_Running'] == 'Yes' && $row['Slave_SQL_Running'] == 'Yes') {
                    $subsite->status = Subsite_Model::CONNECTED;
                    $subsite->save();
                } else {
                    /**
                     * @todo 直接发消息给开发
                     */
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }

    public static function sync_pictures()
    {
        if (DB_SYNC::is_master()) return;
        $opt = Config::get('rpc.master');
        $rpc = new RPC($opt['url']);
        $rpc->set_header(
            [
                "CLIENTID: {$opt['client_id']}",
                "CLIENTSECRET: {$opt['client_secret']}"
            ]
        );

        $tables = ['user', 'lab', 'equipment', 'billing_department'];
        foreach ($tables as $table) {
            if (DB_SYNC::is_module_unify_manage($table)) {
                $objects = Q($table);
                foreach ($objects as $object) {
                    $icon_url = $rpc->db_sync->getRealIcon($object->name(), $object->id);
                    //没有上传图片 则跳过
                    if (!$icon_url) continue;
                    $remote_url = $icon_url['iconreal_url'] ?: $icon_url['icon128_url'];
                    $file_path = self::get_remote_pictures($remote_url);
                    $image = Image::load($file_path);
                    if ($icon_url['iconreal_url']) {
                        $object->save_real_icon($image);
                    }
                    $object->save_icon($image);
                    echo "{$object->name}[{$object->id}] 更新图标成功;\r\n";
                }
            }
        }
    }

    //保存远程网络图片到本地
    private static function get_remote_pictures($url){
        $save_path = '/tmp/';
        $file_name = explode('.',basename($url))[0].'.png';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $img = curl_exec($ch);
        curl_close($ch);

        $fp2 = @fopen($save_path .$file_name ,'a');
        fwrite($fp2,$img);
        fclose($fp2);
        unset($img,$url);
        return $save_path.$file_name ;
    }
}
