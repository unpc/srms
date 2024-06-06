<?php


class dashboard
{
    public static function get_card () {
        $card_list = [];
        // 获取需要展示的botton
        $use = self::get_use();
        //$feedback = self::get_feedback();
        $test = self::get_test();
        $follow = self::get_follow();
        $approval = self::get_approval();
        $billing = self::get_billing();
        $reimbursement = self::get_reimbursement();
        $fillin = self::get_fillin();

        if (is_array($use)) array_push($card_list, $use);
        // if (is_array($feedback)) array_push($card_list, $feedback);
        if (is_array($test)) array_push($card_list, $test);
        if (is_array($follow)) array_push($card_list, $follow);
        if (is_array($approval)) array_push($card_list, $approval);
        if (is_array($billing)) array_push($card_list, $billing);
        if (is_array($reimbursement)) array_push($card_list, $reimbursement);
        if (is_array($fillin)) array_push($card_list, $fillin);
        return $card_list;

    }

    // 待报销
    public static function get_billing()
    {
        $me = L('ME');
        $total = 0;
        $billing_url = '';

        if (Module::is_installed('billing_standard') && $me->access('管理组内报销') && $me->is_lab_pi()) {
            $billing_url = $billing_url === '' ? '!people/dashboard/index.transaction_billing.1' : $billing_url;
            $lab = Q("$me lab")->current();
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);

            try {
                $total = $rpc->distribution->total($lab->id);
            } catch (\Exception $e) {
                
            }
        }

        if ( $billing_url === '') {
            return NULL;
        }

