<?php

abstract class Base_Controller extends Layout_Controller
{
    public function _before_call($method, &$params)
    {
        parent::_before_call($method, $params);
        
        $this->layout->title = I18N::T('nfs', '文件系统');
        $this->layout->body = V('body');
        $me=L('ME');
        $primary_tabs = Widget::factory('tabs');
        $primary_tabs
                ->add_tab('mine', [
                    'url' => URI::url('!nfs_share/finder/user'),
                    'title'=>I18N::T('nfs', '我的个人分区'),
                    'weight' => 100,
                ])
                ->add_tab('index', [
                    'url'=>URI::url('!nfs_share/use'),
                    'title'=>I18N::T('nfs', '使用情况'),
                    'weight' => 200,
                ]);

        if (Module::is_installed('labs') && $me->is_allowed_to('查看各实验室分区', 'nfs_share')) {
            $primary_tabs
                ->add_tab('labs', [
                    'url'=>URI::url('!nfs_share/labs'),
                    'title'=>I18N::T('nfs', '各实验室分区'),
                    'weight' => 300,
                ]);
                
            $primary_tabs
                ->add_tab('people', [
                    'url'=>URI::url('!nfs_share/people'),
                    'title'=>I18N::T('nfs', '个人分区'),
                    'weight' => 400,
                ]);
        }

        if ($me->access('清理文件系统')) {
            $primary_tabs->add_tab('clean', [
                    'url'=>URI::url('!nfs_share/clean'),
                    'title'=>I18N::T('nfs', '清理设置'),
                    'weight' => 500,
            ]);
        }

        $primary_tabs->tab_event('nfs_share.primary.tab', $params);
        $primary_tabs->content_event('nfs_share.primary.content', $params);

        $this->layout->body->primary_tabs = $primary_tabs;

        Event::trigger('nfs_share.auto_open_all_lab');
        Event::trigger('nfs_share.auto_open_all_people');
    }
}
