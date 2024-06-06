<?php

class User_Import {

    static function add_user($e, $row, $fields, $user_submit) {
        $error = [];
        $warning = [];
        $isPI = false;
        $me = L('ME');
        $root = Tag_Model::root('group');
        $lab_name = false;
        //正则匹配
        $check_str_name = "/^[a-zA-Z0-9_\-@.]+$/";
        $check_str_email = '/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/';
        $check_str_password = "/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/";

        $genders = array_flip(User_Model::$genders);
        $members = User_Model::$members;
        $member_types = [];
        foreach ($members as $m_key => $m_value) {
            $member_types =  array_merge($member_types, array_flip($m_value));
        }
        //系统所有的角色
        $sys_roles = clone L('ROLES');
        $sys_roles_arr = $sys_roles->to_assoc('name', 'id');
        foreach ($row as $key => $value) {
            if (!in_array($key, $fields)) continue;
            switch ($key) {
                case 'name':
                    $form['name'] = trim(mb_substr($value, 0, 50, 'utf-8'));
                    if (!isset($form['name']) || $form['name'] == '') {
                        $error[$key]  = T('请输入用户姓名！');
                        goto output;
                    }
                    break;
                case 'token':
                    $tmp = trim($value);
                    if(!preg_match($check_str_name, $tmp)) {
                        $error[$key]  = T('账号不合法');
                        goto output;
                    }
                    $form['token'] = Auth::normalize($tmp, Config::get('auth.default_backend'));
                    $form['token_db'] = Auth::normalize($tmp, 'database');
                    break;
                case 'gender':
                    if (isset($value) && isset($genders[$value])) {
                        $form['gender'] =  $genders[$value];
                    } else {
                        $form['gender'] = '-1';
                    }
                    break;
                case 'member_type':
                    $tmp_tag = 0;
                    $trim_value = trim($value);
                    if ($trim_value == '课题负责人(PI)') {
                        $isPI = true;
                    }
                    if ($trim_value && isset($member_types[$trim_value])) {
                        $form['member_type'] = $member_types[$trim_value];
                    } elseif (isset($member_types['本科生'])) {
                        $form['member_type'] = $member_types['本科生'];
                        $warning[$key] = T('查找不到人员类型，自动设为本科生！');
                    } else {
                        $tmp = array_values($member_types);
                        $form['member_type'] = $tmp[0];
                        $warning[$key] = T('查找不到人员类型，自动设为第一个元素！');
                    }
                    break;
                case 'tag':
                    $trim_value = trim($value);
                    if ($trim_value == "") {
                        continue;
                    } else {
                        $form['tag'] = $trim_value;
                    }
                    
                    break;
                case 'ref_no':
                    $trim_value = trim($value);
                    if ($trim_value) {
                        if (O('user', ['ref_no' => $trim_value])->id) {
                            $warning[$key] = T('学工号已存在，数据将被覆盖');
                        }
                        $form['ref_no'] = $trim_value;
                    } else {
                        $form['ref_no'] = '';
                    }
                    break;
                case 'card_no':
                    $trim_value = trim($value);
                    $card_no = $trim_value;
                    $card_no_s = $trim_value & 0xffffff;
                    if ($trim_value) {
                        if (O('user', ['card_no' => $card_no])->id || O('user', ['card_no_s' => $card_no_s])->id) {
                            $warning[$key] = T('物理卡号已存在，数据将被覆盖');
                        }
                        $form['card_no'] = $card_no;
                        $form['card_no_s'] = $card_no_s;
                    } else {
                        $form['card_no'] = '';
                        $form['card_no_s'] = '';
                    }
                    break;
                case 'email':
                    $trim_value = trim($value);
                    if ($trim_value) {
                        if (O('user', ['email' => $trim_value])->id) {
                            $error[$key] = T('用户邮箱已存在');
                            goto output;
                        } elseif (!preg_match($check_str_email, $trim_value)) {
                            $error[$key] = T('请输入合法邮箱');
                            goto output;
                        }
                        $form['email'] = $trim_value;
                    } else {
                        // $error[$key] = T('必须输入邮箱');
                        // goto output;
                        $warning[$key] = T('邮箱为空');
                    }
                    break;
                case 'phone':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        // $error[$key] = T('必须输入电话');
                        // goto output;
                        $form['phone'] = '';
                    } else {
                        $form['phone'] = $trim_value;
                    }
                    break;
                case 'lab':
                    $lab_name = $trim_value = trim($value);
                    break;
                case 'role':
                    $trim_value = trim($value);
                    if ($trim_value) {
                        $form['roles'] = $trim_value;
                    }
                    break;
                case 'password':
                    $trim_value = trim($value);
                    if (!$trim_value) {
                        // $error[$key] = T('密码不能为空');
                        // goto output;
                        $trim_value = '123456';
                        $warning[$key] = T('密码默认设为123456');
                    }
                    if (strlen($trim_value) >24) {
                        $error[$key] = T('密码过长');
                        goto output;
                    }
                    if (!preg_match($check_str_password, $trim_value)) {
                        $form['password'] = $trim_value;
                        $form['must_change_password'] = true;
                        $warning[$key] = T('密码不符合标准，用户需手动修改密码');
                    } else {
                        $form['password'] = $trim_value;
                        $form['must_change_password'] = false;
                    }
                    $form['confirm_password'] = $value;
                    break;
                default:
                    $form[$key] = trim($value) ? : '';
                    break;
            }
        }
        //当用户不是PI 验证用户课题组是否存在
        // if (!$isPI && !$form['lab']) {
        //     $error[$key] = T('普通用户必须填写已存在课题组');
        //     goto output;
        // }
        $now = Date::time();
        $user = O('user', ['token' => $form['token']]);
        if (!$user->id) {
            $user = O('user', ['token' => $form['token_db']]);
            if (!$user->id) {
                $auth = new Auth($form['token']);
                if (!$auth->create($form['password'])) {
                    $error[$key] = T('新建用户_auth添加失败');
                    goto output;
                }
            }
        }

