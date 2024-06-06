<?php 
class EQ_Charge_Confirm {
    static function is_accessible($e, $name) {
        $me = L('ME');
        if ($me->is_allowed_to('收费确认', 'equipment'))  {
            $e->return_value = TRUE;
            return FALSE;
        }
        $e->return_value = FAlSE;
        return TRUE;
    }

    static function confirm_ACL($e, $user, $action, $equipment, $options) {
		switch ($action) {
            case '收费确认':
                $equipments = Q("{$user}<incharge equipment")->total_count();
                if ($equipments > 0 && $user->access('确认负责仪器的收费')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            default:
                break;
		}
    }

    static function charge_confirm_ACL($e, $user, $action, $charge, $options) {
		switch ($action) {
            case '确认':
                $e->return_value = Equipments::user_is_eq_incharge($user, $charge->equipment) && $user->access('确认负责仪器的收费');
                break;
            default:
                break;
		}
    }

    static function object_is_locked($e, $object, $params) {
        list($action, ) = $params;

        if ($action == 'feedback') {
            $e->return_value = FALSE;
            return FALSE;
        }

        $charge = O('eq_charge', ['source' => $object]);
        if (!$charge->transaction_id && $object->name() == 'eq_record') {
            $charge = O('eq_charge', ['source' => $object->reserv]);
        }

        if ($charge->id && $charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_INCHARGE) {
            $e->return_value = TRUE;
            return FALSE;
        }
        // 20221703 陕西师范大学智能计费同一条使用记录生成2条计费条目
        // 当预约关联的使用记录的使用收费被收费确认的时候，代表该条预约被锁定了
        if ($object->id && $object->name() == 'eq_reserv') {
            //找到预约关联的所有使用记录
            foreach (Q("eq_record[reserv_id=$object->id]") as $record) {
                 //找到使用记录的使用收费
                $charge = O('eq_charge', ['source' => $record]);
                if ($charge->id && $charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_INCHARGE) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
    }

    static function eq_charge_links($e, $charge, $links, $mode) {

        if ($charge->confirm == EQ_Charge_Confirm_Model::CONFIRM_PENDDING
            && L('ME')->is_allowed_to('确认', $charge)) {

            $links['confirm'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">'.I18N::T('eq_charge', '确认').'</span>',
                'tip' => I18N::T('eq_charge', '确认'),
                'extra' => 'class="blue" q-src="' . URI::url('!eq_charge_confirm/confirm') .
                    '" q-static="id=' . $charge->id . '" q-event="click" q-object="charge_confirm"',
            ];
        }
    }

    static function on_eq_charge_saved($e, $charge, $old_data, $new_data) {
        if ($old_data['id'] || $old_data['confirm'] == $new_data['confirm']) return;
        Event::trigger('eq_charge.confirmed', $charge);
    }

    //传入对象$object为eq_record
	static function record_ACL($e, $me, $perm_name, $object, $options) {
        switch ($perm_name) {
            case '反馈':
                $charge = O('eq_charge', ['source' => $object]);
                if (Config::get('eq_record.feedback_need_samples')) {
                    if ($charge->id && $charge->confirm) {
                        $e->return_value = FALSE;
                        return FALSE;
                    }
                }
                
                break;
        }
        return TRUE;
    }

    public static function record_is_locked($e, $record)
    {
        if ($record->reserv->id) {
            if ($record->reserv->is_locked()) {
                $e->return_value = TRUE;
                return FALSE;
            }
        }
    }
}
