#!/usr/bin/env php
<?php
    /*
     * file export_eqs.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-11-26
     *
     * useage SITE_ID=cf LAB_ID=test php export_eqs.php
     * brief 用于对某个SITE LAB下的Equipments进行导出成CSV文件
     */

require dirname(dirname(__FILE__)). '/base.php';

$eq_query = Q('equipment');

$start = 0;
$perpage = 10;

$file_name = strtolower("{$_SERVER['SITE_ID']}_{$_SERVER['LAB_ID']}"). '_eqs.csv';

$csv = new CSV($file_name, 'w');

$head = [
    '名称',
    'ID编号',
    '状态(0: 正常, 1: 故障, 2: 废弃)',
    '是否接受预约',
    '是否接受送样',
    '控制方式',
    '放置地址(楼宇)',
    '放置地址(房间号)',
    '联系人',
    '组织机构',
];

$csv->write($head);

$db = Database::factory();

echo "开始进行仪器导出\n";

while(count($eqs = $eq_query->limit($start, $perpage))) {

    foreach($eqs as $eq) {
        $data = [];
        $data[] = $eq->name;
        $data[] = $eq->id;
        $data[] = $eq->status;
        $data[] = (int) $eq->accept_reserv;
        $data[] = (int) $eq->accept_sample;
        $data[] = $eq->control_mode;

        $data[] = $eq->location;
        $data[] = $eq->location2;
        $data[] = join(',', Q("$eq<contact user")->to_assoc('id', 'name'));

        $data[] = $eq->group->name;

        $csv->write($data);

        echo '.';
    }

    $start += $perpage;
}

$csv->close();

echo "\n导出成功! 详见文件$file_name\n";
