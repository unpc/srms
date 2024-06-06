<?php

class Equipment_Controller extends Base_Controller
{
    public function _before_call($method, &$params)
    {
        $links = OAuth_Client::get_oauth_login_links();

        if ($links) {
            foreach ($links as $key => $opts) {
                if ($_GET['oauth-sso']
                    && in_array($_GET['oauth-sso'], Config::get('oauth.sso_providers'))
                ) {
                    $_SESSION['#OAUTH_LOGIN_REFERER'] = Input::Route();
                    URI::redirect($opts['link']);
                }
                parent::_before_call($method, $params);
            }
        }
        parent::_before_call($method, $params);
    }

    //查看某个仪器的详细信息
    public function index($id = 0, $tab = 'dashboard')
    {
        $equipment = O('equipment', $id);
        $me        = L('ME');
        if (!$equipment->id) {
            URI::redirect('error/404');
        }
        if (!$me->is_allowed_to('查看', $equipment)) {
            URI::redirect('error/401');
        }

        /* (xiaopei.li@2011.04.11) */
        /* BUG #1039::如果一个仪器有公告，将仪器报废后，用户必须阅读公告才可查看仪器的其他tab页面。(kai.wu@2011.08.22) */
        if ($GLOBALS['preload']['equipment.enable_announcement']) {
            if ($equipment->status != EQ_STATUS_MODEL::NO_LONGER_IN_SERVICE) {
                if ($tab != 'announce') {
                    if (!$me->is_allowed_to('添加公告', $equipment) && Event::trigger('enable.announcemente', $equipment, $me)) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您需阅读过仪器公告，方可使用仪器!'));
                        URI::redirect($equipment->url('announce'));
                    }
                    Event::trigger('enable.announcemente.equipment', $equipment);
                }
            }
        }
        /* end(xiaopei.li@2011.04.11) */

       /* $this->layout->body->primary_tabs
           ->add_tab('profile', [
               'url'   => $user->url(),
               'title' => H($user->name),
           ])
           ->set('content', $content)
           ->select('profile'); */

        $this->layout->body->primary_tabs
            ->add_tab('view', ['url'=>$equipment->url(), 'title'=>H($equipment->name)])
            ->select('view');

        $content            = V('equipment/view');
        $content->equipment = $equipment;

        Event::bind('equipment.index.tab.content', [$this, '_index_dashboard'], 0, 'dashboard');
        Event::bind('equipment.index.tab.content', [$this, '_index_records'], 0, 'records');
        Event::bind('equipment.index.tab.content', [$this, '_index_status'], 0, 'status');
        Event::bind('equipment.index.tab.content', [$this, '_index_feedback'], 0, 'feedback');
        Event::bind('equipment.index.tab.content', [$this, '_index_exam_scores'], 0, 'exam_scores');

        Event::bind('equipment.index.tab.tool_box', [$this, '_tool_box_records'], 0, 'records');
        if ($GLOBALS['preload']['equipment.enable_announcement']) {
            Event::bind('equipment.index.tab.content', [$this, '_index_announce'], 0, 'announce');
        }

        $this->layout->body->primary_tabs
        = Widget::factory('tabs')
            ->set('equipment', $equipment)
            ->tab_event('equipment.index.tab')
            ->content_event('equipment.index.tab.content')
            ->tool_event('equipment.index.tab.tool_box')
            //->set('class', 'secondary_tabs')
            ;

        $this->layout->body->primary_tabs
            ->add_tab('dashboard', [
                'url'    => $equipment->url('dashboard'),
                'title'  => I18N::T('equipments', '常规信息'),
                'weight' => 0,
            ]);

        if (Module::is_installed('nfs') && $me->is_allowed_to('列表文件', $equipment, ['type' => 'attachments'])) {
            $this->layout->body->primary_tabs
                ->add_tab('attachments', [
                    'url'=> Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.attachments") ?: $equipment->url('attachments'),
                    'title'=>I18N::T('equipments', '相关附件'),
                    'weight' => 80,
                ]);
            Event::bind('equipment.index.tab.content', [$this, '_index_attachments'], 0, 'attachments');
        }

        if ($me->id && $me->is_allowed_to('查看仪器使用记录', $equipment)) {
            $this->layout->body->primary_tabs
                ->add_tab('records', [
                    'url'=> Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.records") ?: $equipment->url('records'),
                    'title'=>I18N::T('equipments', '使用记录'),
                    'weight' => 30
                ]);
        }
        if ($me->is_allowed_to('查看仪器状态记录', $equipment)) {
            $this->layout->body->primary_tabs
                ->add_tab('status', [
                    'url'=> Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.status") ?: $equipment->url('status'),
                    'title'=>I18N::T('equipments', '状态记录'),
                    'weight' => 60,
                ]);
        }

        $now    = time();
        $me     = L('ME');
        $record = Q("eq_record[equipment=$equipment][user=$me][dtend>0][dtend<=$now][status=0]:sort(dtend D):limit(1)")->current();
        if ($record->id) {
            $this->layout->body->primary_tabs
                ->add_tab('feedback', [
                    'url'=> Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.feedback") ?: $equipment->url('feedback'),
                    'title'=>I18N::T('equipments', '使用反馈'),
                    'weight' => 40
                ]);
                $this->layout->body->primary_tabs->record = $record;
        }
        if (Module::is_installed('exam') && $me->is_allowed_to('列表仪器考试记录', $equipment) && $equipment->require_exam){
            $this->layout->body->primary_tabs
                ->add_tab('exam_scores', [
                    'url'=> $equipment->url('exam_scores'),
                    'title'=>I18N::T('equipments', '考试记录'),
                    'weight' => 40
                ]);
        }

        //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价
        $access = Event::trigger("feedback.need_evaluate_by_source",[],'ignore');
        if(true === $access){
            $record = Q("eq_record[equipment=$equipment][user=$me][dtend>=1541088000][dtend<=$now][status!=0][evaluate_id=0]:sort(dtend D):limit(1)")->current();
            if ($record->id) {
                $this->layout->body->primary_tabs
                    ->add_tab('feedback', [
                        'url'=> Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.feedback") ?: $equipment->url('feedback'),
                        'title'=>I18N::T('equipments', '使用反馈'),
                        'weight' => 40
                    ]);
                    $this->layout->body->primary_tabs->record = $record;
            }
        }

        /* eq_announce(xiaopei.li@2011.04.11) */
        if ($GLOBALS['preload']['equipment.enable_announcement']) {
            if ($me->is_allowed_to('查看仪器公告', $equipment)) {
            $this->layout->body->primary_tabs
                ->add_tab('announce', [
                              'url' => Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.announce") ?: $equipment->url('announce'),
                              'title' => I18N::T('equipments', '仪器公告'),
                              'weight' => 100,
                              ]);
            }
        }

        $this->layout->body->primary_tabs->select($tab);

        $breadcrumbs = [
            [
                'url' => '!equipments/index',
                'title' => I18N::T('equipments', '仪器目录'),
            ],
            [
                'title' => $equipment->name,
            ]
        ];

        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('equipment/header_content', ['equipment' => $equipment]);
        $this->layout->title = I18N::T('equipments', '');

