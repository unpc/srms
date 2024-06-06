<?php

abstract class Base_Controller extends Layout_Controller
{
    public function _before_call($method, &$params)
    {
        parent::_before_call($method, $params);
		$this->add_css('credit:common');

        $me = L('ME');

        $this->layout->title = I18N::T('credit', '信用管理');
        $this->layout->body  = V('body');

        $this->layout->body->primary_tabs = Widget::factory('tabs');
        if ($me->is_allowed_to('查看列表', 'credit')) {
            $this->layout->body->primary_tabs
                ->add_tab('credit', [
                    'url'   => URI::url('!credit'),
                    'title' => I18N::T('credit', '成员信用'),
                ]);
        }

        if ($me->is_allowed_to('查看列表', 'credit_record')) {
            $this->layout->body->primary_tabs
                ->add_tab('credit_record', [
                    'url'   => URI::url('!credit/credit_record'),
                    'title' => I18N::T('credit', '信用明细'),
                ]);
        }

        if ($me->is_allowed_to('查看列表', 'eq_banned')) {
            $this->layout->body->primary_tabs
                ->add_tab('ban', [
                    'url'   => URI::url('!credit/ban'),
                    'title' => I18N::T('credit', '黑名单'),
                ]);
        }
    }
}
