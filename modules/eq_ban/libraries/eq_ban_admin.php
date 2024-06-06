<?php
class EQ_Ban_Admin
{
    public static function eq_ban_add_logs($e, $type)
    {
        if ($type == 'notification.eq_banned') {
            $e->return_value = '加黑名单';
            return false;
        }
    }

    public static function eq_ban_add_notification($e, $options)
    {
        $configs = [
            'notification.eq_ban.eq_banned',
            'notification.eq_ban.eq_banned.eq',
            'notification.eq_ban.eq_banned.tag'
        ];
        $e->return_value = array_merge((array)$e->return_value, $configs);
    }

    public static function setup()
    {
        if (!Module::is_installed('credit')) {
            if (L('ME')->access('管理所有内容') || L('ME')->access('添加/修改所有机构的仪器')) {
                Event::bind('admin.equipment.tab', 'EQ_Ban_Admin::_secondary_tab');
                Event::bind('admin.equipment.content', 'EQ_Ban_Admin::_secondary_content', 0, 'ban');
            }
        }
    }


    public static function _secondary_tab($e, $tabs)
    {
        Event::bind('admin.equipment.content', 'EQ_Ban_Admin::_secondary_content', 0, 'reserv');

        $tabs
            ->add_tab('ban', [
                'url'=>URI::url('admin/equipment.ban'),
                'title'=> I18N::T('eq_ban', '黑名单设置'),
                'weight'=>1,
            ]);
    }