        $this->add_css('equipments:common');
        $this->add_css('equipments:follow');
    }

    function _index_exam_scores($e, $tabs){
        $equipment = $tabs->equipment;
        $form = Form::filter(Input::form());
        $this->layout->form = $form;
        $exam = Q("{$equipment} exam")->current();

        $history_exams =  (array)$equipment->history_exams;
        $exams_id_str = implode(',', $history_exams);
        $remote_exam_app = Config::get('exam.remote_exam_app');
        $remote_ids = Q("exam[id={$exams_id_str}][remote_app={$remote_exam_app}]")->to_assoc('remote_id', 'remote_id');
        $limit = 20;
        if (count($remote_ids) == 0) $remote_ids[] = $exam->remote_id;
        // $result = (new HiExam())->get("exam/{$exam->remote_id}/users/list",
        //             ['currentPage'=> (int)($form['st']/$limit),'pageSize'=>$limit]);
        // $response = (new HiExam())->get("exam/{$exam->remote_id}/users/count");

        $result = (new HiExam())->get("exams/users/list",
                    ['currentPage'=> (int)($form['st']/$limit),'pageSize'=>$limit, 'exams'=>$remote_ids]);
        $response = (new HiExam())->get("exams/users/count", ['exams'=>$remote_ids]);


        $total = 0;
        if (isset($response['count'])) {
            $total = $response['count'];
        }
        $users = [];
        if (isset($result['list'])) {
            $list = $result['list'];
            foreach ($list as $li) {
                $user = O('user', ['gapper_id'=>$li['id']]);
                $lab = '--';
                $group = '--';
                if ($user->id) {
                    $labs = Q("$user lab");
                    $ls = [];
                    foreach ($labs as $lab) {
                        $ls[] = $lab->name;
                    }
                    $lab = implode(',', $ls);

                    $tag = $user->group;
                    $anchors = [];
                    if ($tag) {
                        $tag_root = $tag->root;
                        if ($tag->id !== Tag_Model::root('group')->id) {
                            foreach ((array) $tag->path as $unit) {
                                list($tag_id, $tag_name) = $unit;
                                $anchors[] = HT($tag_name);
                            }
                        }
                    }
                    $group = implode(',', $anchors);
                }
                $users[] = [
                    'gapper_id' => $li['id'],
                    'name' => $li['name'],
                    'finish_time' => $li['finish_time']?:'--',
                    'status' => $li['status'],
                    'exam' => $li['exam_title'],
                    'lab' =>  $lab,
                    'group' => $group
                ];
            }
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $form['st'],
            'per_page' => $limit,
            'total' => $total,
        ]);

        $tabs->content = V('equipment/exam.scores',[
            'equipment'=>$equipment,
            'pagination' => $pagination,
            'users' => $users
        ]);
    }

    public function _index_feedback($e, $tabs)
    {
        $record = $tabs->record;

        $types = [];
        if (class_exists('Lab_Project_Model')) {
            $labs = Q("{$record->user} lab");
            foreach ($labs as $lab) {
                $items = $lab->get_project_items($record->user);
                if ($record->project->id && $record->project->lab->id == $lab->id) {
                    $items[I18N::T('labs', Lab_Project_Model::$types[$record->project->type])][$record->project->id] = $record->project->name;
                }
                $types[] = [
                    'id'    => $lab->id . '_lab_id',
                    'name'  => $lab->name,
                    'items' => $items,
                ];
            }

            //黑名单过滤掉已经被ban掉的课题组
            if(Module::is_installed('eq_ban')){
                $unBannedLabs = EQ_Ban::get_eq_unbanned_lab($record->user,$record->equipment);
                foreach($types as $key => $type){
                    if(!array_key_exists(explode('_',$type['id'])[0],$unBannedLabs)){
                        unset($types[$key]);
                    }
                }
            }

            //如果没有实验室项目，同时使用记录必须关联实验室项目，那么结束反馈操作
            //if (!count($types) && Config::get('eq_record.must_connect_lab_project')) return FALSE;
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form->validate('record_status', 'not_empty', I18N::T('equipments', '请选择使用状态!'));
            if ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM) {
                $form->validate('feedback', 'not_empty', I18N::T('equipments', '请填写反馈信息!'));
            }

            if (class_exists('Lab_Project_Model') && Config::get('eq_record.must_connect_lab_project')) {
                if (isset($form['project_lab']) && $form['project_lab'] == 0) {
                    $form->set_error('project_lab', I18N::T('equipments', '"实验室" 不能为空!'));
                }
                if ($form['project'] == '--' || $form['project'] == 0) {
                    $form->set_error('project', I18N::T('equipments', '"关联项目" 不能为空!'));
                }
            }

            //如果没被锁定, 并且require_samples
            if (!$record->cannot_lock_samples() && !$record->samples_lock && Config::get('feedback.require_samples')) {
                /**
                 * allow_zero没有用哇
                 * 西交大还要更细粒度的判断 "培训|教学|保养维修"的使用类型下可填写为0
                 * trigger一下
                 */
                $is_feedback_problem = ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM);
                if (Config::get('equipment.feedback_samples_allow_zero', FALSE) || Event::trigger('eq_record.check_samples_is_allow_zero', $record)) {
                    if (!is_numeric($form['samples']) || intval($form['samples'])<0 || intval($form['samples'])!=$form['samples']) {
                        $form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                    }
                } else {
                    if ($is_feedback_problem) {
                        if (!is_numeric($form['samples']) || intval($form['samples'])<0 || intval($form['samples'])!=$form['samples']) {
                            $form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                        }
                    } else {
                        if (!is_numeric($form['samples']) || intval($form['samples'])<=0 || intval($form['samples'])!=$form['samples']) {
                            $form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
                        }
                    }
                }
            }

            if (Config::get('eq_record.duty_teacher') && !$form['duty_teacher'] && $record->equipment->require_dteacher) {
                $form->set_error('duty_teacher', I18N::T('equipments', '请选择值班老师!'));
            }

            if(Config::get('eq_record.tag_duty_teacher')) {
                Event::trigger('extra.form.validate_duty_teacher', $equipment,$record, $form);
            }

            Event::trigger('feedback.form.submit', $record, $form);

            if ($form->no_error) {
                if (class_exists('Lab_Project_Model')) {
                    $record->project = O('lab_project', $form['project']);
                }
                $record->feedback = $form['feedback'];
                $record->status   = $form['record_status'];

                //设定samples
                if (!$record->samples_lock && (isset($form['samples']) && $form['samples'] >= 0)) {
                    $record->samples = (int) $form['samples'];
                }
                /*
                TODO 因为样品数的增加导致用户反馈时候会修改按照送样计费的记录的收费, 目前该功能需要暂时关闭, 待之后详细的方案出来之后再进行修正
                 */
                /*
                $old_samples = (int)$record->samples;
                $record->samples = $new_samples = max(1, (int)$form['samples']);
                 */

                if (Config::get('eq_record.duty_teacher') && $record->equipment->require_dteacher) {
                    $duty_teacher         = O('user', $form['duty_teacher']);
                    $record->duty_teacher = $duty_teacher;
                }

                if( $record->save() ){
                    $me = L('ME');
                    $equipment = $record->equipment;

                    Log::add(strtr('[equipments] %user_name[%user_id]填写了%equipment_name[%equipment_id]仪器的使用记录[%record_id]反馈', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id, '%record_id' => $record->id]), 'journal');

                    URI::redirect($record->equipment->url());
                }
            }
        }

        $this->layout->form = $form;
        $tabs->content = V('equipment/add.feedback',['types'=>$types, 'form' => $form,'record'=>$record]);
    }

    public function _index_dashboard($e, $tabs)
    {
        $equipment  = $tabs->equipment;
        $me         = L("ME");
        $sections   = new ArrayIterator;
        $sections[] =
        V('equipments:equipment/info')
            ->set('equipment', $equipment);

        $sections[] =
        V('equipments:equipment/current_user')
            ->set('equipment', $equipment);

        Event::trigger('equipment.view.dashboard.sections', $equipment, $sections);

        $tabs->content = V('equipment/dashboard', ['sections' => $sections]);
    }

    public function _index_status($e, $tabs)
    {
        $equipment     = $tabs->equipment;
        $statuses      = Q("eq_status[equipment=$equipment]:sort(ctime D)");
        $tabs->content = V('equipment/status', [
            'equipment' => $equipment,
            'statuses'  => $statuses,
        ]);
        $me            = L('ME');
        $panel_buttons = [];
        if ($me->is_allowed_to('修改仪器状态设置', $equipment)) {
            $panel_buttons[] = [
                'tip'   => I18N::T('status', '修改状态'),
                'text' => I18N::T('status', '修改状态'),
                'extra' => 'class="button button_add"',
                'url'   => $equipment->url('status', null, null, 'edit'),
            ];
        }

        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons]);
    }

    public function _index_attachments($e, $tabs)
    {
        if (!Module::is_installed('nfs')) {
            URI::redirect('error/404');
        }
        $equipment     = $tabs->equipment;
        $tabs->content = V('equipments:equipment/attachments', [
            'object'    => $equipment,
            'path_type' => 'attachments',
        ]);
    }

    public function _index_records($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $type      = Input::form('type');

        $form = Lab::form(function (&$old_form, &$form) {
            unset($form['type']);

            if ($form == []) {
                $form     = $old_form;
                $old_form = [];
            }

            if ($form['reset_archive'] || $form['unarchive'] || $form['aid']) {
                $form['show_archive_list'] = true;
                if ($form['reset_archive'] || $form['unarchive']) {
                    unset($form['aid']);
                }
                $old_form = [];
            }

            if ($form['reserv_status'][0] == -1) {
                unset($form['reserv_status'][0]);
            }

            if (!count((array) $form['reserv_status'])) {
                unset($old_form['reserv_status']);
            }

        });

        $me = L('ME');

        $pre_selectors = new ArrayIterator;

        $selector = '';

        $user_allowed_list_record = $me->is_allowed_to('列表仪器使用记录', $equipment);

        $archive = O('tag', $form['aid']);

        if ($user_allowed_list_record) {
            $form['unarchive'] = true;
            if ($form['reset_archive'] || $form['aid']) {
                $form['unarchive'] = false;
            }

            if ($archive->id) {
                $pre_selectors[] = "$archive<archive";
            } elseif ($form['unarchive']) {
                // 未归档
                if (Q("$equipment<archive tag")->total_count() && Q("$equipment<archive tag<archive eq_record")->total_count()) {
                    // 判断是否有存档，并且有已存档的eq_record，才进行not处理
                    $next_selector = ":not($equipment<archive tag<archive eq_record)";
                }
            } else {
                $form['reset_archive'] = true;
            }
        }

        if ($form['user_name']) {
            $user_name       = Q::quote(trim($form['user_name']));
            $pre_selectors[] = "user[name*={$user_name}|name_abbr*={$user_name}]";
        }

        if ($form['lab_name']) {
            $lab_name = Q::quote(trim($form['lab_name']));

            if (!$user_allowed_list_record) {
                $pre_selectors[] = "lab[name*={$lab_name}|name_abbr*={$lab_name}] $user";
            } else {
                $pre_selectors[] = "lab[name*={$lab_name}|name_abbr*={$lab_name}] user";
            }
        }

        $group = O('tag_group', $form['group']);
        $group_root = Tag_Model::root('group');

        if (!$user_allowed_list_record) {
            // 不可查看该仪器所有使用记录，则显示自己的记录，附加搜索为group
            if ($group->id && $form['group'] != $group_root->id && $group->root->id == $group_root->id) {
                $pre_selectors[] = "{$group} $me";
            } else {
                $selector .= "user[id={$me->id}]";
            }
        } elseif ($group->id && $form['group'] != $group_root->id && $group->root->id == $group_root->id) {
            // 显示所有记录，但是进行group搜索
            $pre_selectors[] = "{$group} user";
        }

        $now = time();
        $selector .= " eq_record[equipment=$equipment][dtstart<=$now]";
        // $selector .= " eq_record[equipment=$equipment]";
        $new_selector = Event::trigger('eq_record.selector.modify', $selector, $form);

        // 按时间搜索
        if ($new_selector) {
            $selector = $new_selector;
        } else {
            if ($form['dtstart']) {
                $dtstart = Q::quote($form['dtstart']);
                $selector .= "[dtend>=$dtstart]";
            }

            if ($form['dtend']) {
                $dtend = Q::quote($form['dtend']);
                $dtend = Date::get_day_end($dtend);
                $selector .= "[dtend>0][dtend<=$dtend]";
            }
        }

        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id=$id]";
        }

        if (count((array) $form['reserv_status'])) {
            $reserv_status = $form['reserv_status'];
            $late = EQ_Reserv_Model::LATE;
            $overtime = EQ_Reserv_Model::OVERTIME;
            $leave_early = EQ_Reserv_Model::LEAVE_EARLY;
            $late_leave_early = EQ_Reserv_Model::LATE_LEAVE_EARLY;
            $late_overtime    = EQ_Reserv_Model::LATE_OVERTIME;
            if (in_array($late, $form['reserv_status'])) {
                $reserv_status[$late_overtime]    = 'on';
                $reserv_status[$late_leave_early] = 'on';
            }
            if (in_array($overtime, $form['reserv_status'])) {
                $reserv_status[$late_overtime] = 'on';
            }
            if (in_array($leave_early, $form['reserv_status'])) {
                $reserv_status[$late_leave_early] = 'on';
            }
            $flag = Q::quote($reserv_status);
            $selector .= "[flag={$flag}]";
        }

        if (Config::get('equipment.enable_use_type')) {
            if ($form['use_type']) {
                $use_type = Q::quote($form['use_type']);
                $selector .= "[use_type=$use_type]";
            }
        }

        if (Config::get('eq_record.duty_teacher') && $form['duty_teacher'] && $equipment->require_dteacher) {
            $duty_teacher = Q::quote($form['duty_teacher']);
            $selector .= "[duty_teacher_id={$duty_teacher}]";
        }

        if (isset($form['lock_status'])) {
            $is_locked = !!$form['lock_status'] ? 1 : 0;
            $selector .= "[is_locked=$is_locked]";
        }

        $new_selector = Event::trigger('eq_record.search_filter.submit', $form, $selector, $pre_selectors);
        if (null !== $new_selector) {
            $selector = $new_selector;
        }
        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', (array) $pre_selectors) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        $sort_str  = ':sort(dtstart D)';

        $new_sort_str = Event::trigger('eq_record.sort_str_factory', $form, $sort_str, $type);
        if (null !== $new_sort_str) {
            $sort_str = $new_sort_str;
        }

        $selector .= $sort_str;

        if ($next_selector) {
            $selector .= $next_selector;
        }

        $form_token            = Session::temp_token('eq_record_', 300);
        $form['equipment_id']  = $equipment->id;
        $form['page']          = 'equipment_records';
        $_SESSION[$form_token] = ['selector' => $selector, 'form' => $form];
        $form['form_token']    = $form_token;
        $tabs->form_token      = $form_token;

        $records = Q($selector);

        $types = ['print', 'csv'];
        if (!in_array($type, $types)) {
            $type = 'html';
        }

        $search_box_need_param = [
            'group_root'               => $group_root,
            'user_allowed_list_record' => $user_allowed_list_record,
        ];
        $tabs->columns = $this->get_records_columns($form, $search_box_need_param);

        $tabs->content = V('equipment/records', [
            'form'        => $form,
            'object'      => $equipment,
            'total_count' => $records->total_count(),
        ]);

        call_user_func('Equipments::list_records_' . $type, $records, $form, $tabs);
    }

    public function _tool_box_records($e, $tabs)
    {
        $me               = L('ME');
        // $columns接下来的table中的列直接可用，所以这里不释放中间变量
        // unset($tabs->columns);

        $equipment  = $tabs->equipment;
        $form_token = $tabs->form_token;
        unset($tabs->form_token);
        if ($me->is_allowed_to('添加仪器使用记录', $equipment)) {
            $panel_buttons[] = [
                'url'   => '',
                'tip'   => I18N::T('equipments', '添加记录'),
                'text' => I18N::T('equipments', '添加记录'),
                'extra' => 'class="button button_add middle view object:add_record event:click static:id=' . $equipment->id . '&oname=equipment"',
            ];
        }
        if ($me->is_allowed_to('列表仪器使用记录', $equipment)) {
            $panel_buttons[] = [
                'tip'   => I18N::T('equipments', '导出Excel'),
                'text' => I18N::T('equipments', '导出'),
                'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
                '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
                '" class="button button_save "',
            ];
            $panel_buttons[] = [
                'tip'   => I18N::T('equipments', '打印'),
                'text' => I18N::T('equipments', '打印'),
                'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
                '" q-static="' . H(['form_token' => $form_token, 'type' => 'print', 'eid' => $equipment->id]) .
                '" class = "button button_print  middle"',
            ];
        }

        $tabs->panels = $panel_buttons;
        $new_panel_buttons = Event::trigger('eq_record_lab_use.panel_buttons', $panel_buttons, $form_token);
        $panel_buttons     = $new_panel_buttons ? $new_panel_buttons : $panel_buttons;
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['serial_number', 'user_name'], 'columns' => $tabs->columns]);
    }

    public function get_records_columns($form, $search_box_need_param = [])
    {
        $me = L('me');

        if (is_array($search_box_need_param)) {
            extract($search_box_need_param);
        }

        if ($form['dtstart'] || $form['dtend']) {
            $form['date'] = true;
        }

        $columns = [
            'group'         => [
                'title'     => I18N::T('equipments', '组织机构'),
                'invisible' => true,
                'nowrap'    => true,
                'weight'    =>10,
            ],
            'select'=>[
                'align' => 'center',
            ],
            'serial_number' => [
                'title'  => I18N::T('equipments', '记录编号'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/serial_number', ['serial_number' => $form['id'], 'tip' => '请输入编号']),
                    'value' => $form['id'] ? Number::fill(H($form['id']), 6) : null,
                    'field' => 'id',
                ],

                'nowrap' => true,
                'weight' => 20,
            ],
            '@lock_status'  => [
                'nowrap' => true,
                'weight' => 30,
            ],
            'user_name'     => [
                'title'  => I18N::T('equipments', '使用者'),
                'nowrap' => true,
                'weight' => 40,
            ],

            'lab_name'      => [
                'title'     => I18N::T('equipments', '实验室'),
                'invisible' => true,
                'nowrap'    => true,
                'weight'    => 50,
            ],
            'status'        => [
                'title'     => I18N::T('equipments', '状态'),
                'filter'    => [
                    'form'   => V('equipments:records_table/filters/status', ['form_reserv_status' => $form['reserv_status']]),
                    'value'  => $form['reserv_status'] ? (implode(', ', array_map(function ($k) {
                        return EQ_Reserv_Model::$reserv_status[$k] == '正常使用' ? '正常' : EQ_Reserv_Model::$reserv_status[$k];
                    }, $form['reserv_status']))) : '',
                    'field'  => 'reserv_status',
                    'nowrap' => false,
                ],
                'invisible' => true,
                'weight'    => 60,
            ],

            'samples'       => [
                'title'  => I18N::T('equipments', '样品数'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 70,
            ],
            'agent'         => [
                'title'  => I18N::T('equipments', '代开'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 80,
            ],
            'date'          => [
                'title'  => I18N::T('equipments', '使用时间'),
                'filter' => [
                    'form'  => V(
                        'equipments:records_table/filters/date',
                        [
                            'dtstart' => $form['dtstart'],
                            'dtend'   => $form['dtend'],
                        ]
                    ),
                    'value' => $form['date'] ? H($form['date']) : null,
                    'field' => 'dtstart,dtend',
                ],
                'nowrap' => true,
                'weight' => 90,
            ],
            'charge_amount'     => [
                'title'  => I18N::T('equipments', '收费'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 100,
            ],
            'description'   => [
                'title'       => I18N::T('equipments', '备注'),
                'nowrap'      => true,
                'weight'      => 110,
            ],
            'feedback'      => [
                'title'  => I18N::T('equipments', '反馈'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 95,
            ],
            'rest'          => [
                'title'  => I18N::T('equipments', '操作'),
                'align'  => 'left',
                'nowrap' => true,
                'weight' => 200,
            ],
            'lock_status'   => [
                'title'     => I18N::T('equipments', '锁定状态'),
                'invisible' => true,
                'filter'    => [
                    'value' => isset($form['lock_status']) ? ($form['lock_status'] ? I18N::HT('equipments', '已锁定') : I18N::HT('equipments', '未锁定')) : null,
                ],
                'weight'    => 130,
            ],
        ];

        $columns2 = [
            'user_name' => [
                'filter' => [
                    'form'  => V('equipments:records_table/filters/user_name', ['user_name' => $form['user_name'], 'tip' => '请输入使用者姓名']),
                    'value' => $form['user_name'] ? H($form['user_name']) : null,
                ],
            ],
            'group'     => [
                'filter' => [
                    'form'  => V('equipments:records_table/filters/group', ['group' => $form['group']]),
                    'value' => ($form['group'] && $form['group'] != $group_root->id) ? H(O($group_root->name(), $form['group'])->name) : null,
                ],
            ],
        ];

        $installed_labs = Module::is_installed('labs');
        if ($installed_labs) {
            $columns2['lab_name'] = [
                'filter' => [
                    'form'  => V('equipments:records_table/filters/lab_name', ['lab_name' => $form['lab_name']]),
                    'value' => $form['lab_name'] ? H($form['lab_name']) : null,
                ],
            ];
        }


        if (Config::get('eq_record.duty_teacher') && O('equipment',$form['equipment_id'])->require_dteacher) {
            $columns['duty_teacher'] = [
                'title'  => I18N::T('equipments', '值班老师'),
                'filter' => [
                    'form'  => V('equipments:records_table/filters/duty_teacher', ['duty_teacher' => $form['duty_teacher']]),
                    'value' => $form['duty_teacher'] ? O('user', H($form['duty_teacher']))->name : null,
                    'field' => 'duty_teacher',
                ],
                'nowrap' => true,
                'weight' => 85,
            ];
        }

        if ($user_allowed_list_record) {
            $columns = array_merge_recursive($columns, $columns2);
        }

        $columns = new ArrayObject($columns);
        Event::trigger('eq_record.list.columns', $form, $columns, 'equipment_records');
        return (array)$columns;
    }

    /* eq_announce(xiaopei.li@2011.04.11) */
    public function _index_announce($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $selector  = "eq_announce[equipment={$equipment}]:sort(is_sticky D, mtime D)";

        $announces = Q($selector);

        $me            = L('ME');
        $panel_buttons = [];
        if ($me->is_allowed_to('添加公告', $equipment)) {
            $panel_buttons[] = [
                'tip'   => I18N::T('add_announce', '添加公告'),
                'text' => I18N::T('add_announce', '添加公告'),
                'extra' => 'class="button button_add" q-event="click" q-object="add_announce" q-static="' . H(['e_id' => $equipment->id]) . '" q-src="' . H(URI::url('!equipments/announce')) . '"',
            ];
        }

        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons]);

        $tabs->content = V('equipment/announce', [
            'equipment' => $equipment,
            'announces' => $announces,
        ]);
    }

    public function edit($id = 0, $tab = 'info', $stab = 'dashboard')
    {
        $me        = L('ME');
        $equipment = O('equipment', $id);
        // 仪器不存在，401错误
        if (!$equipment->id) {
            URI::redirect('error/404');
        }
        // 没有任何edit相关的权限，401错误
        if (!$me->is_allowed_to('修改', $equipment)) {
            URI::redirect('error/401');
        }

        $content            = V('equipment/edit');
        $content->equipment = $equipment;

        $this->layout->body->primary_tabs
        = Widget::factory('tabs')
            ->set('stab', $stab);
        if ($me->is_allowed_to('修改基本信息', $equipment)) {
            Event::bind('equipment.edit.tab', [$this, '_edit_info'], 0, 'info');
            Event::bind('equipment.edit.content', [$this, '_edit_photo'], 0, 'photo');
            $this->layout->body->primary_tabs
                ->add_tab('info', [
                    'url'    => $equipment->url('info', null, null, 'edit'),
                    'title'  => I18N::T('equipments', '基本信息'),
                    'weight' => 0,
                ])
            /*->add_tab('photo', [
            'url'=> $equipment->url('photo', null, null, 'edit'),
            'title'=>I18N::T('equipments', '设备图标'),
            'weight' => 10,
            ])*/;
        }
        if ($me->is_allowed_to('修改使用设置', $equipment)) {
            Event::bind('equipment.edit.content', [$this, '_edit_use'], 0, 'use');
            $this->layout->body->primary_tabs->add_tab('use', [
                'url'    => $equipment->url('use', null, null, 'edit'),
                'title'  => I18N::T('equipments', '使用设置'),
                'weight' => 20,
            ]);
        }

        /**
         * 用户标签
         */
        if ($me->is_allowed_to('修改标签', $equipment)) {
            Event::bind('equipment.edit.content', [$this, '_edit_tag'], 0, 'tag');
            $this->layout->body->primary_tabs
                ->add_tab('tag', [
                    'url'    => $equipment->url('tag', null, null, 'edit'),
                    'title'  => I18N::T('equipments', '用户标签'),
                    'weight' => 60,
                ]);
        }

        /**
         *  NO.BUG#264(guoping.zhang@2010.12.22)
         *  修改仪器的状态设置权限设置
         */
        if ($equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE && $me->is_allowed_to("修改仪器状态设置", $equipment)) {
            Event::bind('equipment.edit.content', [$this, '_edit_status'], 0, 'status');
            // 如果仪器未报废 则可修改状态
            $this->layout->body->primary_tabs->add_tab('status', [
                'url'    => $equipment->url('status', null, null, 'edit'),
                'title'  => I18N::T('equipments', '状态设置'),
                'weight' => 70,
            ]);
        }

        $this->layout->body->primary_tabs
            ->set('equipment', $equipment)
            ->tab_event('equipment.edit.tab')
            ->content_event('equipment.edit.content')
            ->select($tab);

        $this->layout->title = H($equipment->name);
        $breadcrumbs = [
            [
                'url' => '!equipments/index',
                'title' => I18N::T('equipments', '仪器目录'),
            ],
            [
                'url' => $equipment->url(),
                'title' => $equipment->name,
            ],
            [
                'title' => '修改',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
    }

    // public function empower_setting($id = 0, $type)
    // {
    //     $equipment = O('equipment', $id);

    //     if (!$equipment->id) {
    //         URI::redirect('error/404');
    //     }

    //     //获取breadcrumb
    //     $breadcrumb = Event::trigger('equipment.empower_setting.breadcrumb', $equipment, $type);

    //     $this->layout->body->primary_tabs->
    //         add_tab('edit', [
    //         '*' => $breadcrumb,
    //     ])
    //         ->set('equipment', $equipment)
    //         ->select('edit');

    //     $this->layout->body->primary_tabs->content = Event::trigger('equipment.empower_setting.content', $equipment, $type);
    // }


    function time_counts_setting($id = 0, $type) {

        $equipment = O('equipment', $id);

        if (!$equipment->id) {
            URI::redirect('error/404');
        }

        //获取breadcrumb
        $breadcrumb = Event::trigger('equipment.time_counts_setting.breadcrumb', $equipment, $type);

        $this->layout->body->primary_tabs->
        add_tab('edit', [
            '*' => $breadcrumb
        ])
            ->set('equipment', $equipment)
            ->select('edit');

        $this->layout->body->primary_tabs->content = Event::trigger('equipment.time_counts_setting.content', $equipment, $type);
    }

    // public function empower_setting($id = 0, $type)
    // {
    //     $equipment = O('equipment', $id);

    //     if (!$equipment->id) {
    //         URI::redirect('error/404');
    //     }

    //     //获取breadcrumb
    //     $breadcrumb = Event::trigger('equipment.empower_setting.breadcrumb', $equipment, $type);

    //     $this->layout->body->primary_tabs->
    //         add_tab('edit', [
    //         '*' => $breadcrumb,
    //     ])
    //         ->set('equipment', $equipment)
    //         ->select('edit');

    //     $this->layout->body->primary_tabs->content = Event::trigger('equipment.empower_setting.content', $equipment, $type);
    // }


    // function time_counts_setting($id = 0, $type)
    // {

    //     $equipment = O('equipment', $id);

    //     if (!$equipment->id) {
    //         URI::redirect('error/404');
    //     }

    //     //获取breadcrumb
    //     $breadcrumb = Event::trigger('equipment.time_counts_setting.breadcrumb', $equipment, $type);

    //     $this->layout->body->primary_tabs->
    //     add_tab('edit', [
    //         '*' => $breadcrumb
    //     ])
    //         ->set('equipment', $equipment)
    //         ->select('edit');

    //     $this->layout->body->primary_tabs->content = Event::trigger('equipment.time_counts_setting.content', $equipment, $type);
    // }

    public function extra_setting($id = 0, $type)
    {
        $equipment = O('equipment', $id);

        if (!$equipment->id) {
            URI::redirect('error/404');
        }

        // 获取breadcrumb
        $breadcrumb = Event::trigger('equipment.extra_setting.breadcrumb', $equipment, $type);

        $this->layout->body->primary_tabs->
        add_tab('edit', [
            '*' => $breadcrumb
        ])
            ->set('equipment', $equipment)
            ->select('edit');

        $this->layout->body->primary_tabs->content = Event::trigger('equipment.extra_setting.content', $equipment, $type);

        // $this->layout->body->primary_tabs->content->edit_title = V('application:edit_title', ['name' => $equipment->name, 'url' => $equipment->url()]);
    }

    //复旦化学系 送样时间设置
    public function sample_time($id = 0, $type)
    {
        $equipment = O('equipment', $id);

        if (!$equipment->id) {
            URI::redirect('error/404');
        }
        //获取breadcrumb
        $breadcrumb = Event::trigger('eq_sample.time_setting.breadcrumb', $equipment, $type);

        $this->layout->body->primary_tabs->
            add_tab('edit', [
            '*' => $breadcrumb,
        ])
            ->set('equipment', $equipment)
            ->select('edit');

        $view = Event::trigger('eq_sample.time_setting.content', $equipment, $type);

        $this->layout->body->primary_tabs->content = $view;
    }

    public function delete($id = 0)
    {
        $equipment = O('equipment', $id);

        if (!$equipment->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('删除', $equipment)) {
            URI::redirect('error/401');
        }

        // 如果没有相关的预约和使用记录，直接删除
        if(Q("$equipment eq_record")->total_count() != 0 ||
           Q("$equipment eq_sample")->total_count() != 0 ||
           Q("$equipment eq_reserv")->total_count() != 0
        ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '该设备有相关记录，不可删除。若不再使用，可将其设为报废状态。'));
            URI::redirect($_SESSION['system.current_layout_url']);
        } else {
            Q("$equipment eq_record")->delete_all();
            Q("$equipment eq_status")->delete_all();
            foreach (Q("$equipment user.incharge") as $incharge) {
                $equipment->disconnect($incharge, 'incharge');
            }

            Log::add(strtr('[equipments] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

            $equipment_attachments_dir_path = NFS::get_path($equipment, '', 'attachments', true);
            if ($equipment->delete()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设备删除成功!'));
            }

            if (Config::get('equipment.total_count')) {
                $cache           = Cache::factory();
                $equipment_count = $cache->get('equipment_count');
                $equipment_count[EQ_Status_Model::IN_SERVICE]--;
                $equipment_count['total']--;
                $cache->set('equipment_count', $equipment_count, 3600);
            }

            File::rmdir($equipment_attachments_dir_path);
            URI::redirect('!equipments');
        }
    }

    public function delete_photo($id = 0)
    {
        $me        = L('ME');
        $equipment = O('equipment', $id);
        if (!$me->is_allowed_to('修改', $equipment)) {
            URI::redirect('error/401');
        }

        $equipment->delete_icon();

        URI::redirect($equipment->url('photo', null, null, 'edit'));
    }

    public function _edit_info($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');

        $group_root = Tag_Model::root('group');

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

        if (Input::form('submit')) {
            $form = Form::filter(Input::form());
            if (isset($form['control_mode'])){return true;}
            $requires = Config::get('form.equipment_edit')['requires'];

            // 当基本信息被高权限用户锁定时, 仅可修改指定字段
            if (!$me->is_allowed_to('提交修改', $equipment)) {
                $disables = Config::get('form.equipment_edit')['disables'];
                $requires = array_diff_key($requires, $disables);
            }

            $form->validate('price', 'compare(>=0)', I18N::T('equipments', '仪器价格不能设置为负数!'));

            // $requires = Config::get('form.equipment_edit')['requires'];

            array_walk($requires, function($v, $k) use($form, $user, $group_root, $equipment) {
                if (Event::trigger('equipment.edit.get.disable', $k, $equipment)) {
                    return TRUE;
                }
                switch ($k) {
                    case 'name':
                        $form->validate('name', 'not_empty', I18N::T('equipments', '请输入仪器名称!'));
                        break;
                    case 'price':
                        $form->validate('price', 'compare(>0)', I18N::T('equipments', '请输入仪器价格!'));
                        break;
                    case 'ref_no':
                        $form->validate('ref_no', 'not_empty', I18N::T('equipments', '请输入仪器编号!'));
                        break;
                    case 'location':
                        $form->validate('location', 'not_empty', I18N::T('equipments', '请输入放置房间!'));
                        break;
                    case 'incharges':
                        $incharges = (array) @json_decode($form['incharges'], true);
                        if (count($incharges) == 0) {
                            $form->set_error('incharges', I18N::T('equipments', '请指定至少一名仪器负责人!'));
                        }
                        break;
                    case 'contacts':
                        $contacts = (array) @json_decode($form['contacts'], true);
                        if (count($contacts) == 0) {
                            $form->set_error('contacts', I18N::T('equipments', '请指定至少一名仪器联系人!'));
                        }
                        break;
                    case 'group_id':
                        $group = O('tag_group', (int) Input::form()['group_id']);
                        if (!$group->id || $group->root->id != $group_root->id) {
                            $form->set_error('group_id', I18N::T('equipments', '请选择组织机构!'));
                        }
                    default:
                        break;
                }
            });
            $form['equipment_id'] = $equipment->id;
            Event::trigger('equipment[edit].post_submit_validate', $form);

            if ($form['email']) {
                $form->validate('email', 'is_email', I18N::T('equipments', '联系邮箱填写有误!'));
            }

            $ref_no = trim($form['ref_no']);
            if ($ref_no) {
                $exist_equipment = O('equipment', ['ref_no' => $ref_no]);
                if ($exist_equipment->id && $equipment->id != $exist_equipment->id) {
                    $form->set_error('ref_no', I18N::T('equipments', '您输入的仪器编号在系统中已存在!'));
                }
            }

            /*
            guoping.zhang@2011.01.17
            仪器负责人人数上限
             */
            if (Config::get('equipment.max_incharges')) {
                $max_incharges = Config::get('equipment.max_incharges');

                //现在改仪器负责人数目
                $all_incharges = $contacts + $incharges;

                if (count($all_incharges) > $max_incharges) {
                    $form->set_error('incharges', I18N::T('equipments','仪器最多能指定%count个负责人!',  ['%count'=>$max_incharges]));
                }
            }

            if($form->no_error){
                if (isset($form['ref_no']) && $ref_no) $equipment->ref_no = $ref_no;
                if ($me->is_allowed_to('锁定基本', $equipment)) {
                    if (isset($form['info_lock'])) {
                        $equipment->info_lock = $form['info_lock'];
                    } else {
                        $equipment->info_lock = '';
                    }
                }
                if (isset($form['cat_no'])) $equipment->cat_no = $form['cat_no'];
                if (isset($form['name'])) $equipment->name = $form['name'];
                if (isset($form['en_name'])) $equipment->en_name = $form['en_name'];
                if (isset($form['model_no'])) $equipment->model_no = $form['model_no'];
                if (isset($form['manu_at'])) $equipment->manu_at = $form['manu_at'];
                if (isset($form['manufacturer'])) $equipment->manufacturer = $form['manufacturer'];
                if (isset($form['manu_date'])) $equipment->manu_date = $form['manu_date'];
                if (isset($form['purchased_date'])) $equipment->purchased_date = $form['purchased_date'];
                if (isset($form['tech_specs'])) $equipment->tech_specs = $form['tech_specs'];
                if (isset($form['features'])) $equipment->features = $form['features'];
                if (isset($form['configs'])) $equipment->configs = $form['configs'];
                if (isset($form['open_reserv'])) $equipment->open_reserv = $form['open_reserv'];
                if (isset($form['charge_info'])) $equipment->charge_info = $form['charge_info'];
                if (isset($form['price'])) $equipment->price = (double)$form['price'];
                if (isset($form['specification'])) $equipment->specification = $form['specification'];
                if (isset($form['phone'])) $equipment->phone = $form['phone'];
                if (isset($form['email'])) $equipment->email = $form['email'];
                if (isset($form['atime'])) $equipment->atime = $form['atime'];
                if (isset($form['site'])) $equipment->site = $form['site'];
                if($me->is_allowed_to('共享', 'equipment')) {
                    $equipment->share = $form['share'];
                }
                if ($me->is_allowed_to('进驻仪器控', 'equipment')) {
                    $equipment->yiqikong_share = (int) $form['yiqikong_share'];
                }
                if ($me->is_allowed_to('隐藏', 'equipment')) {
                    $equipment->hidden         = (int) $form['hidden'];
                }
                Event::trigger('equipment[edit].post_submit', $form, $equipment);

                if (isset($form['group_id'])) {
                    $group = O('tag_group', $form['group_id']);

                    $group_root->disconnect($equipment);
                    $equipment->group = null;

                    if ($group->root->id == $group_root->id) {
                        $group_root->disconnect($equipment);
                        $group->connect($equipment);
                        $equipment->group = $group;
                    }
                }

                if (isset($form['tags'])) {
                    //清空仪器分类后，应清空仪器的类型，并且和Tag_Model::root('equipment')进行绑定
                    $tags = @json_decode($form['tags'], true);
                    if (count($tags)) {
                        Tag_Model::replace_tags($equipment, $tags, 'equipment');
                    } else {
                        $equipment_root = Tag_Model::root('equipment');
                        $tags = Q("$equipment tag_equipment[root=$equipment_root]");
                        foreach ($tags as $t) {
                            $t->disconnect($equipment);
                        }

                        $equipment->connect($equipment_root);
                    }
                }

                if (isset($form['tag_education'])) {
                    //清空仪器分类后，应清空仪器的类型，并且和Tag_Model::root('equipment')进行绑定

                    if ($form['tag_education']) {
                        $tags = [$form['tag_education']=>O('tag_equipment_education',$form['tag_education'])->name];
                        Tag_Model::replace_tags($equipment, $tags, 'equipment_education');
                    } else {
                        $equipment_root = Tag_Model::root('equipment_education');
                        $tags = Q("$equipment tag_equipment_education[root=$equipment_root]");
                        foreach ($tags as $t) {
                            $t->disconnect($equipment);
                        }

                        $equipment->connect($equipment_root);
                    }
                }

                if (isset($form['tag_technical'])) {
                    //清空仪器分类后，应清空仪器的类型，并且和Tag_Model::root('equipment')进行绑定

                    if ($form['tag_technical']) {
                        $tags = [$form['tag_technical']=>O('tag_equipment_technical',$form['tag_technical'])->name];
                        Tag_Model::replace_tags($equipment, $tags, 'equipment_technical');
                    } else {
                        $equipment_root = Tag_Model::root('equipment_technical');
                        $tags = Q("$equipment tag_equipment_technical[root=$equipment_root]");
                        foreach ($tags as $t) {
                            $t->disconnect($equipment);
                        }

                        $equipment->connect($equipment_root);
                    }
                }

                if (Config::get('equipment.location_type_select')) {
                    if (isset($form['location_id'])) {
                        $location = O('tag_location', $form['location_id']);

                        $location_root = Tag_Model::root('location');
                        $location_root->disconnect($equipment);
                        $equipment->location = O('tag_location');

                        if ($location->root->id == $location_root->id) {
                            $location_root->disconnect($equipment);
                            $location->connect($equipment);
                            $equipment->location = $location;
                        }
                    }

                    /* if (isset($form['location'])) {
                        $tags = @json_decode($form['location'], true);
                        if (count($tags)) {
                            Tag_Model::replace_tags($equipment, $tags, 'location');
                        } else {
                            $equipment_root = Tag_Model::root('location');
                            $tags = Q("$equipment tag_location[root=$equipment_root]");
                            foreach ($tags as $t) {
                                $t->disconnect($equipment);
                            }
                        }
                    } */
                }

                if (L('ME')->is_allowed_to('修改', $equipment) ){
                    if (isset($form['incharges'])) {
                        $role = O('role', ['name' => '仪器负责人']);

                        foreach (Q("$equipment<incharge user") as $incharge ) {
                            $equipment->disconnect($incharge, 'incharge');

                            if (People::perm_in_uno()) {
                                $is_incharge = Q("equipment {$incharge}.incharge")->total_count();
                                if (!$is_incharge) {
                                    $res = Gateway::deleteRemoteUserGroupRoles([
                                        'user_id' => $incharge->gapper_id,
                                        'role' => ['gid' => 1, 'rid' => $role->gapper_id],
                                    ]);
                                }
                            }
                        }

                        //$me->unfollow($equipment);
                        foreach (json_decode($form['incharges']) as $id=>$name) {
                            $user = O('user', $id);
                            if (!$user->id) continue;

                            $equipment->connect($user, 'incharge');
                            $user->follow($equipment);

                            if (People::perm_in_uno()) {
                                $res = Gateway::postRemoteUserGroupRoles([
                                    'user_id' => $user->gapper_id,
                                    'role' => ['gid' => 1, 'rid' => $role->gapper_id],
                                ]);
                            }
                        }
                    }

                    if (isset($form['contacts'])) {
                        foreach (Q("$equipment<contact user") as $contact ) {
                            $equipment->disconnect($contact, 'contact');
                        }
                        
                        // $me->unfollow($equipment);
                        foreach (json_decode($form['contacts']) as $id=>$name) {
                            $user = O('user', $id);
                            if (!$user->id) continue;
                            $equipment->connect($user, 'contact');
                            /* NO.TASK#285(xiaopei.li@2010.12.02) */
                            $user->follow($equipment);
                        }
                    }
                }

                if ($equipment->save()) {
                    // 更新device
                    // 删除了2.17新的 connect 属性的判断，因为 Device_Agent 是为了类似 CACS 这样的老服务留的，所以只使用老的 is_monitoring 属性进行判断
                    if ($equipment->is_monitoring) {
                        $agent = new Device_Agent($equipment);
                        $agent->call('sync');
                    }

                    if (Module::is_installed('yiqikong')) {
                        CLI_YiQiKong::update_equipment($equipment->id);
                    }

                    Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的基本信息', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设备信息已更新'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设备信息更新失败! 请与系统管理员联系。'));
                }
            }
        }

        $other_view = Event::trigger('equipment[edit].view_diolog', $form, $equipment);

        $tabs->content = V('equipment/edit.info', [
            'group_root' => $group_root,
            'form'       => $form,
            'equipment'  => $equipment,
            'other_view' => $other_view,
        ]);
    }

    public function _edit_photo($e, $tabs)
    {
        $equipment = $tabs->equipment;

        if (Input::form('submit')) {
            $file = Input::file('file');
            if ($file['tmp_name']) {
                try {
                    $ext   = File::extension($file['name']);
                    $image = Image::load($file['tmp_name'], $ext);
                    $equipment->save_real_icon($image);
                    $equipment->save_icon($image);
                    $me = L('ME');
                    Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的设备图标', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '设备图标已更新'));
                } catch (Error_Exception $e) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '设备图标更新失败!'));
                }
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '请选择您要上传的设备图标文件。'));
            }
        }

        $tabs->content = V('equipment/edit.photo');
    }

    /*
    BUG#100
    2010.11.05 by cheng.liu
    仪器附件功能已被撤出，不需要_edit_attachment
     */
    public function _edit_status($e, $tabs)
    {
        $equipment = $tabs->equipment;

        $form = Form::filter(Input::form());
        // ($form);exit(0);
        if ($form['submit']) {
            try {
                $now = time();

                if ($form['status'] == EQ_Status_Model::NO_LONGER_IN_SERVICE && !L('ME')->is_allowed_to('报废仪器', $equipment)) {
                    URI::redirect('error/401');
                }

                if ($form['status'] != EQ_Status_Model::IN_SERVICE) {
                    if (!$form['description']) {
                        $form->set_error('description', I18N::T('equipments', '请填写状态变更的描述!'));
                        throw new Error_Exception;
                    }
                }

                /*仪器状态更改知道原则：
                1.故障=》正常
                //2.报废=》正常

                3.正常=》故障

                4.正常=》报废
                5.故障=》报废
                */

                Event::trigger('edit_status.form.validate', $equipment, $form);

                if (!$form->no_error) {
                    throw new Error_Exception;
                }

                //【定制】RQ194908 大连理工大学	故障仪器的预约计费设置
                if(Event::trigger('edit_status.edit_submit', $form, $equipment)){
                    $statuses = Q("eq_status[equipment=$equipment]:sort(ctime D)");
                    $tabs->content = V('equipment/edit.status', ['form'=>$form, 'statuses'=>$statuses, 'equipment'=>$equipment]);
                    return;
                }

                // 确定最后一项历史记录与当前状态是否符合 不符合则修正
                if ($equipment->status == $form['status']) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '仪器状态未改变, 因此无法更新'));
                    throw new Error_Exception;
                }

                if ($form['status'] == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
                    // ** => 报废
                    $status = O('eq_status', [
                        'equipment' => $equipment,
                        'status'    => $equipment->status,
                        'dtend'     => 0,
                    ]);
                    if ($status->id) {
                        $status->dtend = $now;
                        $status        = O('eq_status');
                    }

                    $status->equipment = $equipment;
                    $status->dtstart   = $now;
                    $status->status    = $form['status'];

                    $sql = "update `eq_record` set status = 1,feedback='仪器废弃时自动对记录进行反馈!' where equipment_id = '{$equipment->id}'";
                    ORM_Model::db('eq_record')->query($sql);

                    $equipment->control_address = uniqid() . $equipment->control_address;
                }
                elseif ($form['status'] == EQ_Status_Model::IN_SERVICE) {
                    // 其他 => 正常
                    $status = O('eq_status', [
                        'equipment' => $equipment,
                        'status'    => $equipment->status,
                        'dtend'     => 0,
                    ]);
                    if (!$status->id) {
                        throw new Error_Exception;
                    }
                    $status->dtend = $now;
                } else {
                    // 关闭之前的记录
                    foreach (Q("eq_status[equipment=$equipment][dtend=0]") as $s) {
                        $s->dtend = $now - 1;
                        $s->save();
                    }
                    // 正常 => 其他
                    $status            = O('eq_status');
                    $status->dtstart   = $now;
                    $status->equipment = $equipment;
                    $status->status    = $form['status'];
                }

                if (Config::get('equipment.total_count')) {
                    $cache           = Cache::factory();
                    $equipment_count = $cache->get('equipment_count');
                    $equipment_count[$equipment->status]--;
                    $equipment_count[$form['status']]++;
                    $cache->set('equipment_count', $equipment_count, 3600);
                }

                $status->description = $form['description'];
                $status->save();
                $equipment->status = $form['status'];
                $equipment->save();
                $me = L('ME');

                Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的状态设置', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '仪器状态已更新'));
                Event::trigger('equipment.notifications.edit.status', $equipment, $form['status']);

                /*
                 * BUG#461(rui.ma@2011.4.19)
                 * 设置仪器状态为报废后跳转到仪器显示页面
                 */

                if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
                    //仪器控删除仪器
                    Event::trigger('yiqikong.on_equipment_deleted');
                    URI::redirect($equipment->url());
                }
            } catch (Error_Exception $e) {
            }
        } else {
            $form['status'] = $equipment->status;
            $status         = O('eq_status', [
                'equipment' => $equipment,
                'status'    => $equipment->status,
                'dtend'     => 0,
            ]);
            if ($status->id) {
                $form['description'] = $status->description;
            }
        }

        $statuses      = Q("eq_status[equipment=$equipment]:sort(ctime D)");
        $tabs->content = V('equipment/edit.status', ['form' => $form, 'statuses' => $statuses, 'equipment' => $equipment]);
    }

    public function _edit_use($e, $tabs)
    {
        $equipment = $tabs->equipment;

        if (Input::form('submit')) {
            try {
                $form = Form::filter(Input::form());
                /* NO.BUG#240(xiaopei.li@2010.12.15) */
                if ($form['require_training']) {
                    $equipment->require_training = 1;
                } else {
                    $equipment->require_training = 0;
                }
                if ($form['lock_incharge_control']) {
                    $equipment->lock_incharge_control = 1;
                } else {
                    $equipment->lock_incharge_control = 0;
                }

                if ($form['require_exam']) {
                    if ($remote_exam_id = (int)$form['equipment_exam']) {
                        $remote_exam_app = Config::get('exam.remote_exam_app');
                        $exam = O('exam', ['remote_id'=>$remote_exam_id, 'remote_app'=>$remote_exam_app]);

                        if (!$exam->id) {
                            $exam->remote_id = $remote_exam_id;
                            $exam->remote_app = $remote_exam_app;
                        }
                        $exam->title = $form['exam_title'];
                        $exam->save();
                        $exams = Q("{$equipment} exam");
                        $history_exams = (array)$equipment->history_exams;
                        if (count($exams)) foreach($exams as $ex) {
                            $equipment->disconnect($ex);
                        }
                        $history_exams[$exam->id] = $exam->id;
                        $equipment->connect($exam);
                        $equipment->require_exam = $remote_exam_id;
                        // 记录仪器关联过的考试
                        $equipment->history_exams = $history_exams;
                    }
                    else {
                        throw new Exception(I18N::T('equipments', '请选择考试'));
                    }
                }
                else {
                    $equipment->require_exam = false;
                    $exams = Q("{$equipment} exam");
                    if (count($exams)) foreach($exams as $exam) {
                        $equipment->disconnect($exam);
                    }
                }
                $old_control_mode = $equipment->control_mode;
                // 数据表中以 NULL 表示 nocontrol
                if ($form['control_mode'] != 'nocontrol') {
                    // 从不控制改为控制, 需要判断是否超额
                    if (!$equipment->control_mode ||
                        $equipment->control_mode == 'nocontrol') {

                        // 若此时已达到"最大可安客户端数目", 则不能再设 control_mode
                        if ($GLOBALS['preload']['equipment.max_clients'] &&
                            Q('equipment[control_mode]')->total_count() >= $GLOBALS['preload']['equipment.max_clients']) {
                            $form['control_mode'] = 'nocontrol';
                            throw new Exception(I18N::T('equipments', '已达到客户端数量限制'));
                        }
                    }

                    if ($form['control_mode'] == 'power') {
                        if ($GLOBALS['preload']['equipment.max_power_clients']
                            && $equipment->control_mode != 'power'
                            && Q('equipment[control_mode=power]')->total_count() >= $GLOBALS['preload']['equipment.max_power_clients']
                        ) {
                            $form['control_mode'] = 'nocontrol';
                            throw new Exception(I18N::T('equipments', '已达到电源控制客户端数量限制'));
                        }

                        $equipment->control_mode = 'power';

                        $form
                            ->validate('control_power_address', 'not_empty', I18N::T('equipments', '请输入终端地址!'));

                        $control_power_address = $form['control_power_address'];
                        if ($form['control_power_address'] && $control_power_address != $equipment->control_address && Q("equipment[id!={$equipment->id}][control_address=$control_power_address]")->total_count()) {
                            $form->set_error('control_power_address', I18N::T('equipments', '终端地址已存在!'));
                        }
                        $equipment->control_address = trim($form['control_power_address']);
                    }
                    elseif ( $form['control_mode'] == 'computer') {
                        if ($GLOBALS['preload']['equipment.max_computer_clients']
                                && $equipment->control_mode != 'computer'
                                && $equipment->control_mode != 'veronica'
                                && Q('equipment[control_mode=computer|control_mode=computer]')->total_count() >= $GLOBALS['preload']['equipment.max_computer_clients']
                           ) {
                            $form['control_mode'] = 'nocontrol';
                            throw new Exception(I18N::T('equipments', '已达到电脑控制客户端数量限制'));
                        }

                        $equipment->control_mode = 'computer';
                        $equipment->access_code_ctime = time();
                        $equipment->access_code = $form['access_code'];
                        
                        $equipment->capture_stream_to = $form['capture_stream_to'];
                        $equipment->capture_upload_to = $form['capture_upload_to'];

                    }
                    elseif ( $form['control_mode'] == 'veronica' ) {
                        if ($GLOBALS['preload']['equipment.max_computer_clients']
                                && $equipment->control_mode != 'computer'
                                && $equipment->control_mode != 'veronica'
                                && Q('equipment[control_mode=computer|control_mode=computer]')->total_count() >= $GLOBALS['preload']['equipment.max_computer_clients']
                           ) {
                            $form['control_mode'] = 'nocontrol';
                            throw new Exception(I18N::T('equipments', '已达到电脑控制客户端数量限制'));
                        }
                        $equipment->control_mode = 'veronica';
                        $equipment->access_code_ctime = time();
                        $equipment->access_code = $form['access_code'];

                        $equipment->capture_stream_to = $form['capture_stream_to'];
                        $equipment->capture_upload_to = $form['capture_upload_to'];
                    }
                    elseif ( $form['control_mode'] == 'bluetooth' ) {
                        // $form->validate('bluetooth_serial_address', 'not_empty', I18N::T('equipments', '请输入蓝牙插座序列号!'));
                        $equipment->control_mode = 'bluetooth';
                        $equipment->control_address = $form['bluetooth_serial_address'];
                        $equipment->bluetooth_serial_address = $form['bluetooth_serial_address'];
                        $equipment->is_using = 0;
                    }
                    elseif ( $form['control_mode'] == 'agent' ) {
                        $equipment->control_mode = 'agent';
                        $equipment->control_address = $form['control_address'];
                        $equipment->is_using = 0;
                    }elseif ($form['control_mode'] == 'ultron'){
                        $equipment->control_mode = 'ultron';
                    }

                } // end ($form['control_mode'] != 'nocontrol')
                else {
                    $equipment->control_mode    = '';
                    $equipment->control_address = '';
                    $equipment->bluetooth_serial_address = '';
                    $equipment->access_code = NULL;
                    $equipment->is_using = 0;
                }

                Event::trigger('equipment_edit_use.form.validate', $equipment, $form);
                if (!$form->no_error) {
                    $messages = [];
                    foreach ($form->errors as $error) {
                        $messages = array_merge($messages, $error);
                    }
                    $messages = I18N::T('equipments', $messages);
                    throw new Exception(join("</br>", $messages));
                }

                if ($equipment->control_mode != $old_control_mode) {
                    $equipment->device2 = null;
                    $equipment->offline_password = null;
                }

                Event::trigger('equipments_edit_use_submit', $equipment, $form);

                if ($equipment->save()) {
                    $me = L('ME');
                    Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的使用设置', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                    if ($equipment->control_mode == 'bluetooth') {
                        Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器蓝牙控制, 蓝牙插座序列号为：%bluetooth_serial_address', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id, '%bluetooth_serial_address' => $equipment->control_address]), 'bluetooth_control');
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '使用设置已更新'));

                    if ($old_control_mode != 'nocontrol' && $old_control_mode != $equipment->control_mode) {
                        $agent = new Device_Agent($equipment);
                        $agent->call('halt');
                    }
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '仪器使用设置更新失败! 请联系系统管理员!'));
                }
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            }
        }

        if ($equipment->require_exam) {
            $exam = Q("{$equipment} exam")->current();
            $form['equipment_exam'] = $exam->remote_id;
            $form['exam_title'] = $exam->title;
        }
        $tabs->content = V('equipment/edit.use', ['form' => $form]);
    }

    /*
    NO. TASK#270 (Cheng.Liu@2010.11.23)
    仪器管理员给仪器添加收费标签，并关联实验室
     */
    public function _edit_tag($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $form      = Input::form();
        //获取仪器的tag_root
        $root     = $equipment->get_root();
        $tags     = Q("tag[root={$root}]:sort(weight A)");
        $tags_arr = $tags->to_assoc('id', 'name');

        $SQL_CHECK_IF_REPEATED = "SELECT * FROM `tag` a WHERE (SELECT COUNT(*) FROM `tag` b WHERE b.weight = a.weight AND b.root_id = {$root->id}) > 1 AND a.root_id = {$root->id}";
        $confused_tags         = new ORM_Iterator('tag', $SQL_CHECK_IF_REPEATED);

        if (count($confused_tags)) {
            //$SQL_UPDATE_WEIGHT = "SET @i := 0;UPDATE `tag` SET weight = (@i:=(@i + 1)) WHERE root_id = $root->id ORDER BY weight ASC";
            $weight = 0;
            foreach ($tags as $tag) {
                $tag->weight = $weight;
                $weight += 1;
                $tag->save();
            }
        }

        $tabs->content = V('equipment_tags/tags', [
            'form'      => $form,
            'equipment' => $equipment,
            'tags'      => $tags_arr,
        ]);
        $this->add_js('equipments:equipment_tag_sortable');
    }

    public function report($id = 0)
    {
        $me = L('ME');
        if (!$me->id) {
            URI::redirect('error/404');
        }

        $equipment = O('equipment', $id);
        if (!$equipment->id || $equipment->status != 0) {
            URI::redirect('error/404');
        }
        if ($me->is_allowed_to('修改仪器使用记录', $equipment)) {
            $panel_buttons[] = [
                'url'   => '',
                'title' => I18N::T('labs', '打印年度报表'),
                'extra' => 'class="button button_print  view object:annual_report event:click static:id=' . $tabs->equipment->id . '&oname=equipment"',
            ];
        }
        if (Input::form('submit')) {
            $form = Form::filter(Input::form())->validate('report', 'not_empty', I18N::T('equipments', '请填写故障描述'));
            if ($form->no_error) {
                $users    = Q("{$equipment} user.contact");
                $contacts = [];
                foreach ($users as $contact) {
                    Notification::send('equipments.report_problem', $contact, [
                        '%incharge'  => Markup::encode_Q($contact),
                        '%user'      => Markup::encode_Q($me),
                        '%equipment' => Markup::encode_Q($equipment),
                        '%report'    => $form['report'],
                    ]);
                }
                Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器故障报告', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '故障报告发送成功，请等待设备管理员处理'));
                URI::redirect('!equipments');
            }
        }

        $this->layout->body->primary_tabs
            ->select('report')->set('equipment', $equipment)
            ->set('content', V('equipment/report', ['form' => $form]))
        ;
    }

    public function access_code($id = 0)
    {
        $equipment = O('equipment', $id);
        if (!$equipment->id) {
            URI::redirect('error/404');
        }

        header('Content-Type: application/force-download');
        $filename = sprintf('%d_%s_%s.eqs', $equipment->id, $equipment->ref_no, $equipment->name);
        if (Browser::name() == 'ie') {
            $encoded_filename = urlencode($filename);
            $encoded_filename = str_replace("+", "%20", $encoded_filename);
            header("Content-Disposition: attachment; filename=\"$encoded_filename\"");
        } else {
            header("Content-Disposition: attachment; filename=\"$filename\"");
        }
        header("Content-Description: File Transfer");

        $computer_config = Config::get('device.computer');
        $computer_port   = $computer_config['port'];

        $host = Config::get('equipment.computer_host') ?: $_SERVER['HTTP_HOST'];

        //先尝试parse_url获取host，获取失败后进行host截取，剔除port
        $host = parse_url($host, PHP_URL_HOST) ?: (substr($host, 0, strrpos($host, ':') ?: strlen($host)));

        $base_url  = $_SERVER['HTTP_HOST'];
        $base_host = parse_url($base_url, PHP_URL_HOST) ?: (substr($base_url, 0, strrpos($base_url, ':') ?: strlen($base_url)));
        $base_port = parse_url($base_url, PHP_URL_PORT);
        $base_url  = $base_port ? $base_host . ':' . $base_port : $base_host;

        $info = [
            'code'    => $equipment->access_code,
            'host'    => $host,
            'port'    => $computer_port,
            'baseurl' => $base_url,
        ];

        echo base64_encode(json_encode($info));

        exit;
    }
}

