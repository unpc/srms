<?php

class Test_Project_Reserv
{
    static function eq_reserv_prerender_component($e, $view, $form)
    {
        $form = $e->return_value ?: $form;
        $component = $view->component;

        $form['test_project'] = [
            'label' => I18N::T('test_project', '测试项目'),
            'path' => ['form' => 'test_project:view/eq_reserv/'],
            'component' => $component,
        ];

        $form['#categories']['reserv_info']['items'][] = 'test_project';
        $e->return_value = $form;
        return TRUE;
    }
}
