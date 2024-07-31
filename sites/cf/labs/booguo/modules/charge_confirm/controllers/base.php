<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$me = L('ME');

		$this->layout->title = I18N::T('charge_confirm', '收费确认');
		$this->layout->body = V('body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        // 只有机主可以对收费进行审核和确认
		if ($me->is_allowed_to('审核', 'eq_charge')) {
			$this->layout->body->primary_tabs
				->add_tab('pending', [
					'url' => URI::url('!charge_confirm/index.pending'),
					'title' => I18N::T('charge_confirm', '待审核记录'),
                ]);
        }
        if ($me->is_allowed_to('确认', 'eq_charge')) {
			$this->layout->body->primary_tabs
				->add_tab('confirm', [
					'url' => URI::url('!charge_confirm/index.confirm'),
					'title' => I18N::T('charge_confirm', '转账单确认'),
				]);
        }

        // 所有用户都可以看到打印转账单的标签页
        $this->layout->body->primary_tabs
            ->add_tab('print', [
                'url' => URI::url('!charge_confirm/index.print'),
                'title' => I18N::T('charge_confirm', '打印转账单'),
            ]);
	}
}