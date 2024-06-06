<?php

class EQ_Meter
{
    public static function setup_eq_meter()
    {
        Event::bind('equipment.index.tab', 'EQ_Meter::_view_eq_meter_tab', 0, 'eq_meter');
        Event::bind('equipment.index.tab.content', 'EQ_Meter::_view_eq_meter_content', 0, 'eq_meter');
    }

    public static function _view_eq_meter_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $eq_meter = Q("eq_meter[equipment={$equipment}]:limit(1)")->current();

        if (!$eq_meter->id) {
            return;
        }

        $tabs->add_tab('eq_meter', [
            'url'=>$equipment->url('eq_meter'),
            'title'=>I18N::T('eq_meter', 'Gmeter'),
            'weight' => 90,
        ]);
    }

    public static function _view_eq_meter_content($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $eq_meter = Q("eq_meter[equipment={$equipment}]:limit(1)")->current();
        if (!$eq_meter->id) {
            return;
        }

        $pre_selectors = [];
        $form = Lab::form();
        $current_controller = Controller::$CURRENT;
        $current_controller->add_css('eq_meter:jqplot eq_meter:common');
        $current_controller->add_js('eq_meter:jqplot eq_meter:jqplot.plugins/logaxisrenderer eq_meter:jqplot.plugins/textrenderer eq_meter:jqplot.plugins/highlighter eq_meter:jqplot.plugins/cursor');
        $tabs->content = V('eq_meter:equipment/stat', [
            'equipment'=>$equipment,
            'form'=>$form
        ]);
    }

    public static function api_eq_gmeter_connect_extra_keys($e, $info, $equipment)
    {
        $eq_meter = Q("{$equipment} eq_meter")->current();
        if (!$eq_meter->id) {
            return true;
        }

        $min = $eq_meter->watt_threshold_min;
        $max = $eq_meter->watt_threshold_max;
        if (is_null($min)) {
            $min = Config::get('eq_meter.watt_threshold_min', 40);
        }

        $info['threshold'] = array_map(
            function ($v) {
                return floatval($v);
            },
            [$min, $max]
        );
    }
}
