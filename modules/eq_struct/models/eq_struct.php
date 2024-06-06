<?php

class EQ_Struct_Model extends Presentable_Model
{
    function & links($mode = NULL) {
        $links = new ArrayIterator;
        $me = L('ME');

        if ($me->access('管理所有内容')) {
            $links['edit'] = [
                'text' => I18N::T('eq_struct', '编辑'),
                'extra' => 'q-object="edit" q-event="click" q-src="' . URI::url('!eq_struct/index') .
                    '" q-static="' . H(['id'=>$this->id]) .
                    '" class="blue"'
            ];
            $links['delete'] = [
                'text' => I18N::T('eq_struct','删除'),
                'extra' => 'q-object="delete" q-event="click" q-src="' . URI::url('!eq_struct/index') .
                    '" q-static="' . H(['id'=>$this->id]) .
                    '" class="blue"',
            ];
        }

        return (array) $links;
    }
}
