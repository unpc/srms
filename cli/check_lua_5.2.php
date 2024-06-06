#!/usr/bin/env php
<?php
    /*
     * file check_lua_5.2.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-03-27
     *
     * useage SITE_ID=cf LAB_ID=nankai php check_lua_5.2.php
     * brief 检测计费、预约脚本中table.getn 、 table.foreach 等不再适用的语法
     */

require dirname(__FILE__). '/base.php';

$done_file = strtr('check_lua_5.2_%site_%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//lab下不计费
//已执行过不再执行
if ($_SERVER['SITE_ID'] == 'lab' || File::exists($done_file)) die;

//table.foreach
//table.getn
//在lua5.2中不再支持

//错误提示
$errors = [];

foreach(Q('equipment') as $e) {

    $id = $e->id;

    $charge_script = $e->charge_script;
    $charge_template = $e->charge_template;

    //type为reserv  record sample等
    //charge_type只需要检测custome的即可
    foreach($charge_template as $type => $charge_type) {
        switch($type) {
            case 'reserv' :
                //自定义计费
                if ($charge_type == 'custom_reserv') {
                    $script = $charge_script['reserv'];

                    if (!(strpos($script, 'table.getn') === FALSE && strpos($script, 'table.foreach') === FALSE)) {
                        $errors[$id]['charge_script'][] = 'reserv';
                    }

                }
            break;
            case 'record' :
                //自定义计费
                if ($charge_type == 'custom_record') {
                    $script = $charge_script['record'];

                    if (!(strpos($script, 'table.getn') === FALSE && strpos($script, 'table.foreach') === FALSE)) {
                        $errors[$id]['charge_script'][] = 'record';
                    }
                }
            break;
            case 'sample' :
                //自定义计费
                if ($charge_type == 'custom_sample') {
                    $script = $charge_script['sample'];

                    if (!(strpos($script, 'table.getn') === FALSE && strpos($script, 'table.foreach') === FALSE)) {
                        $errors[$id]['charge_script'][] = 'sample';
                    }
                }
            break;
        }
    }

    //reserv_script

    $reserv_script = $e->reserv_script;

    if (!(strpos($reserv_script, 'table.get') === FALSE && strpos($reserv_script, 'table.foreach') === FALSE)) {
        $errors[$id]['reserv_script'] = NULL;
    }
}

ob_start();

echo strtr("链接访问地址:%url\n", ['%url'=> Lab::get('system.base_url')]);

if (count($errors)) {
    echo "发现如下脚本兼容问题\n";
    foreach($errors as $id => $error) {
        $e = O('equipment', $id);
        $name = $e->name;

        echo strtr("%name[%id]\n", [
            '%name'=> $name,
            '%id'=> $id
        ]);

        foreach($error as $type=> $value) {
            if (is_array($value)) {
                echo "$type: ";
                echo join(',', $value);
                echo "\n";
            }
            else {
                echo "$type\n";
            }
        }
    }
}
else {
    echo "脚本均兼容lua5.2 无需更新\n";
}
echo '注: 该脚本只针对计费的自定义脚本和预约自定义脚本进行检测, 系统自动生成脚本会在2.10进行升级脚本修正, 暂不检测.';

$content = ob_get_contents();

ob_end_clean();

$mail = new Email();

$subject = strtr('%name, lua脚本5.2兼容检测结果', [
    '%name' => Lab::get('lab.name')
]);

$mail->subject($subject);

$to = [
    'rui.ma@geneegroup.com',
];

$mail->to($to);

$mail->body($content);

$mail->send();

@touch($done_file);
