<?php 

class Data_AJAX_Controller extends AJAX_Controller {

    function index_delete_equipment_submit() {
        $form = Input::form();
        $equipment = O('equipment', $form['id']);

        if (!$equipment->id) {
            JS::alert(I18N::T('support', '没有查找到匹配的仪器信息！'));
            return FALSE;
        }

        $reservs = Q("eq_reserv[equipment={$equipment}]")->total_count();
        $records = Q("eq_record[equipment={$equipment}]")->total_count();
        $samples = Q("eq_sample[equipment={$equipment}]")->total_count();
        
        $view = V('support:data_delete/equipment', [
            'equipment' => $equipment,
            'reservs' => $reservs,
            'records' => $records,
            'samples' => $samples,
        ]);
        JS::dialog($view, ['title' => I18N::T('support', '匹配仪器')]);
    }

    function index_delete_equipment_exec() {
        $form = Input::form();
        $equipment = O('equipment', $form['id']);

        if (!$equipment->id) {
            JS::alert('没有查找到匹配的仪器信息！');
            return FALSE;
        }

        if (JS::confirm(I18N::T('support', '您确定要删除该仪器以及其他关联记录吗?'))) {
            // 删除基础记录
            Q("eq_record[equipment={$equipment}]")->delete_all();
            Q("eq_sample[equipment={$equipment}]")->delete_all();

            $calendars = Q("calendar[parent={$equipment}]");
            foreach ($calendars as $calendar) Q("cal_component[calendar=$calendar]")->delete_all();
            
            $this->db_clean($equipment, 'equipment');
            Lab::message(LAB::MESSAGE_NORMAL , I18N::T('support', '删除成功'));
            JS::Refresh();
        }
    }

    function index_delete_user_submit() {
        $form = Input::form();
        $user = O('user', $form['id']);

        if (!$user->id) {
            JS::alert(I18N::T('support', '没有查找到匹配的用户信息！'));
            return FALSE;
        }
        
        $reservs = Q("eq_reserv[user={$user}]")->total_count();
        $records = Q("eq_record[user={$user}]")->total_count();
        $samples = Q("eq_sample[sender={$user}]")->total_count();
        
        $view = V('support:data_delete/user', [
            'user' => $user,
            'reservs' => $reservs,
            'records' => $records,
            'samples' => $samples,
        ]);
        JS::dialog($view, ['title' => I18N::T('support', '匹配用户')]);
    }

    function index_delete_user_exec() {
        $form = Input::form();
        $user = O('user', $form['id']);

        if (!$user->id) {
            JS::alert('没有查找到匹配的人员信息！');
            return FALSE;
        }

        if (JS::confirm(I18N::T('support', '您确定要删除该人员以及其他关联记录吗?'))) {
            // 删除基础记录
            Q("eq_record[user={$user}]")->delete_all();
            Q("eq_sample[sender={$user}]")->delete_all();
            Q("cal_component[organizer=$user]")->delete_all();
            
            $this->db_clean($user, 'user');
            Lab::message(LAB::MESSAGE_NORMAL , I18N::T('support', '删除成功'));
            JS::Refresh();
        }
    }

    function index_delete_lab_submit() {
        $form = Input::form();
        $lab = O('lab', $form['id']);

        if (!$lab->id) {
            JS::alert(I18N::T('support', '没有查找到匹配的课题组信息！'));
            return FALSE;
        }
        
        $reservs = Q("({$lab} user) eq_reserv")->total_count();
        $records = Q("({$lab} user) eq_record")->total_count();
        $samples = Q("({$lab} user<sender) eq_sample")->total_count();
        
        $view = V('support:data_delete/lab', [
            'lab' => $lab,
            'reservs' => $reservs,
            'records' => $records,
            'samples' => $samples,
        ]);
        JS::dialog($view, ['title' => I18N::T('support', '匹配课题组')]);
    }

    function index_delete_lab_exec() {
        $form = Input::form();
        $lab = O('lab', $form['id']);

        if (!$lab->id) {
            JS::alert('没有查找到匹配的课题组信息！');
            return FALSE;
        }

        if (in_array($lab->id, [Lab_Model::default_lab()->id, Equipments::create_temporary_lab()->id])) {
            JS::alert('默认课题组不能删除！');
            return FALSE;
        }

        if (JS::confirm(I18N::T('support', '您确定要删除该课题组以及其他关联记录吗?'))) {
            // 删除基础记录
            Q("({$lab} user) eq_record")->delete_all();
            Q("({$lab} user<sender) eq_sample")->delete_all();
            Q("({$lab} user<organizer) cal_component")->delete_all();
            
            $this->db_clean($lab, 'lab');
            Lab::message(LAB::MESSAGE_NORMAL , I18N::T('support', '删除成功'));
            JS::Refresh();
        }
    }

    function index_delete_account_submit() {
        $form = Input::form();
        $account = O('billing_account', $form['id']);

        if (!$account->id) {
            JS::alert(I18N::T('support', '没有查找到匹配的财务账号信息！'));
            return FALSE;
        }
        
        $transactions = Q("billing_transaction[account={$account}]")->total_count();
        
        $view = V('support:data_delete/account', [
            'account' => $account,
            'transactions' => $transactions,
        ]);
        JS::dialog($view, ['title' => I18N::T('support', '匹配财务账号')]);
    }

    function index_delete_account_exec() {
        $form = Input::form();
        $account = O('billing_account', $form['id']);

        if (!$account->id) {
            JS::alert('没有查找到匹配的财务账号信息！');
            return FALSE;
        }

        if (JS::confirm(I18N::T('support', '您确定要删除该财务账号以及其他关联记录吗?'))) {
            $this->db_clean($account, 'billing_account');
            Lab::message(LAB::MESSAGE_NORMAL , I18N::T('support', '删除成功'));
            JS::Refresh();
        }
    }

    private function db_clean($object, $object_name) {
            
        $db = Database::factory();
        $result = $db->query("SHOW tables LIKE '_r%{$object_name}%'");
        if ($result) while($row = $result->row('assoc')) {
            $name = preg_replace('/_r_/', '', current($row), 1);
            $names = explode('_', $name);
            $index = array_search($object_name, $names) == 0 ? 1 : 2;
            $sql = "DELETE FROM `" . current($row) . "` WHERE `id{$index}` = {$object->id}";
            $db->exec($sql);
        }

        $schemas = Config::get('schema');
        foreach ($schemas as $name => $schema) {
            if (in_array($name, ['eq_record', 'eq_sample', 'eq_reserv'])) continue;
            foreach ($schema['fields'] as $key => $value) {
                if ($value['type'] == 'object' && $value['oname'] == $object_name) {
                    Q("{$name}[{$object_name}={$object}]")->delete_all();
                    break;
                }
                elseif ($value['type'] == 'object' && !array_key_exists('oname', $value)) {
                    list($object_name, $object_id) = explode('#', $object);
                    Q("{$name}[{$key}_name={$object_name}][{$key}_id={$object_id}]")->delete_all();
                    break;
                }
            }
        }

        $object->delete();
        if ($object_name == 'user'){
            $auth = new Auth($object->token);
            $auth->remove();
        }
    }

}