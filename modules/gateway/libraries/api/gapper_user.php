<?php

class API_Gapper_User extends API_Common
{
    public static function updateUserFromGapper($remote_user)
    {
        $gapper_id = $remote_user['id'];

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
            $remote_user['email'] ? $user->email = $remote_user['email'] : '';
            $remote_user['phone'] ? $user->phone = $remote_user['phone'] : '';
            $remote_user['ref_no'] ? $user->ref_no = $remote_user['ref_no'] : '';
            $user->atime = $user->id ? $user->atime : time();
            if ($remote_user['status'] == 2) $user->atime = 0;
            if ($remote_user['status'] == 4) $user->atime = time();
            $user->dfrom = $remote_user['begin_time'] ? strtotime($remote_user['begin_time']) : 0;
            $user->dto = $remote_user['expire_time'] ? strtotime($remote_user['expire_time']) : 0;
            if (!$user->token) {
                $user->token = $remote_user['identities'][0]['identity'] . "|" . $remote_user['identities'][0]['source'];
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
            $labPi = [];
            if (isset($roles['roles'])) {
                foreach ($roles['roles'] as $role) {
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
        }

        $data = [];
        if ($user->id) {
            $data['user'] = [
                'source_id' => $user->id,
                'source_name' => LAB_ID,
                'token' => $user->token,
                'name' => $user->name,
                'ref_no' => $user->ref_no,
                'atime' => $user->atime,
            ];
            foreach (Q("$user lab") as $lab) {
                $data['lab'] = [
                    'source_id' => $lab->id,
                    'source_name' => LAB_ID,
                    'name' => $lab->name,
                    'atime' => $lab->atime,
                    'type' => Q("$user<pi $lab")->total_count() ? 'pi': '',
                ];
            }
        }
        return $data;
    }
    public function updateUser($gapper_id, $children = true)
    {
	    $this->_ready();

        $default_lab = Lab_Model::default_lab();

        $tag_group = Tag_Model::root('group');

        $remote_user = Gateway::getRemoteUserDetail([
            'USER_ID' => $gapper_id,
            'includes' => 'identities,groups'
        ]);

        $datas = [];
        $datas[$remote_user['id']] = self::updateUserFromGapper($remote_user);
        if (!$children) return $datas;
        if($datas[$remote_user['id']]['lab']) {
            $lab = o('lab', $datas[$remote_user['id']]['lab']['source_id']);
            $total = -1;
            $page = 1;
            $perPage = 100;
            while ($total === -1 || $total >= ($page - 1) * $perPage) {
                $users = Gateway::getRemoteUser([
                    'pp' => $perPage,
                    'pg' => $page,
                    'includes' => 'identities',
                    'group_id' => "$lab->gapper_id"
                ]);
                $total = $users['total'];
                foreach ($users['items'] as $remote_user) {
                    $total = $users['total'];
                    $datas[$remote_user['id']] = self::updateUserFromGapper($remote_user);
                }
                $page++;
            }
        }
        return $datas;
    }
}
