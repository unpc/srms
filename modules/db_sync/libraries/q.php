<?php

class Q extends _Q
{
    //关联模块名 => 表名
    static $map = [
        // 'vidmon'     => 'vidcam',
        // 'gismon'     => 'gis_building',
        // 'envmon'     => 'env_node',
        // 'entrance'   => 'door',
        // 'equipments' => 'equipment',
        // 'billing'    => 'billing_department',
    ];

    public $extra_selector;

    public function __construct($selector, $db = null)
    {
        if (Db_Sync::is_slave()) {
            $selector = self::extra_selector($selector, LAB_ID);
        }

        if (Db_sync::is_master() && $_SESSION['from_lab']) {
            $selector = self::extra_selector($selector, $_SESSION['from_lab']);
        }

        $this->extra_selector = $selector;

        parent::__construct($selector, $db);
    }

    private static function extra_selector($selector, $lab_id)
    {
        $sync_tables = Config::get('db_sync.tables', []);

        // user#1 => user[id=1]
        // $selector = preg_replace('/([a-z]+)#(\d+)/i', "$1[id=$2]", $selector);

        $skip_tables = ['user', 'lab', 'tag_group', 'role', 'perm'];

        foreach ($sync_tables as $table) {
            if (strstr($selector, $table)) {
                if ($GLOBALS['preload']['billing.single_department'] && $table == 'billing_department') {
                    continue;
                }

                // 用户在主站或者从站选择都不需要进行site字段的筛选
                if (in_array($table, $skip_tables)) {
                    continue;
                }

                $selector = preg_replace("/(.*?)([^_]){$table}(?!#|\]|=|_)/i", "$1$2{$table}[site={$lab_id}]", $selector);
            }
        }

        return $selector;
    }

}