    public static function _secondary_content($e, $tabs)
    {
        if (Input::form('submit')) {
            $form = Form::filter(Input::form());
            if ($form['max_allowed_overtime_times'] < 0) {
                $form->set_error('max_allowed_overtime_times', I18N::T('eq_ban', '用户超时使用设备的最大限度必须大于等于零!'));
            }
            if ($form['max_allowed_leave_early_times'] < 0) {
                $form->set_error('max_allowed_leave_early_times', I18N::T('eq_ban', '自动加入黑名单的早退次数上限必须大于等于零!'));
            }
            if ($form['max_allowed_miss_times'] < 0) {
                $form->set_error('max_allowed_miss_times', I18N::T('eq_ban', '自动加入黑名单的爽约次数上限必须大于等于零!'));
            }
            if ($form['max_allowed_late_times'] < 0) {
                $form->set_error('max_allowed_late_times', I18N::T('eq_ban', '自动加入黑名单的迟到次数上限必须大于等于零!'));
            }
            if ($form['max_allowed_violate_times'] < 0) {
                $form->set_error('max_allowed_violate_times', I18N::T('eq_ban', '自动加入黑名单的违规行为上限必须大于等于零!'));
            }
            if ($form['max_allowed_total_count_times'] < 0) {
                $form->set_error('max_allowed_total_count_times', I18N::T('eq_ban', '自动加入黑名单的总违规次数上限必须大于等于零!'));
            }
            /*
             * 2020年03月24日xian.zhou
             * 通知阈值form校验
             */
            if ($form['is_late_times_exceed'] === "on") {
                if ($form['late_times_exceed_preset'] < 0) {
                    $form->set_error('late_times_exceed_preset', I18N::T('eq_ban', '迟到次数通知阈值必须大于等于零!'));
                }
                if ($form['late_times_exceed_preset'] >= $form['max_allowed_late_times']) {
                    $form->set_error('late_times_exceed_preset', I18N::T('eq_ban', '迟到次数通知阈值必须小于自动加入黑名单的迟到次数上限!'));
                }
            }
            if ($form['is_leave_early_times_exceed'] === "on") {
                if ($form['leave_early_times_exceed_preset'] < 0) {
                    $form->set_error('leave_early_times_exceed_preset', I18N::T('eq_ban', '早退次数通知阈值必须大于等于零!'));
                }
                if ($form['leave_early_times_exceed_preset'] >= $form['max_allowed_leave_early_times']) {
                    $form->set_error('leave_early_times_exceed_preset', I18N::T('eq_ban', '早退次数通知阈值必须小于自动加入黑名单的早退次数上限!'));
                }
            }
            if ($form['is_miss_times_exceed'] === "on") {
                if ($form['miss_times_exceed_preset'] < 0) {
                    $form->set_error('miss_times_exceed_preset', I18N::T('eq_ban', '爽约次数通知阈值必须大于等于零!'));
                }
                if ($form['miss_times_exceed_preset'] >= $form['max_allowed_miss_times']) {
                    $form->set_error('miss_times_exceed_preset', I18N::T('eq_ban', '爽约次数通知阈值必须小于自动加入黑名单的爽约次数上限!'));
                }
            }
            if ($form['is_overtime_times_exceed'] === "on") {
                if ($form['overtime_times_exceed_preset'] < 0) {
                    $form->set_error('overtime_times_exceed_preset', I18N::T('eq_ban', '超时次数通知阈值必须大于等于零!'));
                }
                if ($form['overtime_times_exceed_preset'] >= $form['max_allowed_overtime_times']) {
                    $form->set_error('overtime_times_exceed_preset', I18N::T('eq_ban', '超时次数通知阈值必须小于自动加入黑名单的超时次数上限!'));
                }
            }
            if ($form['is_violate_times_exceed'] === "on") {
                if ($form['violate_times_exceed_preset'] < 0) {
                    $form->set_error('violate_times_exceed_preset', I18N::T('eq_ban', '违规行为次数通知阈值必须大于等于零!'));
                }
                if ($form['violate_times_exceed_preset'] >= $form['max_allowed_violate_times']) {
                    $form->set_error('violate_times_exceed_preset', I18N::T('eq_ban', '违规行为次数通知阈值必须小于自动加入黑名单的违规行为次数上限!'));
                }
            }
            if ($form['is_total_count_times_exceed'] === "on") {
                if ($form['total_count_times_exceed_preset'] < 0) {
                    $form->set_error('total_count_times_exceed_preset', I18N::T('eq_ban', '违规总次数通知阈值必须大于等于零!'));
                }
                if ($form['total_count_times_exceed_preset'] >= $form['max_allowed_total_count_times']) {
                    $form->set_error('total_count_times_exceed_preset', I18N::T('eq_ban', '违规总次数通知阈值必须小于自动加入黑名单的违规总次数上限!'));
                }
            }
            /**
             * 通知阈值form校验 done
             */
            if ($form->no_error) {
                Lab::set('equipment.max_allowed_miss_times', (int) $form['max_allowed_miss_times']);
                Lab::set('equipment.max_allowed_leave_early_times', (int) $form['max_allowed_leave_early_times']);
                Lab::set('equipment.max_allowed_overtime_times', (int) $form['max_allowed_overtime_times']);
                Lab::set('equipment.max_allowed_late_times', (int) $form['max_allowed_late_times']);
                Lab::set('equipment.max_allowed_violate_times', (int) $form['max_allowed_violate_times']);
                Lab::set('equipment.max_allowed_total_count_times', (int) $form['max_allowed_total_count_times']);

                Lab::set('equipment.is_late_times_exceed', $form['is_late_times_exceed']);
                Lab::set('equipment.is_leave_early_times_exceed', $form['is_leave_early_times_exceed']);
                Lab::set('equipment.is_miss_times_exceed', $form['is_miss_times_exceed']);
                Lab::set('equipment.is_overtime_times_exceed', $form['is_overtime_times_exceed']);
                Lab::set('equipment.is_violate_times_exceed', $form['is_violate_times_exceed']);
                Lab::set('equipment.is_total_count_times_exceed', $form['is_total_count_times_exceed']);

                Lab::set('equipment.late_times_exceed_preset', (int) $form['late_times_exceed_preset']);
                Lab::set('equipment.leave_early_times_exceed_preset', (int) $form['leave_early_times_exceed_preset']);
                Lab::set('equipment.miss_times_exceed_preset', (int) $form['miss_times_exceed_preset']);
                Lab::set('equipment.overtime_times_exceed_preset', (int) $form['overtime_times_exceed_preset']);
                Lab::set('equipment.violate_times_exceed_preset', (int) $form['violate_times_exceed_preset']);
                Lab::set('equipment.total_count_times_exceed_preset', (int) $form['total_count_times_exceed_preset']);


                Lab::set('equipment.max_allowed_miss_times', null, '*');
                Lab::set('equipment.max_allowed_leave_early_times', null, '*');
                Lab::set('equipment.max_allowed_overtime_times', null, '*');
                Lab::set('equipment.max_allowed_late_times', null, '*');
                Lab::set('equipment.max_allowed_violate_times', null, '*');
                Lab::set('equipment.max_allowed_total_count_times', null, '*');

                Lab::set('equipment.is_late_times_exceed', null, '*');
                Lab::set('equipment.is_leave_early_times_exceed', null, '*');
                Lab::set('equipment.is_miss_times_exceed', null, '*');
                Lab::set('equipment.is_overtime_times_exceed', null, '*');
                Lab::set('equipment.is_violate_times_exceed', null, '*');
                Lab::set('equipment.is_total_count_times_exceed', null, '*');

                Lab::set('equipment.late_times_exceed_preset', null, '*');
                Lab::set('equipment.leave_early_times_exceed_preset', null, '*');
                Lab::set('equipment.miss_times_exceed_preset', null, '*');
                Lab::set('equipment.overtime_times_exceed_preset', null, '*');
                Lab::set('equipment.violate_times_exceed_preset', null, '*');
                Lab::set('equipment.total_count_times_exceed_preset', null, '*');

                Lab::set('eq.max_allowed_miss_times', null, '*');
                Lab::set('eq.max_allowed_leave_early_times', null, '*');
                Lab::set('eq.max_allowed_overtime_times', null, '*');
                Lab::set('eq.max_allowed_late_times', null, '*');
                Lab::set('eq.max_allowed_violate_times', null, '*');
                Lab::set('eq.max_allowed_total_count_times', null, '*');
                $specific_tags = $form['specific_tags'];
                $eq_tags = $form['eq_tags'];

                if ($specific_tags) {
                    foreach ($specific_tags as $i => $tags) {
                        $tags = @json_decode($tags, true);
                        if ($tags) {
                            foreach ($tags as $tag) {
                                Lab::set('equipment.max_allowed_miss_times', (int) $form['specific_max_allowed_miss_times'][$i], $tag);
                                Lab::set('equipment.max_allowed_leave_early_times', (int) $form['specific_max_allowed_leave_early_times'][$i], $tag);
                                Lab::set('equipment.max_allowed_overtime_times', (int) $form['specific_max_allowed_overtime_times'][$i], $tag);
                                Lab::set('equipment.max_allowed_late_times', (int) $form['specific_max_allowed_late_times'][$i], $tag);
                                Lab::set('equipment.max_allowed_violate_times', (int) $form['specific_max_allowed_violate_times'][$i], $tag);
                                Lab::set('equipment.max_allowed_total_count_times', (int) $form['specific_max_allowed_total_count_times'][$i], $tag);
                                /*
                                 * 2020年03月24日xian.zhou
                                 */
                                if ($form['specific_is_late_times_exceed'][$i] === "on") {
                                    if ($form['specific_late_times_exceed_preset'][$i] < 0) {
                                        $form->set_error("specific_is_late_times_exceed[$i]", I18N::T('eq_ban', '迟到次数通知阈值必须大于等于零!'));
                                    } elseif ($form['specific_late_times_exceed_preset'][$i] >= $form['specific_max_allowed_late_times'][$i]) {
                                        $form->set_error("specific_is_late_times_exceed[$i]", I18N::T('eq_ban', '迟到次数通知阈值必须小于自动加入黑名单的迟到次数上限!'));
                                    } else {
                                        Lab::set('equipment.is_late_times_exceed', $form['specific_is_late_times_exceed'][$i], $tag);
                                        Lab::set('equipment.late_times_exceed_preset', (int) $form['specific_late_times_exceed_preset'][$i], $tag);
                                    }
                                }
                                if ($form['specific_is_leave_early_times_exceed'][$i] === "on") {
                                    if ($form['specific_leave_early_times_exceed_preset'][$i] < 0) {
                                        $form->set_error("specific_is_leave_early_times_exceed[$i]", I18N::T('eq_ban', '早退次数通知阈值必须大于等于零!'));
                                    } elseif ($form['specific_leave_early_times_exceed_preset'][$i] >= $form['specific_max_allowed_leave_early_times'][$i]) {
                                        $form->set_error("specific_is_leave_early_times_exceed[$i]", I18N::T('eq_ban', '早退次数通知阈值必须小于自动加入黑名单的早退次数上限!'));
                                    } else {
                                        Lab::set('equipment.is_leave_early_times_exceed', $form['specific_is_leave_early_times_exceed'][$i], $tag);
                                        Lab::set('equipment.leave_early_times_exceed_preset', (int) $form['specific_leave_early_times_exceed_preset'][$i], $tag);
                                    }
                                }
                                if ($form['specific_is_miss_times_exceed'][$i] === "on") {
                                    if ($form['specific_miss_times_exceed_preset'][$i] < 0) {
                                        $form->set_error("specific_is_miss_times_exceed[$i]", I18N::T('eq_ban', '爽约次数通知阈值必须大于等于零!'));
                                    } elseif ($form['specific_miss_times_exceed_preset'][$i] >= $form['specific_max_allowed_miss_times'][$i]) {
                                        $form->set_error("specific_is_miss_times_exceed[$i]", I18N::T('eq_ban', '爽约次数通知阈值必须小于自动加入黑名单的爽约次数上限!'));
                                    } else {
                                        Lab::set('equipment.is_miss_times_exceed', $form['specific_is_miss_times_exceed'][$i], $tag);
                                        Lab::set('equipment.miss_times_exceed_preset', (int) $form['specific_miss_times_exceed_preset'][$i], $tag);
                                    }
                                }
                                if ($form['specific_is_overtime_times_exceed'][$i] === "on") {
                                    if ($form['specific_overtime_times_exceed_preset'][$i] < 0) {
                                        $form->set_error("specific_is_overtime_times_exceed[$i]", I18N::T('eq_ban', '超时次数通知阈值必须大于等于零!'));
                                    } elseif ($form['specific_overtime_times_exceed_preset'][$i] >= $form['specific_max_allowed_overtime_times'][$i]) {
                                        $form->set_error("specific_is_overtime_times_exceed[$i]", I18N::T('eq_ban', '超时次数通知阈值必须小于自动加入黑名单的超时次数上限!'));
                                    } else {
                                        Lab::set('equipment.is_overtime_times_exceed', $form['specific_is_overtime_times_exceed'][$i], $tag);
                                        Lab::set('equipment.overtime_times_exceed_preset', (int) $form['specific_overtime_times_exceed_preset'][$i], $tag);
                                    }
                                }

                                if ($form['specific_is_violate_times_exceed'][$i] === "on") {
                                    if ($form['specific_violate_times_exceed_preset'][$i] < 0) {
                                        $form->set_error("specific_is_violate_times_exceed[$i]", I18N::T('eq_ban', '违规行为次数通知阈值必须大于等于零!'));
                                    } elseif ($form['specific_violate_times_exceed_preset'][$i] >= $form['specific_max_allowed_violate_times'][$i]) {
                                        $form->set_error("specific_is_violate_times_exceed[$i]", I18N::T('eq_ban', '违规行为次数通知阈值必须小于自动加入黑名单的违规行为次数上限!'));
                                    } else {
                                        Lab::set('equipment.is_violate_times_exceed', $form['specific_is_violate_times_exceed'][$i], $tag);
                                        Lab::set('equipment.violate_times_exceed_preset', (int) $form['specific_violate_times_exceed_preset'][$i], $tag);
                                    }
                                }

                                if ($form['specific_is_total_count_times_exceed'][$i] === "on") {
                                    if ($form['specific_total_count_times_exceed_preset'][$i] < 0) {
                                        $form->set_error("specific_is_total_count_times_exceed[$i]", I18N::T('eq_ban', '违规总次数通知阈值必须大于等于零!'));
                                    } elseif ($form['specific_total_count_times_exceed_preset'][$i] >= $form['specific_max_allowed_total_count_times'][$i]) {
                                        $form->set_error("specific_is_total_count_times_exceed[$i]", I18N::T('eq_ban', '违规总次数通知阈值必须小于自动加入黑名单的违规总次数上限!'));
                                    } else {
                                        Lab::set('equipment.is_total_count_times_exceed', $form['specific_is_total_count_times_exceed'][$i], $tag);
                                        Lab::set('equipment.total_count_times_exceed_preset', (int) $form['specific_total_count_times_exceed_preset'][$i], $tag);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($eq_tags) {
                    foreach ($eq_tags as $i => $tags) {
                        $tags = @json_decode($tags, true);
                        if ($tags) {
                            foreach ($tags as $tag) {
                                Lab::set('eq.max_allowed_miss_times', (int) $form['eq_max_allowed_miss_times'][$i], $tag);
                                Lab::set('eq.max_allowed_leave_early_times', (int) $form['eq_max_allowed_leave_early_times'][$i], $tag);
                                Lab::set('eq.max_allowed_overtime_times', (int) $form['eq_max_allowed_overtime_times'][$i], $tag);
                                Lab::set('eq.max_allowed_late_times', (int) $form['eq_max_allowed_late_times'][$i], $tag);
                                Lab::set('eq.max_allowed_violate_times', (int) $form['eq_max_allowed_violate_times'][$i], $tag);
                                Lab::set('eq.max_allowed_total_count_times', (int) $form['eq_max_allowed_total_count_times'][$i], $tag);
                            }
                        }
                    }
                }
                /* 记录日志 */
                Log::add(strtr('[eq_ban] %user_name[%user_name]修改了系统设置中的预约设置', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '信息修改成功！'));
            }
        }

        $tabs->content = V('eq_ban:admin/reserv', ['form'=>$form])
            ->set('max_allowed_miss_times', Lab::get('equipment.max_allowed_miss_times', Config::get('equipment.max_allowed_miss_times'), '@'))
            ->set('max_allowed_leave_early_times', Lab::get('equipment.max_allowed_leave_early_times', Config::get('equipment.max_allowed_leave_early_times'), '@'))
            ->set('max_allowed_overtime_times', Lab::get('equipment.max_allowed_overtime_times', Config::get('equipment.max_allowed_overtime_times'), '@'))
            ->set('max_allowed_late_times', Lab::get('equipment.max_allowed_late_times', Config::get('equipment.max_allowed_late_times'), '@'))
            ->set('max_allowed_violate_times', Lab::get('equipment.max_allowed_violate_times', Config::get('equipment.max_allowed_violate_times'), '@'))
            ->set('max_allowed_total_count_times', Lab::get('equipment.max_allowed_total_count_times', Config::get('equipment.max_allowed_total_count_times'), '@'))

            ->set('is_late_times_exceed', Lab::get('equipment.is_late_times_exceed', Config::get('equipment.is_late_times_exceed'), '@'))
            ->set('is_leave_early_times_exceed', Lab::get('equipment.is_leave_early_times_exceed', Config::get('equipment.is_leave_early_times_exceed'), '@'))
            ->set('is_miss_times_exceed', Lab::get('equipment.is_miss_times_exceed', Config::get('equipment.is_miss_times_exceed'), '@'))
            ->set('is_overtime_times_exceed', Lab::get('equipment.is_overtime_times_exceed', Config::get('equipment.is_overtime_times_exceed'), '@'))
            ->set('is_violate_times_exceed', Lab::get('equipment.is_violate_times_exceed', Config::get('equipment.is_violate_times_exceed'), '@'))
            ->set('is_total_count_times_exceed', Lab::get('equipment.is_total_count_times_exceed', Config::get('equipment.is_total_count_times_exceed'), '@'))

            ->set('late_times_exceed_preset', Lab::get('equipment.late_times_exceed_preset', Config::get('equipment.late_times_exceed_preset'), '@'))
            ->set('leave_early_times_exceed_preset', Lab::get('equipment.leave_early_times_exceed_preset', Config::get('equipment.leave_early_times_exceed_preset'), '@'))
            ->set('miss_times_exceed_preset', Lab::get('equipment.miss_times_exceed_preset', Config::get('equipment.miss_times_exceed_preset'), '@'))
            ->set('overtime_times_exceed_preset', Lab::get('equipment.overtime_times_exceed_preset', Config::get('equipment.overtime_times_exceed_preset'), '@'))
            ->set('violate_times_exceed_preset', Lab::get('equipment.violate_times_exceed_preset', Config::get('equipment.violate_times_exceed_preset'), '@'))
            ->set('total_count_times_exceed_preset', Lab::get('equipment.total_count_times_exceed_preset', Config::get('equipment.total_count_times_exceed_preset'), '@'));
    }
}
