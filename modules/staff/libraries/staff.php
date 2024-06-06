<?php

class Staff {

	static function setup_people(){
		
		Event::bind('profile.view.tab', 'Staff::index_profile_tab' , 30);
		Event::bind('profile.view.content', 'Staff::index_profile_content', 30, 'staff');
        if (!People::perm_in_uno())
		    Event::bind('profile.edit.tab', 'Staff::profile_edit_tab' , 30);
		Event::bind('profile.edit.content', 'Staff::profile_edit_content', 30, 'staff');
		Event::bind('profile.view.content', 'Staff::people_add_form_resume', 40, 'staff_add');
	}

	static function index_profile_tab($e, $tabs){

		$staff = O('staff', ['user'=>$tabs->user ],TRUE );
		if( !L('ME')->is_allowed_to('查看',$staff) ) {
			return;
		}

		$tabs->add_tab('staff',
					   [
						   'url'	=> $staff->user->url('staff'),
						   'title'	=> I18N::T('staff', '人事信息')
					   ]);
	}
							
			
	static function index_profile_content($e, $tabs){

		$user  = $tabs->user;
		$staff = O('staff', ['user'=>$user]);

		$sections	= [];

		$detailed	= V('staff:detailed', ['user'=>$user, 'staff'=>$staff] );
		$sections[] = $detailed;

		$advanced	= V('staff:advanced', ['user'=>$user, 'staff'=>$staff] );
		$sections[] = $advanced;
		$tabs->content = V('staff:staff', ['sections'=>$sections]);

	}

	static function profile_edit_tab($e, $tabs){

		$user  = $tabs->user;
		$staff = O('staff', ['user'=>$user ], TRUE);
		
		if( !L('ME')->is_allowed_to('修改', $staff ) ) {
			return;
		}

		$tabs->add_tab('staff',
					   [
						   'url'	=> $user->url('staff', NULL, NULL, 'edit'),
						   'title'	=> I18N::T('staff', '人事信息')
					   ]);
	}

	static function profile_edit_content($e, $tabs){
		$user  = $tabs->user;
		$staff = O('staff', ["user"=>$user], TRUE);
		$me    = L('ME');
		if( !$me->is_allowed_to('修改', $staff) ) {
			URI::redirect('error/401');
		}

		if( $me->is_allowed_to('修改', $staff, ['@ignore'=>['自己'] ] ) ){
			Staff::_edit_staff_admin($tabs );
		}else{
			Staff::_edit_staff($tabs );
		}
	}

	static function _edit_staff($tabs ) {

		$user  = $tabs->user;
		$staff = O('staff', ["user"=>$user] );

		$form = Form::filter(Input::form() );
		if( Input::form('submit') ){
			if($form->no_error){
				if( !$staff->id ){
					$staff = O('staff');
					$staff->user = $user;
				}

				$staff->IDnumber		= $form['IDnumber'];
				$staff->birthplace		= $form['birthplace'];
				$staff->birthday		= $form['birthday'];
				$staff->school			= $form['school'];
				$staff->professional	= $form['professional'];

				try {
					if( $staff->save() ){
						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('staff', '人事信息已更新') );
					}else{
						Lab::Message(Lab::MESSAGE_ERROR, I18N::T('staff', '人事信息更新失败') );
					}
				} catch (Error_Exception $ex) {
				}
			}

		}

		$tabs->content = V('staff:edit', ['staff'=>$staff, 'form'=>$form]);

	}

	static function _edit_staff_admin($tabs ) {

		$user  = $tabs->user;
		$staff = O('staff', ["user"=>$user] );

		$form = Form::filter(Input::form() );
		if( Input::form('submit') ){
			if($form->no_error){
				if( !$staff->id ){
					$staff = O('staff');
					$staff->user = $user;
				}

				$staff->position		= O('position', $form['position'] );
				$staff->role			= $form['role'];
				$staff->IDnumber		= $form['IDnumber'];
				$staff->birthplace		= $form['birthplace'];
				$staff->birthday		= $form['birthday'];
				$staff->school			= $form['school'];
				$staff->professional	= $form['professional'];
				$staff->practice_time	= $form['has_practice']?$form['practice_time']:0;
				$staff->trial_time		= $form['has_trial']?$form['trial_time']:0;
				$staff->normal_time		= $form['has_normal']?$form['normal_time']:0;
				$staff->insurance		= $form['insurance'];
				$staff->remarks			= $form['remarks'];

				if( L('ME')->is_allowed_to('管理', $staff)) {
					$staff->start_time		= $form['has_start']?$form['start_time']:0;
					$staff->contract_time	= $form['has_contract']?$form['contract_time']:0;
					$staff->salary			= $form['salary'];
					$staff->positions		= $form['positions'];
				}

				try {
					if( $staff->save() ){
						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('staff', '人事信息已更新') );
					}else{
						Lab::Message(Lab::MESSAGE_ERROR, I18N::T('staff', '人事信息更新失败') );
					}
				} catch (Error_Exception $ex) {
				}
			}

		}

		$tabs->content = V('staff:edit_admin', ['staff'=>$staff, 'form'=>$form]);
	}

	static function get_positions(){
		
		$selector 	= 'position';
		$positions	= Q($selector);
		$arr_pos	= [];
		foreach($positions as $pos){
			$arr_pos[ $pos->id ] = $pos->name;
		}
		return $arr_pos;
	}

	static function setup_resume(){
		Event::bind('resume.models.link.index', 'Staff::resume_add_button', 30);
	}
	
	static function resume_add_button($e, $resume, $links){
		if( L('ME')->is_allowed_to('生成新员工', 'resume') ) {
			$links['add_people'] = [
				'url'	=> URI::url('!staff/add_user/index.'.$resume->id),
				'text'	=> I18N::T('staff', '生成新员工'),
				'extra'	=> ' class="button button_add"'
			];
		}
	}

	static function people_user($e, $user){
		$staff	= O('staff', ["user"=>$user]);
		$staff->delete();
	}

	static function people_base_tab($e, $tab){
		$me = L('ME');	
		if( !$me->is_allowed_to('查看', O('staff') ) ) {
			return;
		}

		$tab->add_tab('staff',[
			'url'=>URI::url('!staff/list'),
			'title'=>I18N::T('staff', '人事信息'),
			'weight'=>-1
		]);
	}

	static function staff_ACL($e, $user, $perm, $object, $options){
		$ignores = $options['@ignore'];
		if (!is_array($ignores)){
			$ignores = [$ignores];
		}
		switch ($perm) {
		case '查看':
			if( $user->id == $object->user->id ){
				$e->return_value = TRUE;
				return FALSE;
			}
			if( $user->access('查看基本人事信息')
			&&  !in_array('查看基本人事信息', $ignores)
			) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if( $user->access('管理所有人事信息') ){
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '修改':
			if( $user->id == $object->user->id
			&&  !in_array('自己', $ignores )
			){
				$e->return_value = TRUE;
				return FALSE;
			}
			if( $user->access('修改基本人事信息')){
				$e->return_value = TRUE;
				return FALSE;
			}
			if( $user->access('管理所有人事信息') ){
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '管理':
			if( $user->access('管理所有人事信息') ){
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}

}
