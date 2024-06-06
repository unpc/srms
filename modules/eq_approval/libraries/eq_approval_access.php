<?php

class EQ_Approval_Access {

	static function operate_lab_is_allowed($e, $user, $perm, $lab, $params) {
		switch ($perm) {
			case '修改审核':
			case '查看审核':
				if ($user->is_allowed_to('修改', $lab)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			default:
				break;
		}
	}

	static function operate_voucher_is_allowed($e, $user, $perm, $voucher, $params) {
		$lab = $voucher->lab;
		$owner = $voucher->user;
		switch ($perm) {
			case '查看':
				if ($user->id == $owner->id) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->is_allowed_to('查看审核', $lab)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '修改':
				if ($voucher->status != EQ_Voucher_Model::PENDDING) {
					$e->return_value = FALSE;
					return FALSE;
				}
				if ($user->id == $owner->id) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '删除':
				if ($user->id == $owner->id) {
					if ($voucher->status != EQ_Voucher_Model::APPROVED) {
						$e->return_value = TRUE;
						return FALSE;
					}
					else {
						if ($voucher->use_status == EQ_Voucher_Model::UN_USED) {
							$e->return_value = TRUE;
							return FALSE;
						}
					}
					
				}
				if ($voucher->status != EQ_Voucher_Model::PENDDING && $user->is_allowed_to('修改审核', $lab)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '审批':
				if ($voucher->status == EQ_Voucher_Model::PENDDING && $user->is_allowed_to('修改审核', $lab)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			default:
				break;
		}
	}

}