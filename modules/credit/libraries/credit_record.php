<?php

// 用户得分明细
class Credit_Record
{

    const STATUS_ING = 0;
    const STATUS_DONE = 1;

    public static function setup_view()
    {
        Event::bind('profile.view.tab', 'Credit_Record::index_profile_tab');
        Event::bind('profile.view.content', 'Credit_Record::index_profile_content', 0, 'credit_record');
        Event::bind('profile.view.tool_box', 'Credit_Record::_tool_box', 0, 'credit_record');
    }

    public static function index_profile_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');
        if ($me->is_allowed_to('查看信用记录', $user)) {
            $tabs->add_tab('credit_record', [
                'url'    => $tabs->user->url('credit_record'),
                'title'  => I18N::T('credit', '信用记录'),
                'weight' => 50,
            ]);
        }
    }

    public static function index_profile_content($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');
        if (!$me->is_allowed_to('查看信用记录', $user)) {
            return;
        }

        $form = Lab::form(function (&$old_form, &$form) {
            if ($form['ctstart']) {
                $ctstart         = getdate($form['ctstart']);
                $form['ctstart'] = mktime(0, 0, 0, $ctstart['mon'], $ctstart['mday'], $ctstart['year']);
            }

            if ($form['ctend']) {
                $ctend         = getdate($form['ctend']);
                $form['ctend'] = mktime(23, 59, 59, $ctend['mon'], $ctend['mday'], $ctend['year']);
            }
        });

        $selector = "credit_record[user={$user}]";

        if ($form['ctstart']) {
            $ctstart       = Q::quote($form['ctstart']);
            $selector      = $selector . "[ctime>=$ctstart]";
        }

        if ($form['ctend']) {
            $form['ctime'] = true;
            $ctend         = Q::quote($form['ctend']);
            $selector      = $selector . "[ctime<=$ctend]";
        }

        if ($form['id']) {
            $id         = Q::quote($form['id']);
            $selector   = $selector . "[id={$id}]";
        }

        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
        } else {
            unset($form['ctime']);
        }

        $score_start = (int) $form['score_start'];
        $score_end   = (int) $form['score_end'];
        if ($score_start && $score_end) {
            $selector .= "[score={$score_start}~{$score_end}]";
            $form['credit_score'] = "{$score_start}~{$score_end}";
        } elseif ($score_start) {
            $selector .= "[score>={$score_start}]";
            $form['credit_score'] = "大于等于{$score_start}";
        } elseif ($score_end) {
            $selector .= "[score<={$score_end}]";
            $form['credit_score'] = "小于等于{$score_end}";
        }

        if (isset($form['type']) && $form['type'] != '-1') {
            $type                 = (int) $form['type'];
            $pre_selector['type'] = "credit_rule[type={$type}]";
        }

        if (isset($form['violate_type']) && $form['violate_type'] != '-1') {
            $violate_type = Q::quote($form['violate_type']);
            if ($form['violate_type'] == 'other') {
                $pre_selector['rule'] = "credit_rule[type=" . Credit_Rule_Model::STATUS_CUT . "][ref_no!=late][ref_no!=early][ref_no!=timeout][ref_no!=miss]";
            } else {
                $pre_selector['rule'] = "credit_rule[type=" . Credit_Rule_Model::STATUS_CUT . "][ref_no={$violate_type}]";
            }
        }

        if (count((array) $pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } else {
            $selector .= ':sort(id D)';
        }

        $credit_records = Q($selector);

        $per_page   = 30;
        $pagination = Lab::pagination($credit_records, (int) $form['st'], $per_page);

        $fields        = self::get_records_fields($form);
        $tabs->columns = new ArrayObject($fields);

        $tabs->content = V('credit:profile/credit_record', [
            'form'           => $form,
            'credit_records' => $credit_records,
            'pagination'     => $pagination,
            'sort_by'        => $sort_by,
            'sort_asc'       => $sort_asc,
        ]);
    }

    public static function _tool_box($e, $tabs)
    {
        $me = L('ME');

        $equipment  = $tabs->equipment;
        $form_token = $tabs->form_token;
        unset($tabs->form_token);

        $panel_buttons    = new ArrayIterator;
        $tabs->search_box = V('application:search_box', ['force_show_search_filter' => true, 'columns' => (array) $tabs->columns]);
    }

    public static function get_records_fields($form)
    {
        $me = L('ME');

        $columns = [
            'serial_number' => [
                'title' => I18N::T('credit', '编号'),
                'align' => 'left',
                'invisible' => false,
                'filter' => [
                  'form' => V('credit:profile/record_table/filters/serial_number', ['id' => $form['id']]),
                  'value' => $form['id'] ? Number::fill(H($form['id']), 6) : NULL,
                  'field' => 'id'
                ],
                'weight' => 5,
                'nowrap' => true
              ],
            'ctime'     => [
                'weight'   => 10,
                'title'    => I18N::T('credit', '计分时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('credit:profile/record_table/filters/ctime', ['form' => $form]),
                    'field' => 'ctstart,ctend',
                    'value' => $form['ctime'] ? H($form['ctime']) : null,
                ],
            ],
            'type'      => [
                'title'     => I18N::T('credit', '计分类型'),
                'weight'    => 50,
                'invisible' => true,
                'filter'    => [
                    'form'  => V('credit:credit_table/filters/type', [
                        'form' => $form,
                        'type' => $form['type'],
                    ]),
                    'value' => Credit_Rule_Model::$status[$form['type']] ?: null,
                    'field' => 'type',
                ],
            ],
            'event'     => [
                'title'  => I18N::T('credit', '计分事件'),
                'weight' => 20,
            ],
            'equipment' => [
                'weight' => 20,
                'title'  => I18N::T('credit', '关联仪器'),
                /* 'filter' => [
            'form'  => V('credit:profile/record_table/filter/equipment', [
            'equipment' => $equipment->id ? $equipment : null,
            ]),
            'value' => $equipment->id ? H($equipment->name) : null,
            ], */
            ],
            'score'     => [
                'weight' => 30,
                'title'  => I18N::T('credit', '分数'),
                'align'  => 'right',
                /* 'filter' => [
            'form'  => V('credit:credit_table/filters/credit_score', [
            'score_start' => $form['score_start'],
            'score_end'   => $form['score_end'],
            ]),
            'field' => 'score_start,score_end',
            'value' => $form['credit_score'] ? H($form['credit_score']) : null,
            ], */
            ],
            'violate_type'     => [
                'title'     => I18N::T('credit', '违规类型'),
                'weight'    => 55,
                'invisible' => true,
                'filter'    => [
                    'form'  => V('credit:credit_table/filters/violate_type', [
                        'form'  => $form,
                        'violate_type' => $form['violate_type'],
                    ]),
                    'value' => $form['violate_type'],
                    'field' => 'violate_type',
                ],
            ],
            'total'     => [
                'weight' => 40,
                'title'  => I18N::T('credit', '信用分'),
                'align'  => 'right',
                'nowrap' => true,
            ],
        ];

        return $columns;
    }

    public static function trigger_scoring_rule($e, $user, $score_rule, $equipment = null, $source = null)
    {
        // 如果没有user
        if(!$user->id) return true;

        $credit_rule = O('credit_rule', ['ref_no' => $score_rule]);
        if ($score_rule != 'init_credit_score' && (!$credit_rule->id || $credit_rule->is_disabled || $credit_rule->score <= 0)) {
            return true;
        }

        if ($score_rule == 'init_credit_score'){
            $credit       = O('credit', ['user' => $user]);
            $credit->user = $user;
            // 用户等级, 首次标记为大众会员, 之后由于计算量较大, 走cli脚本更新该值
            if (!$credit->credit_level->id) {
                $credit->credit_level = O('credit_level', ['level' => 1]); // 不管实际名称叫什么, 所占百分比为多少, 这是默认的等级
            }
            $credit->save();
        }

        // 每日登录只能加一次分
        if ($score_rule == 'login') {
            $day_start   = Date::get_day_start();
            $day_end     = Date::get_day_end();
            $total_count = Q("credit_record[user={$user}][ctime>={$day_start}][ctime<={$day_end}][description={$credit_rule->name}]")->total_count();
            if ($total_count) {
                return true;
            }
        }

        /**
         * !$credit->id === true 表明还没有生成得分, 该条目还没有被创建
         * 第一次credit_record_model.saved就会触发生成credit
         */
        $credit = O('credit', ['user' => $user]);

        if (is_object($source)) {
            $credit_record = O('credit_record', ['source' => $source, 'credit_rule' => $credit_rule]);
            $credit_record->source = $source;
        }else {
            $credit_record = O('credit_record');
        }

        $credit_record->user        = $user; // 触发计分用户
        $credit_record->credit_rule = $credit_rule; // 关联计分规则
        $credit_record->score       = $credit_rule->type ? -1 * $credit_rule->score : $credit_rule->score; // 本次得分
        $credit_record->is_auto     = !$credit_rule->is_custom; // 是否是系统自动计分

        if ($credit_rule->type) {
            // 减分
            $total = $credit->id ? ($credit->total - $credit_rule->score) : $credit_rule->score; // 当前计分后的总分
        } else {
            // 加分
            $total = $credit->id ? ($credit->total + $credit_rule->score) : $credit_rule->score; // 当前计分后的总分
        }
        $credit_record->equipment   = $equipment;
        $credit_record->total       = $total;
        $credit_record->description = $credit_rule->name;
        $credit_record->ctime       = Date::time();
        $credit_record->save(); // saved trigger 重新计算credit的得分
    }

    public static function after_component_delete($e, $form, $component)
    {
        // 自己取消自己的预约, 才触发计分事件
        if ($component->organizer->id == L('ME')->id) {
            Event::trigger('trigger_scoring_rule', $component->organizer, 'reserv_cacel', $component->calendar->parent);
        }
    }

    public static function after_reserv_status_changed($e, $reserv)
    {
        if (is_object($reserv) && $reserv->id) {
            Q("credit_record[source={$reserv}]")->delete_all();
        }
    }

    static function on_eq_banned_model_saved($e, $object, $old_data, $new_data) {

		if($object->object_name == 'equipment' && $object->user->id){
            $system_ban_measures = O('credit_measures', ['ref_no' => 'system_ban']);
            $system_ban_limit    = O('credit_limit', ['measures' => $system_ban_measures, 'is_custom' => 0]);
            if($system_ban_limit->enable && $system_ban_limit->score){
                $ban_eq_counts = Q("eq_banned[object_name=equipment][object_id][user={$object->user}]")->to_assoc('id','id');
                if(count($ban_eq_counts) >= $system_ban_limit->score){
                    $eq_banned = O('eq_banned',['user'=>$credit_record->user,'object_id'=>0,'object_name'=>'']);
                    $eq_banned->user = $object->user;
                    $lab = Q("$object->user lab")->current();
                    $eq_banned->lab = $lab;
                    $eq_banned->atime  = 0;
                    $eq_banned->reason = I18N::T('credit',"用户同时存在于{$system_ban_limit->score}台仪器黑名单内时，自动被加入系统黑名单");
                    $eq_banned->save();
                }
             }
             //发消息
             $key = 'credit.eq_ban';
             Notification::send($key, $eq_banned->user, [
                 '%user' => Markup::encode_Q($eq_banned->user),
                 '%time' => Date::format(time(), 'Y/m/d H:i:s'),
                 '%reason' => I18N::T('credit','同时存在于多台仪器黑名单'),
                 '%scope' => "系统黑名单",
             ]);
        }

        if(!$object->object_name && $object->user->id && $new_data['banned_type'] != 'lab'){
            $lab_ban_measures = O('credit_measures', ['ref_no' => 'lab_ban']);
            $lab_ban_limit    = O('credit_limit', ['measures' => $lab_ban_measures, 'is_custom' => 0]);
            if($lab_ban_limit->enable && $lab_ban_limit->score){
                $lab = Q("$object->user lab")->current();
                if(Q("eq_banned[!object_name][lab={$lab}]")->total_count() >= $lab_ban_limit->score){
                    foreach(Q("{$lab} user") as $user){
                        $eq_banned = O('eq_banned',['user'=>$user,'object_id'=>0,'object_name'=>'']);
                        if($eq_banned->id && $eq_banned->is_from_credit_limit) continue;
                        $eq_banned->user = $user;
                        $eq_banned->lab = $lab;
                        $eq_banned->is_from_credit_limit = 1;
                        $eq_banned->atime  = 0;
                        $eq_banned->reason = I18N::T('credit',"同课题组用户出现{$lab_ban_limit->score}人及以上被加入系统黑名单时，全组自动被加入系统黑名单");
                        $eq_banned->save();
                        //发消息
                        $key = 'credit.lab_ban';
                        Notification::send($key, $eq_banned->user, [
                            '%user' => Markup::encode_Q($eq_banned->user),
                            '%time' => Date::format(time(), 'Y/m/d H:i:s'),
                        ]);
                    }
                }
             }
        }

	}

    static function on_eq_banned_model_deleted($e, $object)
    {
        $me = L('ME');
        if($object->is_from_credit_limit && $object->user->id && $object->object_name == 'equipment'){
            $sql = "update credit_record set `status` = 1 where user_id = {$object->user->id} and equipment_id = {$object->object->id} and status = 0";
            Database::factory()->query($sql);
        }
    }


}
