<?php

class Add_User_Controller extends Controller {

	public function index($id){
		$resume			= O('resume', $id);
		$me				= L('ME');
		$user			= O('user');

		$name			= $resume->uname;
		$name_arr		= explode(' ', PinYin::code($name) );
		$first_name		= array_shift($name_arr);
		$name_pinyin	= implode('', $name_arr ) . '.' . $first_name;
		$backend		= Config::get('auth.default_backend');

		$token			= Auth::make_token($name_pinyin, $backend);
		$password		= '123456';
		$email			= $name_pinyin.'@geneegroup.com';
		
		try {
			if(User_Model::is_reserved_token($token) ){
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('staff', '默认的登录帐号已被保留。'));
				throw new Error_Exception;
			}

			if( O('user', ['token'=>$token])->id ) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('staff', '默认的帐号在系统中已存在！'));
				throw new Error_Exception;
			}

			if( O('user', ['email'=>$email])->id ) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('staff', '默认的电子邮箱在系统中已存在！'));
				throw new Error_Exception;
			}
			$auth	= new Auth($token);
			if( !$auth->create($password)){
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('staff', '添加新成员失败! 请与系统管理员联系。'));
				throw new Error_Exception;
			}

			$user->name		= $name;
			$user->token	= $token;
			$user->email	= $email;
			$user->password	= $password;
			$user->gender	= $resume->sex - 1;
			$user->creator	= $me;
			$user->atime	= time();
			$user->ctime	= time();
			$user->ref_no   = NULL;
			$user->phone	= $resume->phone;

			if( $user->save() ){

				$staff				= O('staff');

				$staff->user		= $user;
				$staff->job_number	= $user->id;
				$staff->birthday	= $resume->birthday;
				$staff->school		= $resume->school;
				$staff->position	= $resume->position;
				$staff->start_time	= time();

				if($staff->save()){
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('staff', '人事信息加成功！'));
				}else{
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('staff', '添加人事信息失败! 请与系统管理员联系。'));
				}
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('staff', '用户添加成功！默认密码为123456.'));
				}else{
					$auth->remove();
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('staff', '添加新成员失败! 请与系统管理员联系。'));
					throw new Error_Exception;
				}

			URI::redirect('!people/profile/edit.'.$user->id);
		}catch (Error_Exception $e) {
			URI::redirect('!resume/resume/index.'.$id);
		}

	}
}
