<?php
/*
   导出仪器信息
   (xiaopei.li@2011.09.10)
 */
require 'base.php';

$equipments = Q('equipment');

$manucountrycode = [
'A004' => '中国',
    'A356' => '印度',
    'A392' => '日本',
    'A704' => '越南',
    'A702' => '新加坡',
    'B140' => '中非',
    'B710' => '南非',
    'C040' => '奥地利',
    'C208' => '丹麦',
    'C246' => '芬兰',
    'C250' => '法国',
    'C276' => '德国',
    'C380' => '意大利',
    'C528' => '荷兰',
    'C643' => '俄罗斯联邦',
    'C752' => '瑞典',
    'C756' => '瑞士',
    'C826' => '英国',
    'D076' => '巴西',
    'D124' => '加拿大',
    'D484' => '墨西哥',
    'D840' => '美国',
    'E036' => '澳大利亚',
    'E554' => '新西兰'
    ];
    $output = new CSV('eq_'.LAB_ID.'.csv', 'w');
    $output->write(
            [
            '仪器名称',
            '仪器编号',
            '仪器CF_ID',
            '仪器价格',
            '仪器分类',
            '控制方式',
            '存放地点',
            '房间号',
            '联系人',
            '联系方式',
            '组织机构',
            '规格',
            '型号',
            '生产厂家',
            '制造国家',
            '购置日期',
            '出厂日期',
            '分类号',
            '主要规格及技术指标',
            '主要功能及特色',
            '主要附件及配置',
            '参考收费标准',
            '开放机时安排',
            '固定资产分类编码',
            '仪器认证情况',
            '仪器隶属机组',
            '邮箱',
            '仪器别名',
            '英文名称',
            '共享分类编码',
            '测试研究领域代码',
            '生产厂商资质',
            '产地国别',
            '外币币种',
            '外币原值',
            '知名用户',
            '备注'
            ]
            );

            foreach ($equipments as $e) {
                $contacts = Q("{$e} user.contact")->to_assoc('id', 'name');
                $root = Tag_Model::root('equipment');
                $tags = join('/', Q("$e tag_equipment[root=$root]")->to_assoc('id', 'name'));

                $output->write(
                        [
                        $e->name,
                        $e->ref_no,
                        $e->id,
                        $e->price,
                        $tags,
                        $e->control_mode,
                        $e->location,
                        $e->location2,
                        join('/', $contacts),
                        $e->phone,
                        $e->group->name,
                        $e->specification,
                        $e->model_no,
                        $e->manufacturer,
                        $e->manu_at,
                        $e->purchased_date,
                        $e->manu_date,
                        $e->cat_no,
                        $e->tech_specs,
                        $e->features,
                        $e->configs,
                        $e->ReferChargeRule,
                        $e->OpenCalendar,
                        $e->AssetsCode,
                        $e->Certification,
                        $e->struct->name,
                        $e->email,
                        $e->Alias,
                        $e->ENGName,
                        $e->ClassificationCode,
                        $e->ApplicationCode,
                        $e->ManuCertification,
                        $manucountrycode[$e->ManuCountryCode],
                        $e->PriceUnit,
                        $e->PriceOther,
                        $e->ServiceUsers,
                        $e->OtherInfo
                            ]
                            );
            }

$output->close();

