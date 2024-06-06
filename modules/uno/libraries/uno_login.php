<?php

class Uno_Login
{

    public static function login_post($e, $params, $data, $query)
    {
        $accessToken = $data['accessToken']; // 传递过来的accessToken
        $state = $data['state'];
        $res = ["redirect" => ""];
        if ($accessToken) {
            try {
                $server  = Config::get('gateway.server');
                $rest = new REST($server['url'] . 'auth');
                $_SESSION['gapper_oauth_token'] = $accessToken;
                $data = $rest->get('owner', ['gapper-oauth-token' => $accessToken]);
                if ($data['id'] && $data['type'] && $data['type'] == 'user') {
                    // 获取gapper_id
                    $rest = new REST($server['url'] . 'current-user');
                    $u = $rest->get('default', ['gapper-oauth-token' => $_SESSION['gapper_oauth_token']]);
                    $gapper_id = $u['id'];
                    if ($gapper_id) {
                        // 更正gapper用户的基础信息
                        $lu = O('gapper_user', ['gapper_id' => $gapper_id]);
                        $lu->ref_no = $u['ref_no'];
                        $lu->email = $u['email'];
                        $lu->avatar = $u['avatar'];
                        $lu->name = $u['name'];
                        $lu->gapper_id = $gapper_id;
                        $lu->save();

                        $user = O('user', ['gapper_id' => $gapper_id]);

                        $remoteUser = Uno_Util::get_remote_user();
                        $last_user = O('user');

                        if ($user->id) {
                            if (!empty($remoteUser['lab'])) {
                                // 用户有课题组 && 课题组发生了变更 创建新帐号
                                foreach ($remoteUser['lab'] as $remoteLab) {
                                    $lab = O('lab', ['gapper_id' => $remoteLab['id']]); 
                                    if (!$lab->id) {
                                        $lab->name = $remoteLab['name'];
                                        $lab->gapper_id = $remoteLab['id'];
                                        $lab->atime = time();
                                        $lab->description = I18N::T('uno', '自动创建!');
                                        $lab->save();
                                    }
                                }
                                // 暂时还是直接拿最后一个课题组
                                if ($lab->id) {
                                    $default_lab = Lab_Model::default_lab();
                                    foreach (Q("$user lab[id!=$default_lab->id]") as $l) {
                                        if ($l->id != $lab->id) {
                                            $user->remove_unique();
                                            $user->history_gapperid = $gapper_id;
                                            $user->save();
                                            $last_user = $user;
                                            $user = O('user', ['gapper_id' => $gapper_id]);
                                            break;
                                        }
                                    }
                                } 
                            } else {
                                // 用户无课题组 创建新帐号
                                foreach (Q("$user lab") as $l) {
                                    $default_lab = Lab_Model::default_lab();
                                    if ($l->id != $default_lab->id) {
                                        $user->remove_unique();
                                        $user->history_gapperid = $gapper_id;
                                        $user->save();
                                        $last_user = $user;
                                        $user = O('user', ['gapper_id' => $gapper_id]);
                                        break;
                                    }
                                }
                            }
                        } 

                        if (!$user->id || !$user->lastSynctime || $user->lastSynctime < time() - 5 * 60) {
                            // 注册用户
                            $user->name = $remoteUser['name'];
                            $user->gapper_id = $remoteUser['gapper_id'];
                            $remoteUser['email'] ? $user->email = $remoteUser['email'] : '';
                            $remoteUser['phone'] ? $user->phone = $remoteUser['phone'] : '';
                            $remoteUser['ref_no'] ? $user->ref_no = $remoteUser['ref_no'] : '';
                            $user->atime = !$user->id ? time() : $user->atime;
                            $user->token = $remoteUser['token'];
                            $user->lastSynctime = time();
                            Uno::user_unique_info($user);
                            $user->save();
                            if ($user->id) {
                                if ($last_user->id) {
                                    // 用户更换了课题组 获取到用户之前作为负责人的仪器，自动同步为负责人
                                    foreach (Q("$last_user equipment.incharge") as $equipment) {
                                        $equipment->connect($user, 'incharge');
                                        $user->follow($equipment);
                                    }
                                    // 用户更换了课题组 获取到用户之前作为联系人的仪器，自动同步为联系人
                                    foreach (Q("$last_user equipment.contact") as $equipment) {
                                        $equipment->connect($user, 'contact');
                                        $user->follow($equipment);
                                    }
                                    // 用户更换了课题组 获取到用户之前的关注仪器 自动关注
                                    foreach (Q("follow[user=$last_user][object_name=equipment]") as $follow) {
                                        $user->follow($follow->object);
                                    }
                                    // 用户更换了课题组 获取到用户之前的培训，自动培训
                                    $status = UE_Training_Model::STATUS_APPROVED;
                                    foreach (Q("ue_training[user={$last_user}][status={$status}]") as $training) {
                                        $new_training = O('ue_training');
                                        $new_training->user = $training->user;
                                        $new_training->equipment = $training->equipment;
                                        $new_training->status = $training->status;
                                        $new_training->type = $training->type;
                                        $new_training->atime = $training->atime ;
                                        $new_training->save();
                                    }
                                }
                                //获取角色
                                $roles = Gateway::getRemoteUserRoles([
                                    'user_id' => $gapper_id
                                ]);
                                //同步远程角色到CF
                                foreach(Q("{$user} role[weight>0]") as $rc){
                                    $user->disconnect($rc);
                                }
                                foreach($roles['roles'] as $ro){
                                    $remoteLocalRole = O('role',['gapper_id'=>$ro['role_id']]);
                                    if($remoteLocalRole->id) $user->connect($remoteLocalRole);
                                }

                                $labPi = [];
                                $pi_role = O('role', ['weight' => ROLE_LAB_PI]);
                                if ($pi_role->gapper_id) {
                                    foreach ($roles['roles'] as $role) {
                                        if ($role['role_id'] == $pi_role->gapper_id) {
                                            $labPi[] = $role['group_id'];
                                        }
                                    }
                                }
                                $labIds = [];
                                if (!empty($remoteUser['lab'])) {
                                    foreach ($remoteUser['lab'] as $remoteLab) {
                                        $lab = O('lab', ['gapper_id' => $remoteLab['id']]);
                                        if (!$lab->id) {
                                            $lab->name = $remoteLab['name'];
                                            $lab->gapper_id = $remoteLab['id'];
                                            $lab->atime = time();
                                            $lab->description = I18N::T('uno', '自动创建!');
                                            $lab->save();
                                        } else {
                                            $lab->name = $remoteLab['name'];
                                            $lab->save();
                                        }
                                        if (in_array($lab->gapper_id, $labPi) && $lab->owner->id != $user->id) {
                                            $lab->owner = $user;
                                            $lab->save();
                                        }
                                        $user->connect($lab);
                                        //目前做的是，保留最后一个，所以ID的key 固定
                                        $labIds[$user->id] = $lab->id;
                                        if (in_array($lab->gapper_id, $labPi)) {
                                            $user->connect($lab, 'pi');
                                        }
                                    }
                                } else {
                                    // $lab = Lab_Model::default_lab();
                                    // // 嵌入UNO只支持单课题组的临时处理
                                    // // 非默认课题组，代表用户之前有有效课题组，现在还没进入到课题组还是保留以前课题组
                                    // $allowConnectDefault = true;
                                    // foreach (Q("$user lab") as $l) {

                                    //     if ($l->id != $lab->id) {
                                    //         $allowConnectDefault = false;
                                    //         $labIds[$user->id] = $l->id;
                                    //     }
                                    // }
                                    // if ($allowConnectDefault)  {
                                    //     $user->connect($lab);
                                    //     //目前做的是，保留最后一个，所以ID的key 固定
                                    //     $labIds[$user->id] = $lab->id;
                                    // }
                                }

                                foreach (Q("$user lab") as $l) {
                                    if (!in_array($l->id, $labIds)) {
                                        $user->disconnect($l);
                                    }
                                }

                                $tag_group = Tag_Model::root('group');

                                $user->group = O('tag_group');
                                $user->disconnect_all('group');

                                if (!empty($remoteUser['group'])) {
                                    $parent_groups = [];
                                    foreach ($remoteUser['group'] as $grp){
                                        // 保存获取到的每一个组织机构
                                        $local_group = O('tag_group', ['gapper_id' => $grp['id']]);
                                        if (!$local_group->id) {
                                            $local_group->name = $grp['name'];
                                            $local_group->root = $tag_group;
                                            $local_group->gapper_id = $grp['id'];
                                            $local_group->parent = O('tag_group',['gapper_id'=>$grp['parent_id']]);
                                            $local_group->save();
                                        }
                                        $parent_groups[$local_group->id] = $local_group->parent->id;
                                    }
                                    foreach ($parent_groups as $id => $parent_id) {
                                        unset($parent_groups[$parent_id]);
                                    }
                                    foreach ($parent_groups as $id => $parent_id) {
                                        $user->disconnect_all('group');
                                        $local_group = O('tag_group', $id);
                                        $user->group = $local_group;
                                        $user->save();
                                        $user->connect($local_group);
                                    }
                                }

                                Auth::login($user->token);
                                Cache::L('ME', $user);
                                $entriesConfig = Uno::get_uno_entries();
                                $entriesConfig[$state] && $res["redirect"] = $entriesConfig[$state]["redirect"];
                            } else {
                                Log::add(strtr('uno 新用户注册失败 %accessToken 的新用户注册失败', ['%accessToken' => $accessToken]), 'uno');
                            }
                        } else {
                            if (Q("{$user} lab")->total_count() == 0) {
                                if (!empty($remoteUser['lab'])) {
                                    foreach ($remoteUser['lab'] as $remoteLab) {
                                        $lab = O('lab', ['gapper_id' => $remoteLab['id']]);
                                        if (!$lab->id) {
                                            $lab->name = $remoteLab['name'];
                                            $lab->gapper_id = $remoteLab['id'];
                                            $lab->atime = time();
                                            $lab->description = I18N::T('uno', '自动创建!');
                                            $lab->save();
                                        }
                                        $user->connect($lab);
                                        break;
                                    }
                                }
                            }
                            Auth::login($user->token);
                            Cache::L('ME', $user);
                            $entriesConfig = Uno::get_uno_entries();
                            $entriesConfig[$state] && $res["redirect"] = $entriesConfig[$state]["redirect"];
                        }
                        $login_count = Lab::get('login_plus.login_count');
                        Lab::set('login_plus.login_count', ++$login_count);
                    } else {
                        $auth_url = str_replace('uno-auth/?', 'uno-auth/?refresh=1&', $_SERVER['HTTP_REFERER']);
                        $_SESSION['uno_login_refresh'] = $auth_url;
                        Log::add(strtr('uno 登陆失败 %accessToken 的gapper_id不存在', ['%accessToken' => $accessToken]), 'uno');
                    }
                } else {
                    $auth_url = str_replace('uno-auth/?', 'uno-auth/?refresh=1&', $_SERVER['HTTP_REFERER']);
                    $_SESSION['uno_login_refresh'] = $auth_url;
                    Log::add(strtr('uno 登陆失败 %accessToken 的type不是user', ['%accessToken' => $accessToken]), 'uno');
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        }
        $e->return_value = $res;
    }
}
