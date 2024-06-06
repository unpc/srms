<?php

class Support
{

	static function _index_admin_tab($e, $tabs) {
        if (in_array(L('ME')->token, Config::get('lab.admin'))
            && !Event::trigger('db_sync.need_to_hidden', 'support')) {
            Event::bind('admin.index.content', ['Support', '_index_support_tab'], 0, 'support');
            $tabs
                ->add_tab('support', [
                    'title'  => T('技术支持'),
                    'url'    => URI::url('admin/support'),
                    'weight' => 70,
                ]);
        }
    }

    public static function _index_support_tab($e, $tabs)
    {
        $tabs->content = V('admin/view');
        $tabs->content->set('withoutToolbox', true);
        $configs = Config::get('support');
        $secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs');

        if (Module::is_installed('uno')) {
            foreach (['system', 'online'] as $key) {
                unset($configs["$key"]);
            }
        }
        
        foreach ($configs as $key => $value) {
            if ($key == 'i18n' && Event::trigger('db_sync.need_to_hidden', 'i18n')) continue;
            Event::bind('admin.support.content', ['Support', '_index_support_content'], 10, $key);
            $secondary_tabs->add_tab($key, [
                'url'    => URI::url('admin/support.' . $key),
                'title'  => T($value['name']),
                'weight' => 10,
            ]);
        }

        $secondary_tabs->tab_event('admin.support.tab')
            ->content_event('admin.support.content');

        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs = $secondary_tabs;
        $tabs->content->secondary_tabs->select($params[1]);
    }

    public static function _index_support_content($e, $tabs)
    {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }

        $select  = $tabs->selected;
        $configs = Config::get('support.' . $select)['items'];
        $form    = Form::filter(Input::form());
        try {
            if ($form['submit']) {
                if ($select == 'i18n') I18N::clear_cache();
                if ($configs) {
                    foreach ($configs as $prekey => $subconfigs) {
                        // TODO: 定期备份support
                        if ($subconfigs['require_module'] && !Module::is_installed($subconfigs['require_module'])) continue;
                        foreach ($subconfigs['subitems'] as $key => $item) {
                            // TODO: css页面显示
                            Event::trigger('admin.support.submit', $prekey, $key, $form[$prekey.'_'.$key], $form);
                            Log::add(strtr('[support] %user[%id]修改了系统设置：%name1-%name2-%name3为%value', [
                                '%user' => L('ME')->name,
                                '%id' => L('ME')->id,
                                '%name1' => Config::get('support.'.$select)['name'],
                                '%name2' => $subconfigs['subname'],
                                '%name3' => $item['name'],
                                '%value' => $form[$prekey . '_' . $key],
                            ]), 'support');
                        }
                    }

                    if ($select == 'lab_signup' && Module::is_installed('uno')) {
                        $lab_signup_outside_fields = COnfig::get('support.lab_signup')['items']['outside']['subitems'];
                        foreach ($lab_signup_outside_fields as $key => $lsof) {
                            $custom_field = [
                                'key' => $key,
                                'name' => $lsof['title'],
                                'type' => 'text',
                                'object' => 'group',
                                'unique' => false,
                                'indexable' => false,
                                'nullable' => true,
                                'listable' => true,
                                'ui_type' => 'input',
                                'fit_type' => [5] // 分组类型 - 课题组
                            ];
                            $res = Gateway::postCustomField($custom_field);
                        }
                    }
                }
                Event::trigger('config_sync_slave_site', $select, $form);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipment', '设置更新成功'));
            }
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipment', '设置更新失败'));
        }
        $tabs->content = V('support:tab_content', ['configs' => $configs, 'form' => $form]);
    }

    public static function online_kf5($e)
    {
        if (Lab::get('online.kf5')) {
            $me              = L('ME');
            $e->return_value = (string) V('application:online/kf5', ['me' => $me]);
        }
    }

    static function lab_view_calculate_account($e, $links, $lab_id) {
        
        // 同步计费的站点不能【重新计算课题组财务信息】
        if (in_array('eq_charge.save', (array) Config::get('sync.receive_topics'))) {
            return false;
        }

        if (in_array(L('ME')->token, Config::get('lab.admin'))) {
            $links['calculate_account'] = [
                'url'   => URI::url('!support/index'),
                'text' => I18N::T('support', '重新计算课题组财务信息'),
                'tip'   => I18N::T('support', '重新计算课题组财务信息'),
                'extra' => 'class="button icon-recharge" q-event="click" q-object="lab_calculate_account" q-src="' .
                H(URI::url('!support/index')) . '" q-static="' . H(['lab_id' => $lab_id]) . '"',
            ];
            $e->return_value = $links;
        }
    }
}
