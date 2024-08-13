<?php

class Index_Controller extends Base_Controller {

    public function index($tab = 'pending') {
        $me = L('ME');

        if (!$me->is_allowed_to('审核', 'eq_charge') && !$me->is_allowed_to('确认', 'eq_charge')) {
            $tab = Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_PRINT];
        }

        $this->layout->body->primary_tabs->select($tab);

        $form = Form::filter(Lab::form());

        switch ($tab) {
            case Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_PENDING]:
                $status = Neu_EQ_Charge_Model::CONFIRM_PENDING;
                $selector = "{$me}<incharge equipment eq_charge[amount!=0][confirm={$status}]";
                break;
            case Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_CONFIRM]:
                $status = Neu_EQ_Charge_Model::CONFIRM_CONFIRM;
                $selector = "{$me}<incharge equipment eq_charge[amount!=0][confirm={$status}]";
                break;
            case Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_PRINT]:
                $print_status = Neu_EQ_Charge_Model::CONFIRM_PRINT;
                $confirm_status = Neu_EQ_Charge_Model::CONFIRM_CONFIRM;
                $selector = "eq_charge[amount!=0][confirm={$print_status}, {$confirm_status}][user={$me}]";
                break;
        }

        if ($form['equipment']) {
            $equipment_id = Q::quote($form['equipment']);
            $selector .= "[equipment_id={$equipment_id}]";
        }

        if ($form['lab']) {
            $lab_id = Q::quote($form[lab]);
            $selector .= "[lab_id={$lab_id}]";
        }

        if ($form['user']) {
            $user_id = Q::quote($form['user']);
            $selector .= "[user_id={$user_id}]";
        }

        if ($form['dtstart_check']) {
            $dtstart = Q::quote(strtotime(date('Y-m-d', $form['dtstart'])));
            $selector .= "[ctime>={$dtstart}]";
        }

        if ($form['dtend_check']) {
            $dtend = Q::quote(strtotime(date('Y-m-d', $form['dtend'])));
            $selector .= "[ctime>0][ctime<={$dtend}]";
        }

        if ($form['charge_id']) {
            $charge_id = Q::quote($form['charge_id']);
            $selector .= "[transaction_id={$charge_id}]";
        }

        if ($tab == Neu_EQ_Charge_Model::$confirm[Neu_EQ_Charge_Model::CONFIRM_PRINT]) {
            $selector .= ':sort(confirm A, ctime D)';
        } else {
            $selector .= ':sort(ctime D)';
        }

        $charges = Q($selector);

        $pagination = Lab::pagination($charges, (int) $form['st'], 20);

        $this->layout->body->primary_tabs->content = V('charge_confirm:list', [
            'tab' => $tab,
            'form' => $form,
            'charges' => $charges,
            'pagination' => $pagination,
        ]);
    }

    public function print($id) {
        $me = L('ME');
        
        $charge = O('eq_charge', $id);

        if (!$me->is_allowed_to('打印', $charge)) {
            return FALSE;
        }
        
        $charge->confirm = Neu_EQ_Charge_Model::CONFIRM_CONFIRM;
        $charge->save();
        
        $this->layout = V('charge_confirm:print', [
            'charge' => $charge
        ]);
    }
}


class Index_AJAX_Controller extends AJAX_Controller {

    public function index_pending_click () {
        $me = L('ME');

        if (!$me->is_allowed_to('审核', 'eq_charge')) {
            return FALSE;
        }

        $form = Form::filter(Input::form());

        $id = $form['id'];
        $charge = O('eq_charge', $id);
        
        if (!$charge->id) {
            return FALSE;
        }

        if ($charge->confirm != Neu_EQ_Charge_Model::CONFIRM_PENDING) {
            Lab::message(LAB::MESSAGE_ERROR , I18N::T('charge_confirm', '该收费已被其他仪器负责人确认!'));
            JS::refresh();
            return FALSE;
        }

        if ($charge->source->name() == 'eq_reserv') {
            // 一个预约记录对应多个使用记录的情况下，只要有一个使用记录没有反馈，那么也不可以被确认收费
            $connect_records = Q("{$charge->source}<reserv eq_record[status=".EQ_Record_Model::FEEDBACK_NOTHING."]");
            if ($connect_records->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '当前预约记录关联的使用记录未反馈，不可确认收费!'));
                JS::refresh();
                return;
            }
        } elseif ($charge->source->name() == 'eq_record') {
            if ($charge->source->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '当前使用记录未反馈，不可确认收费!'));
                JS::refresh();
                return;
            }
        }

        $charge->confirm = Neu_EQ_Charge_Model::CONFIRM_PRINT;
        $charge->auditor = $me;
        $charge->rtime = time();

        if ($charge->save()) {
            Lab::message(LAB::MESSAGE_NORMAL , I18N::T('charge_confirm', '确认成功'));
            JS::refresh();
        }
    }

    public function index_confirm_click () {
        $me = L('ME');

        if (!$me->is_allowed_to('确认', 'eq_charge')) {
            return FALSE;
        }

        $form = Form::filter(Input::form());

        $id = $form['id'];
        $charge = O('eq_charge', $id);
        
        if (!$charge->id) {
            return FALSE;
        }

        if ($charge->confirm != Neu_EQ_Charge_Model::CONFIRM_CONFIRM) {
            Lab::message(LAB::MESSAGE_ERROR , I18N::T('charge_confirm', '该收费已被其他仪器负责人确认!'));
            JS::refresh();
            return FALSE;
        }

        $charge->confirm = Neu_EQ_Charge_Model::CONFIRM_DONE;
        if ($charge->save()) {
            Lab::message(LAB::MESSAGE_NORMAL , I18N::T('charge_confirm', '确认成功'));
            JS::refresh();
        }
    }
}
