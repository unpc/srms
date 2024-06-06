<?php

abstract class Base_Controller extends Layout_Controller {

    function _before_call($method, &$params) {

        parent::_before_call($method, $params);

        $me = L('ME');

        $this->layout->title = I18N::T('reserv_approve', '预约审核');
        $this->layout->body = V('body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        if ($me->is_allowed_to('审核', 'reserv_approve')) {
            $this->layout->body->primary_tabs
                ->add_tab('approve', [
                    'url' => URI::url('!reserv_approve/index.approve'),
                    'title' => I18N::T('reserv_approve', '我审核的'),
                ]);
        }
        $this->layout->body->primary_tabs
                ->add_tab('mine', [
                    'url' => URI::url('!reserv_approve/index.mine'),
                    'title' => I18N::T('reserv_approve', '我发起的'),
                ]);
        if ($me->is_allowed_to('查看全部', 'reserv_approve')) {
            $this->layout->body->primary_tabs
                ->add_tab('all', [
                    'url' => URI::url('!reserv_approve/index.all'),
                    'title' => I18N::T('reserv_approve', '所有预约审核'),
                ]);
        }
        $this->add_css("reserv_approve:common");
    }

}
