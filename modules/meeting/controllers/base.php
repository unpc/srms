<?php

abstract class Base_Controller extends Layout_Controller
{

    public function _before_call($method, &$params)
    {
        parent::_before_call($method, $params);
        $me                  = L('ME');
        $this->layout->title = I18N::T('meeting', '空间管理');
        $this->layout->body  = V('body');

        $this->layout->body->primary_tabs = Widget::factory('tabs')
            ->add_tab('index', [
                'url'   => URI::url('!meeting/index'),
                'title' => I18N::T('meeting', '空间列表'),
            ]);

        // if ($me->is_allowed_to('查看所有会议室预约', 'meeting')) {
        //     $this->layout->body->primary_tabs
        //         ->add_tab('reserv', [
        //             'url'   => URI::url('!meeting/reserv/index'),
        //             'title' => I18N::T('meeting', '所有会议室的预约'),
        //         ]);
        // }

        $this->add_css('meeting:common');
    }
}
