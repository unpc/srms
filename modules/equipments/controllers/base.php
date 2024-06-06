<?php

abstract class Base_Controller extends Layout_Controller {

    function _before_call($method, &$params){

        parent::_before_call($method, $params);

        $this->layout->title = I18N::T('equipments', '仪器管理');
        $this->layout->body = V('body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        $me = L('ME');
        if (!$me->is_allowed_to('列表', 'equipment')) {
            URI::url('error/404');
        }

        $this->layout->body->primary_tabs
            ->add_tab('list', [
                'url'=>URI::url('!equipments/index'),
                'title'=>I18N::T('equipments', '仪器列表'),
                'weight' => -999
            ]);

        // all
//        if ($me->is_allowed_to('列表所有仪器使用记录', 'equipment')) {
//            $this->layout->body->primary_tabs
//                ->add_tab('records', [
//                    'url'=>URI::url('!equipments/records/index'),
//                    'title'=>I18N::T('equipments', '所有仪器的使用记录'),
//                ]);
//        }

        $this->layout->body->primary_tabs
            ->tab_event('equipments.primary.tab', $params)
            ->content_event('equipments.primary.content', $params)
            ->tool_event('equipments.primary.tool_box');

        $this->add_css('equipments:common');


    }
}
