<?php

class Cloud {

	static function setup_admin(){
		$perms = Config::get('perms.cloud');
		$me = L('ME');

		foreach ($perms as $perm => $foo) {
			if (strpos($perm, '#')) {
				// perms.php 中除了实际 perm 外还有 #name, #icon
				// 应跳过这些
				continue;
			}

			if ($me->access($perm)) {
				// 只要 $me 有 cloud 模块的权限, 就开启此 tab
				Event::bind('admin.index.tab', 'Cloud::_primary_tab');
				break;
			}
		}
	}

	//系统设置primary tab
	static function _primary_tab($e, $tabs){
		Event::bind('admin.index.content', 'Cloud::_primary_content', 0, 'cloud');

		$tabs->add_tab('cloud', [
			'url'=>URI::url('admin/cloud'),
			'title'=> I18N::T('cloud', '云设置'),
			'weight' => 100,
		]);
	}

	static function _primary_content($e, $tabs){
		$me = L('ME');

		$tabs->content = V('application:admin/view');

		Event::bind('admin.cloud.content', 'Cloud::_secondary_network_content', 0, 'network');
		Event::bind('admin.cloud.content', 'Cloud::_secondary_update_content', 0, 'update');
		Event::bind('admin.cloud.content', 'Cloud::_secondary_backup_content', 0, 'backup');

		$secondary_tabs = Widget::factory('tabs')
			->set('class', 'secondary_tabs')
			->tab_event('admin.cloud.tab')
			->content_event('admin.cloud.content');

		$secondary_tabs->add_tab('backup', [
			'url'=>URI::url('admin/cloud.backup'),
			'title'=>I18N::T('cloud', '备份下载'),
			]);

		/* 暂时隐藏未实现的功能(xiaopei.li@2012-02-20)
		->add_tab('network', array(
			'url'=>URI::url('admin/cloud.network'),
			'title'=>I18N::T('cloud', '远程维护'),
		))
		->add_tab('update', array(
			'url'=>URI::url('admin/cloud.update'),
			'title'=>I18N::T('cloud', '可用更新'),
		))
		*/

		$tabs->content->secondary_tabs = $secondary_tabs;

		$params = Config::get('system.controller_params');
		$tabs->content->secondary_tabs->select($params[1]);
	}

	static function _secondary_network_content($e, $tabs){
		$tabs->content = V('cloud:admin/network');

	}

	static function _secondary_update_content($e, $tabs) {
		$tabs->content = V('cloud:admin/update');
	}

	static function _secondary_backup_content($e, $tabs) {
		$latest_path = self::get_latest_backup_path();
		$tabs->content = V('cloud:admin/backup', ['latest_path' => $latest_path]);
	}

	static function get_latest_backup_path() {
		$latest_path = Core::file_exists(PRIVATE_BASE . '/latest_backup.tgz');

		return $latest_path;
	}
}