<?php

class Billing_Manage {

    static function is_accessible($e, $name) {
		$me = L('ME');
		$e->return_value = TRUE;
		return FALSE;
	}

	static function is_accessible_fund($e, $name) {	
		$me = L('ME');
		$e->return_value = TRUE;
		return FALSE;
	}

	static function is_accessible_transaction_fund($e, $name) {	
		$me = L('ME');
		if (!$me->access('管理所有财务') &&
			!$me->access('管理下属机构财务') &&
			!$me->access('管理负责实验室财务')) {
			$e->return_value = false;
			return FALSE;
		}
	}

	static function is_accessible_stat_platform($e, $name) {	
		$me = L('ME');
		if (!$me->access('管理所有财务') &&
			!$me->access('管理下属机构财务') &&
			!$me->access('管理负责实验室财务')) {
			$e->return_value = false;
			return FALSE;
		}
	}

	static function eq_reserv_prerender_component($e, $view, $form)
    {
        $component = $view->component;

        $form['authorized'] = [
            'label' => I18N::T('billing_manage', '经费卡号'),
            'path' => ['form' => 'billing_manage:view/'],
            'component' => $component
        ];
        $form['billing_authorized'] = [
            'label' => I18N::T('billing_manage', '经费卡号'),
            'path' => ['form' => 'billing_manage:view/'],
            'component' => $component
        ];
        $form['#categories']['reserv_info']['items'][] = 'authorized';
        $form['#categories']['reserv_info']['items'][] = 'billing_authorized';
        $e->return_value = $form;
        return TRUE;
    }

    static function component_form_post_submit($e, $component, $form)
    {
        $object = O('eq_reserv', ['component' => $component]);
        if ($object->id) {
			$fund= o('billing_fund', ['remote_id' => $form['remote_fund_id']]);
			$object->billing_fund = $fund;
			$object->save();
		}
    }

	static function eq_sample_prerender_add_form($e, $form, $equipment)
    {
        $me = L('ME');
        $e->return_value .= V('billing_manage:view/eq_sample/add', ['form' => $form, 'equipment' => $equipment]);
        return false;
    }

    static function eq_sample_prerender_edit_form($e, $sample, $form, $user)
    {
        $me = L('ME');
        $e->return_value .= V('billing_manage:view/eq_sample/edit', ['form' => $form, 'sample' => $sample, 'equipment' => $sample->equipment]);
        return false;
    }

    static function feedback_extra_view($e, $record, $form) {
        $me = L('ME');
        $e->return_value = V('billing_manage:view/eq_record/feedback_extra_view', ['record' => $record, 'form' => $form, 'equipment' => $record->equipment]);
        return false;
    }

    static function feedback_form_submit($e, $record, $form)
    {
		if (!self::isFree($record->equipment, 'eq_record') && !$form['remote_fund_id']) {
			$form->set_error('fund', I18N::T('billing', "请选择经费!"));
			$e->return_value = TRUE;
			return FALSE;
		}

        if ($form->no_error && isset($form['remote_fund_id'])) {
            $fund = o('billing_fund', ['remote_id' => $form['remote_fund_id']]);
			$record->billing_fund = $fund;
			$record->save();
            // 如果该使用记录关联了预约
            if($record->reserv->id) {
                $record->reserv->billing_fund = $fund;
				$record->reserv->save();
            }
        }
    }

