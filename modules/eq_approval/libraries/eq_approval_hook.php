<?php

class EQ_Approval_Hook {

	static function prerender_add_sample_form($e, $form, $equipment) {
		$me = L('ME');
		if ($me->isShowVoucher($equipment)) {
			$e->return_value .= (string)V('eq_approval:sample/form', ['form' => $form, 'equipment' => $equipment]);
		}
		if ($me->isMayBeNeedVoucher($equipment)) {
			$e->return_value .= (string)V('eq_approval:sample/script', ['form' => $form]);
		}
		return TRUE;
	}

	static function prerender_edit_sample_form($e, $sample, $form) {
		$me = L('ME');
		$equipment = $sample->equipment;
		if ($me->isShowVoucher($equipment)) {
			$e->return_value .= (string)V('eq_approval:sample/editForm', [
				'form' => $form, 
				'sample' => $sample
			]);
		}
		return TRUE;
	}

	static function post_add_sample_form_validate($e, $equipment, $oname, $form) {
		if ($oname != 'eq_sample') return TRUE;
		if ($GLOBALS['preload']['people.multi_lab']) return TRUE;
		$me = L('ME');
		if (!$me->isShowVoucher($equipment)) return TRUE;


		$sample = O('eq_sample', $form['id']);
		$sample->equipment = $equipment;
		$sample->count = (int)max($form['count'], 1);
		$sample->dtsubmit = $form['dtsubmit'];
		$sample->description = $form['description'];
		$sample->project = O('lab_project', $form['project']);
		$sample->status = EQ_Sample_Model::STATUS_APPLIED;
		$sample->sender = $me;
		$sample->lab = Q("$me lab")->current();
		$sample->voucher = $form['voucher'];

		if ( $me->isMayBeNeedVoucher($equipment, $sample) ) {
			foreach ( Lab::messages(Lab::MESSAGE_ERROR) as $message) {
				$form->set_error('voucher', $message);
			}
		}
	}

	static function sample_form_post_submit($e, $sample, $form) {
		$me = L('ME');
		$equipment = $sample->equipment;

		$voucher = O('eq_voucher', $form['voucher']);
		$connect_voucher = Q("{$sample} eq_voucher:limit(1)")->current();

		if (!$me->isShowVoucher($equipment)) {
			if ($connect_voucher->id) {
				$user = $sample->sender;
				$owner = $connect_voucher->user;
				if ( $user->id != $owner->id ||
					 $sample->status == EQ_Sample_Model::STATUS_CANCELED || 
					 $sample->status == EQ_Sample_Model::STATUS_REJECTED ) {
					$sample->disconnect($connect_voucher);
					$connect_voucher->unused();
				}
			}
		}
		else {
			if ($voucher->id != $connect_voucher->id) {
				if ($connect_voucher->id) {
					$sample->disconnect($connect_voucher);
					$connect_voucher->unused();
				}
				if ($voucher->id) {
					$sample->connect($voucher);
					$voucher->used();
				}
			}
		}
		
		return TRUE;
	}

	static function before_eq_sample_delete($e, $sample) {
		$connect_voucher = Q("{$sample} eq_voucher:limit(1)")->current();
		if ($connect_voucher->id) {
			$sample->disconnect($connect_voucher);
			$connect_voucher->unused();
		}
	}



