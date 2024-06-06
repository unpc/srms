#!/usr/bin/env php
<?php
    /*
     * file check_table_schema.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  
     *
     * useage SITE_ID=cf LAB_ID=test php create_orm_tables.php
     * brief 检测schema和table是否相同
     */

require 'base.php';

$done_file = strtr('check_table_schema_%site_%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

if (File::exists($done_file)) die;

$db = Database::factory();

$error = [];
//[table]=> [
    //[field]=> [
        //field1,
        //field2,
    //],
    //[indexes]=> [
        //key1,
        //key2,
    //]
//]


foreach(Config::$items['schema'] as $table => $foo) {

    if (!$db->table_exists($table)) continue;

    $schema = ORM_Model::schema($table);

    //获取fields
    $table_fields = $db->table_fields($table, TRUE);
    $fields = $schema['fields'];

    $field_diff = array_diff_key($fields, $table_fields);

    if (count(array_diff_key($fields, $table_fields))) {
        $error[$table]['fields'] = array_keys(array_diff_key($fields, $table_fields));
    }

    //获取indexes
    $table_indexes = $db->table_indexes($table, TRUE);
    $indexes = $schema['indexes'];

    if (count(array_diff_key($indexes, $table_indexes))) {
        $error[$table]['indexes'] = array_keys(array_diff_key($indexes, $table_indexes));
    }
}

ob_start();

echo "链接访问地址:\n". Lab::get('system.base_url'). "\n";

if (count($error)) {

    echo "发现表结构问题\n";

    foreach($error as $field => $info) {
        echo $field. ":\n";
        echo "\tfields: ". join(',', $info['fields']). "\n";
        echo "\tindexes: ". join(',', $info['indexes']). "\n";
    }
}
else {
    echo '表结构都正常';
}

$content = ob_get_contents();
ob_end_clean();

$mail = new Email();
$subject = Lab::get('lab.name'). '表结构检测结果';

$mail->subject($subject);

$to = [
    'rui.ma@geneegroup.com'
];

$mail->to($to);

$mail->body($content);

$mail->send();

@touch($done_file);
