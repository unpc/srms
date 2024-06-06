<?php

class Confirm_Controller extends Base_Controller
{
    public function index()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('收费确认', 'equipment')) {
            $redirectUrl = Event::trigger('eq_charge_confirm.default_url');
            if ($redirectUrl) URI::redirect($redirectUrl);
            URI::redirect('error/401');
        }

        $form = Lab::form(function (&$old_form, &$form) {
          
        });
        if (!isset($form['confirm']) && Config::get('eq_charge_confirm.default_status')) {
            foreach (EQ_Charge_Confirm_Model::conforms() as $k => $v) {
                if($v == Config::get('eq_charge_confirm.default_status'))
                    $form['confirm'] = $k;
            }
        }
        $pre_selector = new ArrayIterator;
        $pre_selector['base'] = " {$me} equipment.incharge ";

        $selector = "eq_charge[amount!=0]";

        if ($form['equipment']) {
            $pre_selector['equipment'] = "equipment[name*=".$form['equipment']."]";
        }

        if ($form['lab']) {
            $lab_id = Q::quote($form['lab']);
            $selector .= "[lab_id={$lab_id}]";
        }

        if ($form['user']) {
            $user_id = Q::quote($form['user']);
            $selector .= "[user_id={$user_id}]";
        }

        if ($form['dtstart']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtstart']));
            $selector .= "[ctime>=$dtstart]";
        }
        
        if ($form['dtend']) {
            $dtend =  Q::quote(Date::get_day_end($form['dtend']));
            $selector .= "[ctime>0][ctime<={$dtend}]";
        }
        
        if (isset($form['confirm']) && $form['confirm'] != -1) {
            $confirm = Q::quote($form['confirm']);
            $selector .= "[confirm={$confirm}]";
        }

        $selector = (string)Event::trigger('eq_charge_confirm.extra.selector', $form, $selector) ? : $selector;

        Event::trigger('eq_charge_confirm.extra.pre_selector', $form, $pre_selector);

        $selector = count($pre_selector) ? '(' . join(',', (array)$pre_selector) . ') ' . $selector : $selector;
        $selector .= ':sort(ctime D)';
        
        $_SESSION['eq_charge_confirm_selector'] = $selector;
        $charges = Q($selector);

        $form_token = Session::temp_token('eq_charge_confirm_list_', 300);

        $_SESSION[$form_token] = ['selector' => $selector, 'form' => $form];

        $pagination = Lab::pagination($charges, (int) $form['st'], 20);
        
        
        $panel_buttons = new ArrayIterator;

        Event::trigger('eq_charge_confirm.extra.panel_buttons', $form, $form_token, $panel_buttons);

        $view = V('list/confirm', [
            'pagination' => $pagination,
            'charges' => $charges,
            'panel_buttons' => $panel_buttons,
            'form' => $form,
            'page' => (int) $form['st'],
            'page_number' => 20
        ]);
                       
        $this->layout->body->primary_tabs->select('confirm');
        $this->layout->body->primary_tabs->content = $view;
    }
}