	// EQ_RESERV hook block
	static function eq_reserv_prerender_component($e, $view, $form) {

		$form = $e->return_value ? (array)$e->return_value : $form;
		$component = $view->component;
		$equipment = $view->component->calendar->parent;
		$me = L('ME');

		if (!$me->isShowVoucher($equipment)) {
			$e->return_value = $form;
			return TRUE;
		}

		$form['voucher'] = [
			'label' => I18N::T('eq_approval', '预约凭证'),
    		'path' => ['form' => 'eq_approval:reserv/calendar_form/'],
    		'component' => $component,
    		'weight' => 9999
    	];

    	$form['#categories']['reserv_info']['items'][] = 'voucher';

    	$e->return_value = $form;
    	return TRUE;
	}

	
	static function component_form_submit($e, $form, $component, $var = []) {
		$me = L('ME');
        $parent = $component->calendar->parent;
        try {
            if ($parent->name() != 'equipment') return TRUE;
        } catch (Exception $e) {
            return TRUE;
        }

        if (!$me->isShowVoucher($parent)) return TRUE;


        $settings = $parent->charge_template;
        $object = NULL;

        if ( $settings['record'] ) {
        	$record = O('eq_record');
        	$record->user = $me;
        	$record->equipment = $parent;
        	$record->dtstart = min($form['dtstart'], $form['dtend']);
	        $record->dtend = max($form['dtstart'], $form['dtend']);
	        $record->voucher = $form['voucher'];
	        $record->samples = max((int)$form['samples'], Config::get('eq_record.record_default_samples'));
	        $object = $record;
        }
        if ($settings['reserv']) {
        	$reserv = O('eq_reserv', ['component' => $component]);
	        $reserv->user = $me;
	        $reserv->equipment = $parent;
	        $reserv->dtstart = min($form['dtstart'], $form['dtend']);
	        $reserv->dtend = max($form['dtstart'], $form['dtend']);
	        $reserv->description = $form['description'];
	        $reserv->voucher = $form['voucher'];
	        $object = $reserv;
        }
		if ($settings['record'] && $settings['reserv']) {
			$record->reserv = $reserv;
			$object = $record;
		}

		if ( $me->isMayBeNeedVoucher($parent, $object) ) {
			foreach ( Lab::messages(Lab::MESSAGE_ERROR) as $message) {
				$form->set_error('voucher', $message);
			}
		}
	}

	static function component_form_post_submit($e, $component, $form) {
		$me = L('ME');
		$parent = $component->calendar->parent;
		if ($parent->name() != 'equipment') return TRUE;
		$reserv = O('eq_reserv', ['component' => $component]);
		if (!$reserv->id) return TRUE;

		$voucher = O('eq_voucher', $form['voucher']);
		$connect_voucher = Q("{$reserv} eq_voucher:limit(1)")->current();
		if (!$me->isShowVoucher($parent)) {
			if ($connect_voucher->id) {
				$user = $reserv->user;

				$owner = $connect_voucher->user;
				if ( $user->id != $owner->id ) {
					$reserv->disconnect($connect_voucher);
					$connect_voucher->unused();
				}
			}
		}
		else {
			if ($voucher->id != $connect_voucher->id) {
				if ($connect_voucher->id) {
					$reserv->disconnect($connect_voucher);
					$connect_voucher->unused();
				}
				if ($voucher->id) {
					$reserv->connect($voucher);
					$voucher->used();
				}
			}
		}
	}


	static function before_eq_reserv_delete($e, $reserv) {
		$connect_voucher = Q("{$reserv} eq_voucher:limit(1)")->current();
		if ($connect_voucher->id) {
			$reserv->disconnect($connect_voucher);
			$connect_voucher->unused();
		}
	}


	// user info hook
	static function short_picture_of_people($e, $user) {
		$me = L('ME');

		if ($me->isCouldSeeQuotaFromInfo($user)) {
			$items = (array)$e->return_value;
			$items[] = (string)V('eq_approval:people/short_info', ['user' => $user]);
			$e->return_value = $items;
		}

		return TRUE;
	}

	static function short_preview_picture_of_people($e, $user) {
		$me = L('ME');

		if ($me->isCouldSeeQuotaFromPreviewInfo($user)) {
			$items = (array)$e->return_value;
			$items[] = (string)V('eq_approval:people/short_info', ['user' => $user]);
			$e->return_value = $items;
		}
	
		return TRUE;
	}

	// user_model hook

	static function isShowVoucher($e, $user, $params) {
		$object = $params[0];

		if ($user->is_allowed_to('管理使用', $object)) {
			$e->return_value = FALSE;
			return FALSE;
		}

		$quota = O('eq_quota', ['user' => $user]);
		if (!$quota->id || $quota->type == EQ_Quota_Model::NO_APPROVAL) {
			$e->return_value = FALSE;
			return FALSE;
		}

		$e->return_value = TRUE;
		return FALSE;
	}

