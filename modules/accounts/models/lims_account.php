<?php

class LIMS_Account_Model extends Presentable_Model {

	const STATUS_NORMAL = 0;
	const STATUS_DELETED = 1;

	const LANGUAGE_ZH = 'zh_CN';
	const LANGUAGE_EN = 'en_US';

	static $language_type = [
		self::LANGUAGE_ZH => '中文',
		self::LANGUAGE_EN => '英文',
	];

	const CURRENCY_RMB = 'RMB';
	const CURRENCY_USD = 'dollar';

	static $currency = [
		self::CURRENCY_RMB => '人民币',
		self::CURRENCY_USD => '美元',
	];

	protected $object_page = [
		'view'=>'!accounts/account/index.%id[.%arguments]',
		'edit'=>'!accounts/account/edit.%id[.%arguments]',
		'delete'=>'!accounts/account/delete.%id',
		'recovery'=>'!accounts/account/recovery.%id',
	];

	static function get_available_types() {
		$available_types = (array) Config::get('accounts.available_types');

		return $available_types;
	}

	static function get_available_type_titles() {
		$available_types = self::get_available_types();
		$available_type_titles = [];
		foreach ($available_types as $type_id => $type_opts) {
			$available_type_titles[$type_id] = $type_opts['title'];
		}

		return $available_type_titles;
	}

	function &links($mode='index') {

		$links = [];

		switch($mode) {
		case 'view':
			if (L('ME')->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
//					'text' => I18N::T('accounts', '修改'),
					'tip' => I18N::T('accounts', '修改'),
					'text' => I18N::T('accounts', ''),
					'extra' => 'class="button button_edit"',
					];
			}
			if ($this->url) {
				$links['visit'] = [
					'url' => $this->url,
					'text'  => I18N::T('accounts', '访问站点'),
					'extra' => 'target="_blank" class="button button_view"',
					];
			}
			if ($this->archive_url) {
				$links['read_archive'] = [
					'url' => $this->archive_url,
					'text'  => I18N::T('accounts', '查看档案'),
					'extra' => 'target="_blank" class="button button_view"',
					];
			}
			break;
		case 'index':
			if (L('ME')->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text' => I18N::T('accounts', ''),
					'extra' => 'class=""',
					];
			}
			if ($this->url) {
				$links['visit'] = [
					'url' => $this->url,
					'text'  => I18N::T('accounts', '访问站点'),
					'extra' => 'target="_blank" class="blue"',
					];
			}
			if ($this->archive_url) {

				$links['read_archive'] = [
					'url' => $this->archive_url,
					'text'  => I18N::T('accounts', '查看档案'),
					'extra' => 'target="_blank" class="blue"',
					];
			}
			break;
		default:
		}

		return $links;
	}

	function save($overwrite=FALSE) {
		$this->touch();
		return parent::save();
	}

	function is_open() {
		$is_open = Event::trigger('lims_account.check_is_open', $this);
		return $is_open;
	}

}
