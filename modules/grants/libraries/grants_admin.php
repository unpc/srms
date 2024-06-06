<?php
class Grants_Admin {

    static function setup() {
        if (L('ME')->access('管理所有经费')) Event::bind('admin.index.tab', 'Grants_Admin::_primary_tab');
    }

    static function _primary_tab($e, $tabs) {
        Event::bind('admin.index.content', 'Grants_Admin::_primary_content', 0, 'grants');

        $tabs->add_tab('grants', [
            'url'=> URI::url('admin/grants'),
            'title'=> I18N::T('grants', '经费管理')
        ]);
    }

    static function _primary_content($e, $tabs) {
        $tabs->content = V('admin/view');

        Event::bind('admin.grants.content', 'Grants_Admin::_secondary_notification_content', 1, 'notification');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->add_tab('notification', [
                'url'=> URI::url('admin/grants.notification'),
                'title'=> I18N::T('grants', '消息提醒')
            ])
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.grants.tab')
            ->content_event('admin.grants.content')
            ->select($params[1]);

            Controller::$CURRENT->add_css('grants:admin');
    }

    static function _secondary_notification_content($e, $tabs) {

        $configs = (array) Config::get('notification.grants_admin.content');

        $vars = [];
        $form = Form::filter(Input::form());
        if($form['submit']){
            $form
                ->validate('title', 'not_empty', I18N::T('people', '消息标题不能为空！'))
                ->validate('body', 'not_empty', I18N::T('people', '消息内容不能为空！'));

            $vars['form'] = $form;

            if ($form->no_error && in_array($form['type'], $configs)) {
                $config = Lab::get($form['type'], Config::get($form['type']));
                $tmp = [
                        'description'=>$config['description'],
                        'strtr'=>$config['strtr'],
                        'title'=>$form['title'],
                        'body'=>$form['body']
                ];

                foreach(Lab::get('notification.handlers') as $k=>$v){
                    if (isset($form['send_by_'.$k])){
                        $value = $form['send_by_'.$k];
                    }
                    else{
                        $value = 0;
                    }
                    $tmp['send_by'][$k] = $value;
                }
                Lab::set($form['type'], $tmp);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('grants', '内容修改成功'));
            }
        }
        elseif ($form['restore']) {
                Lab::set($form['type'], NULL);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('grants', '恢复系统默认设置成功'));
            }

        $views = Notification::preference_views($configs, $vars, 'people');
        $tabs->content = $views;
    }
}
