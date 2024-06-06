<?php

class CLI_Gapper_User
{
    public static function connect_local()
    {

        //同步到本地
        $pg = 1;
        $pp = 100;
        while (true) {
            $condition = [
                'pg' => $pg,
                'pp' => $pp,
            ];
            $gapperUsers = Gapper_User::get_remote_user($condition);
            if (!isset($gapperUsers['items'])) {
                return false;
            }
            if (!count($gapperUsers['items'])) break;
            $gapperUsers = $gapperUsers['items'];
            foreach ($gapperUsers as $gapperUser) {
                $lu = O('gapper_user', ['gapper_id' => $gapperUser['id']]);
                $lu->ref_no = $gapperUser['ref_no'];
                $lu->email = $gapperUser['email'];
                $lu->avatar = $gapperUser['avatar'];
                $lu->name = $gapperUser['name'];
                $lu->gapper_id = $gapperUser['id'];
                $lu->save();
            }
            $pg += 1;
        }

        //本地循环
        $selector = 'user[atime][!gapper_id]';
        $uniKey = Config::get('gateway.bind_gapper_keys');
        if ($uniKey) {
            foreach ($uniKey as $kk)
                $selector .= "[{$kk}]";
        }
        $start = 0;
        $step = 10;
        while (true) {
            $localUsers = Q($selector)->limit($start, $step);
            if (!count($localUsers)) break;
            foreach ($localUsers as $user) {
                foreach ($uniKey as $uk) {
                    $gu = O('gapper_user', ["{$uk}" => $user->$uk]);
                    if ($gu->id) {
                        $user->gapper_id = $gu->gapper_id;
                        $user->save();
                        continue;
                    }
                }
            }
            $start += $step;
        }

        // Q('gapper_user')->delete_all();

        $db = Database::factory();
        $gapperIds = $db->query("select gapper_id from user group by gapper_id HAVING count(*) > 1")->rows();
        foreach ($gapperIds as $u) {
            if ($u->gapper_id) {
                $save_user = Q("user[gapper_id={$u->gapper_id}]:sort(atime D):sort(id D)")->current();
                if ($save_user->id) {
                    foreach (Q("user[gapper_id={$u->gapper_id}][id!=$save_user->id]") as $user) {
                        $user->gapper_id = NULL;
                        $user->save();
                    }
                }
            }
        }
        echo 'done', "\n";
    }

    public static function test()
    {
        $users = [15 => 'superadmin', 12 => '中心管理员', 14 => '实验室负责人', 11 => '仪器负责人', 13 => '院级管理员'];
        $perms = ['查看成员信息'];
        $groups = [null, 506 => 'tag_group', 1171 => 'lab'];
        foreach ($users as $id => $role) {
            $user = O('user', $id);
            $data = [
                $user->id,
                $role,
                $user->name,
                "\n"
            ];
            foreach ($perms as $perm) {
                foreach ($groups as $groupId => $orm_name) {
                    if ($groupId) {
                        $group = O($orm_name, $groupId);
                        $data[] = "$perm ($group->name): " . $user->access($perm, false, $group);
                    } else {
                        $data[] = "$perm: " . $user->access($perm);
                    }
                }
            }
            echo join("\t|\t", $data), "\n";
        }
        // $remote = Gateway::getRemoteUserRoles([
        //     'user_id' => 10218651,
        // ]);

        // $remote = Gateway::getRemoteUserGroups([
        //     'user_id' => 10218651,
        //     // 'group_type' => 'organization' // lab
        // ]);

        // var_dump($remote);
    }

