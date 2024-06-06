<?php

class Nrii_Record_Model extends Presentable_Model {
    const TYPE_RESERVE = 1; 
    const TYPE_TECH = 2; 
    const TYPE_SAMPLE = 3; 
    const TYPE_REMOTE = 4; 
    const TYPE_OTHER = 5;

    static $service_way = [
        self::TYPE_RESERVE => '占用共享',
        self::TYPE_TECH => '技术共享',
        self::TYPE_SAMPLE => '委托共享',
        self::TYPE_REMOTE => '远程共享',
        self::TYPE_OTHER => '其他'
    ];

    const Income_A = 1;
    const Income_B = 2;
    const Income_C = 3;
    const Income_D = 4;
    const Income_E = 5;
    const Income_F = 6;
    const Income_G = 7;
    const Income_H = 8;
    const Income_I = 9;
    const Income_J = 10;
    const Income_K = 11;
    const Income_L = 12;
    const Income_M = 13;
    const Income_N = 14;
    const Income_O = 15;
    const Income_P = 16;
    const Income_Q = 17;

    static $subject_income = [
        self::Income_A => '国家重大科技专项',
        self::Income_B => '国家自然科学基金',
        self::Income_C => '863计划',
        self::Income_D => '国家科技支撑（攻关）计划',
        self::Income_E => '火炬计划',
        self::Income_F => '星火计划',
        self::Income_G => '973计划',
        self::Income_H => '211工程',
        self::Income_I => '985工程',
        self::Income_J => '公益性行业科研专项',
        self::Income_K => '国家社会科学基金',
        self::Income_L => '国家科技基础性工作专项',
        self::Income_M => '科技基础条件平台专项',
        self::Income_N => '除上述计划外由中央政府部门下达',
        self::Income_O => '地方科技计划项目',
        self::Income_P => '其他',
        self::Income_Q => '无'
    ];

    const Comment_A = 1;
    const Comment_B = 2;
    const Comment_C = 3;
    const Comment_D = 4;
    const Comment_E = 5;

    static $comment = [
        self::Comment_A => '非常满意',
        self::Comment_B => '基本满意',
        self::Comment_C => '一般',
        self::Comment_D => '不满意',
        self::Comment_E => '极差'
    ];

    const Status_0 = 0; 
    const Status_1 = 1; 
    const Status_100 = 100; 
    const Status_101 = 101; 
    const Status_200 = 200; 
    const Status_201 = 201; 
    const Status_202 = 202; 
    const Status_203 = 203; 
    const Status_204 = 204; 
    const Status_301 = 301;

    static $nrii_status = [
        self::Status_0 => '数据待补充',
        self::Status_1 => '数据补充完成',
        self::Status_100 => '成功',
        self::Status_101 => '数据填写有误',
        self::Status_200 => '单位编码错误',
        self::Status_201 => '填报数据类型错误',
        self::Status_202 => '推送格式错误',
        self::Status_203 => '验证数据格式错误',
        self::Status_204 => '其他异常',
        self::Status_301 => '数据库异常'
    ]; 

    const SERVICE_TYPE_INNER = 120;
    const SERVICE_TYPE_OUTTER = 130;

    static $service_types = [
        self::SERVICE_TYPE_INNER => '内部用户',
        self::SERVICE_TYPE_OUTTER => '外部用户'
    ];

    const SERVICE_DIRECTION_RESEARCH = 135;
    const SERVICE_DIRECTION_DEVELOP = 136;
    const SERVICE_DIRECTION_EDUCATION = 137;
    const SERVICE_DIRECTION_OTHER = 138;

    static $service_directions = [
        self::SERVICE_DIRECTION_RESEARCH => '科学研究',
        self::SERVICE_DIRECTION_DEVELOP => '科技开发',
        self::SERVICE_DIRECTION_EDUCATION => '教学',
        self::SERVICE_DIRECTION_OTHER => '其他',
    ];

    const ADDRESS_TYPE_IN = 0;
    const ADDRESS_TYPE_OUT = 1;

    static $address_types = [
        self::ADDRESS_TYPE_IN => '单位内使用',
        self::ADDRESS_TYPE_OUT => '单位外使用'
    ];

    const SIGN_AGREEMENT_YES = 0;
    const SIGN_AGREEMENT_NO = 1;

    static $sign_agreements = [
        self::SIGN_AGREEMENT_YES => '是',
        self::SIGN_AGREEMENT_NO => '否'
    ];

    function & links($mode = NULL) {
        $links = new ArrayIterator; 
        $me = L('ME');
    
        $links['edit'] = [
            'url' => URI::url('!nrii/record/edit.'.$this->id),
            'text' => I18N::T('nrii', '编辑'),
            'extra'=>'class="blue"',
        ];
  
        return (array) $links;
    }
}