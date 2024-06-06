#!/usr/bin/env php
<?php

require "base.php";

try {
	
	/*是否需要对系统已经存在相同token，但是密码验证失败的用户增加到系统中*/
	$add_false_verify_users = TRUE;
	
	echo "筛选系统中已经存在的用户:\n";
	echo "======================================\n";
	$db = Database::factory();
	$bynon_users = $db->query("select * from bynon_user")->rows();
	$is_find = 0;
	$has_tutorid_users = [];
	$true_verify_users = [];
	$false_verify_users = [];
	$must_add_users = [];
	foreach ($bynon_users as $bu) {
		$verify = TRUE;
		$t = preg_replace('/\s/', '', PinYin::code($bu->LoginName));
		$token = Auth::normalize($t);
		$user = O('user', ['token'=>$token]);
		if ($user->id) {
			$is_find ++;
			$auth = O('_auth', ['token'=>$bu->LoginName]);
			$pwd = $bu->pw ? md5($bu->pw) : $bu->LoginPassword;
			if ($auth->password == $pwd) {
				$verify = FALSE;
			}
			if ($verify) {
				$true_verify_users[] = $user;
				$user->bynon_id = $user->bynon_id ?: $bu->UserId;
				$user->organizationid = $user->organizationid ?: $bu->OrganizationId;
				$user->save();
				if ($bu->TutorId) {
					$has_tutorid_users[$bu->TutorId][] = $user;
				}
				echo sprintf('  %s[%s]在系统中存在,密码验证正确', $bu->Name, $bu->LoginName)."\n";
			} 
			else {
				$false_verify_users[] = $bu;
				echo sprintf('  %s[%s]在系统中存在,密码验证失败', $bu->Name, $bu->LoginName)."\n";
			}
		} 
		else {
			$must_add_users[] = $bu;
		}
	}
	
	echo "\n\n\n\n";
	echo "筛选结果:\n";
	echo "==========================================\n";
	echo "需要更新到系统中用户数量为:".count($must_add_users)."\n";
	echo "有疑问,在系统中存在token,但密码验证失败的数量为:".count($false_verify_users)."\n";
	echo "没疑问,系统中真是存在的用户数量为:".count($true_verify_users)."\n";
	echo "\n\n\n\n";
	
	echo "系统中增加用户:\n";
	echo "==========================================\n";
	if ($add_false_verify_users) {
		$must_add_users += $false_verify_users;
		echo "将密码验证失败但是token相同的用户合并到需要添加用户列表中\n";
	}
	
	$cards = [];
	$succes_update = 0;
	$must_add = 0;
	$error = 0;
	$email_users = [];
	$no_token_users = [];
	$no_email_users = [];
	if (count($must_add_users)) {
		foreach ($must_add_users as $u) {
			$t = preg_replace('/\s/', '', PinYin::code($u->LoginName));
			$token = Auth::normalize($t);
			$user = O('user', ['token'=>$token]);
			$card = (int)$u->Card;
			if (isset($card) && $card != 0) {
				$num = (int)$db->value('SELECT COUNT(*) FROM `bynon_user` WHERE Card = %d', $card);
				if ($num <= 1) {
					$user->card_no = abs($card);
				}
			}
			
			$user->name = $u->Name;
			$user->token = $token;
			$user->mobile = $u->PhoneNumber ?: '';
			$user->email = $u->Email;
			$user->ctime = strtotime($u->RegisterTime) ?: 0;
			$user->atime = strtotime($u->AuthorizeTime) ?: 0;
			$user->phone = $u->FixedPhone ?: '';
			$user->address = $u->ContactAddress ?: '';
			$user->major = $u->Speciality ?: '';
			$user->gender = $u->Sex ?: 0;
			$user->bynon_id = $u->UserId;
			$user->organizationid = $user->organizationid ?: $u->OrganizationId;
			$db = Database::factory();
			if (!$t) {
				$no_token_users[$u->UserId] = $u->Name;
				echo sprintf('	用户%s[%s]没有登录帐号', $user->name, $user->token)."\n\n";
				continue;
			}
			if (!$user->email) {
				$no_email_users[$u->UserId] = $u->Name;
				echo sprintf('	用户%s[%s]没有填写邮箱', $user->name, $user->token)."\n\n";
				continue;
			}
			
			$email_user = O('user', ['email'=>$user->email]);
			if ($email_user->id) {
				$email_users[$u->UserId] = $email_user->id;
				//echo sprintf('	用户%s[%s]邮箱在系统中有重复 %s[%s]', $user->name, $user->token, $email_user->name, $email_user->id)."\n\n";
				continue;
			}
			
			$must_add ++;
			$pwd = $u->pw ? md5($u->pw) : $u->LoginPassword;
			if ($user->id) {
				$succes = 
				$db->query('UPDATE `%s` SET `password` = %s WHERE `token` = %s', '_auth', $pwd, $t);
			}
			else {
				$succes = 
				$db->query('INSERT INTO `%s` (`token`, `password`) VALUES("%s", "%s")', '_auth', $t, $pwd);
			}
			if ($succes) {
				if ($user->save()) {
					$succes_update ++;
					//echo sprintf('用户%s[%d]添加成功', $user->name, $user->id)."\n\n";
					$true_verify_users[] = $user;
					if ($u->TutorId) {
						$has_tutorid_users[$u->TutorId][] = $user;
					}					
				}
				else {
					$error ++;
					$db->query('DELETE FROM `%s` WHERE `token`="%s"', '_auth', $t);
					echo sprintf('用户%s[%s]添加失败', $user->name, $user->token)."\n\n";	
				}
			}
			else {
				$error ++;
				echo sprintf('用户%s[%s]验证信息失败', $user->name, $user->token)."\n\n";	
			}
		}
		
		echo "\n\n";
		echo "总共需要添加".count($must_add_users)."用户\n";
		echo "================\n";
		echo "邮箱相同用户有".count($email_users)."位\n";
		echo "没有帐号用户有".count($no_token_users)."位\n";
		echo "没有邮箱用户有".count($no_email_users)."位\n";
		echo "================\n";
		echo "实际需要添加".$must_add."用户\n";
		echo "成功添加".$succes_update."用户\n";
		echo "失败数据数:".$error."\n";
	}
	else {
		echo "没有需要添加用户\n";
	}
	echo "\n\n";
	
	/*
		对已经验证成功，且已经在系统中的用户进行课题组认证添加
	*/
	foreach ($has_tutorid_users as $key => $users) {
		$db = Database::factory();
		$sql = sprintf('SELECT * FROM bynon_user WHERE userid = %s', Q::quote($key));
		$bynon_user = $db->query($sql)->rows();
		$u = $bynon_user[0];
		$t = preg_replace('/\s/', '', PinYin::code($u->LoginName));
		$token = Auth::normalize($t);
		$user = O('user', ['token'=>$token]);
		
		if (!$user->id) {
			if (array_key_exists($key, $email_users)) {
				$user = O('user', $email_users[$key]);
				$user->name = $u->name ?: $u->Name;
				$user->mobile = $u->mobile ?: ($u->PhoneNumber ?: '');
				$user->ctime = $user->ctime ?: (strtotime($u->RegisterTime) ?: 0);
				$user->atime = $user->atime ?: (strtotime($u->AuthorizeTime) ?: 0);
				$user->phone = $user->phone ?: ($u->FixedPhone ?: '');
				$user->address = $user->address ?: ($u->ContactAddress ?: '');
				$user->major = $user->major ?: ($u->Speciality ?: '');
				$user->gender = $user->gender ?: ($u->Sex ?: 0);
				$user->bynon_id = $u->UserId;
				$user->organizationid = $user->organizationid ?: $u->OrganizationId;
				if ($user->save()) {
					echo sprintf('	用户%s邮箱在系统中有重复 %s[%s], 但属于教师，先同步以备同步课题组信息。', $u->Name, $user->name, $user->id)."\n";
					unset($email_users[$key]);
				}
			}
			else {
				echo sprintf('%s->tutorid用户在系统未查找到对应用户', $key)."\n\n";
				continue;
			}
		}
		
		$default_lab = Lab_Model::default_lab();
		$name = $user->name . '实验室';
		if ($user->lab->id && $user->lab->id != $default_lab->id) {
			$lab = $user->lab;
			
			if ($lab->name != $name) {
				$lab->name = $name;
				$lab->save();
			}
			
			if ($lab->owner->id != $user->id) {
				$lab->owner = $user;
				$lab->save();
			}
		}
		else {
			$lab = O('lab');
			$lab->name = $name;
			$lab->owner = $user;
			$lab->save();
			$user->lab = $lab;
			$user->save();
			echo sprintf('成功创建%s[%d],pi=>%s[%d]', $lab->name, $lab->id, $user->name, $user->id)."\n";
		}
		foreach ($users as $user) {
			if (!$user->lab->id || $user->lab->id == $default_lab->id) {
				$user->lab = $lab;
				$user->save();
				echo sprintf('		用户%s[%d]成功添加到%s[%d]', $user->name, $user->id, $lab->name, $lab->id)."\n";
			}
		}
	}
	
	echo "\n\n\n\n";
	echo "剩余未处理相同邮箱数据有".count($email_users)."条\n";
	$sync_email = 0;
	foreach ($email_users as $key => $user_id) {
		$db = Database::factory();
		$sql = sprintf('SELECT * FROM bynon_user WHERE userid = %s', Q::quote($key));
		$bynon_user = $db->query($sql)->rows();
		$u = $bynon_user[0];
		$user = O('user', $user_id);
		$user->name = $u->name ?: $u->Name;
		$user->mobile = $u->mobile ?: ($u->PhoneNumber ?: '');
		$user->ctime = $user->ctime ?: (strtotime($u->RegisterTime) ?: 0);
		$user->atime = $user->atime ?: (strtotime($u->AuthorizeTime) ?: 0);
		$user->phone = $user->phone ?: ($u->FixedPhone ?: '');
		$user->address = $user->address ?: ($u->ContactAddress ?: '');
		$user->major = $user->major ?: ($u->Speciality ?: '');
		$user->gender = $user->gender ?: ($u->Sex ?: 0);
		$user->bynon_id = $u->UserId;
		$user->organizationid = $user->organizationid ?: $u->OrganizationId;
		if ($user->save()) {
			$sync_email ++;
			echo sprintf('	用户%s邮箱在系统中有重复 %s[%s], 仅同步基本信息', $u->Name, $user->name, $user->id)."\n";
		}
	}
	
	echo "\n\n==============\n";
	echo "需要处理相同邮箱用户为".count($email_users)."位\n";
	echo "成功更新".$sync_email."位用户信息\n";
	echo "==============\n";
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'sync_tju_user');
}