        return array(
            'name' => '待报销',
            'key' => 'billing',
            'url' => $billing_url,
            'total' => $total
        );
    }

    // 待审批
    public static function get_approval (){
        $me = L('ME');
        $total = 0;
        $approval_url = '';

        if(!People::perm_in_uno()) {
        //成员审批
        if ($me->access('管理所有内容') || $me->access('添加/修改所有成员信息') || $me->access('添加/修改下属机构成员的信息') || $me->is_lab_pi()) {
            $approval_url = $approval_url === ''?'!people/dashboard/index.people_approval.1':$approval_url;

            if ($me->is_lab_pi()) {
                $selector = "lab[owner={$me}] ";
            }

            if ($me->access('添加/修改下属机构成员的信息')) {
                $selector = "{$me->group} ";
            }

            if ($me->access('管理所有内容') || $me->access('添加/修改所有成员信息')) {
                $selector = "";
            }

            $show_hidden_user = $me->show_hidden_user();
            $selector .= $show_hidden_user ? "user[atime=0][approval=0]" : "user[!hidden][atime=0][approval=0]";
            if ($GLOBALS['preload']['people.enable_member_date']) {
                $now = time();
                $selector .= '[dto=0|dto>'.$now.']';
            }

            $total += Q($selector)->total_count();
        }

        //课题组审批
        if ($me->access('管理所有内容') || $me->access('添加/修改实验室') || $me->access('添加/修改下属机构实验室')) {
            $approval_url = $approval_url === ''?'!people/dashboard/index.lab_approval.1':$approval_url;
            $show_hidden_lab = $me->show_hidden_lab();

            if ($me->access('添加/修改下属机构实验室')) {
                $selector = "lab[group={$me->group}]";
            }

            if ($me->access('管理所有内容') || $me->access('添加/修改实验室')) {
                $selector = 'lab';
            }

            if (!$show_hidden_lab) {
                $selector .= '[hidden=0]';
            }
            $selector = $selector.'[atime<=0][approval=0]';
            $total += Q($selector)->total_count();
        }

        }

        // 预约审批
        if (Module::is_installed('yiqikong_approval') && ($me->access('管理所有内容') || $me->access('修改所有仪器的预约') || $me->access('修改下属机构仪器的预约') || $me->access('修改负责仪器的预约'))) {
            $_total = 0;
            $approval_url = $approval_url === '' ? '!people/dashboard/index.reserv_approval.1' : $approval_url;

            if ($me->is_equipment_charge()) {
                $_total = Q("{$me}<incharge equipment<equipment approval[flag=approve]")->total_count();
            }

            if ($me->access('修改下属机构仪器的预约')) {
                $_total = Q("{$me->group} equipment approval[flag=approve]")->total_count();
            }
    
            if ($me->access('管理所有内容') || $me->access('修改所有仪器的预约')) {
                $_total = Q("approval[flag=approve]")->total_count();
            }
            $total += $_total;
        }

        if (Module::is_installed('approval_flow') && ((Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人') || $me->is_lab_pi() || $me->access('管理所有内容') || $me->access('修改所有仪器的预约') || $me->access('修改下属机构仪器的预约') || $me->access('修改负责仪器的预约'))) {
            $_total = 0;
            $approval_url = $approval_url === '' ? '!people/dashboard/index.reserv_approval.1' : $approval_url;

            if ($me->is_equipment_charge()) {
                $_total = Q("{$me}<incharge equipment<equipment approval[source_name=eq_reserv][flag=approve_incharge]")->total_count();
            }

            if ($me->access('修改下属机构仪器的预约')) {
                $_total = Q("{$me->group} equipment approval[source_name=eq_reserv][flag=approve_incharge]")->total_count();
            }

            if ($me->access('管理所有内容') || $me->access('修改所有仪器的预约')) {
                $_total = Q("approval[flag=approve]")->total_count();
            }

            if ((Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人')) {
                $_total = Q(" ({$me}}<incharge lab_project<project eq_reserv<source) approval[source_name=eq_reserv][flag=approve_project]")->total_count();
            }

            if ($me->is_lab_pi()) {
                $_total = Q("approval[flag=approve_pi][source_name=eq_reserv]")->total_count();
            }
            
            $total += $_total;
        }

        // 送样审批
        if (Module::is_installed('approval_flow') && (Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人') || $me->is_lab_pi() || $me->access('管理所有内容') || $me->access('修改所有仪器的送样') || $me->access('修改下属机构仪器的送样') || $me->access('修改负责仪器的送样')) {
            $_total = 0;
            $approval_url = $approval_url === ''?'!people/dashboard/index.sample_approval.1':$approval_url;
            $applied_status = EQ_Sample_Model::STATUS_APPLIED;

            if ($me->is_equipment_charge()) {
                $_total = Q("{$me}<incharge equipment<equipment eq_sample[status={$applied_status}]")->total_count();
            }
    
            if ($me->access('修改下属机构仪器的送样')) {
                $_total = Q("{$me->group} equipment eq_sample[status={$applied_status}]")->total_count();
            }
    
            if ($me->access('修改所有仪器的送样') || $me->access('修改所有仪器的送样')) {
                $_total = Q("eq_sample[status={$applied_status}]")->total_count();
            }

            if ((Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人')) {
                $applied_status = EQ_Sample_Model::STATUS_APPLIED;
                $approved_status = EQ_Sample_Model::STATUS_APPROVED;
                $status=substr(trim($_SERVER['PHP_SELF']),-1);
                switch((int)$status){
                    case 2:
                        $selector = "eq_sample[status={$approved_status}]";
                        break;
                    case 1:
                    default:
                        $selector = "eq_sample[status={$applied_status}]";
                }
                $_total = Q("({$me}<incharge lab_project<project) $selector")->total_count();
            }

            $total += $_total;
        }

        // 仪器培训审批
        if ($me->access('管理所有内容') || $me->access('管理所有仪器的培训记录') || $me->access('管理下属机构仪器的培训记录') || $me->access('管理负责仪器的培训记录')) {
            $_total = 0;
            $approval_url = $approval_url === ''?'!people/dashboard/index.authorization_approval.1':$approval_url;
            $applied_status = implode(',', [
                UE_Training_Model::STATUS_APPLIED,
                UE_Training_Model::STATUS_AGAIN,
            ]);

            if ($me->access('管理负责仪器的培训记录')) {
                $_total = Q("{$me}<incharge equipment<equipment ue_training[status={$applied_status}]")->total_count();
            }
    
            if ($me->access('管理下属机构仪器的培训记录')) {
                $_total = Q("{$me->group} equipment ue_training[status={$applied_status}]")->total_count();
            }
    
            if ($me->access('管理所有内容') || $me->access('管理所有仪器的培训记录')) {
                $_total = Q("ue_training[status={$applied_status}]")->total_count();
            }
            
            $total += $_total;
        }

        // 基金审批
        if (Module::is_installed('fund_report') && $me->is_allowed_to('审批基金申报', 'fund_report_apply')) {
            $approval_url = $approval_url === '' ? URI::url('!people/dashboard/index.fundreport_approval'):$approval_url;
            // 获取到需要审批的基金申报
            $total += Q(self::get_fundreport_approval_sql())->total_count();
        }

        //绩效审批
        if (Module::is_installed('capability') && $me->is_allowed_to('绩效审批', 'capability_equipment_task')) {
            $approval_url = $approval_url === '' ? URI::url('!people/dashboard/index.capability_approval'):$approval_url;
            $total += Q(self::get_capability_approval_sql())->total_count();
        }

        //服务审批
        if (Module::is_installed('technical_service')&& $me->is_allowed_to('列表审批','service_apply')){
            $approval_url = $approval_url === '' ? URI::url('!people/dashboard/index.technical_service'):$approval_url;
            $show_status = [
                Service_Apply_Model::STATUS_APPLY,
            ];
            $status = implode(',', $show_status);
            $selector = "service_apply[status={$status}]";
            $pre_selector = [];
            if ($me->access('管理所有服务')) {
            } elseif ($me->access('管理下属机构服务')) {
                $pre_selector['service'] = "{$me->group} service";
            } else {
                $pre_selector['service'] = "{$me}<incharge service";
            }
            if (!empty($pre_selector)) {
                $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
            }
            $total += Q($selector)->total_count();
        }

        if ( $approval_url === '') {
            return NULL;
        }
        return array(
            'name' => '待审批',
            'key' => 'approval',
            'url' => $approval_url,
            'total' => $total
        );
    }
    // 待使用
    public static function get_use () {
        if (Switchrole::user_select_role() !== '普通用户' && !(Switchrole::isAdmin())) {
            return NULL;
        }
        $user = L('ME');
        $total = 0;
        $url = URI::url('!people/dashboard/index.eqreserv_used');
        $dtstart = time();
        $dtnext = $dtstart + 604800;
        $type = Cal_Component_Model::TYPE_VEVENT;
        $total += Q("calendar[parent_name=equipment] cal_component[type=$type][organizer=$user][dtstart~dtend={$dtstart}|dtstart~dtend={$dtnext}|dtstart={$dtstart}~{$dtnext}]:sort(dtstart)")->total_count();
        $total += Q("eq_sample[sender={$user}][status=".EQ_Sample_Model::STATUS_APPLIED."]")->total_count();
        if (Module::is_installed('technical_service')){
            $show_status = [
                Service_Apply_Model::STATUS_APPLY,
                Service_Apply_Model::STATUS_PASS,
                Service_Apply_Model::STATUS_SERVING,
                Service_Apply_Model::STATUS_REJECT,
            ];
            $status = implode(',', $show_status);
            $total += Q("service_apply[user={$user}][status={$status}]")->total_count();
        }
        return array(
            'name' => '待使用',
            'key' => 'used',
            'url' => $url,
            'total' => $total
        );
    }
    // 待反馈
    public static function get_feedback () {
//        if (Switchrole::user_select_role() !== '普通用户' && !(Switchrole::isAdmin())) {
//            return NULL;
//        }
        $me = L('ME');
        $total = 0;
        $url = URI::url('!people/dashboard/index.record_feedback');
        $record_feedback = Q("eq_record[user={$me}][status=0][is_locked=0]")->total_count();
        $sample_feedback = 0;
        if (Module::is_installed('eq_comment')) {
            $sample_status = EQ_Sample_Model::STATUS_TESTED;
            $sample_feedback = Q("eq_sample[sender={$me}][status={$sample_status}][is_locked=0][feedback=0]")->total_count();
        }
        $total += $record_feedback + $sample_feedback;
        return array(
            'name' => '待反馈',
            'key' => 'feedback',
            'url' => $url,
            'total' => $total,
            ''
        );
    }
    // 已关注
    public static function get_follow () {
        //if (Switchrole::user_select_role() !== '普通用户' && !(Switchrole::isAdmin())) {
        //    return NULL;
        //}
        $me = L('ME');
        $total = 0;
        $url = URI::url('!people/dashboard/index.equipment_follow_reserv');
        if(!People::perm_in_uno()){
            $total += Q("user<object follow[user={$me}][object_name=user]")->total_count();
        }
        $total += Q("equipment<object follow[user={$me}][object_name=equipment]")->total_count();
        return array(
            'name' => '已关注',
            'key' => 'follow',
            'url' => $url,
            'total' => $total
        );
    }
    // 待报销
    public static function get_reimbursement () {
        $total = 0;
        $url = '';
        $me = L('ME');
	
        if ($me->is_equipment_charge()) {
            // 收费确认
            if (Module::is_installed('eq_charge_confirm') && $me->access('确认负责仪器的收费')) {
                $url = URI::url('!people/dashboard/index.eqcharge_reimbursement');
                $total += Q("{$me} equipment.incharge eq_charge[amount!=0][confirm=".EQ_Charge_Confirm_Model::CONFIRM_PENDDING."].equipment:sort(ctime D)")->total_count();
            }
        }
        if ($url === '') {
            return NULL;
        }
        return array(
            'name' => '待确认',
            'key' => 'reimbursement',
            'url' => $url,
            'total' => $total
        );
    }
    // 待填报
    public static function get_fillin () {
        $me = L('ME');
        $url = '';
        $total = 0;
        // 基金申报
        if (Module::is_installed('fund_report') && $me->is_allowed_to('填报申请', 'fund_report_apply')) {
            $url = $url === '' ? URI::url('!people/dashboard/index.fundreport_fillin'):$url;
            $equipments_sql = Q(self::get_equipment_sql());
            $equipments = [];
            foreach ($equipments_sql as $value) {
                $equipments[] = $value->id;
            }
            $now = Date::time();
	    $now = Date::time();
           $fund_report_annual = Q("fund_report_annual[dtstart<{$now}][dtend>{$now}]")->current();
           if ($fund_report_annual->id){
            $s2 = Q("fund_report_apply[fund_report_annual_id={$fund_report_annual->id}][type=开发费|type=维修费][status!=审批驳回][equipment=".implode(',', $equipments)."]");
            $total += $equipments_sql->total_count()*2 - $s2->total_count();
	   }
        }
        // 绩效考核
        if (Module::is_installed('capability') && $me->is_allowed_to('列表效益填报', 'capability_equipment_task')) {
            $url = $url === '' ? URI::url('!people/dashboard/index.capability_fillin'):$url;
            $total += Q(self::get_capability_fillin_sql())->total_count();
        }

        if ($url === '') {
            return NULL;
        }
        return array(
            'name' => '待填报',
            'key' => 'fillin',
            'url' => $url,
            'total' => $total
        );
    }
    // 待检测
    public static function get_test () {
        if (!People::perm_in_uno() && Switchrole::user_select_role() == '普通用户') {
            return NULL;
        }
        $total = 0;
        $url = URI::url('!people/dashboard/index.eqsample_test');;
        $me = L('ME');

        if ($me->is_equipment_charge()) {
            $total += Q("{$me}<incharge equipment eq_sample[status=".EQ_Sample_Model::STATUS_APPROVED."]")->total_count();
            if (Module::is_installed('technical_service')){
                $status = Service_Apply_Record_Model::STATUS_APPLY;
                $selector = "{$me}<incharge equipment service_apply_record[status={$status}]";
                $total += Q($selector)->total_count();
            }

        } else {
            return NULL;
        }
        return array(
            'name' => '待检测',
            'key' => 'test',
            'url' => $url,
            'total' => $total
        );
    }

    // 待使用-申请送样
    public static function _index_equipment_sample_content($e, $tabs){
        $user = $tabs->user;
        $selector        = " eq_sample[sender={$user}][status=".EQ_Sample_Model::STATUS_APPLIED."]:sort(id D)";
        $samples         = Q($selector);
        $start           = (int) Input::form('st');
        $per_page        = 20;
        $pagination      = Lab::pagination($samples, $start, $per_page);
        $fields          = EQ_Sample::get_sample_field_with_user_sample(Input::form(), ['user' => $user]);
        $me = L('ME');
        if ($form['dtsubmit_dtstart'] || $form['dtsubmit_dtend']) {
            $form['dtsubmit_date'] = true;
        }

        if ($form['dtpickup_dtstart'] || $form['dtpickup_dtend']) {
            $form['dtpickup_date'] = true;
        }
        $fields = [
            'serial_number'  => [
                'title'    => I18N::T('eq_sample', '编号'),
                'align'    => 'left',
                'sortable' => true,
                'weight'   => 10,
                'nowrap'   => true,
            ],
            'equipment_name' => [
                'title'    => I18N::T('eq_sample', '仪器'),
                'align'    => 'left',
                'sortable' => true,
                'weight'   => 20,
                'nowrap'   => true,
            ],
            'count'          => [
                'title'    => I18N::T('eq_sample', '样品数'),
                'align'    => 'right',
                'sortable' => true,
                'weight'   => 30,
                'nowrap'   => true,
            ],
            'dtsubmit'       => [
                'title'    => I18N::T('eq_sample', '送样时间'),
                'align'    => 'left',
                'sortable' => true,
                'weight'   => 50,
                'nowrap'   => true,
            ],
            'operator'       => [
                'title'    => I18N::T('eq_sample', '操作者'),
                'align'    => 'center',
                'sortable' => true,
                'weight'   => 80,
                'nowrap'   => true,
            ],
            'description'    => [
                'title'  => I18N::T('eq_sample', '描述'),
                'align'  => 'left',
                'weight' => 100,
                'nowrap' => true,
            ],
            'rest'           => [
                'title'       => I18N::T('eq_sample', '操作'),
                'align'       => 'left',
                'extra_class' => '',
                'weight'      => 110,
                'nowrap'      => true,
            ],
        ];
        $tabs->columns   = new ArrayObject($fields);
        $tabs->content   = V('eq_sample:samples_for_profile', [
            'user'       => $user,
            'samples'    => $samples,
            'columns'    => $tabs->columns,
            'pagination' => $pagination,
            'form'       => $form,
            'time'       => strtotime("+1 day"),
        ]);
    }
    // 待使用-使用预约
    public static function _index_equipment_reserv_content($e, $tabs){
        $user          = $tabs->user;
        $user_v        = O('user_violation', ['user' => $user]);
        $tabs->content = V('dashboard/tabs/user_reserv', [
            'user' => $user, 'user_v' => $user_v, 'form' => $form
        ]);
    }


    // 待审批-预约审批
    public static function _index_reserv_approval_content($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $me            = L('ME');

        $status=substr(trim($_SERVER['PHP_SELF']),-1);

        $total = [
            'approve' => 0,
            'done' => 0,
            'expired' => 0,
        ];


        $pre_selectors = new ArrayIterator;
        if(Module::is_installed('yiqikong_approval')){
            $selector = "approval[flag=approve]";
        }
        if(Module::is_installed('approval_flow')){
            if ($me->is_lab_pi()) {
                $selector = "approval[flag=approve_pi][source_name=eq_reserv]";
            } else {
                $selector = "approval[flag=approve_incharge][source_name=eq_reserv]";
            }
            
            $selector = Event::trigger('approval_flow.update_selector_by_selected_role',$selector) ?: $selector;
        }
        

        if ($me->is_equipment_charge()) {
            $pre_selectors['equipment'] = "{$me}<incharge equipment<equipment";
        }

        if ($me->access('修改下属机构仪器的预约')) {
            $pre_selectors['equipment'] = "{$me->group} equipment";
        }

        if ($me->access('管理所有内容') || $me->access('修改所有仪器的预约')) {
            $pre_selectors['equipment'] = 'equipment';
        }

        if ($form['user']) {
            $user  = Q::quote(trim($form['user']));
            $pre_selectors['user'] = "user<user[name*={$user}|name_abbr*={$user}]";
        }

        if ($form['equipment']) {
            $equipment = Q::quote($form['equipment']);
            $pre_selectors['equipment'] .= "[name*={$equipment}|name_abbr*={$equipment}]";
        }

        if ($form['dtstart']) {
            $dtstart = strtotime(date('Y-m-d 00:00:00', $form['dtstart']));
            $selector .= "[dtstart>={$dtstart}]";
        }

        if ($form['dtend']) {
            $dtend = strtotime(date('Y-m-d 23:59:59', $form['dtend']));
            $selector .= "[dtend<={$dtend}]";
        }

        if ($form['ctime_dtstart']) {
            $ctime_dtstart = strtotime(date('Y-m-d 00:00:00', $form['ctime_dtstart']));
            $selector .= "[ctime>={$ctime_dtstart}]";
        }

        if ($form['ctime_dtend']) {
            $ctime_dtend = strtotime(date('Y-m-d 23:59:59', $form['ctime_dtend']));
            $selector .= "[ctime<={$ctime_dtend}]";
        }

        if (count($pre_selectors) > 0) {
            $pre = '('.implode(', ', (array) $pre_selectors).') ';
        }

        $selector = $pre . $selector . ':sort(ctime D)';

        $approvals = Q($selector);
        $start         = (int) $form['st'];
        $per_page      = 20;
        $pagination    = Lab::pagination($approvals, $start, $per_page);
        $tabs->content = V('dashboard/tabs/reserv_approval', [
            'approval'   => $approvals,
            'pagination' => $pagination,
            'form'       => $form
        ]);
    }

    // 待审批-送样审批
    public static function _index_sample_approval_content($e, $tabs)
    {
        $me              = L('ME');
        $form = Input::form();

        $applied_status = EQ_Sample_Model::STATUS_APPLIED;
        $approved_status = EQ_Sample_Model::STATUS_APPROVED;

        $status=substr(trim($_SERVER['PHP_SELF']),-1);

        $pre_selectors = new ArrayIterator;

        switch((int)$status){
            case 2:
                $selector = "eq_sample[status={$approved_status}]";
                break;
            case 1:
            default:
                $selector = "eq_sample[status={$applied_status}]";
        }

        if ($me->is_equipment_charge()) {
            $pre_selectors['equipment'] = "{$me}<incharge equipment<equipment";
        }

        if ($me->access('修改下属机构仪器的送样')) {
            $pre_selectors['equipment'] = "{$me->group} equipment";
        }

        if ($me->access('管理所有内容') || $me->access('修改所有仪器的送样')) {
            $pre_selectors['equipment'] = "equipment";
        }

        if ($form['sender']) {
            $sender  = Q::quote(trim($form['sender']));
            $pre_selectors['sender'] = "user<sender[name*={$sender}|name_abbr*={$sender}]";
        }

        if ($form['equipment']) {
            $equipment = Q::quote($form['equipment']);
            $pre_selectors['equipment'] .= "[name*={$equipment}|name_abbr*={$equipment}]";
        }

        if ($form['dtsubmit_dtstart']) {
            $form['dtsubmit_dtstart'] = Date::get_day_start($form['dtsubmit_dtstart']);
            $dtstart                  = Q::quote($form['dtsubmit_dtstart']);
            $selector .= "[dtsubmit>=$dtstart]";
        }

        if ($form['dtsubmit_dtend']) {
            $form['dtsubmit_dtend'] = Date::get_day_end($form['dtsubmit_dtend']);
            $dtend                  = Q::quote($form['dtsubmit_dtend']);
            $selector .= "[dtsubmit>0][dtsubmit<=$dtend]";
        }

        if ($form['dtctime_dtstart']) {
            $form['dtctime_dtstart'] = Date::get_day_start($form['dtctime_dtstart']);
            $dtstart                  = Q::quote($form['dtctime_dtstart']);
            $selector .= "[ctime>=$dtstart]";
        }

        if ($form['dtctime_dtend']) {
            $form['dtctime_dtend'] = Date::get_day_end($form['dtctime_dtend']);
            $dtend                  = Q::quote($form['dtctime_dtend']);
            $selector .= "[ctime<=$dtend]";
        }

        if (count($pre_selectors) > 0) {
            $pre = '('.implode(', ', (array) $pre_selectors).') ';
        }

        $selector = $pre . $selector . ':sort(ctime D)';

        $samples = Q($selector);
        $start           = (int) Input::form('st');
        $per_page        = 20;
        $pagination      = Lab::pagination($samples, $start, $per_page);
        $tabs->content   = V('dashboard/tabs/sample_approval', [
            'samples'       => $samples,
            'form'          => $form,
            'pagination'    => $pagination,
            'no_search_box' => true
        ]);
    }
    // 待审批-培训授权审批审批
    public static function _index_authorization_approval_content($e, $tabs)
    {
        $me             = L('ME');

        if ($me->access('管理负责仪器的培训记录')) {
            $selector = "{$me}<incharge equipment<equipment";
        }

        if ($me->access('管理下属机构仪器的培训记录')) {
            $selector = "{$me->group} equipment";
        }

        if ($me->access('管理所有内容') || $me->access('管理所有仪器的培训记录')) {
            $selector = "";
        }

        $applied_status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN,
        ]);

        $approved_status = UE_Training_Model::STATUS_APPROVED;
        $overdue_status = UE_Training_Model::STATUS_OVERDUE;

        $status=substr(trim($_SERVER['PHP_SELF']),-1);
        $total = [
            'approve' => 0,
            'done' => 0,
            'expired' => 0,
        ];
        switch((int)$status){
            case 3:
                $trainings      = Q("{$selector} ue_training[status={$overdue_status}]:sort(ctime D)");
                break;
            case 2:
                $trainings      = Q("{$selector} ue_training[status={$approved_status}]:sort(ctime D)");
                break;
            case 1:
            default:
                $trainings      = Q("{$selector} ue_training[status={$applied_status}]:sort(ctime D)");
        }
        $start          = (int) Input::form('st');
        $per_page       = 20;
        $pagination     = Lab::pagination($trainings, $start, $per_page);
        $tabs->content  = V('dashboard/tabs/authorization_approval', ['trainings' => $trainings, 'pagination' => $pagination, 'total' => $total]);
    }
    // 待审批-成员审批
    public static function _index_people_content($e, $tabs){

        $me = L('ME');
        $form = Input::form();

        if ($me->is_lab_pi()) {
            $selector = "lab[owner={$me}] ";
        }

        if ($me->access('添加/修改下属机构成员的信息')) {
            $selector = "{$me->group} ";
        }

        if ($me->access('管理所有内容') || $me->access('添加/修改所有成员信息')) {
            $selector = "";
        }

        $show_hidden_user = $me->show_hidden_user();
        $selector_activated = $selector.($show_hidden_user ? "user[atime>0][approval=0]" : "user[!hidden][approval=0][atime>0]");
        $selector_unactivated = $selector.($show_hidden_user ? "user[atime=0][approval=0]" : "user[!hidden][approval=0][atime=0]");
        if ($GLOBALS['preload']['people.enable_member_date']) {
            $now = time();
            $selector_unactivated .= '[dto=0|dto>'.$now.']';
        }

        if ($form['name'] && strlen($form['name']) > 0) {
            $selector_activated .= "[name*={$form['name']}]";
            $selector_unactivated .= "[name*={$form['name']}]";
        }

        if ($form['email'] && strlen($form['email']) > 0) {
            $selector_activated .= "[email*={$form['email']}]";
            $selector_unactivated .= "[email*={$form['email']}]";
        }

        if ($form['phone'] && strlen($form['phone']) > 0) {
            $selector_activated .= "[phone*={$form['phone']}]";
            $selector_unactivated .= "[phone*={$form['phone']}]";
        }

        $status=substr(trim($_SERVER['PHP_SELF']),-1);
        switch((int)$status){
            case 2:
                $users = Q($selector_activated);
                $activated_total = $users->total_count();
                $unactivated_total = Q($selector_unactivated)->total_count();
                break;
            case 1:
            default:
                $activated_total = Q($selector_activated)->total_count();
                $users = Q($selector_unactivated);
                $unactivated_total = $users->total_count();
        }

        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);
        $pagination = Lab::pagination($users, $start, $per_page);

        // 成员列表
        $tabs->content =  V('people:dashboard/tabs/people_unactivated', [
            'users'=>$users,
            'pagination'=>$pagination,
            'form'=>$form,
            'total' => [
                'activated' => $activated_total,
                'unactivated' => $unactivated_total,
            ]
        ]);
    }
    // 待审批-课题组审批
    public static function _index_lab_content($e, $tabs){
        $me = L('ME');
        $form = Input::form();

        $show_hidden_lab = $me->show_hidden_lab();

        if ($me->access('添加/修改下属机构实验室')) {
            $selector = "lab[group={$me->group}]";
        }

        if ($me->access('管理所有内容') || $me->access('添加/修改实验室')) {
            $selector = 'lab';
        }

        if (!$show_hidden_lab) {
            $selector .= '[hidden=0][approval=0]';
        }

		if ($form['incharge']) {
			$incharge = Q::quote(trim($form['incharge']));
			$selector = "user[name*=$incharge|name_abbr*=$incharge]<pi ". $selector;
		}

        //GROUP搜索
        $group      = O('tag_group', $form['group_id']);
        $group_root = Tag_Model::root('group');
        if ($group->id && $group->root->id == $group_root->id) {
            $pre_selectors['group'] = "$group";
            $selector = "$group $selector";
        }

        if ($form['lab_name']) {
            $selector .= "[name*={$form['lab_name']}|name_abbr*={$form['lab_name']}]";
        }

        if ($form['ctstart']) {
            $selector .= "[ctime>={$form['ctstart']}]";
        }

        if ($form['ctend']) {
            $ctend = strtotime(date('Y-m-d 23:59:59', $form['ctend']));
            $selector .= "[ctime<={$ctend}]";
        }

        $selector_activated = $selector.'[atime>0]:sort(ctime D)';
        $selector_unactivated = $selector.'[atime<=0]:sort(ctime D)';


        $status=substr(trim($_SERVER['PHP_SELF']),-1);
        switch((int)$status){
            case 2:
                $labs = Q($selector_activated);
                $activated_total = $labs->total_count();
                $unactivated_total = Q($selector_unactivated)->total_count();
                break;
            case 1:
            default:
                $activated_total = Q($selector_activated)->total_count();
                $labs = Q($selector_unactivated);
                $unactivated_total = $labs->total_count();
        }
        error_log($selector);
        error_log($labs->parse_selector()->SQL);
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);
        $pagination = Lab::pagination($labs, $start, $per_page);


        $tabs->content = V('people:dashboard/tabs/lab_unactivated', [
            'labs'           => $labs,
            'pagination'     => $pagination,
            'form'           => $form,
            'group'          => $group,
            'group_root'     => $group_root,
            'total' => [
                'activated' => $activated_total,
                'unactivated' => $unactivated_total,
            ]
        ]);

    }
    // 待审批-基金审批
    public static function get_fundreport_approval_sql(){
        $me              = L('ME');
        $now = Date::time();
        list($username, $backend) = explode('|', $me->username);
        $is_genee = $username == 'genee' || $username == 'Support';

        $selecter = "fund_report_annual[dtstart<{$now}][dtend>{$now}]<fund_report_annual fund_report_apply";

        if (!($me->access('管理所有内容') || $is_genee)) {
            $equipments_selecter = [];
            if ($me->access('查看负责仪器的基金申报')) {
                $equipments_selecter[] = "{$me}<incharge equipment";
            }
            if ($me->access('查看下属机构的基金申报')) {
                $groups = [];
                $tmpgroups = [$me->group->parent_id];
                while(count($tmpgroups)) {
                    $regroups = Q("tag[parent=".join(',', $tmpgroups)."][root=".Tag_Model::root('group')."]");
                    $tmpgroups = [];
                    foreach ($regroups as $regroup) {
                        $tmpgroups[] = $regroup->id;
                        $groups[] = $regroup->id;
                    }
                }
                $equipments_selecter[] = "equipment[group=".join(',', $groups)."]";
            }
            if (count($equipments_selecter)) {
                $equipments = [];
                foreach (Q("equipment[".implode('|', $equipments_selecter)."]") as $value) {
                    $equipments[] = $value->id;
                }
                if (count($equipments)) {
                    $selecter .= "[equipment=".implode(',', $equipments)."]";
                } else {
                    $selecter .= "[equipment=-1]";
                }

            }
        }
        $status_selecter = [];
        if ($me->access('初审基金申请单') || $is_genee){
            $status_selecter[] = 'status=待初审';
        }
        if ($me->access('复审基金申请单') || $is_genee){
            $status_selecter[] = 'status=待复审';
        }
        $selecter .= "[".implode('|', $status_selecter)."]";
        return $selecter;
    }
    public static function _index_fundreport_approval_content($e, $tabs){
        $applys = Q(self::get_fundreport_approval_sql());
        $start           = (int) Input::form('st');
        $per_page        = 20;
        $pagination      = Lab::pagination($applys, $start, $per_page);

        $tabs->content   = V('dashboard/tabs/fundreport_approval', [
            'approval'       => $applys,
            'pagination'    => $pagination
        ]);
    }
    // 待审批-绩效审批
    public static function get_capability_approval_sql(){
        $me              = L('ME');
        list($username) = explode('|', $me->username);
        $is_genee = $username == 'genee';

        #$task = Q("capability_task[status=1]:sort(source_id D)")->current();
        $now = Date::time();
        
	$task = Q("capability_task[status=1][dtstart<{$now}][dtend>{$now}]:sort(source_id D)")->current();
	
	if (!$task->id) return '';  
	$selecter = "{$task} capability_equipment_task";

        if (!($me->access('管理所有内容') || $is_genee)) {
            if ($me->access('管理所有仪器绩效考核')) {
                // 所有
            } else if ($me->access('管理下属机构仪器绩效考核')) {
                $groups = [];
                $tmpgroups = [$me->group->parent_id];
                while(count($tmpgroups)) {
                    $regroups = Q("tag[parent=".join(',', $tmpgroups)."][root=".Tag_Model::root('group')."]");
                    $tmpgroups = [];
                    foreach ($regroups as $regroup) {
                        $tmpgroups[] = $regroup->id;
                        $groups[] = $regroup->id;
                    }
                }
                //下属机构
                $selecter .= "[group_id=".implode(',', $groups)."]";
            } else {
                //无
                $selecter .= "[group_id=-1]";
            }
        }

        $status_selecter = [];
        if ($me->access('初审绩效') || $is_genee){
            $status_selecter[] = 'process_status=5';
        }
        if ($me->access('复审绩效') || $is_genee){
            $status_selecter[] = 'process_status=10';
        }
        $selecter .= "[".implode('|', $status_selecter)."]";
        return $selecter;
    }
    public static function _index_capability_approval_content($e, $tabs){
        $approvals = Q(self::get_capability_approval_sql());
        $start           = (int) Input::form('st');
        $per_page        = 2;
        $pagination      = Lab::pagination($approvals, $start, $per_page);
        $tabs->content   = V('dashboard/tabs/capability_approval', [
            'approval'       => $approvals,
            'pagination'    => $pagination
        ]);
    }



    // 待填报-基金申报
    public static function get_equipment_sql () {
        $me              = L('ME');
        list($username, $backend) = explode('|', $me->username);
        $is_genee = $username == 'genee' || $username == 'Support';
        // 获取到当前的申报
        $selecter = "equipment";
        // 获取所有的仪器
        if (!$is_genee) {
            if ($me->access('填报所有基金申请')) {
                $selecter = "equipment";
            } else if ($me->access('填报负责仪器的基金申请')) {
                $selecter = "{$me}<incharge equipment";
            } else {
                $selecter = "equipment#-1";
            }
        }
        return $selecter;
    }
    public static function get_fundreport_fillin_sql(){
        $now = Date::time();
	    $fund_report_annual = Q("fund_report_annual[dtstart<{$now}][dtend>{$now}]")->current();
	    if (!$fund_report_annual->id){
		    return "";
	    }
        $selecter = self::get_equipment_sql();
        $s2 = "fund_report_apply[type=开发费][status!=审批驳回][fund_report_annual_id={$fund_report_annual->id}]";
        $s3 = "fund_report_apply[type=维修费][status!=审批驳回][fund_report_annual_id={$fund_report_annual->id}]";
        $equipment = [];
        foreach (Q("($s2, $s3) equipment") as $ke) {
            $equipment[] = $ke->id;
        }
        if (count($equipment)) {
            $selecter .= "[id!=".implode(',', $equipment)."]";
        }
        return $selecter;
    }
    public static function _index_fundreport_fillin_content($e, $tabs){
        $fillin = Q(self::get_fundreport_fillin_sql());
        $start           = (int) Input::form('st');
        $per_page        = 20;
        $pagination      = Lab::pagination($fillin, $start, $per_page);

        $tabs->content   = V('dashboard/tabs/fundreport_fillin', [
            'fillin'       => $fillin,
            'pagination'    => $pagination
        ]);
    }
    // 待填报-绩效考核
    public static function get_capability_fillin_sql(){
        $me              = L('ME');
        list($username) = explode('|', $me->username);
        $is_genee = $username == 'genee';
        
	    $now = Date::time();
        
	    $task = Q("capability_task[status=1][dtstart<{$now}][dtend>{$now}]:sort(source_id D)")->current();
	
	    if (!$task->id) return '';
        $selecter = "capability_equipment_task[capability_task={$task}]";
        if (!($me->access('管理所有内容') || $is_genee)) {
            if ($me->access('填报效益')) {
                // 获取我负责的仪器
                $selecter = "{$me} capability_equipment_task_user capability_equipment_task[capability_task={$task}]";
            }
        }
        $selecter .= "[process_status=0|process_status=20]";
        return $selecter;
    }
    public static function _index_capability_fillin_content($e, $tabs){
        $fillin = Q(self::get_capability_fillin_sql());
        $start           = (int) Input::form('st');
        $per_page        = 20;
        $pagination      = Lab::pagination($fillin, $start, $per_page);

        $tabs->content   = V('dashboard/tabs/capability_fillin', [
            'fillin'       => $fillin,
            'pagination'    => $pagination
        ]);
    }

    // 待报销-收费确认
    public static function _index_eqcharge_reimbursement_content($e, $tabs) {
        $form = Input::form();
        $start           = $form['st'];
        $per_page        = 20;
        $selector = "{$tabs->user} equipment.incharge eq_charge[amount!=0][confirm=".EQ_Charge_Confirm_Model::CONFIRM_PENDDING."].equipment:sort(ctime D)";
        $charges = Q($selector);
        $pagination = Lab::pagination($charges, $start, $per_page);
        $tabs->content = V('dashboard/tabs/eqcharge_reimbursement', [
            'pagination' => $pagination,
            'charges' => $charges,
            'form' => $form,
            'no_search' => true,
            'no_select' => true
        ]);
    }




    public static function get_links($ap){
        $links = [];
        $now = Date::time();
        $dev = "fund_report_annual[dtstart<{$now}][dtend>{$now}]<fund_report_annual fund_report_apply[type=开发费][status!=审批驳回] {$ap}";
        $maintain = "fund_report_annual[dtstart<{$now}][dtend>{$now}]<fund_report_annual fund_report_apply[type=维修费][status!=审批驳回] {$ap}";
        if (!Q($dev)->total_count()) {
            $links['fillin_dev'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">'.I18N::T('eq_charge', '填报开发费').'</span>',
                'tip' => I18N::T('eq_charge', '填报开发费'),
                'extra' => 'class="blue" q-src="' . URI::url('!people/dashboard/') .
                    '" q-static="id=' . $ap->id . '" q-event="click" q-object="fillin_dev_apply"',
            ];
        }
        if (!Q($maintain)->total_count()) {
            $links['fillin_maintain'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">'.I18N::T('eq_charge', '填报维修费').'</span>',
                'tip' => I18N::T('eq_charge', '填报维修费'),
                'extra' => 'class="blue" q-src="' . URI::url('!people/dashboard/') .
                    '" q-static="id=' . $ap->id . '" q-event="click" q-object="fillin_maintain_apply"',
            ];
        }
        return $links;
    }

    public static function get_links_capability($ap){
        $links = [];
        if ((int)$ap->process_status === 5) {
            $links['capability_f'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">'.I18N::T('eq_charge', '初审').'</span>',
                'tip' => I18N::T('eq_charge', '初审'),
                'extra' => 'class="blue" q-src="' . URI::url('!people/dashboard/') .
                    '" q-static="id=' . $ap->source_id . '" q-event="click" q-object="capability_f_approval"',
            ];
        };
        if ((int)$ap->process_status === 10) {
            $links['capability_t'] = [
                'url' => NULL,
                'text' => '<span class="after_icon_span">'.I18N::T('eq_charge', '复审').'</span>',
                'tip' => I18N::T('eq_charge', '复审'),
                'extra' => 'class="blue" q-src="' . URI::url('!people/dashboard/') .
                    '" q-static="id=' . $ap->source_id . '" q-event="click" q-object="capability_t_approval"',
            ];
        };
        return $links;
    }

}
