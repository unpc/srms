<?php

abstract class Base_Controller extends Layout_Controller
{

    public function _before_call($method, &$params)
    {
        parent::_before_call($method, $params);

        $this->layout->title              = I18N::T('update', '更新消息');
        $this->layout->body               = V('body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $update_name                      = Config::get('lab.update_module_name');

        $this->layout->body->primary_tabs
            ->add_tab('all', [
                'url'   => URI::url('!update/'),
                'title' => I18N::T('update', '所有更新'),
            ])
            ->tab_event('update.index.tab');
    }
}
