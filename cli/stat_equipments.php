#!/usr/bin/env php
<?php

require 'base.php';

$header = [
    '仪器名称',
    '仪器ID',
    '仪器编号',
    '是否控制',
    '控制方式',
    '是否需要预约',
    '是否需要送样',
    '计费方式',
];

$csv_content = [];

$csv_content[] = join(', ', $header);

$start = 0;
$count = Q('equipment')->total_count();


$db = Database::factory();

$start = 0;
$perpage = 10;

while($_data = $db->query('SELECT `id` FROM `equipment` WHERE `id` > %d ORDER BY `id` ASC LIMIT %d', $start, $perpage)->rows('assoc')) {

    foreach($_data as $id) {
        $e = O('equipment', $id);

        $data = [];
        $data[] = $e->name; //仪器名称
        $data[] = $e->id;
        $data[] = $e->ref_no ? : '--'; //仪器编号

        //control type 控制方式
        $ct = NULL;
        switch($e->control_mode) {
            case 'computer' :
                $ct = '电脑控制';
                break;
            case 'power' :
                $ct = '电源控制';
                break;
            default :
                $ct = NULL; 
                break;
        }

        $data[] = $ct ? '是' : '否'; //是否控制

        $data[] = $ct ? : '--';

        //是否需要预约
        $data[] = $e->accept_reserv ? '是' : '否';

        //是否需要送样
        $data[] = $e->accept_sample ? '是' : '否';

        //计费方式
        $charge_template = $e->charge_template;
        $c = NULL;

        if ( !count($charge_template) ) {
            $data[] = '均免费使用';
        }
        else {

            if ($e->accept_reserv) {

                if ($charge_template['record'] == 'custom_record' && $charge_template['reserv'] == 'custom_reserv') {
                    $c .= '预约 / 使用计费设置 » 高级计费 » 高级自定义 ';
                }
                elseif ($charge_template['record']) {
                    $c .= '预约 / 使用计费设置 » 按实际使用情况计费 » ';

                    switch($charge_template['record']) {
                        case 'record_time' :
                            $c .= ' 按使用时间 ';
                            break;
                        case 'record_times' :
                            $c .= '按使用次数 ';
                            break;
                        case 'record_samples' :
                            $c .= '按样品数 ';
                            break;
                        case 'custom_record' :
                            if (!$charge_template['reserv']) {
                                $c .= '自定义 ';
                            }
                            break;
                    }
                }
                elseif ($charge_template['reserv']) {

                    $c = '预约 / 使用计费设置 » ';

                    switch($charge_template['reserv']) {
                        case 'time_reserv_record' :
                            $c .=  '智能计费 » 综合预约 / 使用时间智能计费 ';
                        break;
                        case 'only_reserv_time' :
                            $c .= '按预约情况计费 » 按预约时间 ';
                        break;
                        case 'custom_reserv' :
                            if (!$charge_template['record']) {
                                $c .= '按预约情况计费 » 自定义 ';
                            }
                        break;
                        default :
                            $c .= '免费使用 » 免费使用 ';
                        break;
                    }
                }
                else {
                    $c .= '预约 / 使用计费设置 » 免费使用 » 免费使用';
                }
            }
            else {

                $c = '使用计费设置 » ';

                switch($charge_template['record']) {
                    case 'record_time' :
                        $c .= '按实际使用情况计费 » 按使用时间 ';
                    break;
                    case 'record_times' :
                        $c .= '按实际使用情况计费 » 按使用次数 ';
                    break;
                    case 'record_samples' :
                        $c .= '按实际使用情况计费 » 按样品数 ';
                    break;
                    case 'custom_record' :
                        $c .= '按实际使用情况计费 » 自定义 ';
                    break;
                    default :
                        $c .= '免费使用 » 免费使用';
                    break;
                }
            }

            $c .= ' | 送样计费设置 » ';
            switch($charge_template['sample']) {
                case 'sample_count' :
                    $c .= '按样品数 ';
                break;
                case 'custom_sample' :
                    $c .= '自定义';
                break;
                default :
                    $c .= '免费检测';
            }

            $data[] = $c;
        }

        $csv_content[] = join(', ', $data);
    }
    $start += $perpage;
}

$csv_content  = join("\n", $csv_content);

$csv_file = Config::get('system.tmp_dir'). '/stat_equipments.csv';

@touch($csv_file);

file_put_contents($csv_file, $csv_content);

$mail = new Email();

$receiver = ['support@geneegroup.com'];

$mail->to($receiver);

$mail->subject('仪器设置情况统计表');

$lab_title = Config::get('page.title_default');
$base_url = Config::get('system.base_url');
$lab_name = Config::get('lab.name');


$body = "仪器设置统计({$lab_title})\t\t站点名称: {$lab_name}\t\t链接地址:{$base_url}\t\t统计结果见附件!\n\n";

$mail->body($body);
$mail->attachment($csv_file);
$mail->send();

File::delete($csv_file);
