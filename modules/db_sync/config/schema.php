<?php

/* $config['subsite'] = [
    'fields'  => [
        'ref_no'      => ['type' => 'varchar(255)', 'null' => true], // 站点名称 LAB_ID
        'name'        => ['type' => 'varchar(255)', 'null' => false, 'default' => ''], // 站点名称
        'links'       => ['type' => 'varchar(255)', 'null' => false, 'default' => ''], // 访问地址
        'ctime'       => ['type' => 'int', 'null' => false, 'default' => 0], // 关联时间
        'status'      => ['type' => 'int', 'null' => false, 'default' => 0], // 关联状态 [0 => 未关联，1 => 已关联]
        'description' => ['type' => 'varchar(255)', 'null' => true], // 描述
    ],
    'indexes' => [
        'ref_no' => ['type' => 'unique', 'fields' => ['ref_no']],
        'name'   => ['fields' => ['name']],
        'ctime'  => ['fields' => ['ctime']],
    ],
]; */

/* $config['equipment'] = [
    'fields'  => [
        'site' => ['type' => 'varchar(32)', 'null' => true, 'default' => ''], // 站点名称 LAB_ID
    ],
    'indexes' => [
        'site' => ['fields' => ['site']],
    ],
]; */

// 标明billing_transaction实际属于哪个站点
// $config['billing_transaction']['fields']['site']  = ['type' => 'varchar(32)', 'null' => false];
// $config['billing_transaction']['indexes']['site'] = ['fields' => ['site']];

// $config['vidcam']['fields']['site']  = ['type' => 'varchar(32)', 'null' => false];
// $config['vidcam']['indexes']['site'] = ['fields' => ['site']];

// $config['door']['fields']['site']  = ['type' => 'varchar(32)', 'null' => false];
// $config['door']['indexes']['site'] = ['fields' => ['site']];

// 'gismon' 标明楼宇属于那个站点
// $config['gis_building']['fields']['site'] = ['type' => 'varchar(32)', 'null' => false];
// $config['gis_building']['indexes']['site'] = ['fields' => ['site']];

// $config['env_node']['fields']['site'] = ['type' => 'varchar(32)', 'null' => false];
// $config['env_node']['indexes']['site'] = ['fields' => ['site']];

