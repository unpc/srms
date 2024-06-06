<?php

class Index_Controller extends Base_Controller {

	function index( $tab = 'normal' ) {

		$selector = 'lims_account';

		$form = Lab::form();

		if ($form['name_or_id']) {
			$name_or_id = Q::quote($form['name_or_id']);
			$selector .= "[lab_name*=$name_or_id | lab_id*=$name_or_id | code_id*=$name_or_id]";
		}
		if ($form['type']) {
			$type = Q::quote($form['type']);
			$selector .= "[type=$type]";
		}

		$secondary_tabs = Widget::factory('tabs');
		$secondary_tabs
			->add_tab('normal', [
						  'url' => URI::url('!accounts/index.normal'),
						  'title' => I18N::T('accounts', '正常站点'),
						  ])
			->add_tab('deleted', [
						  'url' => URI::url('!accounts/index.deleted'),
						  'title' => I18N::T('accounts', '已删除站点'),
						  ])
			->set('class', 'secondary_tabs');

		switch($tab) {
		case 'deleted':
			$secondary_tabs->select('deleted');
			$selector .= '[status=' . LIMS_Account_Model::STATUS_DELETED .']';
			break;
		case 'normal':
		default:
			$secondary_tabs->select('normal');
			$selector .= '[status=' . LIMS_Account_Model::STATUS_NORMAL .']';
			break;
		}

		$accounts = Q($selector);
		$available_types = LIMS_Account_Model::get_available_type_titles();

		$start = (int) $form['st'];
		$per_page = 25;

		$pagination = Lab::pagination($accounts, $start, $per_page);

		$tabs = $this->layout->body->primary_tabs;

		if (!($_SESSION['system.unlisted_messages'] || Lab::$messages)) {

			$notification = Event::trigger('accounts.notification.message');

			if ($notification) {
				Lab::message(Lab::MESSAGE_NORMAL, $notification);
			}
		}

		$tabs->select('list');

		$tabs->content = V('accounts', [
			'secondary_tabs' => $secondary_tabs,
			'accounts' => $accounts,
			'form' => $form,
			'pagination' => $pagination,
			'available_types' => $available_types,
		]);
	}
}