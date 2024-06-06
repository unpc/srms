<?php
$config['equipment.eq_sample']['样品信息'] += [
   'name'=> [
        'adopted'=>TRUE,
        'title'=>'样品名称',
        'weight'=>21,
        'type'=>3,
    ],
   'type'=> [
        'adopted'=>TRUE,
        'title'=>'样品类别',
        'weight'=>22,
        'type'=>3,
    ],
   'code'=> [
        'adopted'=>TRUE,
        'title'=>'样品代号',
        'weight'=>23,
        'type'=>3,
    ],
];
$config['equipment.eq_sample'] += [
    '报告格式及方式' => [
        'format' => [
            'adopted'=>TRUE,
            'title'=>'报告格式',
            'weight'=>20,
            'type'=>1,
            'params'=>Sample_Approval_Model::$format,
            'default_value'=>1
        ],
        'mode' => [
            'adopted'=>TRUE,
            'title'=>'报告获取方式',
            'weight'=>30,
            'type'=>2,
            'params'=>Sample_Approval_Model::$mode,
            'default_value'=>null
        ],
    ]
];
