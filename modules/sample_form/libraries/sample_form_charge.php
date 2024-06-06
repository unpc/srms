<?php
class Sample_Form_Charge
{
    public static function on_eq_record_saved($e, $record, $old_data, $new_data)
    {
        $equipment = $record->equipment;
        $me = L('ME');
        $element = $new_data['sample_element'];
        $user = $element->user;

        $charge = O('eq_charge', ['source'=>$element]);

        // 如果没有检测记录计费脚本，且检测不计费，则不进行使用计费
        if (!$equipment->charge_script['sample_form']) {
            //如果从计费状态切换为不计费，编辑使用记录,如果charge存在且没锁应该将原来的收费删除
            if ($charge->id && !$charge->is_locked) {
                $charge->delete();
            }
        } else {
            $condition = $new_data['sample_element'] && $new_data['sample_element'] != $old_data['sample_element'];

            if ($condition && $user->id) {
                $charge->equipment = $equipment;
                $charge->source = $element;
                $lab = Q("{$user} lab")->current();
                $charge->lab = $lab;
                $charge->user = $user;
                $charge->calculate_amount()->save();
            }
            if ($old_data['sample_element']) {
                $old_charge = O('eq_charge', ['source'=>$old_data['sample_element']]);
                if ($old_charge->id) {
                    $old_charge->calculate_amount()->save();
                }
            }
        }
    }

    public static function on_eq_record_deleted($e, $record)
    {
        $element = $record->sample_element;
        if (!$element->id) {
            return true;
        }

        $config = Config::get('rpc.sample_form');
        $rpc = new RPC($config['url']);
        $rpc->set_header([
            "CLIENTID: {$config['client_id']}",
            "CLIENTSECRET: {$config['client_secret']}"
        ]);
        try {
            $rpc->element->SetSampleElement(
                $element->remote_id,
                [
                    'result' => '',
                    'status' => Sample_Element_Model::STATUS_DOING
                ]
            );
        } catch (Exception $e) {
            return false;
        }

        $charge = O('eq_charge', ['source'=>$element]);
        if ($charge->id) {
            $charge->calculate_amount()->save();
        }

        $element->count = 0;
        $element->status = Sample_Element_Model::STATUS_DOING;
        $element->result = null;
        $element->save();
    }
}
