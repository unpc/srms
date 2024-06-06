<?php

class Test_Project_Sample
{
    static function eq_sample_prerender_add_form($e, $form, $equipment)
    {
        $e->return_value .= V('test_project:view/eq_sample/edit', ['form' => $form, 'equipment' => $equipment]);
    }

    static function eq_sample_prerender_edit_form($e, $sample, $form, $user)
    {
        $equipment = $sample->equipment;
        $e->return_value .= V('test_project:view/eq_sample/edit', ['form' => $form, 'sample' => $sample, 'equipment' => $equipment]);
    }

    static function eq_sample_form_submit($e, $sample, $form)
    {
        $selected_test_project = [];
        foreach ($form['test_project'] as $id => $val) {
            if ($val == 'on') {
                $selected_test_project[$id] = $form['test_project_number'][$id];
            }
        }
        $sample->test_projects = json_encode($selected_test_project);
    }

    static function sample_extra_print($e, $sample)
    {
        $e->return_value .= V("test_project:sample/extra_print", [
            'sample' => $sample,
        ]);
    }
}
