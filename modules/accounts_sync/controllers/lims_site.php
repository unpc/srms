<?php
class LIMS_Site_Controller extends Base_Controller {
	// 将仅在 dir 中的项目同步到 accounts 表中(xiaopei.li@2012-02-06)
	function dir_to_db() {
		$site_id = 'lab';

		$labs_only_in_dir = Accounts_Sync::scan_labs_only_in_dir($site_id);
		$labs_sync = [];
		$labs_failed = [];

		Accounts_Sync::sync_labs_to_db($site_id,
									   $labs_only_in_dir,
									   $labs_sync,
									   $labs_failed);

		if (count($labs_sync)) {
			$message = I18N::T('accounts_sync',
						   '已从系统目录中同步以下客户, 如有需要, 请补充信息: %labs_sync.',
						   ['%labs_sync' => join(', ', $labs_sync),
							   ]);
			Lab::message(Lab::MESSAGE_NORMAL, $message);
		}

		if (count($labs_failed)) {
			$message = I18N::T('accounts_sync',
						   '以下客户同步失败, 请向管理员通知此问题: %labs_failed.',
						   ['%labs_failed' => join(', ', $labs_failed),
							   ]);
			Lab::message(Lab::MESSAGE_ERROR, $message);
		}

		URI::redirect('!accounts');
	}
}

class LIMS_Site_Ajax_Controller extends AJAX_Controller {

	function index_open_click() {
		$form = Input::form();
		$account = O('lims_account', $form['id']);
		if (!$account->id) return;

        if (JS::confirm(I18N::T('accounts_sync', '您是否确认开通该LIMS客户的站点?'))) {
            $content = Accounts_Sync::site_open($account);

            Lab::message(Lab::MESSAGE_NORMAL, '<pre>' . $content . '</pre>');
            JS::refresh();
        }
	}

	function index_close_click() {
		$form = Input::form();
		$account = O('lims_account', $form['id']);
		if (!$account->id) return;

        if (JS::confirm(I18N::T('accounts_sync', '您是否确认关闭该LIMS客户的站点?'))) {
            $content = Accounts_Sync::site_close($account);

            Lab::message(Lab::MESSAGE_NORMAL, '<pre>' . $content . '</pre>');
            JS::refresh();
        }

		// JS::redirect($account->url());
	}

	/*
	function index_sync_click() {
		$form = Input::form();
		$account = O('lims_account', $form['id']);
		if (!$account->id) return;

		// $content = $account->sync();
		$content = Accounts_Sync::site_sync($account);

		Lab::message(Lab::MESSAGE_NORMAL, '<pre>' . $content . '</pre>');
		// JS::redirect($account->url());
	}

	function index_restore_click() {
		$form = Input::form();
		$account = O('lims_account', $form['id']);
		if (!$account->id) return;

		// $content = $account->restore();
		$content = Accounts_Sync::site_restore($account);

		Lab::message(Lab::MESSAGE_NORMAL, $content);
		// JS::redirect($account->url());
	}
	*/
}
