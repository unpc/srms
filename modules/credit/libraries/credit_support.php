<?php

class Credit_Support
{
    public static function setup($e, $controller, $method, $params)
    {
        Event::bind('admin.support.tab', ['Credit_Support', '_credit_tab'], 15, 'credit');
        Event::bind('admin.support.content', ['Credit_Support', '_credit_content'], 15, 'credit');
    }

    public static function _credit_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('credit', [
            'url' => URI::url('admin/support.credit'),
            'title' => I18N::T('support', '信用分'),
            'weight' => 0,
        ]);
    }

    public static function _credit_content($e, $tabs)
    {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) {
            return;
        }

        $select = $tabs->selected;

        $tabs->content = V('credit:admin/credit/update');
    }
}
