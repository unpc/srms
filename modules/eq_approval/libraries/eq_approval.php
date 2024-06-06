<?php

class EQ_Approval {

	static function people_index_ready($e, $controller, $method, $params) {
		if ($params[1] && $params[1] == 'approval') {
			$user = O('user', $params[0]);
			$user->viewVoucherLastTime = Date::time() - 1;
			$user->save();
		}
	}

	static function lab_edit_tab($e, $tabs) {
		$lab = $tabs->lab;
		if (L('ME')->is_allowed_to('修改审核', $lab)) {
			Event::bind('lab.edit.content', "EQ_Approval::lab_edit_content_approval", 0, 'approval');
			$tabs->add_tab('approval', [
			   'url' => $lab->url('approval', NULL, NULL, 'edit'),
			   'title' => I18N::T('eq_approval', '预约/送样审核')
			]);
		}
	}

	static function lab_view_tab($e, $tabs) {
		$me = L('ME');
		$lab = $tabs->lab;
		if ($me->is_allowed_to('查看审核', $lab)) {
			Event::bind('lab.view.content', "EQ_Approval::lab_view_content_approval", 0, 'approval');
			$num = 0;
			if ($lab->owner->id == $me->id) {
				$status = EQ_Voucher_Model::PENDDING;
				$num = Q("eq_voucher[lab={$lab}][status={$status}][hide=0]")->total_count();
			}
			$tabs->add_tab('approval', [
			   'url' => $lab->url('approval', NULL, NULL, 'view'),
			   'title' => I18N::T('eq_approval', '预约审核'),
			   'number' => $num
			]);
		}
	}

	static function lab_edit_content_approval($e, $tabs) {

		$lab = $tabs->lab;
		$form = Form::filter(Input::form());
		$me = L('ME');

		if (!$me->is_allowed_to('修改审核', $lab)) URI::redirect('error/401');

		if ($form['submit']) {
			$approval_types = $form['user_approval'];
			$approval_value = $form['user_value'];

			foreach ($approval_value as $id => $val) {
				if ( $val <  0 ) {
					$form->set_error("user_value\[".$id."\]", I18N::T('eq_approval', '请输入大于或等于0的额度!'));
				}
			}

			if ($form->no_error) {
				foreach ($approval_types as $id => $val) {
					$user = O('user', $id);
					if (!$user->id) continue;
					$quota = O('eq_quota', ['user'=>$user]);
					$quota->type = $val;
					$quota->user = $user;
					if ($val == EQ_Quota_Model::APPROVAL_QUOTA) {
						$quota->value = $approval_value[$id] ?: 0;
					}
					else {
						$quota->value = 0;
					}
					$quota->save();
				}
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_approval', '审核设置更新成功!'));
			}
		}


		$users = Q("$lab user[!hidden][atime]:sort(name_abbr)");
		$start = (int) $form['st'];
		$per_page = 15;
		$start = $start - ($start % $per_page);
		$pagination = Lab::pagination($users, $start, $per_page);

		$tabs->content = V('eq_approval:labs/edit', [
				'lab' => $tabs->lab,
				'users' => $users,
				'pagination' => $pagination,
				'form' => $form
			]);
	}

	static function people_index_tab($e, $tabs) {
		$me = L('ME');
		if ($me->id == $tabs->user->id) {
			Event::bind('profile.view.content', "EQ_Approval::people_content_approval", 0, 'approval');
			$time = $me->viewVoucherLastTime ?: 0;
			$status = join(', ',[EQ_Voucher_Model::APPROVED, EQ_Voucher_Model::REJECTED]);
			$tabs->add_tab('approval', [
			   'url' => $tabs->user->url('approval', NULL, NULL, 'view'),
			   'title' => I18N::T('eq_approval', '我的预约凭证'),
			   'weight' => 200,
			   'number' => Q("eq_voucher[user={$me}][mtime>={$time}][status={$status}]")->total_count()
			]);
		}
	}

