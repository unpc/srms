<?php

class EQ_Keep_Model extends Presentable_Model {

    function & links($mode = null) {
        $me = L('ME');

        if($me->is_allowed_to('修改维修记录', $this->equipment)) {
            $links['update'] = [
                'url' => NULL,
                'text' => I18N::T('eq_maintain', '编辑'),
                'extra' => 'class="blue" q-object="edit" q-event="click" 
                    q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!eq_maintain/keep') . '"',
            ];

            $links['delete'] = [
                'url' => NULL,
                'text' => I18N::T('eq_maintain', '删除'),
                'extra' => 'class="blue" q-object="delete" q-event="click" 
                    q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!eq_maintain/keep') . '"',
            ];
        }

        return (array)$links;
    }

}
