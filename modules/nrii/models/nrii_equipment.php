<?php

class Nrii_Equipment_Model extends Presentable_Model {

    const AFFILIATE_CENTER = 1; 
    const AFFILIATE_DEVICE = 2; 
    const AFFILIATE_UNIT = 3; 
    const AFFILIATE_NONE = 4;
    const AFFILIATE_CENTER_LAB = 6;
    const AFFILIATE_TECH_RESEARCH_CENTER = 7;
    const AFFILIATE_RESOURCE_LIBRARY = 8;

    static $affiliate_type = [
        self::AFFILIATE_NONE => '不附属',
        self::AFFILIATE_CENTER => '附属于科学仪器中心',
        self::AFFILIATE_DEVICE => '附属于重大科研基础设施',
        self::AFFILIATE_CENTER_LAB => '附属于国家重点实验室',
        self::AFFILIATE_TECH_RESEARCH_CENTER => '附属于国家工程技术研究中心',
        self::AFFILIATE_RESOURCE_LIBRARY => '附属于生物种质资源库馆',
    ];

    static $affiliate_resource_type = [self::AFFILIATE_CENTER_LAB, self::AFFILIATE_TECH_RESEARCH_CENTER, self::AFFILIATE_RESOURCE_LIBRARY];

    const SOURCE_PURCHASE = 1;
    const SOURCE_DEVELOP = 2;
    const SOURCE_PRESENT = 3;
    const SOURCE_OTHER = 4;

    static $eq_source = [
        self::SOURCE_PURCHASE => '购置',
        self::SOURCE_DEVELOP => '研制',
        self::SOURCE_PRESENT => '赠送',
        self::SOURCE_OTHER => '其他',
    ];

    const TYPE_SPECIAL = 1; 
    const TYPE_COMMON = 2; 

    static $type_status = [
        self::TYPE_SPECIAL => '专用',
        self::TYPE_COMMON => '通用',
    ];
    
    const STATUS_NORMAL = 1;
    const STATUS_AWAIT = 2;
    const STATUS_REMOTE_SERVICE = 3;
    const STATUS_OCCA_BREAK = 4;
    const STATUS_ALWAYS_BREAK = 5;
    const STATUS_WAIT_FIX = 6;
    const STATUS_WAIT_OFF = 7;

    static $status = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_OCCA_BREAK => '偶有故障',
        self::STATUS_ALWAYS_BREAK => '故障频繁',
        self::STATUS_WAIT_FIX => '待修',
        self::STATUS_WAIT_OFF => '待报废'
    ];

    const SHARE_INNER = 1;
    const SHARE_OUTER = 2;
    const SHARE_NOTHING = 3;

    static $share_status = [
        self::SHARE_INNER => '内部共享',
        self::SHARE_OUTER => '外部共享',
        self::SHARE_NOTHING => '不共享'
    ];

    const FUND_A = 1;
    const FUND_B = 2;
    const FUND_C = 3;
    const FUND_D = 4;

    static $funds = [
        self::FUND_A => '中央财政资金',
        self::FUND_B => '地方财政资金',
        self::FUND_C => '单位自有资金',
        self::FUND_D => '其他资金'
    ];

    const NRII_STATUS_SUCCEED = 100;
    const NRII_STATUS_UNUP = 0;
    const NRII_STATUS_FAIL = 203;

    static $nrii_status = [
        self::NRII_STATUS_UNUP => '未上报',
        self::NRII_STATUS_SUCCEED => '已上报',
        self::NRII_STATUS_FAIL => '上报失败',
    ];

    const SHEN_STATUS_FINISH = 1;
    const SHEN_STATUS_WAIT = 0;
    static $shen_status = [
        self::SHEN_STATUS_FINISH => '已审核',
        self::SHEN_STATUS_WAIT => '待审核',
    ];

    function & links($mode = NULL) {
        $links = new ArrayIterator;
        $me = L('ME');

        if ($me->is_allowed_to('编辑', $this)) {
            $links['edit'] = [
                'url' => URI::url('!nrii/equipment/edit.'.$this->id),
                'text' => I18N::T('nrii', '编辑'),
                'extra'=>'class="blue"',
            ];
            $links['delete'] = [
                'url'=> URI::url('!nrii/equipment/delete.'.$this->id),
                'tip'=>I18N::T('nrii','删除'),
                'extra'=>'class="blue" confirm="'.I18N::T('nrii','你确定要删除吗? 删除后不可恢复!').'"',
            ];
        }

        if ($me->is_allowed_to('上传至科技部', $this)) {
            $links['sync'] = [
                'text' => I18N::T('nrii', '上传'),
                'extra' => 'class="blue" ',
                'url' => URI::url('!nrii/equipment/sync.' . $this->id)
            ];
        }

        if ($me->is_allowed_to('审核', $this)) {
            $links['pass'] = [
                'url'=> URI::url('!nrii/equipment/pass.'.$this->id),
                'text'=>I18N::T('nrii','通过'),
                'extra'=>'class="blue" confirm="'.I18N::T('nrii','你确定要通过审核吗? ').'"',
            ];
        }

        return (array) $links;
    }
}