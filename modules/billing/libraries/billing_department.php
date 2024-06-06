<?php

class Billing_Department {
	static function get($id=0) {
		$department = null;
		if ($GLOBALS['preload']['billing.single_department']) {
        #ifdef (billing.single_department)
            $department = Q('billing_department:limit(1)')->current();
            
			if (!$department->id) {
				$department = O('billing_department');
				$department->name = I18N::T('billing', '财务部门');
				$department->save();
			}
		#endif	
		}
		else {
		#ifndef (billing.single_department)
			$department = O('billing_department', $id);
		#endif
		}
		return $department;
	}

	/** 
	 * $user是否是$department的负责人
	 * (xiaopei.li@2010.12.22)
	 * 
	 * @param user 
	 * @param department 
	 * 
	 * @return 
	 */
	static function user_is_dept_incharge($user, $department = 'billing_department') {
		return Q("{$department} user[id=$user->id]")->total_count() > 0;
	}

	/** 
	 * $user是否是某一department的负责人
	 * (xiaopei.li@2010.12.22)
	 * 
	 * @param user 
	 * 
	 * @return 
	 */
	static function user_is_one_of_dept_incharges($user) 
	{
		return Q("{$user} billing_department")->total_count() > 0;
	}

	/*
	 * 最后修改:(xiaopei.li@2011.03.21)
	 */
	static function billing_department_ACL($e, $me, $perm_name, $department, $options) {
		$unique_department = $GLOBALS['preload']['billing.single_department'];
		switch ($perm_name) {
		case '查看':
		    if ($me->access('查看财务中心')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if (self::user_is_dept_incharge($me, $department)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if ($me->access('列表下属实验室的财务帐号') || $me->access('修改下属实验室的财务帐号')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
		case '列表财务帐号': 		/* TODO 查看和列表财务帐号应合并么？(xiaopei.li@2011.03.21) */
            if ($me->access('查看财务中心')) {
                $e->return_value = TRUE;
                return FALSE;
            }
			if (self::user_is_dept_incharge($me, $department)) {
				$e->return_value = TRUE;
				return FALSE;
			}
            if ($me->access('列表下属实验室的财务帐号')
                && Q("{$me}<group tag_group[parent] lab[hidden=0] billing_account[department={$department}]")->total_count()) {
                $e->return_value = TRUE;
                return FALSE;
            }
			break;
        case '添加财务帐号':
            if (self::user_is_dept_incharge($me, $department)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if ($me->access('修改下属实验室的财务帐号')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
		case '修改':			/* 仅'管理财务中心'可修改财务部门 */
			break;
		/* 以下是多财务部门下才应出现的判断 */
		case '列表':
            if ($me->access('查看财务中心')) {
                $e->return_value = TRUE;
                return FALSE;
            }
			if (self::user_is_one_of_dept_incharges($me)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($unique_department) {
				$e->return_value = FALSE;
				return FALSE;
			}
            if ($me->access('列表下属实验室的财务帐号')) {
                $e->return_value = TRUE;
                return FALSE;
            }
			break;				/* 此处用case下落亦可 */
		case '添加':			/* TODO 添加仅多财务部门才有么？单一财务部门下财务部门尚未建立时是否显示？清空数据库测试？(xiaopei.li@2011.03.21) */
		case '删除':
			if ($unique_department) {
				$e->return_value = FALSE;
				return FALSE;
			}
			break;
		default:
			return;
		}

		if ($me->access('管理财务中心')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	/**
	 * NO.BUG#286(xiaopei.li@2010.12.22)
	 * 判断用户是否有操作此模块最基础的权限
	 * 若无，则不在sidebar中显示此模块图标
	 *
	 * @param e
	 * @param name: module name
	 *
	 * @return
	 */
	static function is_accessible($e, $name) {
		$me = L('ME');
		/*
		 * TODO  
		 * 充值和打印实验室财务明细页面时，会调用billing的controller，此处进行权限判断。为安全起见，需调整权限判断。
		 *
		 */
		if ($me->id && Input::arg(0) == 'autocomplete') {
			$e->return_value = TRUE;
			return FALSE;
		}

		$form = Input::form();

		if (isset($form['lab_id']) && $form['lab_id']) {
			$lab = O("lab", $form['lab_id']);
		} else {
			$lab = Q("$me lab")->current();
		}
		$path = (defined('MODULE_ID') ? '!'.MODULE_ID.'/' :'')
							.Config::get('system.controller_path')
							.'/'
							.Config::get('system.controller_method');
		if ($me->id && $me->is_allowed_to('查看财务情况', $lab) && in_array($path, ['!billing/index/entries', '!billing/index/index_heartbeat_check']))
		{
			$e->return_value = TRUE;
			return FALSE;
		}
        if (($me->id && $me->is_allowed_to('列表收支明细', $lab) && Input::arg(0) == 'transactions') 
        	|| ((in_array(Input::$AJAX['object'],['refresh','export','add_note']) || Input::$AJAX['object'] == 'preview' || Input::$AJAX['object'] == 'refill_notif' || Input::$AJAX['object'] == 'refill_redirect' ) && Q("$me $lab")->total_count())
        	) {
            $e->return_value = TRUE;
            return FALSE;
        }

		if ($GLOBALS['preload']['billing.single_department']) {
			// unique billing department
			$department = Billing_Department::get();
			if (!($department->id
				  && $me->is_allowed_to('查看', $department))) {
				$e->return_value = $is_accessible;
				return false;
			}
		}
		elseif (!$me->is_allowed_to('列表', 'billing_department')) {
			// many billing departments
			$e->return_value = $is_accessible;
			return false;
		}
	}
	
	static function on_enumerate_user_perms($e, $user, $perms) {
		if (!$user->id) return;
        //取消现默认赋予给pi的权限
//		if (Q("$user<pi lab")->total_count()) {
//			$perms['列表负责实验室的收支明细'] = 'on';
//			$perms['列表负责实验室的财务帐号'] = 'on';
//		}
	}
	
	//打印、导出
	static function department_billings_ACL($e, $me, $perm_name, $department, $options) {
		switch ($perm_name) {
			case '导出':
                $e->return_value = L('ME')->is_allowed_to('列表收支明细', 'billing_department');
				return FALSE;
 				break;
		}
		
				
	}

	static function show_supervised_labs($e, $department)
    {
        $me = L('ME');
        if ($me->access('管理财务中心')) {
            $e->return_value = FALSE;
            return FALSE;
        }
        if ($department->id) {
            if (Billing_Department::user_is_dept_incharge($me, $department)) {
                $e->return_value = FALSE;
                return FALSE;
            }
        }
        $e->return_value = TRUE;
        return FALSE;
    }
}
