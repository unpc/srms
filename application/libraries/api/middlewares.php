<?php
class API_Middlewares
{
    public static function gapperTokenAuth($e)
    {
        $token = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION']);
        if (!$token) {
            $token = $_SERVER['HTTP_X_GAPPER_OAUTH_TOKEN'];
        }
        if (!$token) {
            return false;
        }
        $user_token = L("gapperTokenOwner");
        if ($user_token['id']) {
            return (bool) $user_token['id'];
        }
        try {
            $server = Config::get('gateway.server');
            $rest = new REST($server['url']);
            $user_token = $rest->get('/auth/owner', ['gapper-oauth-token' => $token]);
            Cache::L("gapperTokenOwner", $user_token);
            $e->return_value = (bool) $user_token['id'];
        } catch (\REST_Exception $e) {
            return false;
        }
    }
    public static function getGapperUserByToken($e)
    {
        $token = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION']);
        if (!$token) {
            $token = $_SERVER['HTTP_X_GAPPER_OAUTH_TOKEN'];
        }
        if (!$token) {
            return;
        }
        $user_token = L("gapperTokenOwner");
        if ($user_token['type'] == 'user') {
            $server = Config::get('gateway.server');
            $rest = new REST($server['url']);
            $remote_user = $rest->get('/current-user', ['gapper-oauth-token' => $token]);
            self::auto_connect_gapper_user($token,$remote_user);
            $user = O("user", ['gapper_id' => $remote_user['id']]);
            if (!$user->id) {
                throw new Exception(T('找不到对应用户, 请移步网页端进行绑定'), 401);
            }
            Cache::L("gapperUser", $user);
            Cache::L("ME", $user);
            $e->return_value = true;
        }
    }


    public static function getLimsCurrentUser($e, $params, $data, $query)
    {
        $key = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION']);
        $cache = Cache::factory('redis');
        $user_id = $cache->get($key);

        $user = O('user', $user_id);
        if ($user->id) {
            $e->return_value = [
                'id' => $user->id,
                'name' => $user->name,
                'token' => $user->token,
                'email' => $user->email,
                'ref_no' => $user->ref_no,
                'card_no' => $user->card_no,
                'dfrom' => $user->dfrom,
                'dto' => $user->dto,
                'organization' => $user->organization,
                'group' => $user->group->name,
                'gender' => $user->gender,
                'major' => $user->major,
                'phone' => $user->phone,
                'mobile' => $user->mobile,
                'address' => $user->address,
                'member_type' => $user->member_type,
            ];
        } else {
            $e->return_value = [];
        }
        return;
    }

    public static function auto_connect_gapper_user($token,$gapper_user){

        $gapper_id = $gapper_user['id'];
        $user = O('user', ['gapper_id' => $gapper_id]);

        if (!$user->id || !$user->lastSynctime || $user->lastSynctime < time() - 5 * 60) {
            // 注册用户
            $remoteUser = Uno_Util::get_remote_user($token);
            $user->name = $remoteUser['name'];
            $user->gapper_id = $remoteUser['gapper_id'];
            $remoteUser['email'] ? $user->email = $remoteUser['email'] : '';
            $remoteUser['phone'] ? $user->phone = $remoteUser['phone'] : '';
            $remoteUser['ref_no'] ? $user->ref_no = $remoteUser['ref_no'] : '';
            $user->atime = !$user->id ? time() : $user->atime;
            $user->token = $remoteUser['token'];
            $user->lastSynctime = time();
            $user->save();

            if ($user->id) {

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
                        }
                        if (array_key_exists($lab->gapper_id, $labPi) && in_array('实验室负责人', $labPi[$lab->gapper_id]) && $lab->owner->id != $user->id) {
                            $lab->owner = $user;
                            $lab->save();
                        }
                        $user->connect($lab);
                        //目前做的是，保留最后一个，所以ID的key 固定
                        $labIds[$user->id] = $lab->id;
                        if (array_key_exists($lab->gapper_id, $labPi) && in_array('实验室负责人', $labPi[$lab->gapper_id])) {
                            $user->connect($lab, 'pi');
                        }
                    }
                } else {
                    // $lab = Lab_Model::default_lab();
                    // $user->connect($lab);
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
                    foreach ($remoteUser['group'] as $grp){
                        $user->disconnect_all('group');
                        $local_group = O('tag_group', ['gapper_id' => $grp['id']]);
                        if (!$local_group->id) {
                            $local_group->name = $grp['name'];
                            $local_group->root = $tag_group;
                            $local_group->gapper_id = $grp['id'];
                            $local_group->parent = O('tag_group',['gapper_id'=>$grp['parent_id']]);
                            $local_group->save();
                        }
                        $user->group = $local_group;
                        $user->save();
                        $user->connect($local_group);
                    }
                }
            } else {
                Log::add(strtr('uno 新用户注册失败 %accessToken 的新用户注册失败', ['%accessToken' => $accessToken]), 'uno');
            }
        } else {
            if (Q("{$user} lab")->total_count() == 0) {
                $remoteUser = Uno_Util::get_remote_user();
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
        }
    }

}
