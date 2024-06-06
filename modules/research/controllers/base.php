<?php
class Base_Controller extends Layout_Controller
{
    function _before_call($method, &$params) {
        $me = L('ME');
        parent::_before_call($method, $params);
        $this->layout->body = V('body');
        $this->layout->body->primary_tabs= Widget::factory('tabs');
        $this->layout->body->primary_tabs
            ->add_tab('list', [
                'url'=>URI::url('!research'),
                'title'=>I18N::T('research', '服务列表'),
            ]);
        if ($me->is_allowed_to('管理所有科研服务记录','research')){
            $this->layout->body->primary_tabs->add_tab('all', [
                'url'=>URI::url('!research/research/all'),
                'title'=>I18N::T('research', '所有科研服务明细'),
            ]);
        }
        $this->add_css('equipments:common');
    }
}
