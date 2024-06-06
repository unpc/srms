<?php

abstract class Base_Controller extends Layout_Controller {
    
    function _before_call($method, &$params){
        parent::_before_call($method, $params);

        $me = L('ME');
        if (!$me->is_allowed_to('列表', 'equipment')) {
            URI::url('error/404');
        }
        
        $this->layout->title = I18N::T('servant', '校院建设');
        $this->layout->body = V('body');

        $tabs = Widget::factory('tabs');
        $tabs->add_tab('pf', [
            'url' => URI::url('!servant/index/pf'),
            'title' => I18N::T('servant', '机构列表'),
        ]);
        $tabs->add_tab('my', [
            'url' => URI::url('!servant/index/pf.my'),
            'title' => I18N::T('servant', '我的机构'),
        ]);
        $tabs
            ->tab_event('servant.primary.tab', $params)
            ->content_event('servant.primary.content', $params);
        $this->layout->body->primary_tabs = $tabs;
    }

}
