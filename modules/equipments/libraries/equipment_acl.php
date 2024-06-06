<?php

class Equipment_ACL {
	
	//传入对象$object为equipment *
	static function equipment_is_allowed($me, $object, $perm_name, $options) {
		if ($perm_name == "删除") {
			if ($object->id && Equipments::user_is_eq_incharge($me, $object) && $me->access('删除负责仪器')) {
				return TRUE;
			}
		}
		elseif ($object->id && Equipments::user_is_eq_incharge($me, $object)) {
		    // 根据$perm_name 获取不同的权限
            // 修改基本信息 提交修改 添加
            switch ($perm_name) {
                case '添加':
                    if ($me->access('添加负责的仪器')) return TRUE;
                    break;
                case '提交修改':
                case '修改基本信息':
                    if ($me->access('修改负责的仪器')) return TRUE;
                    break;
            }
			// return TRUE;
		}

		if ($me->access('添加/修改所有机构的仪器')) {
			return TRUE;
        }

		if ($me->group->id && $me->access('添加/修改下属机构的仪器') && $me->group->is_itself_or_ancestor_of($object->group)) {
			return TRUE;
        }

        if ($me->group->id && $me->access('添加/修改下属机构的仪器') && !$object->id) {
            /*
             * 创建一个新的仪器的时候，$object无id未考虑
             * rui.ma< rui.ma@geneegroup.com>
             * 2011.12.06
             */
            return TRUE;
		}
		
		// 拥有"添加负责的仪器"权限的用户, 只允许添加仪器负责人和联系人为自己的仪器
		if ($perm_name == '添加' && $me->access('添加负责的仪器')) {
			return TRUE;
        }

        return FALSE;
	}
	
	//传入对象$object为equipment
	/*
		BUG#266 (Cheng.liu@2010.12.21)
		增加新的is_allowed_to 对 管理培训 的处理
	*/
	static function training_is_allowed($me, $object, $perm_name, $options) {
		if ($object->id && Equipments::user_is_eq_incharge($me, $object) && $me->access('管理负责仪器的培训记录')) {
			return TRUE;
		}
        // bug: 24736（3）17Kong/Sprint-285：lims3.3全面测试：院级管理员编辑下属机构仪器-使用设置，应该不可见需要培训/授权才能使用
        if ($object->id && $me->group->is_itself_or_ancestor_of($object->group) && $me->access('管理下属机构仪器的培训记录')) {
            return true;
        }
		if ($me->access('管理所有仪器的培训记录')) {
			return TRUE;
		}
        if ($me->access('查看下属机构的仪器使用记录')
            && $me->group->id && $object->group->id
            && $me->group->is_itself_or_ancestor_of($object->group)
        ) {
            return TRUE;
        }
	}
	
	//传入对象$object为equipment
	static function use_is_allowed($me, $object, $perm_name, $options) {
		if ($object->id && Equipments::user_is_eq_incharge($me, $object) && $me->access('修改负责仪器的使用设置')) {
			return TRUE;
		}
		if ($me->access('修改所有仪器的使用设置')) {
			return TRUE;
		}
		if ($me->group->id && $me->access('修改下属机构仪器的使用设置') && $me->group->is_itself_or_ancestor_of($object->group)) {
			return TRUE;
		}
		return FALSE;
	}
	
	//传入对象$object为equipment
	static function edit_tag_is_allowed($me, $object, $perm_name, $options) {
		if ($object->id && Equipments::user_is_eq_incharge($me, $object) && $me->access('修改负责仪器的用户标签')) {
			return TRUE;
		}
		if ($me->access('修改所有仪器的用户标签')) {
			return TRUE;
		}
		if ($me->group->id && $me->access('修改下属机构仪器的用户标签') && $me->group->is_itself_or_ancestor_of($object->group)) {
			return TRUE;
		}
	}
	
	//传入对象$object为user
	static function follow_equipment_is_allowed($me, $object, $perm_name, $options) {
		if (!$object->get_follows_count('*') && !count(Event::trigger('equipment.extra.follows'))) return;

		if ($me->id == $object->id) {
			return TRUE;
		}
		if ($me->access('查看其他用户关注的仪器')) {
			return TRUE;
		}
        if ($me->access('查看下属机构用户关注的仪器')
            && $me->group->id
            && $object->group->id
            && $me->group->is_itself_or_ancestor_of($object->group)
        ) {
            return true;
        }
	}
	
