<?php

class Broadcast_Model extends Presentable_Model {

    const TYPE_TEXT = 0;
    const TYPE_IMAGE = 1;
    const TYPE_MEDIA = 2;

    static $TYPES = [
        self::TYPE_TEXT => '文字',
        self::TYPE_IMAGE => '图片',
        self::TYPE_MEDIA => '视频',
    ];

    const STATUS_NOT_BEGIN = 0;
    const STATUS_USING = 1;
    const STATUS_EXPIRE = 2;

    static $STATUS = [
        self::STATUS_NOT_BEGIN => '未开始',
        self::STATUS_USING => '使用中',
        self::STATUS_EXPIRE => '已过期',
    ];

    static $STATU_STATES = [
        self::STATUS_NOT_BEGIN => 'status_tag_warning',
        self::STATUS_USING => 'status_tag_info',
        self::STATUS_EXPIRE => 'status_tag_disable',
    ];

    function & links($mode = NULL) {
        $links = new ArrayIterator;
        $me = L('ME');
    
        // $links['edit'] = [
        //     'url' => URI::url('!notice/broadcast/edit.'.$this->id),
        //     'text' => I18N::T('notice', '编辑'),
        //     'extra' => 'class="blue"',
        // ];
        $links['delete'] = [
            'url' => URI::url('!notice/broadcast/delete.'.$this->id),
            'tip' => I18N::T('notice','删除'),
            'text' => I18N::T('notice', '删除'),
            'extra' => 'class="blue" confirm="'.I18N::T('nrii','你确定要删除吗? 删除后不可恢复!').'"',
        ];
    
        return (array) $links;
    }

}
