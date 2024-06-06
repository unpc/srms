<?php
/*
NO.TASK282(guoping.zhang@2010.12.02）
仪器送样预约开发
*/
class Sample_Approval_Model extends EQ_Sample_Model {

    const FORMAT_TEXT = 1;//文本
    const FORMAT_PDF = 2;//电子版

    const MODE_GET = 1;//文本
    const MODE_SEND = 2;//送达
    const MODE_CD = 3;//光盘
    const MODE_EMAIL = 4;//电子邮件
    const MODE_COPY = 5;//电子拷贝

    //TODO 修改值防止状态冲突
    const STATUS_OFFICE     = 6; //
    const STATUS_PLATFORM   = 7; //
    const STATUS_ACCESS     = 8; //

    static $status = [
        1=>'申请中',
        2=>'已批准',
        3=>'已拒绝', 
        4=>'因故取消',
        5=>'已测试',
        6=>'待科室审核',
        7=>'待平台审核',
        8=>'已通过',
    ];

    static $charge_status = [
        self::STATUS_OFFICE,
        self::STATUS_PLATFORM,
        self::STATUS_ACCESS,
    ];
    
    static $status_background_color = [
        1=>'f9c365', 
        2=>'7498e0', 
        3=>'E46558', 
        4=>'888', 
        5=>'6c9',
        6=>'CC6699',
        7=>'9900FF',
        8=>'00CCCC',
    ];
     
    static $format = [
        1=>'文本', 
        2=>'电子版'
    ];

    static $mode = [
        1=>'自取', 
        2=>'送达', 
        3=>'光盘', 
        4=>'电子邮件', 
        5=>'电子拷贝'
    ];
}