	static function isMayBeNeedVoucher($e, $user, $params) {
		$equipment = $params[0];
		$object = $params[1];


		if ($user->is_allowed_to('管理使用', $equipment)) {
			$e->return_value = FALSE;
			return FALSE;
		}

		$quota = O('eq_quota', ['user' => $user]);
		if (!$quota->id || $quota->type == EQ_Quota_Model::NO_APPROVAL) {
			$e->return_value = FALSE;
			return FALSE;
		}

		if ($object) {
			$charge = O('eq_charge');
		    $charge->source = $object;
			$lua = new EQ_Charge_LUA($charge);
			$result = $lua->run(['fee']);
			$fee = $result['fee'];

			if ( $fee > 0 && ( $quota->value == 0 || $fee > $quota->value ) ) {

				$voucher = O('eq_voucher', $object->voucher);
				if (!$voucher->id) {
					$type = $object->name() == 'eq_sample' ? EQ_Voucher_Model::SAMPLE : EQ_Voucher_Model::RESERV;
					$is_used = EQ_Voucher_Model::UN_USED;
					$approval = EQ_Voucher_Model::APPROVED;
					$vouchers = Q("eq_voucher[user={$user}][type={$type}][equipment={$equipment}][use_status={$is_used}][status={$approval}]");
					$cid = $object->id ? Q("{$object} eq_voucher:limit(1)")->current()->id : 0;
					if (!$cid && !$vouchers->total_count()) {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_approval', '您还没有符合条件的凭证, 您可以现在申请! %link', ['%link' => URI::anchor($user->url('approval'), I18N::T('eq_approval', '点击申请'), ' class="blue prevent_default"')]));
						$e->return_value = TRUE;
						return FALSE;
					}
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_approval', '请选择预约凭证进行使用!'));
					$e->return_value = TRUE;
					return FALSE;
				}

				if ($object->name() == 'eq_sample') {
					if ($object->count > $voucher->samples) {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_approval', '您的预约信息与凭证不符, 请检查预约信息是否填写正确!'));
						$e->return_value = TRUE;
						return FALSE;
					}
				}
				else if ($object->name() == 'eq_reserv' ||
						$object->name() == 'eq_record'){
					$time = $object->dtend - $object->dtstart + 1;
					if ($time > $voucher->used_time * 60 ) {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_approval', '您的预约信息与凭证不符, 请检查预约信息是否填写正确!'));
						$e->return_value = TRUE;
						return FALSE;
					}
				}

				if ($fee > $voucher->auto_amount) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_approval', '您的预约超过审核额度, 请申请预约审核凭证进行预约!'));
					$e->return_value = TRUE;
					return FALSE;
				}

			}
		}

		$e->return_value = TRUE;
		return FALSE;
	}

	static function getCanUseVoucher($e, $user, $params) {
		$status = EQ_Voucher_Model::APPROVED;
		$use_status = EQ_Voucher_Model::UN_USED;
		$selector = "eq_voucher[user={$user}][use_status={$use_status}][status={$status}]";

		if (isset($params[0]) && is_numeric($params[0])) {
			$selector .= "[type={$params[0]}]";
		}

		if (isset($params[1])) {
			$selector .= "[equipment={$params[1]}]";
		}

		$e->return_value = Q($selector);
		return FALSE;
	}

	static function isCouldSeeQuotaFromInfo($e, $user, $params) {
		$me = $user;
		$user = $params[0];
		if ($me->id == $user->id) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if (Q("$me<pi lab")->total_count()) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if (Q("{$me}<incharge equipment")->total_count()) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if ($me->access('管理所有内容')) {
			$e->return_value = TRUE;
			return FALSE;
		}
		return TRUE;
	}

	static function isCouldSeeQuotaFromPreviewInfo($e, $user, $params) {
		$me = $user;
		$user = $params[0];
		if (Q("$me<pi lab")->total_count()) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if (Q("{$me}<incharge equipment")->total_count()) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if ($me->access('管理所有内容')) {
			$e->return_value = TRUE;
			return FALSE;
		}
		return TRUE;
	}
}