<?php

class Billing_Transaction_Model extends Presentable_Model {
	
	const STATUS_PENDING = 0; //默认
	const STATUS_CONFIRMED = 1; //确认
	
	protected $object_page = [
		'edit'=>'!billing/transaction/edit.%id[.%arguments]',
		'delete'=>'!billing/transaction/delete.%id[.%arguments]',

	];
	
	function & links($mode='index', $obj=NULL) {
		$links = new ArrayIterator;	
		$me = L('ME');
		switch ($mode) {
		case 'charge':
			Event::trigger('eq_charge.get.links', $this, $links, $obj);
			break;
		case 'index':
		default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['refill'] = [
					'url' => '',
					'text'  => I18N::T('billing', '修改'),
					'tip' => I18N::T('billing', '修改'),
					'extra'=> ' class="blue  view object:edit_transaction event:click static:'.H(['transaction_id'=>$this->id]).' src:'.URI::url('!billing/transaction').'"' ,
				];
			}
		}
        
        Event::trigger('billing_transaction.links', $this, $links, $mode);
		return (array) $links;
	}

	function is_locked() {

        if ($this->ctime < Lab::get('transaction_locked_deadline')) {
            return TRUE;
        }

        //非local的transaction, 均Lock
		if ($this->id && $this->source !='local') {
			return TRUE;
		}
	}

	function save($overwrite = FALSE) {
        $this->source = $this->source ?: 'local';
		return parent::save($overwrite);
	}
}
