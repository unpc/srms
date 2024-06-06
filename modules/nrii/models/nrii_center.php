<?php

class Nrii_Center_Model extends Presentable_Model {

    const ACCEPT_YES = 1;
    const ACCEPT_NO = 0;

    static $accept_status = [
        self::ACCEPT_NO => '否',
        self::ACCEPT_YES => '是'
    ];

    const TYPE_SPECIAL = 1;
    const TYPE_COMMON = 2;

    static $type_status = [
        self::TYPE_SPECIAL => '专用',
        self::TYPE_COMMON => '通用'
    ];

    const SHARE_INNER = 1;
    const SHARE_OUTER = 2;
    const SHARE_NOTHING = 3;

    static $share_status = [
        self::SHARE_INNER => '内部共享',
        self::SHARE_OUTER => '外部共享',
        self::SHARE_NOTHING => '不共享'
    ];

    const LEVEL_COUNTRY = 1;
    const LEVEL_PROVINCE = 2;
    const LEVEL_PREFECTURE = 3;
    const LEVEL_UNIT = 4;

    static $center_level = [
        self::LEVEL_COUNTRY => '国家级',
        self::LEVEL_PROVINCE => '省部级',
        self::LEVEL_PREFECTURE => '地市级',
        self::LEVEL_UNIT => '单位级'
    ];

    function & links($mode = NULL) {
        $links = new ArrayIterator;
        $me = L('ME');
    
        $links['edit'] = [
            'url' => URI::url('!nrii/center/edit.'.$this->id),
            'text' => I18N::T('nrii', '编辑'),
            'extra'=>'class="blue"',
        ];
        $links['delete'] = [
            'url'=> URI::url('!nrii/center/delete.'.$this->id),
            'tip'=>I18N::T('nrii','删除'),
            'extra'=>'class="blue" confirm="'.I18N::T('nrii','你确定要删除吗? 删除后不可恢复!').'"',
        ];
    
        return (array) $links;
    }

}
