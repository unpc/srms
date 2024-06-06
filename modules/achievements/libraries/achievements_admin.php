<?php

class Achievements_Admin
{
    public static function setup()
    {
        if (L('ME')->access('添加/修改所有实验室成果')) {
            Event::bind('admin.index.tab', 'Achievements_Admin::_primary_tab');
        }
    }

    public static function _primary_tab($e, $tabs)
    {
        if (!Event::trigger('db_sync.need_to_hidden', 'achievements')){
            Event::bind('admin.index.content', 'Achievements_Admin::_primary_content', 0, 'achievements');
            $tabs->add_tab('achievements', [
                'url'    => URI::url('admin/achievements'),
                'title'  => I18N::T('achievements', '成果管理'),
                'weight' => 50,
            ]);
        }
    }
    public static function _primary_content($e, $tabs)
    {
        Controller::$CURRENT->add_js('tag_sortable');
        $params = Config::get('system.controller_params');
        $name=$params[1]?:'achievements_publication';
        $tabs->content=V('admin/view');
        $secondary_tabs = Widget::factory('tabs');
        $uniqid="tag_".uniqid();
        $url=URI::url('tags');

        Event::bind('admin.achievements.tab', 'Achievements_admin::achievements_tab');

        $root = Tag_Model::root($name);

        $tabs->content->secondary_tabs = $secondary_tabs
        ->tab_event('admin.achievements.tab')
        ->select($name);

        $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => []]);

        $tabs->content->secondary_tabs->content = V('achievements:admin/tags', ['root'=>$root,'uniqid'=>$uniqid, 'button_title' => $root->name]);

        
    }
   
    public static function achievements_tab($e,$tabs){
        $tabs
        ->set('class', 'secondary_tabs')
        ->add_tab('achievements_publication', [
            'url'   => URI::url('admin/achievements.achievements_publication'),
            'title' => I18N::T('achievements', '文献标签'),
        ])
        ->add_tab('achievements_award', [
            'url'   => URI::url('admin/achievements.achievements_award'),
            'title' => I18N::T('achievements', '奖项标签'),
        ])
        ->add_tab('achievements_patent', [
            'url'   => URI::url('admin/achievements.achievements_patent'),
            'title' => I18N::T('achievements', '专利标签'),
        ]);
    }

}