    public static function sync_user()
    {
        $default_lab = Lab_Model::default_lab();

        $tag_group = Tag_Model::root('group');
        $total = -1;
        $page = 1;
        $perPage = 100;
        while ($total === -1 || $total >= ($page - 1) * $perPage) {
            $users = Gateway::getRemoteUser([
                'pp' => $perPage,
                'pg' => $page,
                'includes' => 'identities,groups'
            ]);
            $total = $users['total'];

            foreach ($users['items'] as $remote_user) {
                $gapper_id = $remote_user['id'];
                $total = $users['total'];

                $lu = O('gapper_user', ['gapper_id' => $gapper_id]);
                $lu->ref_no = $remote_user['ref_no'];
                $lu->email = $remote_user['email'];
                $lu->avatar = $remote_user['avatar'];
                $lu->name = $remote_user['name'];
                $lu->gapper_id = $gapper_id;
                $lu->save();

                $user = O('user', ['gapper_id' => $gapper_id]);
                // 开启了uno模块才需要更新用户信息与UNO保持一致(更新人员、课题组、角色等信息)
                if (Module::is_installed('uno')) {

                    $remoteLab = Gateway::getRemoteUserGroups([
                        'user_id' => $remote_user['id'],
                        'group_type' => 'lab' // lab
                    ]);
                    $labs = isset($remoteLab['items']) ? $remoteLab['items'] : [];
                    foreach ($labs as $remoteLab) {
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
                        // 用户有课题组 && 课题组发生了变更 创建新帐号
                        $default_lab = Lab_Model::default_lab();
                        foreach (Q("$user lab[id!=$default_lab->id]") as $l) {
                            if ($l->id != $lab->id) {
                                $user->remove_unique();
                                $user->history_gapperid = $gapper_id;
                                $user->save();
                                $user = O('user', ['gapper_id' => $gapper_id]);
                                break;
                            }
                        }
                    } 

                    
                    if (!count($labs)) {
                        // 用户无课题组 创建新帐号
                        foreach (Q("$user lab") as $l) {
                            $default_lab = Lab_Model::default_lab();
                            if ($l->id != $default_lab->id) {
                                $user->remove_unique();
                                $user->history_gapperid = $gapper_id;
                                $user->save();
                                $user = O('user', ['gapper_id' => $gapper_id]);
                                break;
                            }
                        }
                    }

                    $user->name = $remote_user['name'];
                    $user->gapper_id = $gapper_id;
                    if ($remote_user['avatar']) {
                        if (preg_match_all('/\.(jpg|png|jpeg)$/', $remote_user['avatar'], $matches)) {
                            $ext = $matches[1][0];
                            $path = '/tmp/user_icon.' . $ext;
                            try {
                                file_put_contents($path, file_get_contents($remote_user['avatar']));
                                $img = Image::load($path, $ext);
                                $user->save_icon($img);
                            } catch (Exception $e) {
                            }
                        }
                    }
                    $remote_user['email'] ? $user->email = $remote_user['email'] : '';
                    $remote_user['phone'] ? $user->phone = $remote_user['phone'] : '';
                    $remote_user['ref_no'] ? $user->ref_no = $remote_user['ref_no'] : '';
                    $user->atime = $user->id ? $user->atime : time();
                    if ($remote_user['status'] == 2) $user->atime = 0;
                    if ($remote_user['status'] == 4) $user->atime = time();
                    $user->dfrom = $remote_user['begin_time'] ? strtotime($remote_user['begin_time']) : 0;
                    $user->dto = $remote_user['expire_time'] ? strtotime($remote_user['expire_time']) : 0;
                    if (count($remote_user['identities'])) {
                        $user->token = $remote_user['identities'][0]['identity'] . "|" . $remote_user['identities'][0]['source'];
                    } else {
                        $user->token = $gapper_id . "_" . uniqid() . "|tmp";
                    }
                    $user->group = O('tag_group');
                    $user->save();
                }

                if ($user->id && Module::is_installed('uno')) {

                    $user->disconnect_all('labs');
                    $user->disconnect_all('groups');

                    //获取group
                    $remoteGroup = Gateway::getRemoteUserGroups([
                        'user_id' => $remote_user['id'],
                        'group_type' => 'organization' // lab
                    ]);

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
                    if (isset($roles['roles'])) {
                        foreach ($roles['roles'] as $role) {
                            if(!$role['group_id']) continue;
                            $labPi[$role['group_id']][] = trim($role['role_name']);
                        }
                    }

                    $groups = isset($remoteGroup['items']) ? $remoteGroup['items'] : [];
                    
                    $labIds = [];
                    if (!empty($labs)) {
                        foreach ($labs as $remoteLab) {
                            $lab = O('lab', ['gapper_id' => $remoteLab['id']]);
                            if (!$lab->id) {
                                $lab->name = $remoteLab['name'];
                                $lab->gapper_id = $remoteLab['id'];
                                $lab->atime = time();
                                $lab->description = I18N::T('uno', '自动创建!');
                                $lab->save();
                            }
                            if (array_key_exists($lab->gapper_id, $labPi) && in_array('课题组管理员', $labPi[$lab->gapper_id]) && $lab->owner->id != $user->id) {
                                $lab->owner->disconnect($lab, 'pi');
                                $lab->owner = $user;
                                $lab->save();
                            }
                            $user->connect($lab);
                            //目前做的是，保留最后一个，所以ID的key 固定
                            $labIds[$user->id] = $lab->id;
                            if (array_key_exists($lab->gapper_id, $labPi) && in_array('课题组管理员', $labPi[$lab->gapper_id])) {
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
                    
                    if (!empty($groups)) {
                        $parent_groups = [];
                        foreach ($groups as $grp){
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

                    Upgrader::echo_title("[{$user->id}]{$user->name}");
                }
            }
            $page++;
        }
        Upgrader::echo_success("Done.");
    }

    public static function push_user(){
        $users = Q("user[email][!gapper_id]");
        $remote_group = Gateway::getRemoteGroupRoot();
        foreach ($users as $user){
            list($token, $backend) = Auth::parse_token($user->token);
            $push_data = [
                'name' => $user->name,
                'email' => $user->email,
                'type' => ($user->member_type >= 10 && $user->member_type <= 13) ? 'teacher' : 'student',
                'phone' => $user->phone,
                'ref_no' => $user->ref_no,
                'username' => $token,
                'backend' => $backend,
                'password' => 'Az123456',
            ];
            if ($user->email) $push_data['email'] = $user->email;
            if ($user->ref_no) $push_data['ref_no'] = $user->ref_no;
            $result = Gateway::pushRemoteUser($push_data);
            $user->gapper_id = $result['id'];
            $user->save();

            //同步课题组、组织机构、role
            if ($user->group->id && $user->gapper_id){
                $group_data = [
                    'USER_ID' => $user->gapper_id,
                    'payload' => [$user->group->gapper_id]
                ];
                Gateway::pushRemoteUserGroup($group_data);
            }
            $lab = Q("{$user} lab")->current();
            if ($lab->gapper_id){
                $group_data = [
                    'USER_ID' => $user->gapper_id,
                    'payload' => [$lab->gapper_id],
                    'lab_id'  => $lab->gapper_id
                ];
                Gateway::pushRemoteUserGroup($group_data);
            }

            //角色
            $roles = Q("{$user} role[weight>0][gapper_id]");
            if ($roles->total_count()){
                foreach ($roles as $role){
                    $push_data = [
                        'USER_ID' => $user->gapper_id,
                        'rid' => $role->gapper_id,
                        'gid' => $remote_group['id'],
                    ];
                    Gateway::pushRemoteUserRole($push_data);
                }
            }
        }
        Upgrader::echo_success("Done.");
    }

}
