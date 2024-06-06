<?php

class EQ_Charge_Assist {

    static function sample_fee ($form) {
        if ($form['sample_custom_charge'] == 'on') {
            return $form['sample_amount'];
        }

        $me = L('ME');
        $user = O('user', $form['sender']);// 代开
        $user = $user->id ? $user : $me;

        $sample = O('eq_sample', $form['id']);
        $sample->sender = $user;
        $sample->lab = $user->lab;
        $sample->count = $form['count'];
        if ($form['equipment_id']) $sample->equipment = O('equipment', $form['equipment_id']);
        $sample->dtpickup = $form['dtpickup'];
        $sample->dtstart = $form['dtstart'];
        $sample->dtend = $form['dtend'];
        $sample->dtsubmit = $form['dtsubmit'];
        $sample->status = $form['status'] ? : EQ_Sample_Model::STATUS_APPLIED;
        $sample->success_samples = $form['success_samples'] ? : 0;

        $charge = O('eq_charge');
        $charge->source = $sample;
        $charge->user = $user;
        $charge->lab = $user->lab;
        $charge->equipment = $sample->equipment;

        $tags = [];
        foreach((array)$form['sample_charge_tags'] as $k => $v) {
            if ($v['checked'] == 'on') {
                $k = rawurldecode($k);
                $tags[$k] = $v['value'];
            }
        }
        $charge->charge_tags = $tags;

        //自定义送样表单传入供lua计算
        if (Module::is_installed('extra')) {
            $charge->source->extra_fields = (array)$form['extra_fields'];
        }

        $lua = new EQ_Charge_LUA($charge);

        $result = $lua->run(['fee']);
        return $result['fee'];
    }
    
    static function record_fee ($form) {
        if ($form['record_custom_charge'] == 'on') {
            return $form['record_amount'];
        }

        $me = L('ME');
        $user = O('user', $form['user_id']);// 代开
        $user = $user->id ? $user : $me;

        $record = O('eq_record', $form['record_id']);
        $record->user = $user;
        if ($form['equipment_id']) $record->equipment = O('equipment', $form['equipment_id']);
        $record->samples = $form['samples'];
        $record->dtstart = $form['dtstart'];
        $record->dtend = $form['dtend'];

        $charge = O('eq_charge');
        $charge->source = $record;
        $charge->user = $user;
        $charge->lab = $user->lab;
        $charge->equipment = $record->equipment;

        if ($form['charge_tags']) {
            $tags = [];
            foreach((array)$form['charge_tags'] as $k => $v) {
                if ($v['checked'] == 'on') {
                    $k = rawurldecode($k);
                    $tags[$k] = $v['value'];
                }
            }
        }
        $charge->charge_tags = $tags;

        $lua = new EQ_Charge_LUA($charge);
        
        $result = $lua->run(['fee']);
        return $result['fee'];
    }
        
    static function reserv_fee ($form) {
        $me = L('ME');
        $user = O('user', $form['organizer'] ? : $form['currentUserId']); // 代开
        $user = $user->id ? $user : $me;

        $reserv = $form['component_id'] 
        ? O('eq_reserv', ['component' => O('cal_component', $form['component_id'])]) 
        : O('eq_reserv');
        $reserv->user = $user;
        if ($form['equipment_id']) $reserv->equipment = O('equipment', $form['equipment_id']);
        $reserv->dtstart = $form['dtstart'];
        $reserv->dtend = $form['dtend'];

        $charge = O('eq_charge');
        $charge->source = $reserv;
        $charge->user = $user;
        $charge->lab = $user->lab;
        $charge->equipment = $reserv->equipment;

        if ($form['reserv_charge_tags']) {
            $tags = [];
            foreach((array)$form['reserv_charge_tags'] as $k => $v) {
                if ($v['checked'] == 'on') {
                    $k = rawurldecode($k);
                    $tags[$k] = $v['value'];
                }
            }
        }
        $charge->charge_tags = $tags;

        //自定义送样表单传入供lua计算
        if (Module::is_installed('extra')) {
            $charge->source->extra_fields = (array)$form['extra_fields'];
        }

        $lua = new EQ_Charge_LUA($charge);

        $result = $lua->run(['fee']);
        return $result['fee'];
    }

}