	static function isFree($equipment, $type) {
		//$type_array = ['reserv', 'record', 'sample', 'service'];
		if ($type == "eq_sample" && !$equipment->charge_script['sample']) {
			return true;
		}
		if ($type == "eq_reserv" && !$equipment->charge_script['reserv']) {
			return true;
		}
		if ($type == "eq_record" && !$equipment->charge_script['record']) {
			return true;
		}
		return false;
	}
	static function extra_form_validate($e, $equipment, $type, $form)
    {
        $me = L('ME');

        $lab = $form['project_lab'] ? O('lab', $form['project_lab']) : Q("{$me} lab")->current();
        switch ($type) {
            case 'eq_sample':
                $user = O('user', $form['sender']); // 代开
                $user = $user->id ? $user : L('ME');
                break;
            case 'eq_reserv':
                $user = O('user', $form['organizer'] ?: $form['currentUserId']); // 代开
                $user = $user->id ? $user : L('ME');
				break;
            default:
                $user = L('ME');
        }
		if (in_array($type, ['eq_sample', 'eq_reserv']) && !self::isFree($equipment, $type) && !$form['remote_fund_id']) {
			$form->set_error('fund', I18N::T('billing', "请选择经费!"));
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($form['remote_fund_id']) {
			try {
				$remote_fund = Remote_Billing_Manage::callRemote("getFund", $params = [
					'path' => ["fundId" => $form['remote_fund_id']],
				]);
				$available_amount = $remote_fund['available_amount'];
			} catch (Exception $e) {
				$available_amount = 0;
			} finally {
				// 欠费
				if ($available_amount < 0) {
					$form->set_error('remote_fund_id', I18N::T('billing_manage', "所选经费额度不足，请更换经费或联系老师追加充值金额"));
					$e->return_value = TRUE;
					return FALSE;
				}
				// 低于预约限制
				if ($type == "eq_reserv" && $equipment->reserv_limit && $available_amount < $equipment->reserv_balance_required) {
					$form->set_error('remote_fund_id', I18N::T('billing_manage', "所选经费额度不足，请更换经费或联系老师追加充值金额"));
					$e->return_value = TRUE;
					return FALSE;
				}
				// 低于送样限制
				if ($type == "eq_sample" && $equipment->sample_limit && $available_amount < $equipment->sample_balance_required) {
					$form->set_error('remote_fund_id', I18N::T('billing_manage', "所选经费额度不足，请更换经费或联系老师追加充值金额"));
					$e->return_value = TRUE;
					return FALSE;
				}
			}
		}
		
        $e->return_value = FALSE;
        return;
    }

	static function eq_sample_form_submit($e, $sample, $form)
    {
		$fund= o('billing_fund', ['remote_id' => $form['remote_fund_id']]);
		$sample->billing_fund = $fund;
    }

	public static function get_grants($user, $equipment)
    {
        if (!$user->id) return [];
		$funds = [];
		$n = 10;
		$start = 0;

		$groupIds = [];
		$groupIds[] =  0;
		foreach ((array) $equipment->group->path as $unit) {
			list($tag_id, $tag_name) = $unit;
			$groupIds[] = $tag_id;
		}

		while (true) {
			$remote_funds = Remote_Billing_Manage::callRemote("getFunds", $params = [
				'st' => $start, 
				'pp' => $n,
				'tab' => 'lims',
				'user_id' => $user->id,
				'group_id' =>  implode(',', $groupIds),
			]);
			if ($remote_funds['items']) foreach ($remote_funds['items'] as $item) {
				$name = "";
				$name .= $item['prot_name'];
				if ($item['card_no']) {
					$name .= "[{$item['card_no']}]";
				}
				$funds[$item['id']] = $name;
				$fund = o('billing_fund', ['remote_id' => $item['id']]);
				$fund->remote_id = $item['id'];
				$fund->name = $name;
				$fund->save();
			}
			$start += $n;
			if (!count($remote_funds['items'])) break; 
		}
		return $funds;
    }

	public static function orm_model_saved($e, $object, $old, $new)
	{
		if (in_array($object->name(),["eq_reserv", "eq_sample", "service_apply_record", "eq_record"])) {
			$charge = o("eq_charge", ["source" => $object]);
			if ($new['billing_fund']->id && $charge->id && $charge->billing_fund->id != $new['billing_fund']->id) {
				$charge->billing_fund = $new['billing_fund'];
				$charge->charge_billing_fund = 1;
				$charge->save();
			}
		}
	}

	public static function eq_charge_before_save($e, $charge, $new)
    {
		$charge->old_billing_fund = o('billing_fund', $charge->billing_fund_id);
		if ($charge->charge_billing_fund) {
			$charge->charge_billing_fund = 0;
		} else {
			$charge->billing_fund     = o('billing_fund', $charge->source->billing_fund_id); 
		}
    }

	public static function eq_charge_saved($e, $charge, $old, $new)
    {
		$fund     = o('billing_fund', $charge->billing_fund_id); 
		switch ($charge->source->name()) {
			case 'eq_reserv':
				$description = [
					"template" => "%user 预约 %equipment 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%equipment" => $charge->equipment->id
					]
				];		
				break;
			case 'eq_sample':
				$description = [
					"template" => "%user 送样预约 %equipment 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%equipment" => $charge->equipment->id
					]
				];
				break;
			case 'service_apply_record':
				$description = [
					"template" => "%user 使用 %apply_service 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%apply_service" => $charge->source->apply->service->id
					]
				];
				break;
			default:
				$description = [
					"template" => "%user 使用  %equipment 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%equipment" => $charge->equipment->id
					]
				];	
				break;
		}
		if (!isset($old['amount']) && !isset($new['amount'])) {
			$old['amount'] = $new['amount'] = $charge->amount;
		}
		$params = [];
		$params['description'] = json_encode($description);
		$params['remote_app'] = 'lims';
		$params['remote_name'] =  $charge->name();
		$params['remote_id'] =  $charge->id;
		$params['type'] 	 =  "thaw";
		if ($charge->old_billing_fund->id != $new['billing_fund']->id || $old['amount'] != $new['amount']) {
			if ($charge->old_billing_fund->remote_id && $old['amount']) {
				$params["transactions"][] = [
					"fund_id" 	=> $charge->old_billing_fund->remote_id,
					"type" 		=> "freeze",
					"amount"    => $old['amount'],
					"remarks" 	=> "",
					"evidence" 	=> "",
					"user_id"   => $charge->user->id
				];
			}
			if ($fund->remote_id && $new['amount']) {
				$params["transactions"][] = [
					"fund_id" 	=> $fund->remote_id,
					"type" 		=> "thaw",
					"amount"    => $charge->amount,
					"remarks" 	=> "",
					"evidence" 	=> "",
					"user_id"   => $charge->user->id
				];
			}
		}
		try {
			if(count($params["transactions"])) 
				$res = Remote_Billing_Manage::callRemote("postTopic",  $params);
		} catch (Exception $e) {
		} 
    }

    public static function on_charge_deleted($e, $charge)
    {
		$fund     = o('billing_fund', $charge->billing_fund_id); 
		switch ($charge->source->name()) {
			case 'eq_reserv':
				$description = [
					"template" => "%user 预约 %equipment 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%equipment" => $charge->equipment->id
					]
				];		
				break;
			case 'eq_sample':
				$description = [
					"template" => "%user 送样预约 %equipment 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%equipment" => $charge->equipment->id
					]
				];
				break;
			case 'service_apply_record':
				$description = [
					"template" => "%user 使用 %apply_service 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%apply_service" => $charge->source->apply->service->id
					]
				];
				break;
			default:
				$description = [
					"template" => "%user 使用  %equipment 的费用",
					"params" => [
						"%user" => $charge->user->id,
						"%equipment" => $charge->equipment->id
					]
				];	
				break;
		}
		$params = [];
		$params['description'] = json_encode($description);
		$params['remote_app'] = 'lims';
		$params['remote_name'] =  $charge->name();
		$params['remote_id'] =  $charge->id;
		$params['type'] 	 =  "thaw";
		if ($fund->remote_id && $charge->amount) {
			$params["transactions"][] = [
				"fund_id" 	=> $fund->remote_id,
				"type" 		=> "freeze",
				"amount"    =>  $charge->amount,
				"remarks" 	=> "",
				"evidence" 	=> "",
				"user_id"   => $charge->user->id
			];
		}
		try {
			if(count($params["transactions"])) 
				$res = Remote_Billing_Manage::callRemote("postTopic",  $params);
		} catch (Exception $e) {
		} 
    }

	static function cannot_reserv_equipment ($e, $equipment, $params) {
        $me = L('ME');
        if ($equipment->charge_script['reserv']) {
			$grants = (array)Billing_Manage::get_grants($me, $equipment);
            if (!count($grants)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '课题组在该设备所在收费平台无经费，您目前无法预约该设备。'));
                $e->return_value = TRUE;
                return FALSE;
            }
        }
    }

    static function cannot_sample_equipment ($e, $equipment, $params) {
        $user = $params[0];
        $sample = $params[1];

        $me = L('ME');
        if ($equipment->charge_script['sample']) {
            $grants = (array)Billing_Manage::get_grants($me, $equipment);
            if (!count($grants)) {
                Lab::message('sample', I18N::T('eq_charge', '课题组在该设备所在收费平台无经费，您目前无法申请送样。'));
                $e->return_value = TRUE;
                return FALSE;
            }
        }
    }


    public static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $check_list = $view->check_list;
        $me = L('ME');
        $equipment = $view->calendar->parent;
        if (($me->access('为所有仪器添加预约'))
            || ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
            || ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment))
        ) {
            $check_list[] = [
                'title' => I18N::T('billing_manage', '平台经费'),
                'result' => true,
                'description' => ''
            ];
        } else {
            if (!$equipment->accept_reserv) {
                $check_list[] = [
                    'title' => I18N::T('billing_manage', '平台经费'),
                	'result' => true,
                	'description' => ''
                ];
            } else {
                $grants = (array)Billing_Manage::get_grants($me, $equipment);
            	if (!count($grants)) {
                    $check_list[] = [
                        'title' => I18N::T('billing_manage', '平台经费'),
                        'result' => false,
                        'description' => I18N::T('credit', '没有可用经费')
                    ];
                }else{
                    $check_list[] = [
                        'title' => I18N::T('billing_manage', '平台经费'),
                        'result' => true,
                        'description' => ''
                    ];
                }

            }
        }
        $view->check_list = $check_list;
    }

	static function equipment_billing_department($e, $equipment, $params)
    {
        if (!Module::is_installed('billing')) {
            $e->return_value = true;
            return false;
        }
    }
}

