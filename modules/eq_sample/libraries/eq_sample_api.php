<?php
class Eq_Sample_API
{
    static $status_label = [
        EQ_Sample_Model::STATUS_APPLIED => 'requested',
        EQ_Sample_Model::STATUS_APPROVED => 'approved',
        EQ_Sample_Model::STATUS_TESTED => 'completed',
        EQ_Sample_Model::STATUS_REJECTED => 'rejected',
        EQ_Sample_Model::STATUS_CANCELED => 'cancelled',
    ];

    public static function equipment_samples_get($e, $params, $data, $query)
    {
        /* * 
         * equipmentId 仪器ID Y
         * startTime 开始时间
         * endTime 结束时间
         * st 起始位置
         * pp 每页条目
         * */
        $selector = "eq_sample";

        $user = L('gapperUser');

        if (isset($query['equipmentId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['equipmentId$']));
            if (!count($ids)) {
                throw new Exception('equipmentId Cannot be empty', 404);
            }

            // 查询固定仪器的记录，任意一台没有权限,就应该让他只能看到自己的
            foreach (Q("equipment[id=" . join(",", $ids) . "]") as $equipment) {
                if (!$user->is_allowed_to('查看所有送样记录', $equipment)) {
                    $selector = "{$user}<sender eq_sample";
                    break;
                }
            }

            $selector .= "[equipment_id=" . join(",", $ids) . "]";
        } else {
            // 查询所有的仪器
            if (!$user->is_allowed_to('查看所有送样记录', 'equipment')) {
                if (!isset($query['labId$'])) {
                    $selector = "{$user}<sender eq_sample";
                }
            }
        }

        if (isset($query['labId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['labId$']));
            if (!count($ids)) {
                throw new Exception('labId cannot be empty', 404);
            }
            $selector .= "[lab_id=" . join(",", $ids) . "]";
        }

        if (isset($query['startTime']) && intval($query['startTime'])) {
            $dtstart = intval($query['startTime']);
            $selector .= "[dtsubmit>={$dtstart}]";
        }
        if (isset($query['endTime']) && intval($query['endTime'])) {
            $dtend = intval($query['endTime']);
            $selector .= "[dtsubmit>0][dtsubmit<={$dtend}]";
        }
        if (isset($query['senderId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['senderId$']));
            if (!count($ids)) {
                throw new Exception('senderId Cannot be empty', 404);
            }
            $selector .= "[sender_id=" . join(",", $ids) . "]";
        }
        
        if (isset($query['status']) && in_array($query['status'], self::$status_label)) {
            foreach (self::$status_label as $k => $v) {
                if ($query['status'] == $v) {
                    $selector .= "[status=$k]";
                }
            }
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(ctime D)";
        $samples = [];
        foreach (Q("$selector") as $sample) {
            $samples[] = self::sample_format($sample);
        }
        $e->return_value = ["total" => $total, "items" => $samples];
    }

    public static function equipment_sample_get($e, $params, $data, $query)
    {
        $sample = O('eq_sample', $params[0]);
        if (!$sample->id) {
            throw new Exception('sample not found', 404);
        }
        $e->return_value = self::sample_format($sample);
    }

    public static function equipment_sample_post($e, $params, $data, $query)
    {
        $equipment = O('equipment', ['id' => $data['equipment']['identity']]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        if (!$equipment->accept_sample) {
            throw new Exception('equipment is not access sample', 403);
        }
        $user = O('user', ['id' => $data['user']['identity']]);
        if (!$user->id) {
            $user = Event::trigger('get_user_by_username', $data['user']['identity']);
        }
        if (!$user->id) {
            $user = L('gapperUser');
        }

        if (!$equipment->id || !$user->is_allowed_to('添加送样请求', $equipment)) {
            $messages = array_merge(Lab::messages(Lab::MESSAGE_ERROR), Lab::messages('sample'));
            throw new Exception($messages[0], 403);
        }

        $form = self::_makeForm($data, $equipment);
        $now = Date::time();

        try {
            if (!is_numeric($form['count']) || intval($form['count']) <= 0 || intval($form['count']) != $form['count']) {
                $form->set_error('count',  I18N::T('eq_sample', '样品数 填写有误, 请填写大于0的整数!'));
            }

            if (intval($form['dtsubmit']) < $now && !Config::get('eq_sample.dtsubmit_allowed_in_the_past', false)) {
                $form->set_error('dtsubmit',  I18N::T('eq_sample', '送样时间必须大于当前时间!'));
            }

            Event::trigger('extra.form.validate', $equipment, 'eq_sample', $form);

            // TODO: 前端没这个view
            // $must_connect_project = Config::get('eq_sample.must_connect_lab_project');
            // if ( $must_connect_project && !$form['project'] ) {
            //     $form->set_error('project', I18N::T('eq_sample', '"关联项目" 不能为空!') );
            // }

            if (!$form->no_error) {
                throw new Error_Exception;
            }

            $sample = O('eq_sample');
            $sample->equipment = $equipment;
            if ($form['count']) {
                $sample->count = (int)max($form['count'], 1);
            }
            if ($form['dtsubmit']) {
                $sample->dtsubmit = $form['dtsubmit'];
            }
            if ($form['status']) {
                $sample->status = $form['status'];
            }
            if ($form['description']) {
                $sample->description = $form['description'];
            }
            // if ($form['project']) {
            //     $sample->project = O('lab_project', $form['project']);
            // }

            Event::trigger('sample.form.submit', $sample, $form);
            $sample->status = EQ_Sample_Model::STATUS_APPLIED;
            $sample->sender = $user;
            $sample->lab = Q("$user lab")->current();

            //自定义送样表单存储供lua计算
            if (Module::is_installed('extra')) {
                $sample->extra_fields = $form['extra_fields'];
            }

            if ($sample->save()) {
                Event::trigger('extra.form.post_submit', $sample, $form);
                /* 记录日志 */
                Log::add(strtr('[eq_sample_api] %sender_name[%sender_id]申请了%equipment_name[%equipment_id]的送样[%sample_id]', [
                    '%sender_name' => $sample->sender->name,
                    '%sender_id' => $sample->sender->id,
                    '%equipment_name' => $sample->equipment->name,
                    '%equipment_id' => $sample->equipment->id,
                    '%sample_id' => $sample->id
                ]), 'journal');
            } else {
                throw new Error_Exception();
            }
            $e->return_value = self::sample_format($sample);
        } catch (Error_Exception $err) {
            $messages = [];
            foreach ($form->errors as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $messages[] = $vv;
                }
            }
            throw new Exception(join(" ", $messages), 400);
        }
    }

    public static function equipment_sample_patch($e, $params, $data, $query)
    {
        $me = L("gapperUser");
        $sample = O('eq_sample', ['id' => $params[0]]);
        $is_admin = $me->is_allowed_to('管理', $sample);
        $is_general = $me->id == $sample->sender->id && $sample->status == EQ_Sample_Model::STATUS_APPLIED;
        if (!$sample->id || !($is_admin || $is_general)) {
            throw new Exception('Forbidden', 403);
        };
        $equipment = $sample->equipment;
        $form = self::_makeForm($data, $equipment);

        if ($form['status']) {
            $sample->status = $form['status'];
        }

        try {

            // Event::trigger('extra.form.validate', $equipment, 'eq_sample', $form);

            //验证是否为整数
            if (!is_numeric($form['count']) || intval($form['count']) <= 0 || intval($form['count']) != $form['count']) {
                if (!Event::trigger('sample.count.save_except_validate', $equipment)) {
                    $form->set_error('count',  I18N::T('eq_sample', '样品数 填写有误, 请填写大于0的整数!'));
                }
            }

            if (!$is_admin && $form['dtsubmit'] < $now && !Config::get('eq_sample.dtsubmit_allowed_in_the_past', false)) {
                $form->set_error('dtsubmit',  I18N::T('eq_sample', '送样时间必须大于当前时间!'));
            }

            if ($form['status'] == EQ_Sample_Model::STATUS_TESTED) {
                if (intval($form['success_samples']) < 0 || intval($form['success_samples']) != $form['success_samples']) {
                    $form->set_error('success_samples',  I18N::T('eq_sample', '测样成功数填写有误, 请重新填写!'));
                }

                if ($form['success_samples'] > $form['count']) {
                    $form->set_error('success_samples',  I18N::T('eq_sample', '测样成功数须小于样品数!'));
                }
            }

            // //如果进行测样设定
            // if ($form['dtrial_check'] == 'on') {
            //     if ($form['dtend'] < $form['dtstart']) {
            //         $form->set_error('dtend', I18N::T('eq_sample', '截止时间不能小于开始时间!'));
            //     }

            //     if ($form['dtend'] == $form['dtstart']) {
            //         $form->set_error('dtend', I18N::T('eq_sample', '测样起止时间不能相同!'));
            //     }
            // }

            // $must_connect_project = Config::get('eq_sample.must_connect_lab_project');
            // if ($must_connect_project && $GLOBALS['preload']['people.multi_lab'] && !$form['project_lab']) {
            //     $form->set_error('project_lab', I18N::T('eq_sample', '该送样申请必须关联实验室, 请关联实验室!'));
            // }
            // if ($must_connect_project && !$form['project']) {
            //     $form->set_error('project', I18N::T('eq_sample', '该送样申请必须关联项目, 请关联项目!'));
            // }

            if ($form['user'] && $is_admin) {
                if ($is_admin) {
                    $sender = O('user', $form['user']['identity']);
                    if (!$sender->id) {
                        $sender = Event::trigger('get_user_by_username', $data['user']['identity']);
                    }
                }

                if (!$sender->id) {
                    $form->set_error('sender', I18N::T('eq_sample', '申请人不能为空!'));
                }
            }
            // if (isset($form['project'])) {
            //     $sample->project = O('lab_project', $form['project']);
            // }

            // if ($GLOBALS['preload']['people.multi_lab']) {
            //     $sample->lab = $form['project_lab'] ? O('lab', $form['project_lab']) : Q("$sender lab")->current();
            // } else {
            // }

            if ($form['user'] && $is_admin) {
                $sample->lab = $sample->project->lab->id ? $sample->project->lab : Q("$sender lab")->current();
                $sample->sender = $sender;
            }

            $sample->dtsubmit = $form['dtsubmit'] ?: $sample->dtsubmit;
            $sample->dtstart = $form['dtstart'] ?: $sample->dtstart;
            $sample->dtend = $form['dtend'] ?: $sample->dtend;
            $sample->dtpickup = $form['dtpickup'] ?: $sample->dtpickup;

            if ($sample->is_locked()) {
                $form->set_error('id', I18N::T('eq_sample', '您设置的时段已被锁定!'));
            }

            if (!$form->no_error) {
                throw new Error_Exception;
            }

            if ($form['status'] == EQ_Sample_Model::STATUS_TESTED || (!isset($form['status']) && $sample->status == EQ_Sample_Model::STATUS_TESTED)) {
                $sample->success_samples = (int)max($form['success_samples'], 0);
            }

            //管理员进行sample信息修改, 就会设定操作者
            if ($me->is_allowed_to('修改', $sample)) $sample->operator = $me;

            if ($form['count']) {
                $sample->count = (int)max($form['count'], 1);
            }
            if ($form['description']) {
                $sample->description = $me->id == $sample->sender->id ? $form['description'] : $sample->description;
            }

            Event::trigger('sample.form.submit', $sample, $form);

            //系统管理员/本仪器负责人编辑送样请求
            if ($is_admin && $form['status']) {
                $sample->status = $form['status'];
                $sample->note = $form['note'];
                if ($sample->status == EQ_Sample_Model::STATUS_APPLIED) {
                    $sample->dtpickup = 0;
                }
            }

            //自定义送样表单存储供lua计算
            if (Module::is_installed('extra')) {
                $sample->extra_fields = $form['extra_fields'];
            }
            if ($sample->save()) {
                Event::trigger('extra.form.post_submit', $sample, $form);

                /* 记录日志 */
                Log::add(strtr('[eq_sample_api] %user_name[%user_id]修改了%equipment_name[%equipment_id]的送样[%sample_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%equipment_name' => $sample->equipment->name,
                    '%equipment_id' => $sample->equipment->id,
                    '%sample_id' => $sample->id
                ]), 'journal');
            }

            $e->return_value = self::sample_format($sample);
        } catch (Error_Exception $e) {
            $messages = [];
            foreach ($form->errors as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $messages[] = $vv;
                }
            }
            throw new Exception(join(" ", $messages), 400);
        }
    }
    public static function equipment_sample_delete($e, $params, $data, $query)
    {
        $me = L("gapperUser");
        $sample = O('eq_sample', ['id' => $params[0]]);
        $equipment = $sample->equipment;
        $sender = $sample->sender;
        $now = Date::time();
        $is_admin = $me->is_allowed_to('修改', $sample);
        $is_general = ($me->id == $sample->sender->id) && ($sample->status == EQ_Sample_Model::STATUS_APPLIED);
        if (!$sample->id || !($is_admin || $is_general)) {
            throw new Exception('Forbidden', 403);
        }

        $sample_attachments_dir_path = NFS::get_path($sample, '', 'attachments', TRUE);

        $ret = self::sample_format($sample);
        if ($sample->delete()) {
            /* 记录日志 */
            Log::add(strtr('[eq_sample_api] %user_name[%user_id]删除了%equipment_name[%equipment_id]的送样[%sample_id]', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_name' => $sample->equipment->name,
                '%equipment_id' => $sample->equipment->id,
                '%sample_id' => $sample->id
            ]), 'journal');
            File::rmdir($sample_attachments_dir_path);
            $e->return_value = $ret;
        }
    }

    public static function sample_permission_post($e, $params, $data, $query)
    {
        $me = L('gapperUser');
        $sample = O('eq_sample', ['id' => $params[0]]);
        if (!$sample->id) {
            throw new Exception('sample not found', 404);
        }

        $links = [
            'actions' => []
        ];

        if ($me->is_allowed_to('修改', $sample)) {
            $links['actions'][] = [
                'title' => I18N::T('equipments', '编辑'),
                'action' => 'edit',
            ];
        }
        if ($me->is_allowed_to('删除', $sample)) {
            $links['actions'][] = [
                'title' => I18N::T('equipments', '删除'),
                'action' => 'delete',
            ];
        }

        $links['total'] = count($links['actions']);
        $e->return_value = $links;
    }

    public static function sample_format($sample)
    {
        $extra_value = O('extra_value', ['object' => $sample]);
        $ret = Extra_API::extra_value_format($extra_value);
        $ret['category_0']['count'] = $sample->count;
        $ret['category_0']['description'] = $sample->description;
        $ret['category_0']['successSamples'] = $sample->success_samples;
        $ret['category_0']['status'] = self::$status_label[$sample->status];
        $ret['category_0']['submitTime'] = $sample->dtsubmit ?: 0;
        $ret['category_0']['startTime'] = $sample->dtstart ?: 0;
        $ret['category_0']['endTime'] = $sample->dtend ?: 0;
        $ret['category_0']['pickupTime'] = $sample->dtpickup ?: 0;
        return array_merge(
            $ret,
            [
                'id' => (int)$sample->id,
                'user' => [
                    'id' => $sample->sender->id,
                    'name' => $sample->sender->name,
                ],
                'count' => $sample->count,
                'equipment' => [
                    'id' => $sample->equipment->id,
                    'name' => $sample->equipment->name,
                    'icon' => [
                        'original' => $sample->equipment->icon_url($sample->equipment->icon_file('real') ? 'real' : 128),
                        '32×32' => $sample->equipment->icon_url('32'),
                    ],
                ],
            ]
        );
    }

    private static function _makeForm($data, $equipment)
    {
        $form = [];
        unset($data['equipment']);
        unset($data['lab']);
        // unset($data['user']);
        $extra = O('extra', ['object' => $equipment, 'type' => 'eq_sample']);
        if ($extra->id) {
            $categories = $extra->get_categories();
            foreach ($categories as $ck => $category) {
                $data_ck = "category_{$ck}";
                $fields = $extra->get_fields($category);
                foreach ($fields as $fk => $field) {
                    $data_fk = "field_{$fk}";
                    if ($field['adopted']) {
                        $form[$fk] = $data[$data_ck][$fk];
                        continue;
                    }
                    switch ($field["type"]) {
                        case Extra_Model::TYPE_RADIO: // 单选
                        case Extra_Model::TYPE_SELECT: // 下拉菜单
                            $vv = $data[$data_ck][$data_fk] === null ? '' : (int) $data[$data_ck][$data_fk];
                            $form['extra_fields'][$fk] = $field["params"][$vv];
                            break;
                        case Extra_Model::TYPE_CHECKBOX: // 多选
                            if (!$data[$data_ck][$data_fk]) {
                                continue;
                            }
                            $value = array_flip($field["params"]);
                            foreach ($value as $k => $v) {
                                $value[$k] = in_array($v, $data[$data_ck][$data_fk]) ? "on" : null;
                            }
                            $form['extra_fields'][$fk] = $value;
                            break;
                        case Extra_Model::TYPE_TEXT: // 单行文本
                        case Extra_Model::TYPE_NUMBER: // 数值
                        case Extra_Model::TYPE_TEXTAREA: // 多行文本
                            $form['extra_fields'][$fk] = $data[$data_ck][$data_fk];
                            break;
                        case Extra_Model::TYPE_RANGE: // 数值范围
                            $value = [];
                            if (preg_match('/.*_pre/', $data_fk)) {
                                continue;
                            }
                            $form['extra_fields'][$fk] = [
                                $data[$data_ck][$data_fk . "_pre"],
                                $data[$data_ck][$data_fk]
                            ];
                            break;
                        case Extra_Model::TYPE_STAR: // 评星
                            break;
                        case Extra_Model::TYPE_DATETIME: // 日期时间
                            $form['extra_fields'][$fk] = intval($data[$data_ck][$data_fk]);
                            break;
                    }
                }
            }
        }

        if(isset($data['category_0']['status'])){
            $form['status'] = array_flip(self::$status_label)[$data['category_0']['status']];
        }

        $form['dtsubmit'] = intval($data['category_0']['submitTime']);
        $form['user'] = intval($data['user']);
        $form['success_samples'] = intval($data['category_0']['successSamples']);
        $form['dtstart'] = intval($data['category_0']['startTime']);
        $form['dtend'] = intval($data['category_0']['endTime']);
        $form['dtpickup'] = intval($data['category_0']['pickupTime']);
        return Form::filter($form);
    }
}
