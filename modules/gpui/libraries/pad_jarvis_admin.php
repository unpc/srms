<?php

class Pad_Jarvis_Admin {

    public static function setup()
    {
        if ( L('ME')->access('管理所有内容') ) {
            Event::bind('admin.index.tab', 'Pad_Jarvis_Admin::primary_tab');
        }
    }

    //系统设置primary tab
    public static function primary_tab($e, $tabs)
    {
        if ( L('ME')->access('管理所有内容') ) {
            Event::bind('admin.index.content', 'Pad_Jarvis_Admin::primary_content', 0, 'watcher');

            $tabs->add_tab('watcher', [
                'url' => URI::url('admin/watcher'),
                'title' => I18N::T('gpui', '多媒体设置'),
            ]);
        }
    }

    public static function primary_content($e, $tabs)
    {
        if ( !L('ME')->access('管理所有内容') ) {
            URI::redirect('error/401');
        }
        $form = Lab::form();

        $selector = "equipment";

        if ($form['name']) {
            $name = Q::quote(H($form['name']));
            $selector .= "[name*={$name}]";
        }

        if ($form['ref_no']) {
            $ref_no = Q::quote(H($form['ref_no']));
            $selector .= "[ref_no*={$ref_no}]";
        }

        $selector .= ":sort(is_using D, is_monitoring D, name_abbr A)";

        $equipments = Q($selector);

        $pagination = Lab::pagination($equipments, $form['st'], 20);

        $tabs->content = V('gpui:list', [
            'pagination' => $pagination,
            'equipments' => $equipments,
            'form' => $form
        ]);
    }

    public static function equipment_links($e, $equipment, $links, $mode)
    {
        if (L('ME')->access('管理所有内容')) {
            if ($mode == 'watcher' || $mode == 'view') {
                $links['watcher'] = [
                    'url' => '#',
                    'text' => I18N::T('gpui', '查看验证码'),
                    'tip' => I18N::T('gpui', '查看验证码'),
                    'extra' => 'class="button button_view" q-src="'.URI::url('!gpui').'" q-object="watcher_view" q-event="click" q-static="'.H(['id' => $equipment->id]).'"'
                ];

                $e->return_value = $links;
                return TRUE;
            }
        }
    }

}