class Confirm_AJAX_Controller extends AJAX_Controller
{
    public function index_charge_confirm_click()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $charge = O('eq_charge', $form['id']);
        if (!$me->is_allowed_to('确认', $charge)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '权限不足!'));
            JS::refresh();
            return;
        }

        if (!self::validate($charge)) {
            JS::refresh();
            return;
        }

        if (JS::Confirm('您要确认该条计费么？')) {
            $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_INCHARGE;
            if ($charge->save()) {
                Log::add(strtr('[eq_charge_confirm] %user_name[%user_id]确认了经费记录[%charge_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%charge_id' => $charge->id,
                ]), 'journal');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '收费确认成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '收费确认失败!'));
            }
            JS::refresh();
        }
    }

    public function index_charge_batch_confirm_click()
    {
        $v_memory_limit = ini_get('memory_limit') *1073741824;
        ini_set('memory_limit',$v_memory_limit);
        $me = L('ME');
        $form = Form::filter(Input::form());
        if (!$me->is_allowed_to('收费确认', 'equipment')) {
            JS::refresh();
            return;
        }

        $ids = $_SESSION['check_confirm_select'];
        $failed = 0;
        foreach ($ids as $id => $val) {
            $charge = O('eq_charge', $id);
            if (!$me->is_allowed_to('确认', $charge)) {
                $failed++;
                continue;
            }

            if (!self::validate($charge)) {
                $failed++;
                continue;
            }

            $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_INCHARGE;
            if ($charge->save()) {
                Log::add(strtr('[eq_charge_confirm] %user_name[%user_id]确认了经费记录[%charge_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%charge_id' => $charge->id,
                ]), 'journal');
            }
        }
        $_SESSION['check_confirm_select'] = [];
        $_SESSION['check_all_confirm_charge'] = FALSE;
        if (count($ids)) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', $failed > 0 ? '批量收费部分成功!' : '批量收费确认成功!'));
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '请勾选至少一条收费记录!'));
        }
        JS::refresh();
    }

    public static function validate($charge)
    {
        $extra = Event::trigger('eq_charge_confirm.confirm.extra.validate', $charge);
        if ($extra !== null) {
            return $extra;
        }

        if ($charge->source->name() == 'eq_sample' && $charge->source->status != EQ_Sample_Model::STATUS_TESTED) {
            $s = str_pad(Module::is_installed('billing') ? $charge->transaction->id : $charge->id,6,0,STR_PAD_LEFT);
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', "计费编号{$s}的送样记录未测试,无法确认！"));
            return false;
        }

        // eq_charge_confirm.need_feedback: 确认收费前必须反馈
        // 如果需要反馈才可以确认收费功能, 在site下need_feedback=true
        if (!Config::get('equipment.feedback_show_samples', 0) && !Config::get('eq_charge_confirm.need_feedback', false)) {
            return true;
        }
        
        // 无论何种计费方式，只要收费记录有对应的使用记录，那么该使用记录没有反馈的话，都不可以被确认收费
        switch ($charge->source->name()) {
            case 'eq_record':
                if ($charge->source->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', "计费编号".(Module::is_installed('billing') ? $charge->transaction->id : $charge->id)."使用记录未反馈，不可确认收费!"));
                    return false;
                }
                break;
            case 'eq_reserv':
                // 一个预约记录对应多个使用记录的情况下，只要有一个使用记录没有反馈，那么也不可以被确认收费
                $connect_records = Q("{$charge->source}<reserv eq_record[status=".EQ_Record_Model::FEEDBACK_NOTHING."]");
                if ($connect_records->total_count()) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', "计费编号".(Module::is_installed('billing') ? $charge->transaction->id : $charge->id)."预约记录关联的使用记录未反馈，不可确认收费!"));
                    return false;
                }
                break;
            case 'eq_sample':
            default:
                break;
        }

        return true;
    }

    public static function index_check_all_click()
    {

        $me = L('ME');
        if (!$me->is_allowed_to('收费确认', 'equipment')) {
            JS::refresh();
            return;
        }
        $status = Input::form('status');
        $charge_ids = [];
		$all = FALSE;
		if ($status == 'checked') {
			$all = TRUE;
			$selector = $_SESSION['eq_charge_confirm_selector'];
            $selector .= "[confirm=0]";
            if (Config::get('eq_charge_confirm.check_all_is_now_page')){
                $page = Input::form('page');
                $page_number = Input::form('page_number');
                $selector .= ":limit($page, $page_number)";
                $selector .= ':sort(ctime D)';
                $charge_ids = [];
                foreach (Q($selector) as $charge) {
                    if ($me->is_allowed_to('确认', $charge)) {
                        $charge_ids[$charge->id] = $charge->id;
                    }
                }
            } else {
                $charge_ids  = Q($selector)->to_assoc('id', 'id');
            }
        }
        $_SESSION['check_confirm_select'] = $charge_ids;
        $_SESSION['check_all_confirm_charge'] = $all;
        
    }

    public static function index_select_submit() {
        $me = L('ME');
        if (!$me->is_allowed_to('收费确认', 'equipment')) {
            JS::refresh();
            return;
        }
        $array = $_SESSION['check_confirm_select'] ? : [];
        $ids  = Input::form('ids');
        foreach ($ids as $key => $item) {
            if ($item) $array[$key] = true;
            else unset($array[$key]);
        }
        
        $_SESSION['check_confirm_select'] = $array;
    }
}
