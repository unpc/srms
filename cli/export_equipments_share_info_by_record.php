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


//使用记录
$s = 0;
$p = 100;
$records = Q("eq_record[dtstart={$start}~{$end}]:sort(dtstart):limit({$s},{$p})");
while ($records->count() > 0) {
    foreach ($records as $record) {
        $charge = O('eq_charge', ['source' => $record]);
        if ($charge->id) continue;
        $output->write([
            $args[3],
            Config::get('page.title_default'),
            $record->equipment->ref_no,
            $record->equipment->name,
            $record->equipment->price,
            $record->equipment->group->name,
            $record->equipment->location . $record->equipment->location2,
            $record->user->group->name,
            $record->user->name,
            strstr($record->user->group->name, '校外') ? '校外人员' : '校内人员',
            date('Ymd', $record->dtstart),
            $record->dtend ? date('Ymd', $record->dtend) : '使用中',
            $record->dtend ? round(($record->dtend - $record->dtstart) / 3600, 2) : 0,
            0,
            use_type($record),
        ]);
        echo date('Y-m-d', $record->dtstart) . "\n";
    }
    $s += $p;
    $records = Q("eq_record[dtstart={$start}~{$end}]:sort(dtstart):limit({$s},{$p})");
}

function use_type($source)
{
    switch ($source->name()) {
        case 'eq_sample':
            return '送样';
        case 'eq_reserv':
            return '预约';
        case 'eq_record':
            return $source->use_type ? EQ_Record_Model::$use_type[$source->use_type] : '使用';
    }
}

$output->close();