        if ($user->id) {
            list($token, $backend) = Auth::parse_token($user->token);
            if ($backend == 'database') {
                $auth = new Auth($user->token);
                $auth->change_password($form['password']);
            }
        }

        /* 如果无此用户，则新建 */
        $user->creator_id = L('ME')->id;
        $user->name = $form['name'];
        $user->token = $form['token'];
        $user->gender = isset($form['gender']) ? $form['gender'] : $user->gender;
        $user->member_type = $form['member_type'] ? : $user->member_type;
        $user->ref_no = $form['ref_no'] ? : $user->ref_no;
        $user->card_no = $form['card_no'] ? : $user->card_no;
        $user->card_no_s = $form['card_no_s'] ? : $user->card_no_s;
        $user->major = $form['major'] ? : $user->major;
        $user->organization = $form['organization'] ? : $user->organization;
        if ($user->email || $form['email']) $user->email = $form['email'] ? : $user->email;
        if ($user->phone || $form['phone']) $user->phone = $form['phone'] ? : $user->phone;
        if ($user->address || $form['address']) $user->address = $form['address'] ? : $user->address;
        $user->must_change_password = $form['must_change_password'];
        $user->atime = $user->atime ? : $now;
        // if (!($dfrom > 0 && $dto > 0 && $dfrom > $dto)) {
        //     $user->dfrom = $dfrom ? : $user->dfrom;
        //     $user->dto = $dto ? : $user->dto;
        // }
        if ($user->save()) {
            if ($form['roles']) {
                $connect_role = [];
                $tmp_role = explode(',', $trim_value);
                foreach ($tmp_role as $tmp_r_value) {
                    $roles_object = O('role');
                    $tmp_r_value = trim($tmp_r_value);
                    if (!$tmp_r_value) continue;
                    if($sys_roles_arr[$tmp_r_value]) {
                        $connect_role[] = $sys_roles_arr[$tmp_r_value];
                    } else {
                        $dbRole = O('role',['name'=>$tmp_r_value]);
                        if($dbRole->id){
                            $connect_role[] = $dbRole->id;
                        }else{
                            $roles_object->name = $tmp_r_value;
                            if ($roles_object->save()) {
                                $connect_role[] = $roles_object->id;
                            } else {
                                $warning[$key] = T('新建角色%s保存失败', ['%s' =>$tmp_r_value]);
                            }
                        }
                    }
                }
                if (count($connect_role)) $user->connect(['role', $connect_role]);
            }

            if ($form['tag']) {
                $tags = explode('-', $form['tag']);
                foreach ($tags as $tag_key => $tag_value) {
                    if ($tag_key == 0) {
                        $tmp_group = O('tag_group', [
                            'name' => $tag_value,
                            'root' => $root
                            ]);
                        if ($tmp_group->id) {
                            $user->group = $tmp_group;
                            $parent = $tmp_group;
                        } else {
                            $parent = $root;
                            $tmp_group = O('tag_group');
                            $tmp_group->parent = $parent;
                            $tmp_group->root = $root;
                            $tmp_group->name = $tag_value;
                            $tmp_group->weight = $user_submit['weight'];
                            if ($tmp_group->save()) {
                                $user->group = $tmp_group;
                                $parent = $tmp_group;
                                $warning['tag'] = T('找不到名称为 %s 的机构,自动创建成功', ['%s' => $tag_value]);
                            } else {
                                $warning['tag'] = T('找不到名称为 %s 的机构,且自动创建失败', ['%s' => $tag_value]);
                            }
                        }
                    } else {
                        $tmp_group = O('tag_group', [
                            'name' => $tag_value,
                            'parent_id' => $parent->id
                            ]);
                        if ($tmp_group->id) {
                            $user->group = $tmp_group;
                            $parent = $tmp_group;
                        } else {
                            $tmp_group = O('tag_group');
                            $tmp_group->parent = $parent;
                            $tmp_group->root = $root;
                            $tmp_group->name = $tag_value;
                            $tmp_group->weight = $user_submit['weight'];
                            if ($tmp_group->save()) {
                                $user->group = $tmp_group;
                                $parent = $tmp_group;
                                $warning['tag'] = T('找不到名称为 %s 的机构,自动创建成功', ['%s' => $tag_value]);
                            } else {
                                $warning['tag'] = T('找不到名称为 %s 的机构,且自动创建失败', ['%s' => $tag_value]);
                            }
                        }
                    }
                    if ($user->group->id) $user->group->connect($user);
                }
                $user->save();
            }
            if ($lab_name) {
                if ($isPI) {
                    $lab = O('lab', ['name'=>$lab_name]);
                    if (!$lab->id) {
                        $lab = O('lab');
                        $lab->name = $lab_name;
                        $lab->group = $user->group;
                        $lab->owner = $user;
                        $lab->atime = $now;
                        $lab->description = T('导入用户自动创建');
                        $lab->save();
                    }

	                $lab->atime = $now;
					$lab->owner = $user;
					$lab->description = T('导入用户自动创建');
					$lab->save();
        			if ($lab->group->id) $lab->group->connect($lab);
                    $user->connect($lab);
                    $user->connect($lab, 'pi');
				} else {
					$lab = O('lab', ['name' => $lab_name]);
					if ($lab->id) {
	            		$user->connect($lab);
					} else {
						$warning['lab'] = T('找不到名称为 %s 的课题组', ['%s' => $lab_name]);
						if ( !$default_lab->id ) $default_lab = Lab_Model::default_lab();
						$user->connect($default_lab);
					}
				}
	            
	        } elseif ( $isPI ) { 
	        	$lab = O('lab');
                $lab->name = $form['name']."课题组";
                $lab->group = $user->group;
                $lab->atime = $now;
                $lab->description = T('导入用户自动创建');
                $lab->owner = $user;
                if ($lab->save()) {
                    $warning['lab'] = T('未设置课题组,已创建 %s 课题组', ['%s' => $lab->name]);
                } else {
                    $warning['lab'] = T('未设置课题组,且自动创建课题组失败');
                }
                if ($lab->group->id) $lab->group->connect($lab);
                $user->connect($lab);
                $user->connect($lab, 'pi');
            } else {
                $warning['lab'] = T('未设置课题组');
            }
        }
        else {
            if (!is_null($auth)) $auth->remove();
            $error[] = T('保存失败，请联系管理员');
            goto output;
        }

