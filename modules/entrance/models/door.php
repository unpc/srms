<?php

class Door_Model extends Presentable_Model
{

	const CONNECT = 1;
	const DISCONNECT = 0;

	const OPEN = 1;
	const CLOSED = 0;

	protected $object_page = [
		'view' => '!entrance/door/index.%id[.%arguments]',
		'edit' => '!entrance/door/edit.%id[.%arguments]',
		'delete' => '!entrance/door/delete.%id[.%arguments]',
		'in' => '!entrance/door/in.%id[.%arguments]',
		'out' => '!entrance/door/out.%id[.%arguments]',
	];

	static $monitoring = [
		self::CONNECT => '连接服务器',
		self::DISCONNECT => '断开服务器',
	];

	static $status = [
		self::OPEN => '打开',
		self::CLOSED => '关闭',
	];

	public static function types()
	{
		$ret = [];
		foreach (Config::get('entrance.handlers', []) as $handler) {
			$ret[$handler['id']] = $handler['name'];
		}
		return $ret;
	}
	public static function type($name)
	{
		$config  = Config::get('entrance.handlers', []);
		if (!$config[$name]) {
			return null;
		}
		return $config[$name]['id'];
	}

	public static function type_labels()
	{
		$ret = [];
		foreach (Config::get('entrance.handlers', []) as $handler) {
			$ret[$handler['id']] = $handler['short_name'];
		}
		return $ret;
	}

	public static function iot_door_driver($key = 'driver_name')
	{
		$ret = [];
		foreach (Config::get('entrance.handlers') as $handler) {
			if (!$handler[$key]) {
				continue;
			}
			$ret[$handler['id']] = $handler[$key];
		}
		return $ret;
	}

	function &links($mode = 'index')
	{
		$links = new ArrayIterator;
		/*
		NO.TASK#274(guoping.zhang@2010.11.27)
		应用权限设置新规则
		*/
		$me = L('ME');
		switch ($mode) {
		case 'view':
			if($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url'=>$this->url(NULL,NULL,NULL,'edit'),
                    'text'=>I18N::T('entrance','修改'),
					'tip'=>I18N::T('entrance','修改'),
					'extra'=>'class="button icon-edit"',
				];
			}
			if ($me->is_allowed_to('删除', $this)) {
				$links['delete'] = [
                    'text'=>I18N::T('entrance','删除'),
					'url'=>$this->url(NULL,NULL,NULL,'delete'),
					'tip'=>I18N::T('entrance','删除'),
					'extra'=>'style="border: 1px solid #F5222D;color:#F5222D" class="button icon-trash" confirm="'.I18N::T('entrance','你确定要删除吗? 删除后不可恢复!').'"',
				];
			}
			break;
		case 'index':
		default:
			if($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url'=>$this->url(NULL,NULL,NULL,'edit'),
					'text'  => I18N::T('entrance','修改'),
					'tip'=>I18N::T('entrance','修改'),
					'extra'=>'class="blue"',
				];
			}
			if ($me->is_allowed_to('删除', $this)) {
				$links['delete'] = [
					'url'=>$this->url(NULL,NULL,NULL,'delete'),
					'text'  => I18N::T('entrance','删除'),
					'tip'=>I18N::T('entrance','删除'),
					'extra'=>'class="blue" confirm="'.I18N::T('entrance','你确定要删除吗? 删除后不可恢复!').'"',
				];
			}
		}
		return (array)$links;
	}

	function get_free_access_cards($free_access_cards = 0)
	{
		$max_free_access_cards = (int)Config::get('entrance.max_free_access_cards');

		if (empty($free_access_cards) || $free_access_cards > $max_free_access_cards) {
			$free_access_cards = $max_free_access_cards ?: 200;
		}

		$free_access_tokens = array_unique(array_merge((array)Config::get('lab.admin'), (array)Config::get('entrance.free_access_users')));
		foreach ($free_access_tokens as $token) {
			$token = Auth::normalize($token);
			$user = O('user', ['token' => $token]);
			// 原卡号和短卡号都写入管理员卡号, 以适合不同的读卡器(xiaopei.li@2012-01-13)
			if ($user->id && $user->card_no) {
				$cards[(string)$user->card_no] = $user;
			}
		}

		$users = Q("$this<incharge user");
		foreach ($users as $incharge) {
			if ($incharge->id && $incharge->card_no) {
				$cards[(string)$incharge->card_no] = $incharge;
			}
		}

		//管理所有内容的用户也应该写入离线卡号
		$roles = clone L('ROLES');
		$admin_all_roles = [];
		foreach ($roles as $key => $value) {
			$role_perm = $value->perms;
			if ((int)$key < 0 || empty($role_perm)) continue;
			if (array_key_exists('管理所有内容', $role_perm) && $role_perm['管理所有内容'] == 'on') {
				$admin_all_roles[] = $key;
			}
		}

		//根据用户自定义的角色进行查找用户
		$free_users = [];
		if (!empty($admin_all_roles)) {
			$selector = 'role[id=' . implode(',', $admin_all_roles) . '] user[atime>0]';
			$users = Q($selector)->limit($free_access_cards);
			foreach ($users as $user) {
				if ($user->id && $user->card_no) {
					$cards[$user->card_no] = $user;
				}
			}
		}
		if (count($cards) > $free_access_cards) {
			$cards = array_slice($cards, 0, $free_access_cards, TRUE);
		}
		return $cards;
	}

	function tag_label()
	{
		switch ($this->type) {
			case self::type('genee'):
				return "<span class=\"prevent_default  status_tag status_tag_info\" style=\"font-size: 14px;\">" . self::type_labels()[$this->type] . "</span>";
				break;
			default:
				return "<span class=\"prevent_default  status_tag status_tag_normal\" style=\"font-size: 14px;\">" . self::type_labels()[$this->type] . "</span>";
				break;
		}
	}
}
