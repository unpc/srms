<?php

class EQ_Voucher_Model extends Presentable_Model {

    protected $object_page = [
        'view'=>'!eq_approval/voucher/index.%id[.%arguments]',
        //'edit'=>'!equipments/equipment/edit.%id[.%arguments]',
        //'delete'=>'!equipments/equipment/delete.%id[.%arguments]',
    ];

	const RESERV = 0;
    const SAMPLE = 1;

    const PENDDING = 0;
    const APPROVED = 2;
    const REJECTED = 1;

    const USED = 1;
    const UN_USED = 0;
    

    static $TYPES = [
        self::RESERV => '预约',
        self::SAMPLE => '送样'
    ];

    static $TYPES_STYLE = [
        self::RESERV => 'reserv',
        self::SAMPLE => 'sample'
    ];

    static $STATUS = [
    	self::PENDDING => '申请中',
    	self::REJECTED => '已拒绝',
    	self::APPROVED => '已通过'
    ];

    static $STATUS_STYLE = [
        self::APPROVED => 'approved',
        self::PENDDING => 'pendding',
        self::REJECTED => 'rejected',
    ];

    static $USE_STATUS = [
    	self::UN_USED => '未使用',
    	self::USED => '已使用'
    ];

    static $USE_STATUS_STYLE = [
        self::UN_USED => 'un_used',
        self::USED => 'used'
    ];

    function & links($mode= 'index', $ajax_id = '') {
        $links = new ArrayIterator;
        $me = L('ME');     
        
        switch($mode){
            case 'lab_index':
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'url' => '#',
                        'text' => I18N::T('eq_approval', '隐藏'),
                        'extra'=>' class="blue" q-event="click" q-object="voucher_hide" q-static="id='.$this->id.'" q-src="'.$this->url().'"',
                    ];
                }
                break;
            case 'index':
            default:
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => '#',
                        'text' => I18N::T('eq_approval', '修改'),
                        'extra'=>' class="blue" q-event="click" q-object="voucher_edit" q-static="id='.$this->id.'" q-src="'.$this->url().'"',
                    ];  
                }
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'url' => '#',
                        'text' => I18N::T('eq_approval', '删除'),
                        'extra'=>' class="blue" q-event="click" q-object="voucher_delete" q-static="id='.$this->id.'" q-src="'.$this->url().'"',
                    ];
                }
                break;
        }

        return (array) $links;
    }

    function approved() {
        $this->status = self::APPROVED;
        $this->touch()->save();
    }

    function rejected() {
        $this->status = self::REJECTED;
        $this->touch()->save();
    }

    function used() {
        $this->use_status = self::USED;
        $this->save();
    }

    function unused() {
        $this->use_status = self::UN_USED;
        $this->save();
    }

    function hide() {
        $this->hide = 1;
        $this->save();
    }
}	
