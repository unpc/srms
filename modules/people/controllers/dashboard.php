<?php
class Dashboard_Controller extends Base_Controller
{

    public function index($tab = null)
    {

        $me = L('ME');
        if (!$me->id) {
            URI::redirect('error/404');
        }

        //获取到默认打开的$tab;
        $card_list = dashboard::get_card();
        if ($tab === null) {
            foreach ($card_list as $card) {
                $tab = "default_".$card['key'];
                break;
            }
        } else {
            $tmptab = '';
            $judge = false;
            foreach ($card_list as $card) {
                $tmptab = "default_".$card['key'];
                if (strpos($tab, $card['key'])) {
                    $judge = true;
                    break;
                }
            }
            if (!$judge) $tab = $tmptab;
        }

        $content = V('dashboard/view', ['me' => $me, 'card_list' => $card_list]);
        $this->layout->body->primary_tabs
            ->add_tab('dashboard', [
                'url'    => URI::url('!people/dashboard'),
                'title'  => I18N::T('people', '个人主页'),
                'weight' => 0,
            ])
            ->set('active_tab', $tab)
            ->set('content', $content)
            ->select('dashboard');

        $this->layout->body->primary_tabs->set_tab('dashboard', null);
        $this->layout->body->primary_tabs->set_tab('all', null);

        $active = explode('_', $tab)[1];

        Event::bind('dashboard.view.tab', [$this, "_index_{$active}_tab"], 0, $tab);

        $content->secondary_tabs = Widget::factory('tabs');

        $content->secondary_tabs
        //  ->set('class', 'secondary_tabs')
            ->set('user', $me)
            ->tab_event('dashboard.view.tab')
            ->content_event('dashboard.view.content')
        // ->tool_event('dashboard.view.tool_box') // 没有search_box和panel_button就不用这个了
            ->select($tab);

        $this->layout->title = I18N::T('people', '个人主页');
        $this->layout->header_content = V('people:dashboard/header_content',
            [
                'card_list' => $card_list,
                'active_tab' => $tab
            ]);

        $this->add_css('people:dashboard');

        $this->add_css('preview');
        $this->add_js('preview');
        $this->add_css('labs:common');
    }

    public function _index_feedback_tab($e, $tabs)
    {
        Event::bind('dashboard.view.content', [$this, '_index_record_feedback_content'], 0, 'record_feedback');
        $tabs->add_tab('record_feedback', [
            'url'   => URI::url('!people/dashboard/index.record_feedback'),
            'title' => I18N::T('people', "使用反馈"),
        ]);

        if (Module::is_installed('eq_comment')) {
            Event::bind('dashboard.view.content', [$this, '_index_sample_feedback_content'], 0, 'sample_feedback');
            $tabs->add_tab('sample_feedback', [
                'url'   => URI::url('!people/dashboard/index.sample_feedback'),
                'title' => I18N::T('people', "送样反馈"),
            ]);
        }
    }

    public function _index_record_feedback_content($e, $tabs)
    {
        $me            = L('ME');
        $status        = EQ_Record_Model::FEEDBACK_NOTHING;
        $records       = Q("eq_record[is_locked=0][status=$status][user={$me}]");
        $start         = (int) Input::form('st');
        $per_page      = 20;
        $pagination    = Lab::pagination($records, $start, $per_page);
        $tabs->content = V('dashboard/tabs/record_feedback', ['records' => $records, 'pagination' => $pagination]);
    }

    public function _index_sample_feedback_content($e, $tabs)
    {
        $me         = L('ME');
        $status     = EQ_Sample_Model::STATUS_TESTED;
        $samples    = Q("eq_sample[sender={$me}][is_locked=0][status=$status][feedback=0]");
        $start      = (int) Input::form('st');
        $per_page   = 20;
        $pagination = Lab::pagination($samples, $start, $per_page);

        $fields        = EQ_Sample::get_sample_field_with_user_sample([], ['user' => $me]);
        $tabs->columns = new ArrayObject($fields);
        $tabs->content = V('eq_sample:samples_for_profile', [
            'user'       => $me,
            'samples'    => $samples,
            'columns'    => $tabs->columns,
            'pagination' => $pagination,
            'time'       => strtotime("+1 day"),
        ]);
    }

