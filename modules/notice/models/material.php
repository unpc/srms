<?php

class Material_Model extends Presentable_Model {

    const TYPE_TEXT = 0;
    const TYPE_IMAGE = 1;
    const TYPE_MEDIA = 2;

    static $TYPES = [
        self::TYPE_TEXT => '文字',
        self::TYPE_IMAGE => '图片',
        self::TYPE_MEDIA => '视频',
    ];

    function source_path($file = '')
    {
        $path = LAB_PATH.PRIVATE_BASE.'notice/'.$this->name().'/'.(int)$this->id.'/source/';
        return "{$path}{$file}";
    }

    function & links($mode = NULL) {
        $links = new ArrayIterator;
        $me = L('ME');
    
        $links['edit'] = [
            'url' => URI::url('!notice/material/edit.'.$this->id),
            'text' => I18N::T('notice', '编辑'),
            'extra' => 'class="blue"',
        ];
        $links['delete'] = [
            'url' => URI::url('!notice/material/delete.'.$this->id),
            'tip' => I18N::T('notice','删除'),
            'text' => I18N::T('notice', '删除'),
            'extra' => 'class="blue" confirm="'.I18N::T('nrii','你确定要删除吗? 删除后不可恢复!').'"',
        ];
    
        return (array) $links;
    }

}
