<?php

class Stock_Use_Model extends Presentable_Model {

    public function & links($mode=NULL) {
        $links = new ArrayIterator; 
        $me = L('ME');

        if ($me->is_allowed_to('修改', $this)) {
            $links['edit'] = [
                'url'=> NULL,
                'tip'=> I18N::T('inventory', '修改'),
                'extra'=> 'class="blue" q-object="use_edit" q-event="click" q-static="'. H(['id'=> $this->id]). '" q-src="'. URI::url('!inventory/use'). '"'
            ];
        }

        if ($me->is_allowed_to('删除', $this)) {
            $links['delete'] = [
                'url'=> NULL,
                'tip'=> I18N::T('inventory', '删除'),
                'extra'=> 'class="blue" q-object="use_delete" q-event="click" q-static="'. H(['id'=> $this->id]). '" q-src="'. URI::url('!inventory/use'). '"'
            ];
        }

        return (array) $links;
    }
}
