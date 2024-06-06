<?php

class API_Billing_Later {

	//验证失败返回错误信息
	const AUTH_FAILED = 0;
	const OUTSIDE_LAB = 1;
	const INSIDE_LAB = 0;

    private function _checkAuth()
    {
		// 先进行返回 否则低版本财务应用会进不来
		return;
        $billing_later = Config::get('rpc.servers')['billing.later'];
        if ((!isset($_SESSION['billing.later.client_id']) || 
            $billing_later['client_id'] != $_SESSION['billing.later.client_id'])
			&& !Scope_Api::is_authenticated('billing.later')) {
            throw new API_Exception('Access denied.', 401);
        }
    }

    function authorize($clientId, $clientSecret)
    {
        $billing_later = Config::get('rpc.servers')['billing.later'];
        if ($billing_later['client_id'] == $clientId && 
            $billing_later['client_secret'] == $clientSecret) {
            $_SESSION['billing.later.client_id'] = $clientId;
            return session_id();
        }
        return false;
	}

	function searchItems($opts) {
	//	$this->_checkAuth();

		$selector = "(user[token]) eq_charge[amount>0]";
		$sub_selector = new ArrayIterator();
		$pre_selector = new ArrayIterator();

		if ($opts['username']) {
			list($token, $rpc) = explode('%', $opts['username']);
			$userId = O('user', ['token' => $token])->id;
			$sub_selector[] = "[user_id={$userId}]";
		}

		if (isset($opts['isLock'])) {
			$isLock = (int)$opts['isLock'];
			$sub_selector[] = $isLock ? "[is_locked>0]" : "[is_locked=0]";
		}

		if (isset($opts['remoteLock'])) {
			$remoteLock = (int)$opts['remoteLock'];
			$sub_selector[] = $remoteLock ? "[remoteLock>0]" : "[remoteLock=0]";
		}

		if ($opts['labId']) {
			$labId = (int)$opts['labId'];
			$sub_selector[] = "[lab_id={$labId}]";
		}
		
		if ($pots['notLab']) {
			$labId = (int)$opts['notLab'];
			$sub_selector[] = "[lab_id!={$labId}]";
		}

		if ($opts['grant']) {
			$grant = Q::quote($opts['grant']);
			$sub_selector[] = "[grant*={$grant}]";
		}

		if ($opts['eqName'] || $opts['struct']) {
			$pre_selector['equipment'] = "equipment";
			if ($opts['eqName']) {
				$eqName = Q::quote($opts['eqName']);
				$pre_selector['equipment'] .= "[name*={$eqName}]";
			}
			if ($opts['struct']) {
				$pre_selector['equipment'] .= "[struct]";
			}
		}

		if (count($pre_selector)) $selector = '(' . join(',', (array)$pre_selector) . ') ' . $selector;
		if (count($sub_selector)) $selector .= join('', (array)$sub_selector);


		$token = md5('Billing_Later_'.time().uniqid());
		$_SESSION[$token] = $selector;
		$total = Q($selector)->total_count();

		return ['token' => $token, 'total' => $total];

	}

