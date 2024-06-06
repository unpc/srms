#!/usr/bin/env php
<?php
    /*
     * file #!/usr/bin/env php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2014-05-21
     *
     * useage SITE_ID=cf LAB_ID=nankai php check_custom_scripts.php
     * brief 用于获取系统中所有的仪器中包含自定义脚本的仪器
     */


require 'base.php';

$done_file = strtr('%site_%lab_custom_script.csv', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//如果已存在该done_file 不再进行执行
if (File::exists($done_file)) die();

//不再可用排除
$status = EQ_Status_Model::NO_LONGER_IN_SERVICE;

//所有数据
$data = [];

foreach(Q("equipment[status!={$status}]") as $e) {
    //清空
    $scripts = [];

    //预约限制脚本
    if ($e->reserv_script) {
        $scripts[] = '预约限制脚本';
    }

    $charge_template = $e->charge_template;

    if ($e->accept_reserv) {
        if ($charge_template['reserv'] == 'custom_reserv') {
            $scripts[] = '预约计费脚本';
        }
    }

    if ($charge_template['record'] == 'custom_record') {
        $scripts[] = '使用计费脚本';
    }

    if ($e->accept_sample) {
        if ($charge_template['sample'] == 'custom_sample') {
            $scripts[] = '送样计费脚本';
        }
    }

    $id = $e->id;

    if (count($scripts))  {
        $data[$id] = [
            $id,
            $e->name,
            join(',', $scripts),
        ];
    }
}

//如果存在数据
//发送邮件
if (count($data)) {

    $csv = new CSV($done_file, 'w');

    $csv->write([
        '仪器编号',
        '仪器名称',
        'lua自定义脚本列表',
    ]);

    foreach($data as $d) {
        $csv->write($d);
    }

    $csv->close();

    $to = [
        'support@geneegroup.com',
        'rui.ma@geneegroup.com',
    ];

    $subject = strtr('%name, 仪器自定义脚本检测', [
        '%name'=> Lab::get('lab.name'),
    ]);

    $body = "Lua自定义脚本检测结果\n";
    $body .=  "链接访问地址:". Lab::get('system.base_url'). "\n\n";
    $body .= file_get_contents($done_file);

    $mail = new Email();
    $mail->to($to);
    $mail->subject($subject);
    $mail->body($body);
    $mail->send();
}

//重新touch不会覆盖
@touch($done_file);
