<?php

class EQ_Meter_AJAX_Controller extends AJAX_Controller {
    function index_plot_fetch_data() {

        $plot_weight = Config::get('eq_meter.plot_weight', 10);

        $form = Input::form();
        $db = Database::factory();
        $equipment = O('equipment', $form['equipment_id']);
        $eq_meter = Q("eq_meter[equipment={$equipment}]:limit(1)")->current();
        if (!$eq_meter->id) return;
        if( !$form['xmin'] || !$form['xmax'] ) {
            $xmax = Date::time();
            $xmin = $xmax - 4*24*60*60;
        } else {
            $xmax = $form['xmax'];
            $xmin = $form['xmin'];
        }

        $pwidth = $form['width'];

        $spp = (int) max(1, ($xmax - $xmin) * $plot_weight / $pwidth);

        $eq_meter_data = [];
        $sql = "SELECT ctime, voltage, amp, watt, ROUND(ctime/%d, 0) atime FROM eq_meter_data WHERE eq_meter_id=%d AND ctime>=%d AND ctime<=%d AND watt>=0 GROUP BY atime";
        $sql2 = "SELECT MAX(voltage) max_voltage, MIN(voltage) min_voltage, MAX(amp) max_amp, MIN(amp) min_amp,  MAX(watt) max_watt, MIN(watt) min_watt FROM eq_meter_data WHERE eq_meter_id=%d AND ctime>=%d AND ctime<=%d";
        $points = $db->query($sql, $spp, $eq_meter->id, $xmin, $xmax);
        $obj_query = $db->query($sql2, $eq_meter->id, $xmin, $xmax);
        if(!is_object($points) || !is_object($obj_query)) return;
        
        $obj = $obj_query->row();
        $voltage_data = [];
        $amp_data     = [];
        $watt_data    = [];

        while( $row = $points->row() ) {
            $voltage_data[] = [(int)$row->ctime, (float)$row->voltage];
            $amp_data[]     = [(int)$row->ctime, (float)$row->amp];
            $watt_data[]    = [(int)$row->ctime, (float)$row->watt];
        }

        $type_arrays = ['watt', 'amp', 'voltage'];
        foreach ($type_arrays as $key => $value) {
            if ($value == 'watt') {
                $eq_meter_data[$key] = [
                    'data' => $watt_data,
                    'min'  => $obj->min_watt - ($obj->max_watt - $obj->min_watt),
                    'max'  => $obj->max_watt + ($obj->max_watt - $obj->min_watt),
                    'name' => I18N::T('eq_meter','功率')
                ];					
            }
            elseif ($value == 'amp') {
                $eq_meter_data[$key] = [
                    'data' => $amp_data,
                    'min'  => $obj->min_amp - ($obj->max_amp - $obj->min_amp),
                    'max'  => $obj->max_amp + ($obj->max_amp - $obj->min_amp),
                    'name' => I18N::T('eq_meter','电流'),
                ];					
            }
            elseif ($value == 'voltage') {
                $eq_meter_data[$key] = [
                    'data' => $voltage_data,
                    'min'  => $obj->min_voltage - ($obj->max_voltage - $obj->min_voltage),
                    'max'  => $obj->max_voltage + ($obj->max_voltage - $obj->min_voltage),
                    'name' => I18N::T('eq_meter','电压')
                ];	
            }
        }

        Output::$AJAX['curves'] = $eq_meter_data;
    }

    function index_edit_stat_click() {
        $form = Input::form();
        JS::dialog(V('eq_meter:bucket/edit_stat_dialog', ['equipment_id'=>$form['equipment_id'], 'watt_threshold_min'=>$form['watt_threshold_min'], 'watt_threshold_max'=>$form['watt_threshold_max']]), ['width'=> 420, 'title'=> I18N::T('eq_meter', '设置功率阈值')]);
    }

    function index_set_threshold_submit() {
        $form = Form::filter(Input::form());
        $eq_meter = O('eq_meter', ['equipment_id'=>$form['equipment_id']]);
        if ($eq_meter->id) {

            if ($form['watt_threshold_min'] && !(is_numeric($form['watt_threshold_min']) && $form['watt_threshold_min'] >= 0
                 && $form['watt_threshold_min'] != '-0')) {
                $form->set_error('watt_threshold_min', I18N::T('eq_meter', '最小阈值填写有误, 请填写大于等于0的数值!'));
            }

            if ($form['watt_threshold_max'] && !(is_numeric($form['watt_threshold_max']) && $form['watt_threshold_max'] >= 0 
            && $form['watt_threshold_max'] != '-0' )) {
                $form->set_error('watt_threshold_max', I18N::T('eq_meter', '最大阈值填写有误, 请填写大于等于0的数值!'));
            }

            if (!$form['watt_threshold_min'] && !$form['watt_threshold_max']) {
                $form->set_error('watt_threshold_max', NULL);
                $form->set_error('watt_threshold_min', NULL);
                $form->set_error('', I18N::T('eq_meter', '最小阈值和最大阈值不能都为空!'));
            }
            if ($form['watt_threshold_min'] == $form['watt_threshold_max']) {
                $form->set_error('watt_threshold_max', NULL);
                $form->set_error('watt_threshold_min', NULL);
                $form->set_error('', I18N::T('eq_meter', '最小阈值和最大阈值不能相等!'));
            }

            if ($form->no_error) {
                if ( $form['watt_threshold_max'] !== null && 
                    $form['watt_threshold_max'] !== '' 
                    && $form['watt_threshold_min'] > $form['watt_threshold_max']) {
                    list($watt_threshold_min, $watt_threshold_max) = [
                        $form['watt_threshold_max'],
                        $form['watt_threshold_min'],
                    ];
                }
                else {
                    $watt_threshold_min = $form['watt_threshold_min'];
                    $watt_threshold_max = $form['watt_threshold_max'];
                }
                
                $eq_meter->watt_threshold_min = $watt_threshold_min;
                $eq_meter->watt_threshold_max = $watt_threshold_max;
                if ($eq_meter->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_meter', '功率阈值设置成功!'));
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_meter', '功率阈值设置失败!'));
                }
                JS::refresh();
            }
            else {
                JS::dialog(V('eq_meter:bucket/edit_stat_dialog', ['equipment_id'=>$eq_meter->equipment->id, 'form'=>$form]), ['width'=>420, 'title'=> I18N::T('eq_meter', '设置功率阈值')]);
            }
        }
    }
    /*clear watt setting, and recover to default*/
    function index_clear_watt_setting_click() {
        $form = Input::form();
        if ($form['equipment_id']) {
            $eq_meter = O('eq_meter', ['equipment_id'=>$form['equipment_id']]);
            if ($eq_meter->id) {
                $watt_threshold_min = Config::get('eq_meter.watt_threshold_min', 40);
                $eq_meter->watt_threshold_min = $watt_threshold_min;
                $eq_meter->watt_threshold_max = null;
                if ($eq_meter->save()) {
                    JS::refresh();
                }
            }
        }
    }
}