	function getItems($token, $start, $num=5) {
		//$this->_checkAuth();

		if (!$token) {
			throw new API_Exception(T('Token未定义!'), self::AUTH_FAILED);
		}

		$selector = $_SESSION[$token];
		$charges = Q($selector)->limit($start, $num);

		$items = [];

		foreach ($charges as $charge) {

            if (Event::trigger('billing_later.syncitem.custom', $charge)) continue;

			$period = [];
			$oname = $charge->source->name();
			$equipment = $charge->equipment;
            $lab_type = self::INSIDE_LAB;    

            if ($charge->user->group->name == '校外组织机构') {
                $lab_type = self::OUTSIDE_LAB;
            }
            if ($oname == 'eq_reserv') {
                $reserv = O('eq_reserv', $charge->source->id);
                $dtstart = $reserv->dtstart;
                $dtend = $reserv->dtend;
                $records = Q("eq_record[equipment={$equipment}][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend]");
                if ($records->total_count() == 0) {
                    $period[] = ['start' => $reserv->dtstart, 'end' => $reserv->dtend];   
                } else {
                    foreach ($records as $record) {
                        $period[] = ['start' => $record->dtstart, 'end' => $record->dtend];
                    }
                }
            } elseif ($oname == 'eq_sample') {
                $sample = O('eq_sample', $charge->source->id);
                $period[] = ['mtime' => $sample->mtime];
            } elseif ($oname == 'eq_record') {
                $eq_record = O('eq_record', $charge->source->id);
                $period[] = ['start' => $eq_record->dtstart, 'end' => $eq_record->dtend];
            }

			$items[] = [
				'id' => $charge->id,
				'eqInfo' => [
					'name' => $equipment->name,
					'refNo' => $equipment->ref_no,
					'url' => $equipment->url(),
					'structId' => $equipment->struct->id,
					'structName' => $equipment->struct->name,
					'structGroup' => $equipment->struct->group,
                    'structToken' => $equipment->struct->token,
                    'school_level' => $equipment->school_level,
                    'group_id' => $equipment->group->id,
                    'incharges' => Q("{$equipment} user.incharge")->to_assoc('id', 'name')
				],
				// 无奈之举,中南大学grant不是这么存的
				'grantId' => $charge->source->grant ? : $charge->source->project->card,
				'type' => $oname == 'eq_sample' ? '送样收费' : ( $oname == 'eq_reserv' ? '预约收费' : '使用收费'),
				'amount' => $charge->amount,
                'period' => $period ? json_encode($period) : '',
				'username' => $charge->user->token,
				'tranId' => $charge->transaction->id,
                'lab_type' => $lab_type
			];
		}

		return $items;

	}

	function getItem($id) {
		$this->_checkAuth();

		if (!$id) {
			throw new API_Exception(T('Id未定义!'), self::AUTH_FAILED);
		}

		$charge = O('eq_charge', $id);

		if (!$charge->id) return [];

		$oname = $charge->source->name();
		$equipment = $charge->equipment;
		return [
			'id' => $charge->id,
			'eqInfo' => [
				'name' => $equipment->name,
				'url' => $equipment->url(),
				'structToken' => $equipment->struct->token,
				'structName' => $equipment->struct->name,
				'structGroup' => $equipment->struct->group
			],
			// 无奈之举,中南大学grant不是这么存的
			'grantId' => $charge->source->grant ? : $charge->source->project->card,
			'type' => $oname == 'eq_sample' ? '送样收费' : ( $oname == 'eq_reserv' ? '预约收费' : '使用收费'),
			'amount' => $charge->amount,
			'username' => $charge->user->token,
			'tranId' => $charge->transaction->id
		];
	}

	function updateItem($id, $opts=[]) {
		$this->_checkAuth();

		if (!$id) {
			throw new API_Exception(T('Id未定义!'), self::AUTH_FAILED);
		}

		$charge = O('eq_charge', $id);

		if (!$charge->id) return [];

		$db = Database::factory();

		if (isset($opts['remoteLock'])) {
			$remoteLock = (int)$opts['remoteLock'];
			$db->query(
				"UPDATE eq_charge SET remoteLock = %d, is_locked = 1 WHERE id = %d",
				$remoteLock,
				$id
			);
		}

		return ['success' => 1];
	}

	function searchEqStructs() {
		$this->_checkAuth();

		$selector = 'eq_struct';

		$token = md5('Billing_Later_'.time().uniqid());
		$_SESSION[$token] = $selector;
		$total = Q($selector)->total_count();

		return ['token' => $token, 'total' => $total];
	}

	function getEqStructs($token, $start, $num=5) {
		$this->_checkAuth();

		if (!$token) {
			throw new API_Exception(T('Token未定义!'), self::AUTH_FAILED);
		}

		$selector = $_SESSION[$token];
		$eqStructs = Q($selector)->limit($start, $num);

		$items = [];

		foreach ($eqStructs as $eqStruct) {
			$items[] = [
				'id' => $eqStruct->id,
				'token' => $eqStruct->token,
				'name' => $eqStruct->name,
				'group' => $eqStruct->group,
				'depno' => $eqStruct->depno,
				'depname' => $eqStruct->depname,
				'prono' => $eqStruct->prono,
				'proname' => $eqStruct->proname,
			];
		}

		return $items;
	}

