<?php

class Charge_Confirm {

    public static function object_is_locked($e, $object, $params) {
        $charge = O('eq_charge', ['source' => $object]);
        if ($object->name() == 'eq_record') {
            $reserv = $object->reserv;
            if ($reserv->id) {
                $c = O('eq_charge', ['source' => $reserv]);
                if ($c->id && $c->confirm != Neu_EQ_Charge_Model::CONFIRM_PENDING
                    && $c->confirm != Neu_EQ_Charge_Model::CONFIRM_EMPTY) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
        if ($charge->id) {
            if ($charge->confirm != Neu_EQ_Charge_Model::CONFIRM_PENDING
                && $charge->confirm != Neu_EQ_Charge_Model::CONFIRM_EMPTY) {
                $e->return_value = TRUE;
                return FALSE;
            }
            $e->return_value = FALSE;
            return TRUE;
        }
    }

    public static function links($tab, $charge) {
        $me = L('ME');
        $links = [];

        if ($tab == Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_PENDING] && $me->is_allowed_to('审核', $charge)) {
            $links['pending'] = [
                'url' => '#',
                'text' => I18N::T('charge_confirm', '确认'),
                'extra'=>' class="blue" q-event="click" q-object="pending" q-static="id=' . $charge->id . '" q-src="' . URI::url('!charge_confirm/index') . '"',
            ];
        }

        if ($tab == Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_CONFIRM] && $me->is_allowed_to('确认', $charge)) {
            $links['confirm'] = [
                'url' => '#',
                'text' => I18N::T('charge_confirm', '确认'),
                'extra'=>' class="blue" q-event="click" q-object="confirm" q-static="id=' . $charge->id . '" q-src="' . URI::url('!charge_confirm/index') . '"',
            ];
        }

        if ($tab == Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_PRINT] && $me->is_allowed_to('打印', $charge)) {
            $links['print'] = [
                'url' => URI::url('!charge_confirm/index/print.'.$charge->id),
                'text' => I18N::T('charge_confirm', '打印'),
                'extra'=>'class="blue" target="_blank"',
            ];
        }
        
        return $links;
    }

    public static function get_confirm_str($charge) {
        $equipment = $charge->equipment;
        $incharges = Q("$equipment<incharge user");
        if ($incharges->total_count() > 2) {
            $incharges = $incharges->limit(2);
            $text = '等';
        }
        $user = $charge->user;
        switch ($charge->confirm) {
            case Neu_EQ_Charge_Model::CONFIRM_EMPTY:
                $status = I18N::T('charge_confirm', "--");
                break;
            case Neu_EQ_Charge_Model::CONFIRM_PENDING:
                $status = I18N::T('charge_confirm', "待仪器负责人%next_user{$text}审核", [
                        '%next_user' => V('charge_confirm:next_user', ['users' => $incharges])
                    ]);
                break;
            case Neu_EQ_Charge_Model::CONFIRM_PRINT:
                $status = I18N::T('charge_confirm', '仪器负责人审核通过, 待用户 %next_user 打印', [
                        '%next_user' => '<a style="text-decoration: underline;" href="' . H($user->url()) . '" class="prevent_default">' . H($user->name) . '</a>'
                    ]);
                break;
            case Neu_EQ_Charge_Model::CONFIRM_CONFIRM:
                $status = I18N::T('charge_confirm', "%user 已打印转账单, <br/>待%next_user{$text}确认收到转账单", [
                    '%user' => '<a style="text-decoration: underline;" href="' . H($user->url()) . '" class="prevent_default">' . H($user->name) . '</a>',
                    '%next_user' => V('charge_confirm:next_user', ['users' => $incharges])
                ]);
                break;
            case Neu_EQ_Charge_Model::CONFIRM_DONE:
                $status = I18N::T('charge_confirm', '已完成');
                break;
        }
        return sprintf('<span>%s</span>', $status);
    }

    public static function charges_table_list_columns ($e, $form, $columns, $obj) {
        $me = L('ME');

        $columns['confirm'] = [
            'title' => '收费确认',
            'nowrap' => TRUE,
            'weight' => 31,
        ];

        return TRUE;
   }

    public static function charges_table_list_row ($e, $row, $charge, $obj) {
        $me = L('ME');

        $row['confirm'] = (string)V('charge_confirm:charges_table/data/confirm', ['c' => $charge]);
        
        $e->return_value = $row;
        return TRUE;
   }
}
