<?php

class UM_Auth_Model extends Presentable_Model {

	protected $object_page = [
		'apply'=>'!meeting/auth/apply_user[.%arguments]',
		'approve'=>'!meeting/auth/approve_user.%id',
		'reject'=>'!meeting/auth/reject_user.%id',
	];

	const STATUS_APPLIED = 1; //申请
	const STATUS_APPROVED = 2; //批准
	const STATUS_REFUSE = 3; //申请培训被拒绝

	function & links($mode = 'applied') {
		$links = new ArrayIterator;
		$me = L('ME');
		switch ($mode) {
		case 'approved':
			$links['edit'] = [
				'url' => '#',
				'text'  => I18N::T('meeting', '修改'),
				'extra' => 'class="blue nowrap"
							q-event="click"
							q-object="edit_approved_user"
							q-static="'.HT(['tid'=>$this->id]).'"
							q-src="'.URI::url('!meeting/auth').'"',
				];
			$links['delete'] = [
				'url' => $this->url(NULL, NULL, NULL, 'reject'),
				'text'  => I18N::T('meeting', '删除'),
				'extra' => 'class="blue nowrap" confirm="'.I18N::T('meeting', '您确定删除该用户的授权吗?').'"',
				'weight' => 99,
				];
			break;
		case 'applied':
		default:
			$links['approve'] = [
				'url' => '#',
				'text'  => I18N::T('meeting', '批准'),
				'extra' => 'class="blue nowrap"
							q-event="click"
							q-object="approve_user"
							q-static="'.HT(['tid'=>$this->id]).'"
							q-src="'.URI::url('!meeting/auth').'"',
				];
			$links['reject'] = [
				'url' => $this->url(NULL, NULL, NULL, 'reject'),
				'text'  => I18N::T('meeting', '拒绝'),
				'extra' => 'class="blue nowrap" confirm="'.I18N::T('meeting', '您确定拒绝该用户的授权申请吗?').'"',
				'weight' => 99,
				];

		}
		return (array) $links;
	}
	
}
