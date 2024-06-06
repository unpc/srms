<?php


class Billing_Account_Model extends Presentable_Model {
    //调账
    const OPERATION_TRANSFER = 0;
    //充值
    const OPERATION_CREDIT = 1;
    //扣费
    const OPERATION_DEDUCTION = 2;

	protected $object_page = [
		'edit'=>'!billing/account/edit.%id[.%arguments]',
		'delete'=>'!billing/account/delete.%id',
		'credit'=> '!billing/account/credit.%id[.%arguments]',

	];
	
	function & links($mode='index') {
		$links = new ArrayIterator;	
		$me = L('ME');
		
		switch ($mode) {
		case 'index':
		default:

			//如果没有远程账号，才可以充值
			if ($this->source == 'local' && !$this->voucher) {
				$sources = Config::get('billing.sources');
				if ($me->is_allowed_to('充值', $this)) {
					$links['refill'] = [
						'url' => '#',
						'tip' => I18N::T('billing', '充值'),
						'text' => '<span class="after_icon_span">'.I18N::T('billing', '充值').'</span>',
						'extra'=> 'class="blue view object:account_credit event:click static:'.H(['account_id'=>$this->id]).' src:'.URI::url('!billing/account').'"' ,
					];
				}
				elseif (count($sources) && $this->lab->owner->id == $me->id)  {
					$links['refill'] = [
						'url' => '#',
						'tip' => I18N::T('billing', '充值'),
						'text' => '<span class="after_icon_span">'.I18N::T('billing', '充值').'</span>',
						'extra'=> 'class="blue view" q-object="refill_notif" q-event="click" q-static="'.H(['lab_id'=>$this->lab->id]).'" q-src="'.URI::url('!billing/account').'"' ,
					];
				}
            }
			//有远程账号，且用户是当前课题组的pi，可以看到充值按钮，跳转到远程
			elseif ($this->source != 'local' && $this->voucher && $this->lab->owner->id == $me->id) {
				$billing_link = Config::get('billing.sources')[$this->source]['http_url'];
				if ($billing_link) {
					$links['refill'] = [
						'url' => $billing_link,
						'tip' => I18N::T('billing', '充值'),
						'text' => '<span class="after_icon_span">'.I18N::T('billing', '充值').'</span>',
						'extra'=> 'class="blue view" target="_blank"',
					];
				}
			}

			if ($me->is_allowed_to('扣费', $this)) {
				$links['deduction'] = [
					'url' => '#',
					'tip' => I18N::T('billing', '扣费'),
					'text' => '<span class="after_icon_span">'.I18N::T('billing', '扣费').'</span>',
					'extra' => 'class="blue view object:account_deduction event:click static:'. H(['account_id'=>$this->id]). ' src:'. URI::url('!billing/account'). '"',
				];
			}

            if ($me->is_allowed_to('充值', $this)) {
                $links['credit_line'] = [
                    'url' => '#',
                    'tip' => I18N::T('billing', '信用额度'),
                    'text' => I18N::T('billing', '信用额度'),
                    'extra'=> 'class="blue view object:credit_line event:click static:'.H(['account_id'=>$this->id]).' src:'.URI::url('!billing/account').'"' ,
                ];
            }
			
			/*
				NO. BUG#206 (Cheng.Liu@2010.11.30)
				新增加删除财务帐号权限
			*/
			if ($me->is_allowed_to('删除', $this)) {
				$links['delete'] = [
					'url' => '#',
					'tip' => I18N::T('billing', '删除'),
					'text'  => I18N::T('billing', '删除'),
					'extra'=> 'class="blue red" q-object="delete_account" q-event="click" q-static="'.H(['account_id'=>$this->id]).'" q-src="'.URI::url('!billing/account').'"',
				];
			}
		}
        
        Event::trigger('billing_account.links', $this, $links, $mode);

		return (array) $links;
	}

    function update_balance() {
        return Billing_Account::update_balance($this);
    }
}