    public function _index_billing_tab($e, $tabs)
    {
        Event::bind('dashboard.view.content', [$this, '_index_transaction_billing_content'], 0, 'transaction_billing');
        Event::bind('dashboard.view.content', [$this, '_index_distribution_billing_content'], 0, 'distribution_billing');

        $tabs->add_tab('transaction_billing', [
            'url'   => URI::url('!people/dashboard/index.transaction_billing.1'),
            'title' => I18N::T('people', "报销项目"),
        ]);

        $tabs->add_tab('distribution_billing', [
            'url'   => URI::url('!people/dashboard/index.distribution_billing.1'),
            'title' => I18N::T('people', "报销单"),
        ]);
    }

    public function _index_transaction_billing_content($e, $tabs)
    {
        $me                 = L('ME');
        $form               = Input::form();
        $transactions       = [];
        $total_transactions = 0;
        $total_amount        = 0;
        $start              = (int) Input::form('st');
        $per_page           = 10;

        if (Module::is_installed('billing_standard')) {
            $lab = Q("$me lab")->current();
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);

            try {
                $res = $rpc->transaction->list($start, $lab->id, $form);

                $transactions = $res['transactions'];
                $total_transactions = $res['total_transactions'];
                $total_amount = $res['total_amount'];
            } catch (\Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '网络请求异常，获取报销项目失败!'));
            }
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $total_transactions
        ]);

        $tabs->content = V('dashboard/tabs/transaction_billing', [
            'form' => $form,
            'transactions' => $transactions,
            'total_transactions' => $total_transactions,
            'total_amount' => $total_amount,
            'pagination' => $pagination
        ]);
    }

    public function _index_distribution_billing_content($e, $tabs)
    {
        $me                  = L('ME');
        $form                = Input::form();
        $distributions       = [];
        $total_distributions = 0;
        $total_amount        = 0;
        $start               = (int) Input::form('st');
        $per_page            = 10;

        if (Module::is_installed('billing_standard')) {
            $lab = Q("$me lab")->current();
            $opt = Config::get('rpc.servers')['billing_standard'];
            $rpc = new RPC($opt['api']);

            try {
                $res = $rpc->distribution->list($start, $lab->id, $form);

                $distributions = $res['distributions'];
                $total_distributions = $res['total_distributions'];
                $total_amount = $res['total_amount'];
            } catch (\Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing_standard', '网络请求异常，获取报销单失败!'));
            }
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $total_distributions
        ]);

        $tabs->content = V('dashboard/tabs/distribution_billing', [
            'form' => $form,
            'distributions' => $distributions,
            'total_distributions' => $total_distributions,
            'total_amount' => $total_amount,
            'pagination' => $pagination
        ]);
    }

    public function _index_follow_tab($e, $tabs)
    {
        Event::bind('dashboard.view.content', [$this, '_index_equipment_follow_reserv_content'], 0, 'equipment_follow_reserv');
        Event::bind('dashboard.view.content', [$this, '_index_equipment_follow_content'], 0, 'equipment_follow');
        Event::bind('dashboard.view.content', [$this, '_index_user_follow_content'], 0, 'user_follow');
        $tabs->add_tab('equipment_follow_reserv', [
            'url'   => URI::url('!people/dashboard/index.equipment_follow_reserv'),
            'title' => I18N::T('people', "关注可预约仪器"),
        ]);
        $tabs->add_tab('equipment_follow', [
            'url'   => URI::url('!people/dashboard/index.equipment_follow'),
            'title' => I18N::T('people', "关注非预约仪器"),
        ]);
        if (!People::perm_in_uno()) {
            $tabs->add_tab('user_follow', [
                'url'   => URI::url('!people/dashboard/index.user_follow'),
                'title' => I18N::T('people', "关注成员"),
            ]);
        }
    }

    public function _index_equipment_follow_content($e, $tabs)
    {
        $me            = L('ME');
        $selector       = "equipment[accept_reserv=0]<object follow[user={$me}][object_name=equipment]:sort(ctime A)";
        $follows       = Q($selector);
        $extra_follows = Event::trigger('equipment.extra.follows', $follows);
        $follows       = count($extra_follows) ? $extra_follows : $follows;
        $start         = (int) Input::form('st');
        $per_page      = 20;
        $pagination    = Lab::pagination($follows, $start, $per_page);
        $tabs->content = V('equipments:follow/equipments', ['follows' => $follows, 'pagination' => $pagination]);
    }

    public function _index_equipment_follow_reserv_content($e, $tabs)
    {
        $me            = L('ME');
        $selector       = "equipment[accept_reserv=1]<object follow[user={$me}][object_name=equipment]:sort(ctime A)";
        $follows       = Q($selector);
        $extra_follows = Event::trigger('equipment.extra.follows', $follows);
        $follows       = count($extra_follows) ? $extra_follows : $follows;
        $start         = (int) Input::form('st');
        $per_page      = 20;
        $pagination    = Lab::pagination($follows, $start, $per_page);
        $tabs->content = V('equipments:follow/equipments_reserv', ['follows' => $follows, 'pagination' => $pagination, 'reserv' => true]);
    }

    public function _index_user_follow_content($e, $tabs)
    {
        $me            = L('ME');
        $follows       = $me->followings('user');
        $start         = (int) Input::form('st');
        $per_page      = 20;
        $pagination    = Lab::pagination($follows, $start, $per_page);
        $tabs->content = V('follow/users', ['follows' => $follows, 'pagination' => $pagination]);
    }

    public function _index_reimbursement_tab($e, $tabs)
    {
        $me = L('ME');
        Event::bind('dashboard.view.content', 'dashboard::_index_eqcharge_reimbursement_content', 0, 'eqcharge_reimbursement');
        if ($me->is_equipment_charge()) {
            // 收费确认
            if (Module::is_installed('eq_charge_confirm') && $me->access('确认负责仪器的收费')) {
                $tabs->add_tab('eqcharge_reimbursement', [
                    'url'   => URI::url('!people/dashboard/index.eqcharge_reimbursement'),
                    'title' => I18N::T('people', "收费确认"),
                ]);
            }
        }
    }

    public function _index_prepare_reimbursement_content($e, $tabs)
    {
        $me            = L('ME');
        $charges       = Q("eq_charge[amount>0][is_locked=0][user={$me}]");
        $start         = (int) Input::form('st');
        $per_page      = 20;
        $pagination    = Lab::pagination($charges, $start, $per_page);
        $tabs->content = V('dashboard/tabs/prepare_reimbursemen', ['charges' => $charges, 'pagination' => $pagination]);
    }


    static function _index_used_tab($e, $tabs){
        Event::bind('dashboard.view.content', 'dashboard::_index_equipment_reserv_content', 0, 'eqreserv_used');
        Event::bind('dashboard.view.content', 'dashboard::_index_equipment_sample_content', 0, 'eqsample_used');
        if (Module::is_installed('technical_service')){
            $tabs->add_tab('serviceapply_used', [
                'url'   => URI::url('!people/dashboard/index.serviceapply_used'),
                'title' => I18N::T('people', "服务预约"),
                'weight'=> 50
            ]);
            Event::bind('dashboard.view.content', 'Technical_Service::_index_service_apply_content', 50, 'serviceapply_used');
        }
        $tabs->add_tab('eqreserv_used', [
            'url'   => URI::url('!people/dashboard/index.eqreserv_used'),
            'title' => I18N::T('people', "仪器预约"),
        ]);
        $tabs->add_tab('eqsample_used', [
            'url'   => URI::url('!people/dashboard/index.eqsample_used'),
            'title' => I18N::T('people', "仪器送样"),
        ]);
    }

    public function _index_test_tab($e, $tabs)
    {
        Event::bind('dashboard.view.content', [$this,'_index_eqsample_tested_content'], 0, 'sample_test');
        $tabs->add_tab('sample_test', [
            'url'   => URI::url('!people/dashboard/index.sample_test'),
            'title' => I18N::T('people', "送样检测"),
        ]);
        if (Module::is_installed('technical_service')){
            $tabs->add_tab('serviceapply_test', [
                'url'   => URI::url('!people/dashboard/index.serviceapply_test'),
                'title' => I18N::T('people', "服务项目待检测"),
                'weight'=> 50
            ]);
            Event::bind('dashboard.view.content', 'Technical_Service::_index_service_apply_test_content', 50, 'serviceapply_test');
        }
    }

    public function _index_eqsample_tested_content($e, $tabs)
    {
        $me         = L('ME');
        $status     = EQ_Sample_Model::STATUS_APPROVED;
        $samples    = Q("{$me}<incharge equipment eq_sample[status=".EQ_Sample_Model::STATUS_APPROVED."]:sort(ctime D)");
        $start      = (int) Input::form('st');
        $per_page   = 20;
        $pagination = Lab::pagination($samples, $start, $per_page);

        $fields = [
            'serial_number'  => [
                'title'    => I18N::T('eq_sample', '编号'),
                'align'    => 'left',
                'weight'   => 10,
                'nowrap'   => true,
            ],
            'equipment_name' => [
                'title'    => I18N::T('eq_sample', '仪器'),
                'align'    => 'left',
                'weight'   => 20,
                'nowrap'   => true,
            ],
            'sender'=>[
                'title'=>I18N::T('eq_sample', '申请人'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'weight' => 30,
            ],
            'count'          => [
                'title'    => I18N::T('eq_sample', '样品数'),
                'align'    => 'right',
                'weight'   => 30,
                'nowrap'   => true,
            ],
            'dtctime'       => [
                'title'    => I18N::T('eq_sample', '送样申请时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'weight'   => 40,
            ],
            'dtsubmit'       => [
                'title'    => I18N::T('eq_sample', '送样时间'),
                'align'    => 'left',
                'weight'   => 50,
                'nowrap'   => true,
            ],
            'operator'       => [
                'title'    => I18N::T('eq_sample', '操作者'),
                'align'    => 'center',
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

        $tabs->content = V('eq_sample:samples_for_tested', [
            'user'       => $me,
            'samples'    => $samples,
            'columns'    => new ArrayObject($fields),
            'pagination' => $pagination,
            'time'       => strtotime("+1 day"),
        ]);
    }


    public function _index_approval_tab($e, $tabs)
    {
        $me = L('ME');

        Event::bind('dashboard.view.content', 'dashboard::_index_people_content', 0, 'people_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_lab_content', 0, 'lab_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_reserv_approval_content', 0, 'reserv_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_sample_approval_content', 0, 'sample_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_authorization_approval_content', 0, 'authorization_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_fundreport_approval_content', 0, 'fundreport_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_capability_approval_content', 0, 'capability_approval');
        Event::bind('dashboard.view.content', 'dashboard::_index_billing_approval_content', 0, 'billing_approval');

        // 成员审批
        if (!People::perm_in_uno()) {

        if ($me->access('管理所有内容') || $me->access('添加/修改所有成员信息') || $me->access('添加/修改下属机构成员的信息') || $me->is_lab_pi()) {
            $tabs->add_tab('people_approval', [
                'url'   => URI::url('!people/dashboard/index.people_approval.1'),
                'title' => I18N::T('people', "成员审批"),
            ]);
        }

        // 课题组审批
        if ($me->access('管理所有内容') || $me->access('添加/修改实验室') || $me->access('添加/修改下属机构实验室')) {
            $tabs->add_tab('lab_approval', [
                'url'   => URI::url('!people/dashboard/index.lab_approval.1'),
                'title' => I18N::T('people', "课题组审批"),
            ]);
        }
        
        }

        // 预约审批
        if (Module::is_installed('yiqikong_approval') && ($me->access('管理所有内容') || $me->access('修改所有仪器的预约') || $me->access('修改下属机构仪器的预约') || $me->access('修改负责仪器的预约'))) {
            $tabs->add_tab('reserv_approval', [
                'url'   => URI::url('!people/dashboard/index.reserv_approval.1'),
                'title' => I18N::T('people', "预约审批"),
            ]);
        }
        if (Module::is_installed('approval_flow') && ((Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人') || $me->is_lab_pi() || $me->access('管理所有内容') || $me->access('修改所有仪器的预约') || $me->access('修改下属机构仪器的预约') || $me->access('修改负责仪器的预约'))) {
            $tabs->add_tab('reserv_approval', [
                'url'   => URI::url('!people/dashboard/index.reserv_approval.1'),
                'title' => I18N::T('people', "预约审批"),
            ]);
        }
        // 送样审批
        if (Module::is_installed('approval_flow') && (Q("{$me}<incharge lab_project")->total_count() && Switchrole::user_select_role() == '项目负责人') || $me->is_lab_pi() || $me->access('管理所有内容') || $me->access('修改所有仪器的送样') || $me->access('修改下属机构仪器的送样') || $me->access('修改负责仪器的送样')) {
            $tabs->add_tab('sample_approval', [
                'url'   => URI::url('!people/dashboard/index.sample_approval.1'),
                'title' => I18N::T('people', "送样审批"),
            ]);
        }

        // 仪器培训审批
        if ($me->access('管理所有内容') || $me->access('管理所有仪器的培训记录') || $me->access('管理下属机构仪器的培训记录') || $me->access('管理负责仪器的培训记录')) {
            $tabs->add_tab('authorization_approval', [
                'url'   => URI::url('!people/dashboard/index.authorization_approval.1'),
                'title' => I18N::T('people', "仪器培训/授权"),
            ]);
        }

        // 基金审批
        if (Module::is_installed('fund_report') && $me->is_allowed_to('审批基金申报', 'fund_report_apply')) {
            $tabs->add_tab('fundreport_approval', [
                'url'   => URI::url('!people/dashboard/index.fundreport_approval'),
                'title' => I18N::T('people', "基金审批"),
            ]);
        }

        //绩效审批
        if (Module::is_installed('capability') && $me->is_allowed_to('绩效审批', 'capability_equipment_task')) {
            $tabs->add_tab('capability_approval', [
                'url'   => URI::url('!people/dashboard/index.capability_approval'),
                'title' => I18N::T('people', "绩效审批"),
            ]);
        }

        // 服务审批
        if (Module::is_installed('technical_service')){
            if ($me->is_allowed_to('列表审批', 'service_apply')){
                $tabs->add_tab('serviceapply_approval', [
                    'url'   => URI::url('!people/dashboard/index.serviceapply_approval'),
                    'title' => I18N::T('people', "服务审批"),
                    'weight'=> 50
                ]);
            }
            Event::bind('dashboard.view.content', 'Technical_Service::_index_service_apply_approval_content', 50, 'serviceapply_approval');
        }

        Event::trigger('people.dashboard.tab.add',$tabs);

    }

    public function _index_fillin_tab($e, $tabs){
        Event::bind('dashboard.view.content', 'dashboard::_index_fundreport_fillin_content', 0, 'fundreport_fillin');
        Event::bind('dashboard.view.content', 'dashboard::_index_capability_fillin_content', 0, 'capability_fillin');
        $me = L('ME');
        // 基金申报
        if (Module::is_installed('fund_report') && $me->is_allowed_to('填报申请', 'fund_report_apply')) {
            $tabs->add_tab('fundreport_fillin', [
                'url' => URI::url('!people/dashboard/index.fundreport_fillin'),
                'title' => I18N::T('fund_report', "基金申报"),
            ]);
        }
        //绩效审批
        if (Module::is_installed('capability') && $me->is_allowed_to('列表效益填报', 'capability_equipment_task')) {
            $tabs->add_tab('capability_fillin', [
                'url'   => URI::url('!people/dashboard/index.capability_fillin'),
                'title' => I18N::T('people', "绩效填报"),
            ]);
        }
    }
}


class Dashboard_AJAX_Controller extends AJAX_Controller
{
    public function index_approval_apply_click()
    {
        $host = $_SERVER['HTTP_HOST'].'/fundreport';
        $form = Form::filter(Input::form());
        $url = "http://{$host}/approve/view/{$form['id']}/?oauth-sso=fundreport.". LAB_ID;
        JS::run("window.open('$url')");
    }

    public function index_capability_fillin_click() {
        $host = $_SERVER['HTTP_HOST'].'/capability';
        $form = Form::filter(Input::form());
        $url = "http://{$host}/benefit/report/{$form['id']}/?oauth-sso=capability.". LAB_ID;
        JS::run("window.open('$url')");
    }

    public function index_fillin_maintain_apply_click(){
        $host = $_SERVER['HTTP_HOST'].'/fundreport';
        $form = Form::filter(Input::form());
        $url = "http://{$host}/apply/maintain/?source_id={$form['id']}&&oauth-sso=fundreport.". LAB_ID;
        JS::run("window.open('$url')");
    }

    public function index_fillin_dev_apply_click(){
        $host = $_SERVER['HTTP_HOST'].'/fundreport';
        $form = Form::filter(Input::form());
        $url = "http://{$host}/apply/develop/?source_id={$form['id']}&&oauth-sso=fundreport.". LAB_ID;
        JS::run("window.open('$url')");
    }

    public function index_capability_f_approval_click(){
        $host = $_SERVER['HTTP_HOST'].'/capability';
        $form = Form::filter(Input::form());
        $url = "http://{$host}/approve/first/{$form['id']}/5/?oauth-sso=capability.". LAB_ID;
        JS::run("window.open('$url')");
    }
    public function index_capability_t_approval_click(){
        $host = $_SERVER['HTTP_HOST'].'/capability';
        $form = Form::filter(Input::form());
        $url = "http://{$host}/approve/second/{$form['id']}/10/?oauth-sso=capability.". LAB_ID;
        JS::run("window.open('$url')");
    }
}