	function rechargeForLab($amount, $lab_id, $serial_number, $transaction_id = 0) {
  		$this->_checkAuth();

		if ((!$amount) || (!$lab_id)) {
			throw new API_Exception(T('参数传递错误!'), self::AUTH_FAILED);
		}

		$billing_account = O('billing_account', ['lab_id' => $lab_id, 'department_id' => Config::get('billing.default_department')]);

		// 可能存在两种可能，所以此处用transaction更为靠谱：
		// 1. 用户课题组更换了，但是在 billing_later 会自动更新课题组，所以会更新到最新的PI名下
		// 2. 存在借款账户的可能性，用的经费的课题组与自身的课题组不一致
		$transaction = O('billing_transaction', $transaction_id);
		if ($transaction->id) {
			$billing_account = $transaction->account;
		}

		if (!$billing_account) {
			throw new API_Exception(T('课题组的财务账号不存在！'), self::AUTH_FAILED);
		}

		$billing_transaction = O('billing_transaction', ['voucher' => $serial_number]);
		$billing_transaction->account = $billing_account;
		$billing_transaction->income = $amount;
		$template = '%user 报销成功, 报销单号: ' . $serial_number;
		$lab = O('lab', $lab_id);
		$billing_transaction->description = [
		    'module' => 'billing',
		    'template' => $template,
		    '%user' => Markup::encode_Q($lab->owner),
		];
		$billing_transaction->save();
	}

	function update_card_info($grant) {
		$card = $grant['card'];

        $lab_project = O('lab_project', ['card'=>$card]);

        if (!$lab_project->id) {
            $token = explode('%', $grant['username'])[0];
			$user = O('user', ['token' => $token]);
            $lab = Q("$user lab")->current();

			$lab_project = O('lab_project');
			$lab_project->lab = $lab;
			$lab_project->is_del = !!$grant['hide'];
		}
		else {
            $lab_project->is_del = !!$grant['hide'];
		}
		$lab_project->name = $grant['projectName'];
		$lab_project->prono = $grant['projectNo'];
		$lab_project->depno = $grant['departmentNo'];
		$lab_project->depname = $grant['departmentName'];
		$lab_project->incharge = $grant['name'];
		$lab_project->grant = $card;
		$lab_project->card = $card;
		$lab_project->type = TRUE;
		$lab_project->ptype = TRUE;
		$lab_project->isimport = TRUE;
		$lab_project->save();
	}

    /**
     * @param $remoteId eq_charge中transcation_id
     * @param $finishData extra
     */
	function updateStatusData($remoteId,$finishData){
        $this->_checkAuth();

        if ((empty($remoteId)) || empty($finishData)) {
            throw new API_Exception(T('参数传递错误!'), self::AUTH_FAILED);
        }

        $charges = Q('eq_charge[transaction_id='.implode(',',$remoteId).']');
        if($charges->total_count()){
            foreach($charges as $c){
                isset($finishData['status']) ? $c->billingstatus=$finishData['status'] : '';
                isset($finishData['status']) ? $c->bl_status=$finishData['status'] : '';
                isset($finishData['pzbh']) ? $c->serialcode=$finishData['pzbh'] : '';
                isset($finishData['zflsh']) ? $c->serialnum=$finishData['zflsh'] : '';
                isset($finishData['mtime']) ? $c->billingdate=$finishData['mtime'] : '';
                isset($finishData['bl_status']) ? $c->bl_status=$finishData['bl_status'] : '';
                isset($finishData['is_locked']) ? $c->is_locked=$finishData['is_locked'] : '';
                isset($finishData['remoteLock']) ? $c->remoteLock=$finishData['remoteLock'] : '';
                Module::is_installed('eq_charge_confirm') ? $c->confirm = 0 : '';
                $c->save();
            }
        }
        return true;
    }

}
