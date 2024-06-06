#!/usr/bin/env php
<?php
    /*
     * file check_same_ref_no_equipments.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-04-11
     *
     * useage SITE_ID=cf LAB_ID=nankai php check_same_ref_no_equipments.php
     * brief 用于检测系统中具有重复ref_no的仪器
     */

require dirname(__FILE__). '/base.php';

$done_file = strtr('check_same_ref_no_equipments_%site_%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//存在done_file 不予执行
if (File::exists($done_file)) die;

$content = strtr("链接访问地址:%url\n", [
    '%url'=> Lab::get('system.base_url'),
]); 

$db = Database::factory();

$SQL = "SELECT `id` FROM `equipment` WHERE `ref_no` IN 
    (SELECT `ref_no` FROM `equipment` GROUP BY `ref_no` HAVING COUNT(`ref_no`) > 1)
    AND `ref_no` != ''
    ORDER BY `ref_no` ASC, `id` ASC;
    ";

$query = $db->query($SQL);

$data = [];

while(TRUE) {

    $row = $query->row('assoc');

    if (!count($row)) break; 

    $id = $row['id'];

    $e = O('equipment', $id);

    $incharges = [];

    foreach(Q("{$e}<incharge user") as $incharge) {

        $incharges[$incharge->id] = strtr('%name%phone', [
            '%name'=> $incharge->name,
            '%phone'=> $incharge->phone ? '('. $incharge->phone. ')' : NULL,
        ]);
    }

    $owners = Q("{$e}<incharge user");

    $data[$id] = [
        'id'=> $id,             //仪器id
        'name'=> $e->name,      //仪器名称
        'ref_no'=> $e->ref_no,  //仪器编号
        'address'=> strtr('%location1%location2', [ //地址
            '%location1'=> $e->location1,
            '%location2'=> $e->location2 ? '('. $e->location2. ')' : NULL,
        ]),
        'incharge'=> join(',', $incharges), //incharge
        'group'=> $e->group->id ? $e->group->name : '--', //组织机构
    ];
}

if (count($data)) {

    $csv_file = strtr('%site_%lab_same_equipments.csv', [
        '%site'=> $_SERVER['SITE_ID'],
        '%lab'=> $_SERVER['LAB_ID'],
    ]);

    $csv = new CSV($csv_file, 'w+');

    $csv->write([
        '仪器ID',
        '仪器名称',
        '仪器编号',
        '放置地址',
        '负责人(电话)',
        '组织机构',
    ]);

    foreach($data as $d) {
        $csv->write($d); 
    }

    $csv->close();
    $content .= '发现重复ref_no, 见附件';
}
else {
    $content .= '无仪器编号相同的仪器';
}

$mail = new Email();

$subject = strtr('%name, 仪器重复ref_no检测结果', [
    '%name' => Lab::get('lab.name')
]);

$mail->subject($subject);

$to = [
    'rui.ma@geneegroup.com',
    'jihao.zang@geneegroup.com',
];

$mail->to($to);

$mail->body(NULL, $content);

if (isset($csv_file))  $mail->attachment($csv_file);

$mail->send();

@touch($done_file);