	static function people_content_approval($e, $tabs)
    {
        Event::bind('profile.approval.secondary.view.tabs', 'EQ_Approval::_profile_approval_secondary_tabs', 0, EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::APPROVED]);
        Event::bind('profile.approval.secondary.view.tabs', 'EQ_Approval::_profile_approval_secondary_tabs', 0, EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::PENDDING]);
        Event::bind('profile.approval.secondary.view.tabs', 'EQ_Approval::_profile_approval_secondary_tabs', 0, EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::REJECTED]);

        $tabs->content = V('equipments:profile/content');

        $params = Config::get('system.controller_params');
        $status = isset($params[2]) ? $params[2] : EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::APPROVED];

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->set('status', $status)
            ->tab_event('profile.approval.secondary.view.tabs')
            ->content_event('profile.approval.secondary.view.content')
            ->select($status);
	}

    public static function _profile_approval_secondary_tabs($e, $tabs)
    {
        Event::bind('profile.approval.secondary.view.content', 'EQ_Approval::_profile_approval_secondary_content', 0, $tabs->status);

        $user = $tabs->user;

        foreach (EQ_Voucher_Model::$STATUS_STYLE as $status => $style) {
            $tabs->add_tab($style, [
                'url'=> $user->url('approval.'.$style),
                'title'=> I18N::T('eq_approval', EQ_Voucher_Model::$STATUS[$status].' (%count)', [
                    '%count'=> Q("eq_voucher[user={$user}][status={$status}]")->total_count(),
                ]),
            ]);
        }
    }

    public static function _profile_approval_secondary_content($e, $tabs)
    {
        $user = $tabs->user;
        $status = array_flip(EQ_Voucher_Model::$STATUS_STYLE)[$tabs->status];
        $me = L('ME');

        if ($me->id != $user->id) URI::redirect('error/401');

        $form = Lab::form(function(&$old_form, &$form) {
            if ( isset($form['date_filter']) ) {
                if ( !$form['dtstart_check'] ) {
                    unset($old_form['dtstart_check']);
                }
                else {
                    $dtstart = getdate($form['dtstart']);
                    $form['dtstart'] = mktime(0, 0, 0, $dtstart['mon'], $dtstart['mday'], $dtstart['year']);
                }

                if ( !$form['dtend_check'] ) {
                    unset($old_form['dtend_check']);
                }
                else {
                    $dtend = getdate($form['dtend']);
                    $form['dtend'] = mktime(23, 59, 59, $dtend['mon'], $dtend['mday'], $dtend['year']);
                }
                unset($form['date_filter']);
            }
        });

        $selector = "eq_voucher[user={$user}][status={$status}]";

        $pre_selector = [];

        if ($form['equipment_name']) {
            $ename = Q::quote($form['equipment_name']);
            $pre_selector['ename'] = "equipment[name*={$ename}]";
        }

        if ($form['dtstart_check']) {
            $dtstart = $form['dtstart'];
            $selector .= "[ctime>={$dtstart}]";
        }

        if ($form['dtend_check']) {
            $dtend = $form['dtend'];
            $selector .= "[ctime<={$dtend}]";
        }

        /*

        if ($form['used_time']) {
            $used_time = Q::quote($form['used_time']);
            $selector .= "[used_time={$used_time}]";
        }

        if ($form['samples']) {
            $samples = (int)$form['samples'];
            $selector .= "[samples=$samples]";
        }

        */

        if (isset($form['type']) && $form['type'] >= 0) {
            $type = (int)$form['type'];
            $selector .= "[type={$type}]";
        }

        if (isset($form['use_status']) && $form['use_status'] >= 0) {
            $use_status = (int)$form['use_status'];
            $selector .= "[use_status={$use_status}]";
        }

        if (count($pre_selector)) {
            $selector = '(' . join(', ', $pre_selector) . ') ' . $selector;
        }


        $selector .= ":sort(use_status A, ctime D)";

        $vouchers = Q($selector);

        $pagination = Lab::pagination($vouchers, (int)$form['st'], 10);

        $panel_buttons = new ArrayIterator;
        $panel_buttons[] = [
            'text'  => I18N::T('eq_approval', '添加预约凭证'),
            'extra' => 'q-object="add_reserv_voucher" q-event="click" q-src="' . URI::url('!eq_approval/voucher') .
                '" q-static="' . H(['user_id'=>$user->id]) .
                '" class="button button_add"',
        ];

        $tabs->content = V('eq_approval:user/list', [
            'panel_buttons' => $panel_buttons,
            'user' => $user,
            'vouchers' => $vouchers,
            'pagination' => $pagination,
            'form' => $form
        ]);
    }

	static function lab_view_content_approval($e, $tabs) {
        Event::bind('lab.approval.secondary.view.tabs', 'EQ_Approval::_lab_approval_secondary_tabs', 0, EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::APPROVED]);
        Event::bind('lab.approval.secondary.view.tabs', 'EQ_Approval::_lab_approval_secondary_tabs', 0, EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::PENDDING]);
        Event::bind('lab.approval.secondary.view.tabs', 'EQ_Approval::_lab_approval_secondary_tabs', 0, EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::REJECTED]);

        $tabs->content = V('equipments:profile/content');

        $params = Config::get('system.controller_params');
        $status = isset($params[2]) ? $params[2] : EQ_Voucher_Model::$STATUS_STYLE[EQ_Voucher_Model::APPROVED];

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('lab', $tabs->lab)
            ->set('status', $status)
            ->tab_event('lab.approval.secondary.view.tabs')
            ->content_event('lab.approval.secondary.view.content')
            ->select($status);
	}

    public static function _lab_approval_secondary_tabs($e, $tabs)
    {
        Event::bind('lab.approval.secondary.view.content', 'EQ_Approval::_lab_approval_secondary_content', 0, $tabs->status);

        $lab = $tabs->lab;

        foreach (EQ_Voucher_Model::$STATUS_STYLE as $status => $style) {
            $tabs->add_tab($style, [
                'url'=> $lab->url('approval.'.$style),
                'title'=> I18N::T('eq_approval', EQ_Voucher_Model::$STATUS[$status]),
            ]);
        }
    }

    public static function _lab_approval_secondary_content($e, $tabs)
    {
        $lab = $tabs->lab;
        $status = array_flip(EQ_Voucher_Model::$STATUS_STYLE)[$tabs->status];
        $me = L('ME');
        if ( !$me->is_allowed_to('查看审核', $lab) ) URI::redirect('error/401');

        $form = Lab::form(function(&$old_form, &$form) {
            if ( isset($form['date_filter']) ) {
                if ( !$form['dtstart_check'] ) {
                    unset($old_form['dtstart_check']);
                }
                else {
                    $dtstart = getdate($form['dtstart']);
                    $form['dtstart'] = mktime(0, 0, 0, $dtstart['mon'], $dtstart['mday'], $dtstart['year']);
                }

                if ( !$form['dtend_check'] ) {
                    unset($old_form['dtend_check']);
                }
                else {
                    $dtend = getdate($form['dtend']);
                    $form['dtend'] = mktime(23, 59, 59, $dtend['mon'], $dtend['mday'], $dtend['year']);
                }
                unset($form['date_filter']);
            }
        });

        $selector = "eq_voucher[lab={$lab}][status=$status][hide=0]";

        $pre_selector = [];

        if ($form['equipment_name']) {
            $ename = Q::quote($form['equipment_name']);
            $pre_selector['ename'] = "equipment[name*={$ename}]";
        }

        if ($form['dtstart_check']) {
            $dtstart = $form['dtstart'];
            $selector .= "[ctime>={$dtstart}]";
        }

        if ($form['dtend_check']) {
            $dtend = $form['dtend'];
            $selector .= "[ctime<={$dtend}]";
        }


        /*

        if ($form['used_time']) {
            $used_time = Q::quote($form['used_time']);
            $selector .= "[used_time={$used_time}]";
        }
        */

        if (isset($form['type']) && $form['type'] >= 0) {
            $type = (int)$form['type'];
            $selector .= "[type={$type}]";
        }

        if (count($pre_selector)) {
            $selector = '(' . join(', ', $pre_selector) . ') ' . $selector;
        }

        $selector .= ":sort(status A, ctime D)";

        $vouchers = Q($selector);

        $pagination = Lab::pagination($vouchers, (int)$form['st'], 15);

        $panel_buttons = [];
        $panel_buttons[] = [
            'text'  => I18N::T('eq_approval', '批量处理'),
            'extra' => 'class="button button_archive batch_process"',
        ];

        $tabs->content = V('eq_approval:labs/list', [
            'panel_buttons' => $panel_buttons,
            'vouchers' => $vouchers,
            'pagination' => $pagination,
            'form' => $form
        ]);
    }

	static function fill_voucher_labels($vouchers=[]) {
		$ss = [];
		foreach ($vouchers as $v) {
			if ($v->type == EQ_Voucher_Model::RESERV) {
				$count = sprintf('%.2f', $v->used_time / 60) . I18N::T('eq_approval', '小时');
			}
			else {
				$count = $v->samples . I18N::T('eq_approval', '个');
			}
			$ss[$v->id] .= '&#160;&#160;' . $count;
			$ss[$v->id] .= '&#160;&#160;&#160;&#160;' . EQ_Voucher_Model::$TYPES[$v->type];
			$ss[$v->id] .= '&#160;&#160;&#160;&#160;' . Number::currency($v->auto_amount);
			$ss[$v->id] .= '&#160;&#160;&#160;&#160;' . $v->equipment->name;
		}
		return $ss;
	}

}
