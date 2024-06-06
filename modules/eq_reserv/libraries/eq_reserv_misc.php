<?php
class EQ_Reserv_Misc
{
    public static function hide_calendar_add_button_when_equipment_accept_block_time($e, $calendar)
    {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;
            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;
        } catch (Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

        if ($calendar->parent->name() != 'equipment' || !$calendar->parent->accept_block_time) {
            return;
        }

        $e->return_value = true;
        return;
    }
}
