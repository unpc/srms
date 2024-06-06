<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('eq_stat','仪器统计');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$me = L('ME');
		if ($me->access('查看统计图表')) {
			$this->layout->body->primary_tabs
				->add_tab('chart', [
					'url'=>URI::url('!eq_stat/chart'),
					'title'=>I18N::T('eq_stat', '统计图表'),
				]);
		}	

        if ($me->is_allowed_to('列表统计', 'eq_stat')) {
            $this->layout->body->primary_tabs 
                ->add_tab('list', [
                    'url' =>URI::url('!eq_stat/list'),
                    'title'=> I18N::T('eq_stat', '统计列表'),
                ]); 
        }

		if ($me->is_allowed_to('列表', 'eq_perf')) {	
			$this->layout->body->primary_tabs	
				->add_tab('perfs', [
						'url'=>URI::url('!eq_stat/perfs'),
						'title' => I18N::T('eq_stat', '绩效评估'),
				]);
        }
	}
}
