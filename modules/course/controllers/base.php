<?php

abstract class Base_Controller extends Layout_Controller
{

    public function _before_call($method, &$params)
    {
        parent::_before_call($method, $params);
        $me                  = L('ME');
        $this->layout->title = I18N::T('course', '课程管理');
        $this->layout->body  = V('body');

        $this->layout->body->primary_tabs = Widget::factory('tabs')
            ->add_tab('index', [
                'url'   => URI::url('!course/index'),
                'title' => I18N::T('course', '课程列表'),
            ]);

        $this->add_css('course:common');
    }
}
