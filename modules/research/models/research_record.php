<?php

class Research_Record_Model extends Presentable_Model
{
    const CHARGE_STATUS_NONE = 0;
    const CHARGE_STATUS_DONE = 1;

    static $charge_status = [
        self::CHARGE_STATUS_NONE => '未收费',
        self::CHARGE_STATUS_DONE => '已收费',
    ];

    function & links($mode = 'index') {
        $me = L('ME');
        $links = new ArrayIterator;
        switch($mode){
            case 'index':
            default:
                if ($me->is_allowed_to('编辑', $this)) {
                    $links['edit'] = [
                        'url' => '#',
                        'text' => I18N::T('equipments', '编辑'),
                        'extra'=>' class="blue" q-event="click" q-object="edit_record" q-static="record_id='.$this->id.'" q-src="'.URI::url('!research/research').'"',
                    ];
                }
            break;
        }

        return (array) $links;
    }

    function auto_amount() {
        return round($this->amount * $this->discount / 100, 2);
    }

    function save($overwrite=FALSE) {
        $this->auto_amount = $this->auto_amount();
        return parent::save();
    }
}
