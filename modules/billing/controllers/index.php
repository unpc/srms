<?php

class Index_Controller extends Base_Controller {
	
	function index() {
		#ifdef (billing.single_department)
		if ($GLOBALS['preload']['billing.single_department']) {
			URI::redirect(URI::url('!billing/department'));
		}
		#endif
		#ifndef (billing.single_department)
		URI::redirect(URI::url('!billing/departments'));
		#endif
	}

	public function entries($tab) {
        $me = L("ME");

        if(!$me->id){
            URI::redirect('error/401');
        }

		$lab = Q("$me lab")->current();

		if (!$me->is_allowed_to('查看财务情况', $lab) || !Q("billing_account[lab={$lab}]")->total_count() > 0) {
			URI::redirect('error/401');
		}

        $this->layout->body = V('body_entries');
		$this->layout->title = '财务记录';
        $tabs = Widget::factory("tabs");
        $allow_tabs = [];

		if ($me->is_allowed_to('查看财务概要', $lab)) {
			Event::bind('lab.billing.entries.view.content', 'Billing_Account::lab_index_list', 0, 'list');
			Event::bind('lab.billing.entries.view.tool_box', 'Billing_Account::lab_department_view_tool', '0','list');
			
			$tabs
				->add_tab('list', [
					'url'=> URI::url('!billing/index/entries.list'),
					'title'=>I18N::T('billing', '组内汇总'),
				]);
				!$tab && $tab = 'list';
				$allow_tabs[] = 'list';
		}

		if ($me->is_allowed_to('列表收支明细', $lab)) {
			Event::bind('lab.billing.entries.view.content', 'Billing_Account::lab_index_transaction', 0, 'transaction');
			Event::bind('lab.billing.entries.view.tool_box', 'Billing_Account::lab_department_view_tool', '0','transaction');
			$tabs
				->add_tab('transaction', [
					'url'=> URI::url('!billing/index/entries.transaction'),
					'title'=>I18N::T('billing', '组内明细'),
				]);
				!$tab && $tab = 'transaction';
				$allow_tabs[] = 'transaction';
		}

        if (!in_array($tab, $allow_tabs)) {
            URI::redirect('error/401');
        }

		$tabs->lab = $lab;
		$tabs->account_type = $tab;

        $tabs
            ->tab_event('lab.billing.entries.view.tab')
            ->content_event('lab.billing.entries.view.content')
            ->tool_event('lab.billing.entries.view.tool_box')
            ->select($tab);

        $this->layout->body->primary_tabs = $tabs;
    }
}
