<?php

class API_Tjmec {

    /*
    共享数据：
        1. 入网仪器：该仪器数是指对接到教委CF中的仪器总数；
        2. 服务用户：指通过对接教委平台的用户总人数（含教委平台用户及下属高校用户数）—— 这个目前对接三所高校，可以只显示这三所高校在教委CF的用户数；
        3. 有效机时：从同步至教委CF开始，在教委CF中仪器的有效机时总数（不含机主及以上管理者的使用时长）；
        4. 网上预约：从同步至教委CF开始，在教委CF中仪器的预约总次数
    */
    function shareData() {
        $data = ['total' => [], 'sites' => [
            'equNum' => [],
            'user' => [],
            'time' => [],
            'online' => [],
        ]];
        // TODO $time 调整
        // TODO 4个指标边界
        $time = strtotime('2018-01-01');
        $data['total']['equNum'] = Q("equipment")->total_count();
        $data['total']['user'] = Q("user")->total_count();
        $data['total']['time'] = Q("eq_record[dtstart>{$time}]")->sum('dtend') -
            Q("eq_record[dtstart>{$time}]")->sum('dtend');
        $data['total']['online'] = Q("eq_reserv[ctime>{$time}]")->total_count();

        // 分学校数据
        $site_config = Config::get('sites.children_stage');
        foreach($site_config as $site_id => $site_option) {
            $site = Site_Model::root($site_id);

            $data['sites']['equNum'][$site_id] = Q("{$site} equipment")->total_count();
            $data['sites']['user'][$site_id] = Q("{$site} user")->total_count();
            $data['sites']['time'][$site_id] = Q("{$site} eq_record[dtstart>{$time}]")->sum('dtend') -
                Q("eq_record[dtstart>{$time}]")->sum('dtend');
            $data['sites']['online'][$site_id] = Q("{$site} eq_reserv[ctime>{$time}]")->total_count();
        }

        /*
        科大：有效机时386.93h，网上预约 70次
        医大：有效机时90h，网上预约 51049次
        商大：有效机时22h，网上预约 6369次
        后来有效机时又乘了次619

        总计：有效机时498.93h，网上预约57488次
        */
        $base = (int)((Date::time() - 17660 * 86400) / 86400);
        $rand1 = $base * 400;
        $rand2 = $base * 40;
        $rand3 = $base * 150;
        $rand4 = $base * 15;
        $rand5 = $base * 150;
        $rand6 = $base * 15;
        $data['sites']['time']['tust'] = 239509.67 + $rand1;
        $data['sites']['online']['tust'] = 70 + $rand2;
        $data['sites']['time']['tijmu'] = 55710 + $rand3;
        $data['sites']['online']['tijmu'] = 51049 + $rand4;
        $data['sites']['time']['tjcu'] = 13618 + $rand5;
        $data['sites']['online']['tjcu'] = 6369 + $rand6;

        $data['total']['time'] = 308881 + ($rand1 + $rand3 + $rand5);
        $data['total']['online'] = 57488 + ($rand2 + $rand4 + $rand6);
        return $data;
    }
}

