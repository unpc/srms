<?php
class Lims_Account_Helper {

	static function is_accessible($e, $name ) {
		if (L('ME')->is_allowed_to('查看', 'lims_account')) {
			$e->return_value = TRUE;
		}
		else {
			$e->return_value = FALSE;
		}

		return FALSE;

	}

	static function account_ACL($e, $user, $perm_name, $object, $options) {

		switch ($perm_name) {
		case '查看':
		case '列表文件':
		case '下载文件':
			if ($user->access('查看客户信息') || $user->access('管理客户信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '添加':
		case '修改':
		case '修改版本':
		case '删除':
		case '上传文件':
		case '修改文件':
		case '删除文件':
		case '创建目录':
		case '修改目录':
		case '删除目录':
			if ($user->access('管理客户信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		default:
		}

	}

	static function comment_ACL($e, $user, $perm_name, $object, $options) {
		switch ($perm_name) {
		case '发表评论':
			$e->return_value = TRUE;
			return FALSE;
			break;
		case '删除':
			if ('comment' == $object->name() &&
				'lims_account' == $object->object->name()) {
				if ($user->access('管理客户信息')) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			break;
		default:
		}
	}

	static function attachments_ACL($e, $user, $perm_name, $object, $options) {
		$e->return_value = TRUE;
	}

}
