<?php

class Gather_Equipment
{

    public static function setup()
    {
        Event::bind('equipments.primary.tab', 'Gather_Equipment::university_tab', 0, 'university');
        Event::bind('equipments.primary.content', 'Gather_Equipment::university_tab_content', 0, 'university');
    }

    public static function university_tab($e, $tabs)
    {
        $me = L('ME');

        if ($me->access('管理所有内容')) {
            $tabs->add_tab('university', [
                'url'    => URI::url('!equipments/extra/university'),
                'title'  => I18N::T('equipments', '高校列表'),
                'weight' => 999,
            ]);
        }
    }

    public static function university_tab_content($e, $tabs)
    {
        $params = Config::get('system.controller_params');

        $list = Config::get('university.list');

        $data  = [];
        $count = 0;
        foreach ($list as $id => $name) {
            $count       = Q("equipment[source_name=$id]")->total_count();
            $data[$name] = $count;
        }

        $tabs->content = V('gather:university/list', [
            'data' => $data,
        ]);
    }

    public static function equipment_list_columns($e, $form, $columns)
    {
        unset($columns['current_user']);
        unset($columns['rest']);
        $columns['university'] = [
            'title'  => '所属站点',
            'nowrap' => true,
            'filter' => [
                'form'  => V('gather:gather_table/filters/university', ['form' => $form]),
                'value' => H(Config::get('university.list')[$form['university']]),
                'field' => 'university',
            ],
            'weight' => 21,
        ];
        return true;
    }

    public static function equipment_list_row($e, $row, $equipment)
    {
        unset($row['rest']);
        $row['university'] = Config::get('university.list')[$equipment->source_name];
        return true;
    }

    public static function equipment_extra_selector($e, $form, $selector, $pre_selectors)
    {
        if ($form['university']) {
            $selector .= "[source_name={$form['university']}]";
        }

        $e->return_value = $selector;
        return false;
    }

    public static function eq_reserv_extra_selector($e, $form, $selector)
    {
        if ($form['university']) {
            $selector .= "[source_name={$form['university']}]";
        }

        $e->return_value = $selector;
    }

    public static function eq_sample_extra_selector($e, $selector, $form, $pre_selectors)
    {
        if ($form['university']) {
            $selector .= "[source_name={$form['university']}]";
        }

        $e->return_value = $selector;
    }

    public static function eq_record_extra_selector($e, $form, $selector, $pre_selectors = [])
    {
        if ($form['university']) {
            $selector .= "[source_name={$form['university']}]";
        }

        $e->return_value = $selector;
    }

    public static function get_export_record_columns($e, $columns, $type)
    {
        $columns['university'] = '所属站点';
        $e->return_value = $columns;
        return true;
    }

    public static function export_record_columns($e, $columns) 
    {
        $columns['university'] = '所属站点';
        $e->return_value = $columns;
        return true;
    }

    public function eq_record_list_columns($e, $form, $columns)
    {
        unset($columns['rest']);
        $columns['university'] = [
            'title'  => '所属站点',
            'nowrap' => true,
            'weight' => 1.5,
        ];
        return true;
    }

    public static function eq_record_list_row($e, $row, $record)
    {
        $row['university'] = Config::get('university.list')[$record->source_name];
        return true;
    }

    public function eq_sample_list_columns($e, $form, $columns)
    {
        unset($columns['rest']);
        $columns['university'] = [
            'title'  => '所属站点',
            'nowrap' => true,
            'weight' => 15,
        ];
        return true;
    }

    public static function eq_sample_list_row($e, $row, $sample)
    {
        $row['university'] = Config::get('university.list')[$sample->source_name];
        return true;
    }

    public function eq_reserv_list_columns($e, $form, $columns)
    {
        unset($columns['rest']);
        $columns['university'] = [
            'title'  => '所属站点',
            'nowrap' => true,
            'weight' => 0,
        ];
        return true;
    }

    public static function eq_reserv_list_row($e, $row, $sample)
    {
        $row['university'] = Config::get('university.list')[$sample->source_name];
        return true;
    }

    public static function people_list_columns($e, $form, $columns)
    {
        $columns['university'] = [
            'title'  => '所属站点',
            'nowrap' => true,
            'filter' => [
                'form'  => V('gather:gather_table/filters/university', ['form' => $form]),
                'value' => H(Config::get('university.list')[$form['university']]),
                'field' => 'university',
            ],
            'weight' => 85,
        ];
        return true;
    }

    public static function people_list_row($e, $row, $user)
    {
        $row['university'] = Config::get('university.list')[$user->source_name];
        return true;
    }

    public static function people_extra_selector($e, $form, $selector, $pre_selectors)
    {
        if ($form['university']) {
            $selector .= "[source_name={$form['university']}]";
        }

        $e->return_value = $selector;
        return true;
    }

    public static function lab_list_columns($e, $form, $columns)
    {
        $columns['university'] = [
            'title'  => '所属站点',
            'nowrap' => true,
            'filter' => [
                'form'  => V('gather:gather_table/filters/university', ['form' => $form]),
                'value' => H(Config::get('university.list')[$form['university']]),
                'field' => 'university',
            ],
            'weight' => 15,
        ];
        return true;
    }

    public static function lab_list_row($e, $row, $lab)
    {
        $row['university'] = Config::get('university.list')[$lab->source_name];
        return true;
    }

    public static function lab_extra_selector($e, $form, $selector, $pre_selectors)
    {
        if ($form['university']) {
            $selector .= "[source_name={$form['university']}]";
        }

        $e->return_value = $selector;
    }

    public static function info_api_extra($e, $equipment, $data)
    {
        $data['source_name'] = !$equipment->source_name ? LAB_ID : $equipment->source_name;
        $e->return_value = $data;
        return FALSE;
    }

}
