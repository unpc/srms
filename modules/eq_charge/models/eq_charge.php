<?php

class EQ_Charge_Model extends Presentable_Model {

	protected $object_page = [
		'view'=>'!eq_charge/charge/index.%id[.%arguments]',
	];

	function save($overwrite = FALSE) {
		if (!Module::is_installed('billing')) {
			return parent::save($overwrite);
		}
		$this->amount = $this->amount > 0 ? $this->amount : 0;
		if (!$this->source->id) return false;

		/*
			因为20191018案例的发生，察觉此处的transaction的判断存在缓冲行为，不管是进程内变量缓冲还是cache缓冲, 会导致数据错误获取
			故为了确保底层缓冲机制不影响处理, 此处更改获取transaction的机制为DB查询
			- cheng.liu@geneegroup.com
		*/
		$db = Database::factory();
		$trans = $db->query(sprintf("SELECT transaction_id FROM eq_charge WHERE id = %d", (int)$this->id));
		$transaction_id = $trans ? (int)$trans->value() : 0;
		if (!$transaction_id) {
			$new = true;
		}

		$transaction = O('billing_transaction', $transaction_id);

		// 如果charge的计费为0则不产生新的transaction
		// 对于已经有transaction，费用被修改后变为0，则继续处理
		if($transaction->id || $this->amount || $this->auto_amount){
			$outcome = $transaction->outcome;
			$transaction->outcome = $this->amount;

			$user = $this->user;
			if ($GLOBALS['preload']['people.multi_lab']) {
				$lab = $this->lab;
			}
			else {
				$lab = Q("$user lab")->current();
			}
			if (!$lab->id) return FALSE;

			$equipment = $this->equipment;
			$department = Billing_Department::get($equipment->billing_dept->id);
			if (!$department->id) return FALSE;

			$account = O('billing_account', ['department'=>$department, 'lab'=>$lab]);
			if (!$account->id) $account = $department->add_account_for_lab($lab);
			if (!$account->id) return FALSE;
			$transaction->account = $account;

			switch ($this->source->name()) {
				case 'eq_reserv':
					$template = '%user 预约 %equipment 的费用。';
					break;
				case 'eq_sample':
					$template = '%user 送样预约 %equipment 的费用。';
					break;
                case 'service_apply_record':
                    $template = '%user 使用 %equipment 的费用。';
                    $desc = "{$this->source->apply->service->name}项目({$this->source->apply->service->ref_no})";
                    break;
				default:
					$template = '%user 使用 %equipment 的费用。';
					break;
			}

			$transaction->description = [
				'module'=>'eq_charge',
				'template'=> $template,
				'%user'=>Markup::encode_Q($user),
				'%equipment'=> isset($desc) ? $desc : Markup::encode_Q($equipment),
				//'amend'=>H($this->description)
            ];

			if (Module::is_installed('db_sync')
                && DB_SYNC::is_module_unify_manage('billing_transaction')
                && !$transaction->site) {
                $transaction->site = $equipment->site;
            }
            
            switch($this->source->name()) {
                case 'eq_record' :
                case 'eq_reserv' :
                    $transaction->ctime = max($this->source->dtstart, $this->source->dtend);
                    break;
                case 'eq_sample' :
                    $transaction->ctime = max($this->source->dtstart, $this->source->dtend, $this->source->dtsubmit, $this->source->dtpickup);
                    break;
                default :
            }

			$transaction->touch()->save();

			$this->transaction = $transaction;
			$this->ctime = $transaction->ctime;
		}
		$result = parent::save($overwrite);

		if ($new) {
			Log::add(strtr('[eq_charge] 产生了新收费[%charge_id] 计费编号[%transaction_id] 收费 %amount 元，相关资源%source_name[%source_id]', [
				'%charge_id' => $this->id,
				'%transaction_id' => $transaction->id,
				'%amount' => $this->amount,
				'%source_name' => $this->source->name(),
				'%source_id' => $this->source->id
			]), 'charge');
		}
		else if ($transaction->outcome != $outcome) {
			Log::add(strtr('[eq_charge] 仪器收费[%charge_id] 计费编号[%transaction_id] 计费产生了变更 现收费 %amount 元，相关资源%source_name[%source_id]', [
				'%charge_id' => $this->id,
				'%transaction_id' => $transaction->id,
				'%amount' => $this->amount,
				'%source_name' => $this->source->name(),
				'%source_id' => $this->source->id
			]), 'charge');
		}

		return $result;
	}

	function delete(){
		if (Module::is_installed('eq_charge_confirm') && $this->confirm == EQ_Charge_Confirm_Model::CONFIRM_INCHARGE) {
            return FALSE;
        }
		
		if ($this->transaction->id) $this->transaction->delete();

		return parent::delete();
	}

	function & links($mode = 'index') {
		$links = new ArrayIterator();

		Event::trigger('eq_charge.links', $this, $links, $mode);
		return $links->getArrayCopy();
	}
}
