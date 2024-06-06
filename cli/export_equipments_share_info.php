<?php
// 给审计署用的
require 'base.php';

$args = $argv;
$start = $args[1];
$end = $args[2];

$s = 0;
$p = 100;
$charges = Q("eq_charge[ctime={$start}~{$end}][amount>0]:sort(ctime):limit({$s},{$p})");

$output = new CSV('eq_charge.csv', 'w');
$output->write(
    [
        '学校识别码',
        '学校（机构）名称',
        '设备仪器编码',
        '设备仪器名称',
        '设备仪器单价',
        '设备仪器所属部门',
        '设备仪器地址',
        '使用单位',
        '使用人员',
        '是否校内',
        '使用开始时间',
        '使用结束时间',
        '使用时长',
        '使用费用',
        '设备使用用途',
        '备注'
    ]
);
while ($charges->count() > 0) {
    foreach ($charges as $charge) {
        $source = $charge->source;
        $output->write([
            $args[3],
            Config::get('page.title_default'),
            $charge->equipment->ref_no,
            $charge->equipment->name,
            $charge->equipment->price,
            $charge->equipment->group->name,
            $charge->equipment->location . $charge->equipment->location2,
            $charge->user->group->name,
            $charge->user->name,
            strstr($charge->user->group->name, '校外') ? '校外人员' : '校内人员',
            $source->name() == 'eq_sample' ? date('Ymd', $charge->ctime) : date('Ymd', $source->dtstart),
            $source->name() == 'eq_sample' ? date('Ymd', $charge->ctime) : (
                $source->dtend ? date('Ymd', $source->dtend) : '使用中'
            ),
            $source->dtend ? round(($source->dtend - $source->dtstart) / 3600, 2) : 0,
            $charge->amount,
            use_type($source),
        ]);
        echo date('Y-m-d', $charge->ctime) . "\n";
    }
    $s += $p;
    $charges = Q("eq_charge[ctime={$start}~{$end}][amount>0]:sort(ctime):limit({$s},{$p})");
}

function use_type($source) {
    switch ($source->name()) {
        case 'eq_sample':
            return '送样';
        case 'eq_reserv':
            return '预约';
        case 'eq_record':
            return $record->use_type ? EQ_Record_Model::$use_type[$record->use_type] : '使用';
    }
}

$output->close();
