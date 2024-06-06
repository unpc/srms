<?php

class EQ_Maintain_Model extends Presentable_Model {
    const SERVICE = 1;
    const MAINTAIN = 2;
    const KEEP = 3;
    const OTHER = 4;
    
    static $type = [
        self::SERVICE => '维修',
        self::MAINTAIN => '维保',
        self::KEEP => '保养',
        self::OTHER => '其他',
    ];

    function & links($mode = null) {
        $me = L('ME');

        $links = new ArrayIterator;
        if($me->is_allowed_to('修改维修记录', $this->equipment)) {
            $links['update'] = [
                'url' => NULL,
                'text' => I18N::T('eq_maintain', '编辑'),
                'extra' => 'class="blue" q-object="edit" q-event="click" 
                    q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!eq_maintain/index') . '"',
            ];

            $links['delete'] = [
                'url' => NULL,
                'text' => I18N::T('eq_maintain', '删除'),
                'extra' => 'class="blue" q-object="delete" q-event="click" 
                    q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!eq_maintain/index') . '"',
            ];
        }
        Event::trigger('eq_maintain.links', $this, $links, $mode);
        return (array) $links;
    }
}