        output:
        $e->return_value = [
            'error' => $error,
            'warning' => $warning
        ];
        return;
    }


    static function add_cardno($e, $row, $fields, $user_submit) {
        $error = [];
        $warning = [];
        $me = L('ME');
        foreach ($row as $key => $value) {
            if (!in_array($key, $fields)) continue;
            switch ($key) {
                case 'name':
                    $form['name'] = trim(mb_substr($value, 0, 50, 'utf-8'));
                    if (!isset($form['name']) || $form['name'] == '') {
                        $warning[$key]  = T('请输入用户姓名！');
                    }
                    break;
                case 'ref_no':
                    $trim_value = trim($value);
                    if ($trim_value) {
                        if (!O('user', ['ref_no' => $trim_value])->id) {
                            $error[$key] = T('用户不存在');
                            goto output;
                        }
                        $form['ref_no'] = $trim_value;
                    } else {
                        $form['ref_no'] = '';
                        $error[$key]  = T('请输入学工号！');
                        goto output;
                    }
                    break;
                case 'card_no':
                    $trim_value = trim($value);
                    $card_no = $trim_value;
                    $card_no_s = $trim_value & 0xffffff;
                    if ($trim_value) {
                        if (O('user', ['card_no' => $card_no])->id || O('user', ['card_no_s' => $card_no_s])->id) {
                            $warning[$key] = T('物理卡号已存在，数据将被覆盖');
                        }
                        $form['card_no'] = $card_no;
                        $form['card_no_s'] = $card_no_s;
                    } else {
                        $form['card_no'] = '';
                        $form['card_no_s'] = '';
                    }
                    break;
                default:
                    $form[$key] = trim($value) ? : '';
                    break;
            }
        }

        $user = O('user', ['ref_no' => $form['ref_no']]);
        if (!$user->id) {
            $error[$key] = T('导入卡号失败，用户不存在！');
        }

        if ($user->id) {
            $user->ref_no = $form['ref_no'] ? : $user->ref_no;
            $user->card_no = $form['card_no'] ? : $user->card_no;
            $user->card_no_s = $form['card_no_s'] ? : $user->card_no_s;
            $user->save();
        }
        output:
        $e->return_value = [
            'error' => $error,
            'warning' => $warning
        ];
        return;
    }
    
}