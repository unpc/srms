<?php
abstract class Base_Controller extends Layout_Controller {
	function _before_call($method, &$params){
	    //跳主站确认
	    if (Module::is_installed('db_sync') && DB_SYNC::is_slave()) {
	        URI::redirect(Event::trigger('db_sync.transfer_to_master_url'));
        }
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('eq_charge', '收费确认');
		$tabs = Widget::factory('tabs');

        $me = L('ME');

		if ($me->is_allowed_to('收费确认', 'equipment')) {
            $tabs->add_tab('confirm', [
                'url' => URI::url('!eq_charge_confirm/confirm'),
                'title' => I18N::T('eq_charge', '待审核记录'),
            ]);

        }

		$tabs->tab_event('charge_confirm.primary.tab', $params);
		$tabs->content_event('charge_confirm.primary.content', $params);
        
		$this->layout->body = V('body');
        $this->layout->body->primary_tabs = $tabs;
	}
}