	//传入对象$object为user
	static function list_user_records_is_allowed($me, $object, $perm_name, $options) {
		if ($me->id == $object->id) {
			return TRUE;
		}

		if (Q("$object lab")->total_count()
			&& Q("($me, $object) lab")->total_count()
			&& $me->access('查看本实验室成员的仪器使用情况')) {
			return TRUE;
		}
		if (Q("$object lab")->total_count()
			&& Q("($me<pi, $object) lab")->total_count()
			&& $me->access('查看负责实验室成员的仪器使用情况')) {
			return TRUE;
		}
		
		if ($me->group->id && $me->access('查看下属机构的仪器使用记录') && $me->group->is_itself_or_ancestor_of($object->group)) {
			return TRUE;
		}
		
		if ($me->access('查看所有仪器的使用记录')) {
			return TRUE;
		}
 	}

    public static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $check_list = $view->check_list;
        $me = L('ME');
        $equipment = $view->calendar->parent;

        $result = true;
        $descriptions = [];

		if (($me->access('为所有仪器添加预约'))
			|| ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
			|| ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment))
		) {
			$result = true;
		} else {
			if ($equipment->charge_script['reserv'] || $equipment->charge_script['record']) {
				if (Module::is_installed('billing')) {
					$department = $equipment->billing_dept;
					$accounts = Q("$me lab billing_account[department={$department}]");
					if (!$accounts->total_count()) {
						$result = false;
						$description[] = I18N::T('eq_charge', "实验室在该设备指定财务部门内无帐号, 您目前无法预约该设备");
					}
					if ($accounts->total_count() == 1 && $account = $accounts->current()) {
						if (($account->balance + $account->credit_line) < ($equipment->reserv_limit ? $equipment->reserv_balance_required : 0) && !Config::get('billing.ignore_lab_balance_limit')) {
							$result = false;
							$description[] = I18N::T('eq_charge', "实验室余额不足, 您目前无法预约该设备");
						}
					}
				}

				// 新财务中心
				if (Module::is_installed('billing_manage')) {
					if (($me->access('为所有仪器添加预约'))
					|| ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
					|| ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment))
					) {
						// true
					} else {
						if (!$equipment->accept_reserv) {
							// true
						} else {
							$grants = (array)Billing_Manage::get_grants($me, $equipment);
							if (!count($grants)) {
								$result = false;
								$description[] = I18N::T('eq_charge', "您在该设备指定收费平台内无可用经费, 您目前无法预约该设备");
							}else{
								// true
								$available_amount = 0;
								foreach ($grants as $remote_id => $grant) {
									try {
										$remote_fund = Remote_Billing_Manage::callRemote("getFund", $params = [
											'path' => ["fundId" => $remote_id],
										]);
										$available_amount += $remote_fund['available_amount'];
									} catch (Exception $e) {
										$available_amount += 0;
									} finally {
									}
								}
								if ($available_amount < ($equipment->reserv_limit ? $equipment->reserv_balance_required : 0)) {
									$result = false;
									$description[] = I18N::T('eq_charge', "您在该设备指定收费平台内经费余额不足, 您目前无法预约该设备");		
								}
							}
						}
					}
				}

				// 预约券
				if (Module::is_installed('eq_approval')) {
					$quota = O('eq_quota', ['user' => $me]);
					if ($quota->id && $quota->type == EQ_Quota_Model::APPROVAL_QUOTA) {
						$type = EQ_Voucher_Model::RESERV;
						$is_used = EQ_Voucher_Model::UN_USED;
						$approval = EQ_Voucher_Model::APPROVED;
						$vouchers = Q("eq_voucher[user={$me}][type={$type}][equipment={$equipment}][use_status={$is_used}][status={$approval}]");
						if (!$vouchers->total_count()) {
							$result = false;
							$description[] = I18N::T('eq_approval', "未获得该仪器的预约券(若该仪器预约免费，则无需预约券可直接预约)")
								. '<a class="blue prevent_default" href="'.$me->url('approval', NULL, NULL, 'view').'">'.I18N::T('equipments', '点击申请').'</a>';
						}
					}
				}
			}

		}


		if ($result) {
            $description = I18N::T("equipments", "有权限");
        } else {
            $description = I18N::T("equipments", "无权限, ") . join("<br />", $description);
        }

        $check_list[] = [
            'title' => I18N::T('equipments', '设备使用权限'),
            'result' => $result,
            'description' => $description
        ];

        $view->check_list = $check_list;
    }
}