class Equipment_AJAX_Controller extends AJAX_Controller
{
    public function index_output_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return false;
        }
        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.eq_record');
        $columns = new ArrayIterator($columns);
        $new_columns = Event::trigger('equipments.get.export.record.columns', $columns, $type, $form['eid']);
        if ($new_columns) {
            $columns = (array) $new_columns;
        }
        switch ($type) {
            case 'csv':
                $title       = I18N::T('equipments', '请选择要导出Excel的列');
                $query       = $_SESSION[$form_token]['selector'];
                $total_count = Q($query)->total_count();
                if ($total_count > 8000) {
                    $description = I18N::T('equipments', '数据量过多, 可能导致导出失败, 请缩小搜索范围!');
                }
                break;
            case 'print':
                $title = I18N::T('equipments', '请选择要打印的列');
                break;
        }
        JS::dialog(V('equipments:report/output_form', [
            'form_token'  => $form_token,
            'columns'     => $columns,
            'type'        => $type,
            'description' => $description,
            'eid'         => $form['eid'],
        ]), [
            'title' => $title,
        ]);
    }

    public function index_add_record_click($id = 0)
    {
        //年度选择列表
        $me = L('ME');

        $equipment = O('equipment', $id);

        if (!$me->is_allowed_to('修改仪器使用记录', $equipment)) {
            return;
        }

        $sections = new ArrayIterator;

        $record            = O('eq_record');
        $record->equipment = $equipment;
        $record->user      = $me;

        $form = Form::filter(Input::form());

        Event::trigger('eq_record.add_view', $record, $form, $sections);

        if ($me->is_allowed_to('修改仪器使用记录', $equipment)) {
            JS::dialog(V('record.edit', [
                'record'    => $record,
                'form'      => $form,
                'sections'  => $sections,
                'equipment' => $equipment,
            ]), ['title' => I18N::T('equipments', '添加使用记录')]);
        }
    }

    public function index_use_click($id = 0)
    {
        $equipment = O('equipment', $id);

        if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
            return;
        }

        $me       = L('ME');
        $is_admin = $me->is_allowed_to('修改仪器使用记录', $equipment);
        $now      = time();
        if ($equipment->control_mode == 'computer'
        || $equipment->control_mode == 'veronica'
        || ($equipment->control_mode == 'power' && preg_match('/^gmeter/', $equipment->control_address))
        || $equipment->control_mode == 'ultron' || $equipment->control_mode == 'bluetooth') {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $equipment->server,
                'http_errors' => FALSE,
                'timeout' => Config::get('device.computer.timeout', 5)
            ]);
        }

        if ($equipment->is_using && (Input::form('state') == 'off' || Input::form('state') == 'force_off')) {
            // 尝试关闭

            if (!$is_admin && Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}][user={$me}]")->total_count() == 0) {
                JS::alert(I18N::T('equipments', '该设备正在被其他人使用, 您无权关闭. \n如果有其他问题,请联系管理员.'));
                return;
            }

            if (!$is_admin || !JS::confirm(I18N::T('equipments', '您确定要关闭仪器吗?'))) return;

            if ($client) {
                $success = false;
                $config = Config::get('rpc.client.jarvis');
                try{
                    $success = (boolean) $client->post('switch_to', [
                        'headers' => [
                            'HTTP-CLIENTID' => $config['client_id'],
                            'HTTP-CLIENTSECRET' => $config['client_secret'],
                        ],
                        'form_params' => [
                            'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                            'user' => [
                                'equipmentId' => $equipment->id,
                                'username' => $me->token,
                                'cardno' => $me->card_no,
                                'name' => $me->name,
                                'id' => $me->id
                            ],
                            'power_on' => FALSE
                        ]
                    ])->getBody()->getContents();
                }catch (Exception $e){
                }
            }
            else {
                $agent = new Device_Agent($equipment);
                $success = $agent->call('switch_to', ['power_on' => FALSE]);
            }

            if ($success) {
                if ($equipment->control_mode != 'power') {
                    $equipment->is_using = FALSE;
                }
                $equipment->save();
                Log::add(strtr('[equipments] %user_name[%user_id]关闭%equipment_name[%equipment_id]仪器', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');
            }

            if (!$success && Input::form('state') == 'force_off') {
                //强制关闭
                $equipment->is_using = false;
                $equipment->save();
                Log::add(strtr('[equipments] %user_name[%user_id]强制关闭%equipment_name[%equipment_id]仪器', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                $record = Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
                if ($record->id) {
                    $record->dtend = time();
                    //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                    if ($record->user->id==$me->id) {
                        $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
                        $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                    }
                    $record->save();
                }

                $success = true;
            }

            if ($success) {
                Output::$AJAX[Input::form('rel')] = [
                    'data' => (string) V('equipment/control', ['equipment' => $equipment]),
                    'mode' => 'replace',
                ];
            } else {
                if (strpos($equipment->control_address, 'gmeter://') == 0) {
                    // 开关 gmeter 是异步的, 无法在 switch_to 调用时获得结果,
                    // 只能通过之后 gstation 汇报的 power (会被 epc-server 转为
                    // offline record) 得知.
                    // 此处 sleep 再 refresh, 一般可等到 gstation 回复
                    // (Xiaopei Li@2013-12-07)
                    sleep(1);
                    // TODO sleep 的秒数可以设为超时秒数, sleep 后, 从 db refetch
                    // equipment, 并根据此时状态告知用户开关成功与否
                    JS::refresh();
                } else {
                    JS::alert(I18N::T('equipments', '无法关闭电源控制设备，请确认仪器已经关闭!'));
                }
            }
        } elseif (!$equipment->is_using && Input::form('state') == 'on') {
            // 尝试打开

            if (!$me->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($me, $now)) {
                JS::alert(I18N::T('equipments', '您无权打开该设备电源.\n如果有其他问题,请联系管理员.'));
                return;
            }

            if (JS::confirm(I18N::T('equipments', '您确定要打开仪器吗?'))) {
                Log::add(strtr('[equipments] %user_name[%user_id]尝试通过CGI打开%equipment_name[%equipment_id]仪器', [
                    '%user_name'      => $me->name,
                    '%user_id'        => $me->id,
                    '%equipment_name' => $equipment->name,
                    '%equipment_id'   => $equipment->id,
                ]), 'journal');

                if ($client) {
                    $config  = Config::get('rpc.client.jarvis');
                    $success = (boolean) $client->post('switch_to', [
                        'headers'     => [
                            'HTTP-CLIENTID'     => $config['client_id'],
                            'HTTP-CLIENTSECRET' => $config['client_secret'],
                        ],
                        'form_params' => [
                            'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                            'user' => [
                                'equipmentId' => $equipment->id,
                                'username' => $me->token,
                                'cardno' => $me->card_no,
                                'name' => $me->name
                            ],
                            'power_on' => true,
                        ],
                    ])->getBody()->getContents();
                } else {
                    $agent   = new Device_Agent($equipment);
                    $success = $agent->call('switch_to', ['power_on' => true]);
                }

                if ($success) {
                    if ($equipment->control_mode != 'power') {
                        $equipment->is_using = TRUE;
                    }
                    $equipment->save();
                    Log::add(strtr('[equipments] %user_name[%user_id]通过CGI打开%equipment_name[%equipment_id]仪器', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id]), 'journal');

                    Output::$AJAX[Input::form('rel')] = [
                        'data' => (string) V('equipment/control', ['equipment' => $equipment]),
                        'mode' => 'replace',
                    ];
                } else {
                    if (strpos($equipment->control_address, 'gmeter://') == 0) {
                        // 开关 gmeter 是异步的, 无法在 switch_to 调用时获得结果,
                        // 只能通过之后 gstation 汇报的 power (会被 epc-server 转为
                        // offline record) 得知.
                        // 此处 sleep 再 refresh, 一般可等到 gstation 回复
                        // (Xiaopei Li@2013-12-07)
                        sleep(1);
                        // TODO sleep 的秒数可以设为超时秒数, sleep 后, 从 db refetch
                        // equipment, 并根据此时状态告知用户开关成功与否
                        JS::refresh();
                    } else {
                        Log::add(strtr('[equipments] %user_name[%user_id]无法通过GCI打开%equipment_name[%equipment_id]仪器', [
                            '%user_name'      => $me->name,
                            '%user_id'        => $me->id,
                            '%equipment_name' => $equipment->name,
                            '%equipment_id'   => $equipment->id,
                        ]));
                        JS::alert(I18N::T('equipments', '无法打开电源控制设备，请确认此仪器电源控制设置正确!'));
                    }
                }
                Event::trigger('remote_open_equipment', $me, $equipment);
            }
        } else {
            Output::$AJAX[Input::form('rel')] = [
                'data' => (string) V('equipment/control', ['equipment' => $equipment]),
                'mode' => 'replace',
            ];
        }
    }

    public function index_feedback_edit_click($id = 0, $tab = 0)
    {
        $equipment = O('equipment', $id);
        if (!$equipment->id || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }
        $form = Form::filter(Input::form());

        $record = O('eq_record', (int) $form['record_id']);

        if (!$record->id) {
            return;
        }
        JS::dialog(V('equipment/edit.feedback', ['record' => $record]), ['title' => I18N::T('people', '使用反馈')]);
    }

    public function index_feedback_submit($id = 0)
    {
        $form = Form::filter(Input::form());

        //修改反馈报告入口
        if (!$form['record_id']) {
            return;
        }

        $record = O('eq_record', $form['record_id']);
        if (!$record->id) {
            return;
        }

        $equipment = $record->equipment;
        if (!$equipment->id || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }

        $me = L('ME');

        if (!$me->is_allowed_to('反馈', $record)) {
            return;
        }

        $types = [];
        /*if (class_exists('Lab_Project_Model')) {
            $status = Lab_Project_Model::STATUS_ACTIVED;
            if (!Q("{$record->user} lab lab_project[status={$status}]")->total_count() && Config::get('eq_record.must_connect_lab_project')) {
                JS::close_dialog();
                return false;
            }
        }*/

        //清除无用空字符
        $form['feedback'] = trim($form['feedback']);

        $form->validate('record_status', 'not_empty', I18N::T('equipments', '请选择当前状态!'));
        if ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM) {
            $form->validate('feedback', 'not_empty', I18N::T('equipments', '请认真填写反馈信息!'));
        }

        // 如果没被锁定, 并且require_samples
        $is_feedback_problem = ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM);
        if (!$record->cannot_lock_samples() && !$record->samples_lock && Config::get('feedback.require_samples')) {
            if (Config::get('equipment.feedback_samples_allow_zero', FALSE) || Event::trigger('eq_record.check_samples_is_allow_zero', $record)) {
                if (!is_numeric($form['samples']) || intval($form['samples'])<0 || intval($form['samples'])!=$form['samples']) {
                    $form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                }
            } else {
                if ($is_feedback_problem) {
                    if (!is_numeric($form['samples']) || intval($form['samples'])<0 || intval($form['samples'])!=$form['samples']) {
                        $form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                    }
                } else {
                    if (!is_numeric($form['samples']) || intval($form['samples'])<=0 || intval($form['samples'])!=$form['samples']) {
                        $form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
                    }
                }
            }
        }

        if (class_exists('Lab_Project_Model')) {
            if (Config::get('eq_record.must_connect_lab_project')) {
                if ($form['project'] == 0) {
                    $form->set_error('project', I18N::T('equipments', '"关联项目" 不能为空!'));
                }
            }
            $record->project = O('lab_project', $form['project']);
        }
        /*
        TODO 因为样品数的增加导致用户反馈时候会修改按照送样计费的记录的收费, 目前该功能需要暂时关闭, 待之后详细的方案出来之后再进行修正
         */
        /*
        $old_samples = (int)$record->samples;
        $record->samples = $new_samples = max(1, (int)$form['samples']);
         */

        if (Config::get('eq_record.duty_teacher') && !$form['duty_teacher'] && $record->equipment->require_dteacher) {
            $form->set_error('duty_teacher', I18N::T('equipments', '请选择值班老师!'));
        }

        if(Config::get('eq_record.tag_duty_teacher')) {
            Event::trigger('extra.form.validate_duty_teacher', $equipment,$record, $form);
        }

        Event::trigger('feedback.form.submit', $record, $form);

        if ($form->no_error) {
            $record->feedback = $form['feedback'];
            $record->status   = $form['record_status'];
            //设定samples

            if (!$record->samples_lock && (isset($form['samples']) && $form['samples'] >= 0)) {
                $record->samples = (int) $form['samples'];
            }

            if (Config::get('eq_record.duty_teacher') && $record->equipment->require_dteacher) {
                $duty_teacher         = O('user', $form['duty_teacher']);
                $record->duty_teacher = $duty_teacher;
            }

            $record->save();

            Log::add(strtr('[equipments] %user_name[%user_id]填写了%equipment_name[%equipment_id]仪器的使用记录[%record_id]反馈', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id, '%record_id' => $record->id]), 'journal');

            JS::refresh();
        } else {
            JS::dialog(V('equipment/edit.feedback', ['record' => $record, 'form' => $form]), ['title' => I18N::T('people', '使用反馈')]);
        }
    }

    function index_access_code_click() {
        if (!JS::confirm( I18N::T('equipments', '你确定要重新生成验证码吗? 重新生成验证码后需要重新安装客户端, 请谨慎操作!') )) {
			return;
        }

        $form = Input::form();

        $me = L('ME');
        $equipment = O('equipment',$form['equipment_id']);

        if ($equipment->id && $me->is_allowed_to('修改使用设置', $equipment)) {
            $equipment->access_code = strtoupper(Misc::random_password(16, 1));

            Output::$AJAX['#'.$form['code_id']]=[
                'data'=>(string)V('equipments:equipment/access_code', ['equipment'=>$equipment]),
                'mode'=>'replace'
            ];
        }
    }

    function index_replace_tag_click() {
        $form = Form::filter(Input::form());
        $tid = $form['tid'];
        $tag_name = $form['tag_name'] ?: 'tag';
        $uniqid = $form['uniqid'];
        if (!$tid) {
            return;
        }
        Output::$AJAX["#$uniqid > .relate_view"] = [
            'data' => (string)V('equipments:equipment_tags/relate_view', [
                                    'tid'=>$tid,
                                    'tag_name'=>$tag_name,
                                    'relate_uniqid'=>$uniqid
                        ]),
            'mode' => 'replace'
        ];
    }

    function index_admin_replace_tag_click() {
        $form = Form::filter(Input::form());
        $tid = $form['tid'];
        $tag_name = $form['tag_name'] ?: 'tag';
        $uniqid = $form['uniqid'];
        if (!$tid) {
            return;
        }
        Output::$AJAX["#$uniqid > .relate_view"] = [
            'data' => (string)V('equipments:admin/user_tags/relate_view', ['tid'=>$tid, 'tag_name'=>$tag_name, 'relate_uniqid'=> $uniqid]),
            'mode' => 'replace'
        ];
    }

    public function index_delete_tag_click()
    {
        if (!JS::confirm(I18N::T('equipments', '请谨慎操作!您确认要删除该标签？'))) {
            return;
        }
        $tid = Input::form('tid');
        $tag_name = Input::form('tag_name') ?: 'tag';
        if (!$tid) return;
        $tag = O($tag_name, $tid);
        $labs = Q("{$tag} lab");
        if (count($labs)) {
            foreach ($labs as $lab) {
                $tag->disconnect($lab);
            }
        }

        #ifdef (equipment.enable_group_specs)
        if (Config::get('equipment.enable_group_specs')) {
            $root = Tag_Model::root('group');
            $groups = Q("{$tag} tag_group[root={$root}]");
            if (count($groups)) foreach($groups as $group) {
                $tag->disconnect($group);
            }
        }
        #endif
        $tag->delete();
        JS::refresh();
    }

    public function index_create_tag_click()
    {
        $eid = Input::form('eid');
        if (!$eid) {
            return;
        }
        JS::dialog(V('equipments:equipment_tags/create_tag', ['eid' => $eid]), ['title' => I18N::T('equipment', '添加用户标签')]);
    }

    public function index_create_tag_submit()
    {
        $form = Form::filter(Input::form());
        $eid  = $form['eid'];
        if (!$eid) {
            return;
        }
        $me       = L('ME');
        $tag_name = $form['name'];
        $form->validate('name', 'not_empty', I18N::T('equipments', '标签名称不能为空!'));
        if (is_numeric($tag_name)) {
            $form->set_error('name', I18N::T('equipments', '标签名称不能为纯数字!'));
        }
        if ($form->no_error) {
            $equipment = O('equipment', $eid);
            $root      = $equipment->get_root();
            $tag       = O('tag', ['root' => $root, 'name' => $tag_name]);
            if (!$tag->id) {
                $tag->name   = $tag_name;
                $tag->parent = $root;

                /* BUG #1217::仪器计费问题
                 * 在添加标签的时候为标签指定weight，weight的值按照标签添加的先后顺序递增
                 * (kai.wu@2011.10.13)
                 */
                $tags = Q("tag[root={$root}]:sort(weight D):limit(1)");
                if (!$tags->total_count()) {
                    $tag->weight = 0;
                } else {
                    $tag->weight = $tags->current()->weight + 1;
                }

                $tag->update_root()->save();

                Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器的用户标签%tag_name[%tag_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id, '%tag_name' => $tag->name, '%tag_id' => $tag->id]), 'journal');

                JS::refresh();
            } else {
                $form->set_error('name', I18N::T('equipments', '该标签已存在，请输入其他名称!'));
            }
        }
        JS::dialog(V('equipments:equipment_tags/create_tag', ['eid' => $eid, 'form' => $form]), ['title' => I18N::T('equipment', '添加用户标签')]);
    }

    function index_edit_tag_blur() {
        $form = Form::filter(Input::form());
        $tid = $form['tid'];
        $tag_name = $form['tag_name'] ?: 'tag';
        $name = $form['tname'];
        if (is_numeric($name)) {
            JS::alert('名称不允许为纯数字');
            return false;
        }
        $uniqid = $form['uniqid'];
        $relate_uniqid = $form['relate_uniqid'];
        if (!$tid) {
            return;
        }
        if ($name) {
            $tag = O($tag_name, $tid);
            $tag->name = $name;
            $tag->save();
        }
        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string)V('equipments:equipment_tags/tag', [
                                            'tid'=>$tid,
                                            'id'=>$tid,
                                            'tag_name'=>$tag_name,
                                            'relate_uniqid'=>$relate_uniqid
                                    ]),
            'mode' => 'replace'
        ];
    }

    function index_tag_relate_data_submit() {
        $form = Form::filter(Input::form());
        $users = $form['users'];
        $labs = $form['labs'];
        $tid = $form['tid'];
        $tag_name = $form['tag_name'] ?: 'tag';
        $uniqid = $form['uniqid'];
        if (!$tid) {
            return;
        }
        $me        = L('ME');
        $equipment = O('equipment', $form['eid']);
        $tag = O($tag_name, $tid);

        if ($users) {
            $users           = json_decode($users, true);
            $connected_users = Q("{$tag} user");
            foreach ($users as $uid => $name) {
                //给标签关联新的user,并删除不存在的user
                $user = O('user', $uid);
                if ($user->id) {
                    if (!isset($connected_users[$user->id])) {
                        $tag->connect($user);
                    } else {
                        unset($connected_users[$user->id]);
                    }
                }
            }
            if (count($connected_users)) {
                foreach ($connected_users as $user) {
                    $tag->disconnect($user);
                }
            }
        }

        if ($labs) {
            $labs           = json_decode($labs, true);
            $connected_labs = Q("{$tag} lab");
            foreach ($labs as $lid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $lab = O('lab', $lid);
                if ($lab->id) {
                    if (!isset($connected_labs[$lab->id])) {
                        $tag->connect($lab);
                    } else {
                        unset($connected_labs[$lab->id]);
                    }
                }
            }
            if (count($connected_labs)) {
                foreach ($connected_labs as $lab) {
                    $tag->disconnect($lab);
                }
            }
        }
        #ifdef (equipment.enable_group_specs)
        if (Config::get('equipment.enable_group_specs')) {
            $groups = $form['groups'];
            if ($groups) {
                $root = Tag_Model::root('group');
                $groups = json_decode($groups, TRUE);
                $connected_groups = Q("{$tag} tag_group[root={$root}]");
                foreach ($groups as $gid => $name) {
                    //给标签关联新的lab,并删除不存在的lab
                    $group = O('tag_group', $gid);
                    if ($group->id) {
                        if (!isset($connected_groups[$gid])) {
                            $tag->connect($group);
                        } else {
                            unset($connected_groups[$gid]);
                        }
                    }
                }
                if (count($connected_groups)) {
                    foreach ($connected_groups as $group) {
                        $tag->disconnect($group);
                    }
                }
            }
        }
        #endif
        Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的用户标签%tag_name[%tag_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id, '%tag_name' => $tag->name, '%tag_id' => $tag->id]), 'journal');

        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string) V('equipments:equipment_tags/message',
                ['uniqid' => $uniqid, 'type' => Lab::MESSAGE_NORMAL, 'message' => I18N::T('equipments', '用户标签设置成功!')]),
            'mode' => 'replace',
        ];
    }

    public function index_admin_tag_relate_data_submit()
    {
        $form   = Form::filter(Input::form());
        $users  = $form['users'];
        $labs   = $form['labs'];
        $tid    = $form['tid'];
        $uniqid = $form['uniqid'];
        $tag_name = $form['tag_name'] ?: 'tag';
        if (!$tid) return;
        $me = L('ME');
        $tag = O($tag_name, $tid);

        if ($users) {
            $users           = json_decode($users, true);
            $connected_users = Q("{$tag} user");
            foreach ($users as $uid => $name) {
                //给标签关联新的user,并删除不存在的user
                $user = O('user', $uid);
                if ($user->id) {
                    if (!isset($connected_users[$user->id])) {
                        $tag->connect($user);
                    } else {
                        unset($connected_users[$user->id]);
                    }
                }
            }
            if (count($connected_users)) {
                foreach ($connected_users as $user) {
                    $tag->disconnect($user);
                }
            }
        }

        if ($labs) {
            $labs           = json_decode($labs, true);
            $connected_labs = Q("{$tag} lab");
            foreach ($labs as $lid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $lab = O('lab', $lid);
                if ($lab->id) {
                    if (!isset($connected_labs[$lab->id])) {
                        $tag->connect($lab);
                    } else {
                        unset($connected_labs[$lab->id]);
                    }
                }
            }
            if (count($connected_labs)) {
                foreach ($connected_labs as $lab) {
                    $tag->disconnect($lab);
                }
            }
        }
        #ifdef (equipment.enable_group_specs)
        if (Config::get('equipment.enable_group_specs')) {
            $groups = $form['groups'];
            if ($groups) {
                $root = Tag_Model::root('group');
                $groups = json_decode($groups, TRUE);
                $connected_groups = Q("{$tag} tag_group[root={$root}]");
                foreach ($groups as $gid => $name) {
                    //给标签关联新的lab,并删除不存在的lab
                    $group = O('tag_group', $gid);
                    if ($group->id) {
                        if (!isset($connected_groups[$gid])) {
                            $tag->connect($group);
                        } else {
                            unset($connected_groups[$gid]);
                        }
                    }
                }
                if (count($connected_groups)) {
                    foreach ($connected_groups as $group) {
                        $tag->disconnect($group);
                    }
                }
            }
        }
        #endif

        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string) V('equipments:admin/user_tags/message',
                ['type' => Lab::MESSAGE_NORMAL, 'message' => I18N::T('equipments', '用户标签设置成功!')]),
            'mode' => 'append',
        ];
    }

    function index_tag_change_weight() {

        $form = Form::filter(Input::form());
        $tag_type = isset($form["tag_type"])?$form["tag_type"]:"tag";
        $tag = O("{$tag_type}", $form['tag_id']);
        $root = $tag->root;
        $uniqid = $form['uniqid'];
        if (!$tag->id) {
            return;
        }

        $prev_weight    = Q::quote($form['prev_index']);
        $next_weight    = $prev_weight + 1;
        $current_weight = $tag->weight;
        if ($prev_weight == $next_weight) {
            return;
        }
        $next_tag = O("{$tag_type}", ['weight' => $next_weight, 'root' => $root]);
        $change_weight = $next_weight;
        if ($next_tag->id) {
            $selector = "{$tag_type}[root={$root}][weight > %s][weight < %s]:sort(weight %s)";
            if ($prev_weight < $current_weight) {
                $tmp_weight = $change_weight = $next_weight;
                $selector   = sprintf($selector, $prev_weight, $current_weight, 'A');
                $way        = true;
            } else {
                $tmp_weight = $change_weight = $prev_weight;
                $selector   = sprintf($selector, $current_weight, $next_weight, 'D');
                $way        = false;
            }
            $tags = Q($selector);
            foreach ($tags as $t) {
                if ((int) $t->weight != $tmp_weight) {
                    continue;
                } else {
                    if ($way) {
                        $tmp_weight++;
                    } else {
                        $tmp_weight--;
                    }
                    $t->weight = $tmp_weight;
                    $t->save();
                }
            }
        }
        $tag->weight = $change_weight;
        $tag->save();

        $tags = Q("{$tag_type}[root={$root}]:sort(weight A)");
        foreach($tags as $key => $tag) {
            $views .=  (string)V('equipments:equipment_tags/tag', ['id'=>$key, 'tid'=>$form['tid'], 'relate_uniqid'=>$uniqid]);
        }

        Output::$AJAX['.relate_list > .equipment_tags'] = (string) $views;
        if (in_array($tag_type, ['tag_equipment_user_tags'])) {
            return;
        }
        $root                                           = $tag->root;
        $equipment                                      = Q("$root equipment.tag_root:limit(1)")->current();
        if ($equipment->control_mode != 'nocontrol') {
            $agent = new Device_Agent($equipment);
            $agent->call('halt');
        }
    }

    public function index_admin_create_tag_click()
    {
        JS::dialog(V('equipments:admin/user_tags/create_tag', ['eid' => $eid]),['title' => I18N::T('equipment', '添加用户标签')]);
    }

    public function index_admin_create_tag_submit()
    {
        $form     = Form::filter(Input::form());
        $me       = L('ME');
        $tag_name = $form['name'];
        $form->validate('name', 'not_empty', I18N::T('equipments', '标签名称不能为空!'));
        if ($form->no_error) {
            //仪器用户标签
            $root = Tag_Model::root('equipment_user_tags');

            $tag = O('tag_equipment_user_tags', ['root' => $root, 'name' => $tag_name]);
            if (!$tag->id) {
                $tag->name   = $tag_name;
                $tag->parent = $root;

                $tags = Q("tag_equipment_user_tags[root={$root}]:sort(weight D):limit(1)");
                if (!$tags->total_count()) {
                    $tag->weight = 0;
                } else {
                    $tag->weight = $tags->current()->weight + 1;
                }

                $tag->update_root()->save();

                JS::refresh();
            } else {
                $form->set_error('name', I18N::T('equipments', '该标签已存在，请输入其他名称!'));
            }
        }
        JS::dialog(V('equipments:admin/user_tags/create_tag', ['form' => $form]), ['title' => I18N::T('equipment', '添加用户标签')]);
    }

    /* 增加仪器列表中对仪器的按编号快捷搜索(kai.wu@2011.11.16) */
    public function index_eq_search_submit()
    {
        $ref_no = trim(Input::form('ref_no'));
        if ($ref_no == null) {
            JS::alert(I18N::T('equipments', '请输入您想要查询的仪器编号!'));
        } else {
            $equipment = O('equipment', ['ref_no' => $ref_no]);
            if (!$equipment->id) {
                JS::alert(I18N::T('equipments', '您查询的仪器不存在!'));
            } else {
                JS::redirect($equipment->url());
            }
        }
    }

    //添加archive
    public function index_add_archive_click()
    {
        $form      = Input::form();
        $equipment = O('equipment', $form['eid']);

        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        JS::dialog(V('record/archive/add', [
            'equipment' => $equipment,
        ]),['title' => I18N::T('equipments','新建档案')]);
    }

    public function index_add_archive_submit()
    {
        $form      = Form::filter(Input::form());
        $name      = trim($form['name']);
        $equipment = O('equipment', $form['eid']);

        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        $form->validate('name', 'not_empty', I18N::T('equipments', '档案名称不能为空!'));

        if ($name && in_array($name, Q("$equipment tag.archive")->to_assoc('id', 'name'))) {
            $form->set_error('name', I18N::T('equipments', '档案名称重复!'));
        }

        if ($form->no_error) {
            $archive = O('tag', [
                'name' => $name,
            ]);
            if (!$archive->id) {
                $archive->name = $name;
                $archive->save();
            }

            $equipment->connect($archive, 'archive');

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '档案添加成功!'));

            //添加结束后跳转到未归档列表
            JS::redirect(URI::url($equipment->url('records'), ['unarchive' => true]));
        } else {
            JS::dialog(V('record/archive/add', [
                'equipment' => $equipment,
                'form'      => $form,
            ]));
        }
    }

    public function index_archive_click()
    {
        $form      = Input::form();
        $archive   = O('tag', $form['archive_id']);
        $equipment = O('equipment', $form['eid']);
        $me        = L('ME');
        if (!$archive->id) {
            return false;
        }
        if (!$equipment->id) {
            return false;
        }
        if (!$me->is_allowed_to('列表仪器使用记录', $equipment)) {
            return false;
        }

        JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
    }

    public function index_archive_all_click()
    {
        $form      = Input::form();
        $equipment = O('equipment', $form['eid']);
        $archive   = O('tag', $form['archive_id']);

        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        $form_token = $form['form_token'];
        $selector   = $_SESSION[$form_token]['selector'];
        $records    = Q($selector);

        if (!count($records)) {
            JS::alert(I18N::T('equipments', '暂无可归档的使用记录!'));
            return false;
        }

        if ($archive->id) {
            foreach ($records as $record) {
                $record->connect($archive, 'archive');
            }
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '使用记录归档成功!'));
            JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
        } else {
            JS::dialog(V('record/archive/archive_all', [
                'form'      => $form,
                'equipment' => $equipment,
            ]));
        }
    }

    public function index_archive_all_submit()
    {
        $form      = Form::filter(Input::form());
        $name      = trim($form['new_archive']);
        $equipment = O('equipment', $form['eid']);

        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        $form_token = $form['form_token'];
        $selector   = $_SESSION[$form_token]['selector'];
        $records    = Q($selector);

        try {
            $error_records = [];
            if ($form['type'] == 'new') {
                $form->validate('new_archive', 'not_empty', I18N::T('equipments', '档案名称不能为空!'));

                // 判断档案名称是否冲突
                if ($name && in_array($name, Q("$equipment tag.archive")->to_assoc('id', 'name'))) {
                    $form->set_error('new_archive', I18N::T('equipments', '档案名称重复!'));
                }

                if ($form->no_error) {
                    // 创建档案
                    $archive = O('tag', ['name' => $name]);
                    if (!$archive->id) {
                        $archive->name = $name;
                        $archive->save();
                    }

                    // 关联档案
                    $equipment->connect($archive, 'archive');

                    // 归档
                    foreach ($records as $record) {
                        if (!$record->dtend || $record->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                            $error_records[] = $record->id;
                            continue;
                        }
                        $record->connect($archive, 'archive');
                        // 归档后同步锁定仪器使用记录
                        $record->is_locked = true;
                        $record->save();
                    }
                    if (count($error_records)) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '部分使用记录未成功归档!'));
                    } else {
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '归档成功!'));
                    }
                    JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
                } else {
                    throw new Error_Exception();
                }
            } else {
                $form->validate('old_archive', 'not_empty', I18N::T('equipments', '已有档案不能为空!'));
                if ($form['old_archive']) {
                    if (!in_array($form['old_archive'], Q("$equipment tag.archive")->to_assoc('id', 'id'))) {
                        // 如果archive不在这个仪器的archive列表中
                        $form->set_error('old_archive', I18N::T('equipments', '错误的档案!'));
                    }

                    if ($form->no_error) {
                        $archive = O('tag', $form['old_archive']);
                        foreach ($records as $record) {
                            if (!$record->dtend || $record->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                                $error_records[] = $record->id;
                                continue;
                            }
                            $record->connect($archive, 'archive');
                            $record->is_locked = true;
                            $record->save();
                        }

                        if (count($error_records)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '部分使用记录未成功归档!'));
                        } else {
                            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '归档成功!'));
                        }
                        JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
                    } else {
                        throw new Error_Exception();
                    }
                } else {
                    JS::dialog(V('record/archive/archive_all', ['form' => $form, 'equipment' => $equipment]));
                }
            }
        } catch (Error_Exception $e) {
            JS::dialog(V('record/archive/archive_all', [
                'form'      => $form,
                'equipment' => $equipment,
            ]));
        }
    }

    public function index_archive_selected_click()
    {
        $form      = Input::form();
        $equipment = O('equipment', $form['eid']);

        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        JS::dialog(V('record/archive/archive_selected', [
            'form'      => $form,
            'equipment' => $equipment,
        ]));
    }

    public function index_archive_selected_submit()
    {
        $form      = Form::filter(Input::form());
        $name      = trim($form['new_archive']);
        $equipment = O('equipment', $form['eid']);

        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        try {
            if ($form['type'] == 'new') {
                $form->validate('new_archive', 'not_empty', I18N::T('equipments', '档案名称不能为空!'));

                // 判断档案名称是否冲突
                if ($name && in_array($name, Q("$equipment tag.archive")->to_assoc('id', 'name'))) {
                    $form->set_error('new_archive', I18N::T('equipments', '档案名称重复!'));
                }

                if ($form->no_error) {
                    // 创建档案
                    $archive = O('tag', ['name' => $name]);
                    if (!$archive->id) {
                        $archive->name = $name;
                        $archive->save();
                    }

                    // 关联档案
                    $equipment->connect($archive, 'archive');
                    // 归档
                    foreach (explode(',',$form['ids']) as $record_id) {
                        $record = O('eq_record',$record_id);
                        if (!$record->dtend || $record->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                            $error_records[] = $record->id;
                            continue;
                        }
                        $record->connect($archive, 'archive');
                        // 归档后同步锁定仪器使用记录
                        $record->is_locked = true;
                        $record->save();
                    }
                    if (count($error_records)) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '部分使用记录未成功归档!'));
                    } else {
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '归档成功!'));
                    }

                    JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
                } else {
                    throw new Error_Exception();
                }
            } else {
                $form->validate('old_archive', 'not_empty', I18N::T('equipments', '已有档案不能为空!'));
                if ($form['old_archive']) {
                    if (!in_array($form['old_archive'], Q("$equipment tag.archive")->to_assoc('id', 'id'))) {
                        // 如果archive不在这个仪器的archive列表中
                        $form->set_error('old_archive', I18N::T('equipments', '错误的档案!'));
                    }

                    if ($form->no_error) {
                        $archive = O('tag', $form['old_archive']);
                        foreach (explode(',',$form['ids']) as $record_id) {
                            $record = O('eq_record',$record_id);
                            if (!$record->dtend || $record->status == EQ_Record_Model::FEEDBACK_NOTHING) {
                                $error_records[] = $record->id;
                                continue;
                            }
                            $record->connect($archive, 'archive');
                            // 归档后同步锁定仪器使用记录
                            $record->is_locked = true;
                            $record->save();
                        }
                        if (count($error_records)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '部分使用记录未成功归档!'));
                        } else {
                            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '归档成功!'));
                        }

                        JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
                    } else {
                        throw new Error_Exception();
                    }
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '请选择正确的档案!'));
                    JS::redirect(URI::url($equipment->url('records')));
                }
            }
        } catch (Error_Exception $e) {
            $first_error = array_shift($form->errors);
            Lab::message(Lab::MESSAGE_ERROR, $first_error[0]);
            JS::redirect(URI::url($equipment->url('records')));
        }
    }

    //点击archive右侧的find_button后触发事件
    public function index_operate_archive_click()
    {
        $form      = Input::form();
        $uniqid    = $form['uniqid'];
        $archive   = O('tag', $form['archive_id']);
        $equipment = O('equipment', $form['equipment_id']);

        if (!$archive->id) {
            return false;
        }
        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        Output::$AJAX['#' . $uniqid] = [
            'data' => (string) V('record/archive/operate_archive', [
                'archive'    => $archive,
                'equipment'  => $equipment,
                'form_token' => $form['form_token'],
            ]),
            'mode' => 'html',
        ];
    }

    //添加记录到archive
    public function index_add_records_to_archive_click()
    {
        $form    = Form::filter(Input::form());
        $archive = O('tag', $form['archive_id']);

        if (!$archive->id) {
            return false;
        }

        $form_token = $form['form_token'];
        $selector   = $_SESSION[$form_token]['selector'];
        $records    = Q($selector);
        if ($records->total_count()) {
            foreach ($records as $record) {
                $record->connect($archive, 'archive');
                $record->is_locked = true;
                $record->save();
            }
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '归档成功!'));
            JS::redirect(URI::url(null, ['aid' => $archive->id]));
        } else {
            JS::alert(I18N::T('equipments', '暂无可归档的使用记录!'));
            return false;
        }
    }

    //编辑archive
    public function index_edit_archive_click()
    {
        $form      = Input::form();
        $archive   = O('tag', $form['archive_id']);
        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id) {
            return false;
        }
        if (!$archive->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        JS::dialog(V('record/archive/edit', [
            'archive'   => $archive,
            'equipment' => $equipment,
        ]));
    }

    //编辑archive后提交
    public function index_edit_archive_submit()
    {
        $form      = Form::filter(Input::form());
        $name      = trim($form['name']);
        $archive   = O('tag', $form['archive_id']);
        $equipment = O('equipment', $form['equipment_id']);

        if (!$archive->id) {
            return false;
        }
        if (!$equipment->id) {
            return false;
        }
        if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
            return false;
        }

        $form->validate('name', 'not_empty', I18N::T('equipments', '档案名称不能为空!'));

        if ($name && $name != $archive->name && in_array($name, Q("$equipment tag.archive")->to_assoc('id', 'name'))) {
            $form->set_error('name', I18N::T('equipments', '档案名称重复!'));
        }

        if ($form->no_error) {
            $archive->name = $name;
            $archive->save();

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '编辑档案成功!'));
            JS::redirect(URI::url($equipment->url('records'), ['aid' => $archive->id]));
        } else {
            JS::dialog(V('record/archive/edit', [
                'archive'   => $archive,
                'equipment' => $equipment,
                'form'      => $form,
            ]));
        }
    }

    //删除archive
    public function index_delete_archive_click()
    {
        if (JS::confirm(I18N::T('equipments', '确定要删除该档案吗? 删除该档案, 其中的使用记录不会被删除.'))) {
            $form      = Form::filter(Input::form());
            $archive   = O('tag', $form['archive_id']);
            $equipment = O('equipment', $form['equipment_id']);
            if (!$archive->id) {
                return false;
            }
            if (!$equipment->id) {
                return false;
            }
            if (!L('ME')->is_allowed_to('添加仪器使用记录', $equipment)) {
                return false;
            }

            $records = Q("$archive<archive eq_record");
            foreach ($records as $record) {
                if (!$record->locked_flag) {
                    $record->is_locked = false;
                }

                $record->save();
                $record->disconnect($archive, 'archive');
            }

            $archive->delete();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '删除档案成功!'));
            JS::redirect(URI::url($equipment->url('records'), ['unarchive' => true]));
        }
    }

    public function index_custom_offline_password_click()
    {
        $form = Input::form();
        if (!$form['uniqid'] || !$form['equipment_id']) {
            return;
        }
        $equipment = O('equipment', $form['equipment_id']);

        if (!L('ME')->is_allowed_to('修改', $equipment)) {
            return;
        }

        if (!$equipment->connect) {
            Output::$AJAX['#' . $form['uniqid']] = ['data' => (string) V('equipment/offline_password/view', ['equipment' => $equipment, 'form_uniqid' => $form['uniqid']]), 'mode' => 'replace'];
            return;
        }

        JS::dialog(V('equipment/offline_password/edit', ['equipment' => $equipment, 'uniqid' => $form['uniqid']]), ['title' => I18N::T('equipments', '自定义密码')]);
    }

    public function index_custom_offline_password_submit()
    {
        $form = Form::filter(Input::form());
        if (!$form['uniqid'] || !$form['equipment_id']) {
            return;
        }
        $equipment = O('equipment', $form['equipment_id']);

        if (!L('ME')->is_allowed_to('修改', $equipment)) {
            return;
        }

        if (!$equipment->connect) {
            JS::alert(I18N::T('equipments', '客户端连接异常, 请恢复后再试!'));
            return;
        }

        $form->validate('offline_password', 'length(6,24)', I18N::T('equipments', '密码不能小于6位或大于24位!'));

        //如果密码相同则不作修改
        if ($form['offline_password'] == $equipment->offline_password) {
            JS::close_dialog();
            return;
        }

        if (!$form->no_error) {
            JS::dialog(V('equipment/offline_password/edit', ['equipment' => $equipment, 'uniqid' => $form['uniqid'], 'form' => $form]), ['title' => I18N::T('equipments', '自定义密码')]);
        } else {
            $equipment->offline_password = $form['offline_password'];
            $equipment->save();

            $client = new \GuzzleHttp\Client([
                'base_uri'    => $equipment->server,
                'http_errors' => false,
                'timeout'     => Config::get('device.computer.timeout', 5),
            ]);

            //进行物理关机
            $success = (boolean) $client->post('password', [
                'form_params' => [
                    'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                ],
            ])->getBody()->getContents();

            //发送离线密码
            Equipments::send_offline_password($equipment);

            Output::$AJAX['#' . $form['uniqid']] = ['data' => (string) V('equipment/offline_password/view', ['equipment' => $equipment]), 'mode' => 'replace'];

            JS::alert(I18N::T('equipments', '离线密码更新成功!'));
            JS::close_dialog();
        }
    }

    //更新控制状态
    public function index_control_status_get()
    {
        $form      = Input::form();
        $equipment = O('equipment', $form['equipment_id']);
        if ($equipment->id &&
            ($equipment->is_using != $form['is_using']
                || $equipment->is_monitoring != $form['is_monitoring']
                || $equipment->connect != $form['is_monitoring'])) {
            Output::$AJAX['#' . $form['container_id']] = [
                'data'            => (string) V('equipment/control', ['equipment' => $equipment, 'uniqid' => $form['container_id']]),
                'mode'            => 'replace',
                'requestInterval' => $form['requestInverval'],
            ];
        }
    }

    function index_photo_click () {
        $form = Input::form();
        $equipment = O('equipment', $form['id']);
        $icon_file = $equipment->icon_file('real');
        if ($icon_file) {
            $url = Config::get('system.base_url') . Cache::cache_file($icon_file) . '?_=' . $equipment->mtime;
        }else{
            $url = $equipment->icon_url();
        }

        $view = V('equipment/view/icon_dialog', [
            'url' => $url
        ]);
        JS::dialog($view, ['title' => I18N::T('equipments', '设备图标')]);
    }


    function index_create_input_click() {
        JS::dialog(V('equipments:create_input'));
    }

    function index_script_visualization_click() {
        $form = Input::form();
        if(!isset($form['custom_content']) || !isset($form['custom_type'])){
            Output::$AJAX['data'] = '';
        }
        $equipment = O('equipment', $form['eid']);
        $output = Equipments::custom_content_render($equipment,$form['custom_type'],$form['custom_content']);
        Output::$AJAX['data'] = $output;
    }

    public function index_auth_code_refresh_click ()
    {
        $form = Input::form();

        $me = L('ME');
        $equipment = O('equipment',$form['equipment_id']);

        if ($equipment->id && $me->is_allowed_to('修改使用设置', $equipment)) {
            Output::$AJAX['#'.$form['code_id']]=[
                'data'=>(string)V('equipments:equipment/auth_code', ['equipment'=>$equipment]),
                'mode'=>'replace'
            ];
        }
    }
}
