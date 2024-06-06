<?php

class API_YiQiKong_User extends API_Common {

	/* 配合 17kong-reserv ( websocket) 请求创建的API机制, 取消所有debade存在的断连机制 */

	public static $errors = [
        1001 => '请求非法!',
        1002 => '您没有权限进行该操作!', 
        1003 => '用户注册失败',
        1004 => '找不到匹配的用户',
        1010 => '用户信息不合法!',
        1011 => '用户保存失败',
        1012 => '该二维码已被他人绑定',
        1013 => '该二维码已失效，请刷新web端二维码并重新扫描'
    ];
    
    private function _checkAuth() {
        $yiqikong = Config::get('rpc.servers')['yiqikong'];
        if (!isset($_SESSION['yiqikong.client_id']) || 
            $yiqikong['client_id'] != $_SESSION['yiqikong.client_id']) {
            throw new API_Exception('Access denied.', 401);
        }
    }

    private static function _checkValid($user) {
        $yiqikong_lab_id = YiQiKong_Lab::default_lab()->id;
        if ( !Q("$user lab[id={$yiqikong_lab_id}]")->total_count() ) {
            $time = time();

            if (($user->dto &&  $user->dto < $time) 
                || ($user->dfrom &&$user->dfrom > $time)
                || ! $user->atime) {
                throw new API_Exception(self::$errors[1002]);
            }

        }
    }

    public function create($data) {
        $this->_checkAuth();

        $validate = [
            'name', 'email', 'phone',
            'institution', 'password',
        ];

        foreach ($validate as $key) {
            if (!isset($data[$key])) {
                throw new Error_Exception(self::$errors[1010]);
            }
        }

        $user = O('user', ['email' => $data['email']]);
        
        if ($user->id) {
            $user->gapper_id = $data['gapper'];
            $user->outside = 1;
            $user->save();
            
            $token = $user->token;
        }
        else{
            $token = Auth::make_token($data['email'], 'database');
            $user->token = $token;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'];
            $user->gapper_id = $data['gapper'];
            $user->member_type = 0;
            $user->outside = 1;

            $lab = O('lab', ['name' => $data['institution']]);
            if (!$lab->id) {
                $lab->name = $data['institution'];
                $lab->save();
            }

            
            $auth = new Auth($token);
            if(!$auth->create($data['password'])) {
                throw new Error_Exception(self::$errors[1003]);
            }

            $user->save();

            if (!$user->id) {
                $auth->remove(); //添加新成员失败，去掉已添加的 token
                throw new Error_Exception(self::$errors[1011]);
            }
            $user->connect($lab);
            
            if (!$lab->owner->id) {
                $lab->owner = $user;
                $lab->save();
                $user->connect($lab, 'pi');
            }
        }

        $uuid = uniqid();
        $cache = Cache::factory('redis');
        $cache->set($uuid, $token, 600);
        return $uuid;
    }

    /**
     * yiqikong 用户更新接口
     * 目前仅仅实现了修改yiqikong_id的逻辑
     *
     * @param [array] $data
     * @return void
     */
    public function update($data) {
        $this->_ready(); // 别再用老机制了 全都用API_Common

        $validate = ['yiqikong_id'];

        foreach ($validate as $key) {
            if (!isset($data[$key])) {
                throw new API_Exception(self::$errors[1010]);
            }
        }

        if (isset($data['id'])) {
            $user = O('user', ['id' => $data['id']]);
            if (!$user->id) return true;
            $cache = Cache::factory('redis');
            if ($data['yiqikong_id'] && $cache->get("qrcode_{$user->id}") != $data['code']) throw new API_Exception(self::$errors[1013]);
        } elseif (Module::is_installed('sync') && isset($data['uuid'])) {
            $user = O('user', ['uuid' => $data['uuid']]);
            if (!$user->id) return true;
        }

        if ($data['yiqikong_id'] && $user->yiqikong_id && $data['yiqikong_id'] != $user->yiqikong_id) throw new API_Exception(self::$errors[1012]);
        
        //把之前绑定的仪器控id缓存给删除关注用
        $tmp_yiqikong_id = $user->yiqikong_id;

        if (isset($data['yiqikong_id'])) $user->yiqikong_id = $data['yiqikong_id'];
        if (!$user->save()) return false;

        // 获取人员类型数组
        foreach (User_Model::get_members() as $key => $value) foreach ($value as $k => $v) {
            if ($k != $user->member_type) continue;
            $user_role = $key;
            $user_member = $v;
            break;
        }

        // 获取人员组织机构数组
        $root = Tag_Model::root('group');
        //绑定节点只有推送关注仪器
        $root_path = ROOT_PATH;
        $site_id = SITE_ID;
        $lab_id = LAB_ID;
        if ($data['yiqikong_id']) {//绑定
            putenv("Q_ROOT_PATH={$root_path}");
            $cmd = "SITE_ID={$site_id} LAB_ID={$lab_id} php {$root_path}cli/cli.php yiqikong_user update_follows {$user->id} >/dev/null 2>&1 &";
            exec($cmd, $output);
        } else {//解绑
            putenv("Q_ROOT_PATH={$root_path}");
            $cmd = "SITE_ID={$site_id} LAB_ID={$lab_id} php {$root_path}cli/cli.php yiqikong_user delete_follows {$user->id} {$tmp_yiqikong_id} >/dev/null 2>&1 &";
            exec($cmd, $output);
        }
        
        return [
            'id' => $user->id,
            'lab_id' => LAB_ID,
            'lab_name' => Config::get('page.title_default'),
            'role' => Q("$user role")->to_assoc('id', 'name'),
            'type' => [$user_role, $user_member],
            'group' => Q("{$user} tag_group[root={$root}]")->to_assoc('id', 'name'),
            'phone' => $user->phone,
        ];
    }

    public function follow($userId, $sourceId, $sourceName = 'equipment') {
        $this->_checkAuth();
        if (!$userId || !$sourceId) throw new API_Exception(self::$errors[1001]);

        $user = O('user', ['gapper_id' => $userId]);

        if (!$user->id) throw new API_Exception(self::$errors[1010]);

        self::_checkValid($user);

        $object = O($sourceName, ['yiqikong_id' => $sourceId]);

        return $user->follow($object);
    }

    public function unfollow($userId, $sourceId, $sourceName = 'equipment') {
        $this->_checkAuth();
        if (!$userId || !$sourceId) throw new API_Exception(self::$errors[1001]);

        $user = O('user', ['gapper_id' => $userId]);

        if (!$user->id) throw new API_Exception(self::$errors[1010]);

        self::_checkValid($user);

        $object = O($sourceName, ['yiqikong_id' => $sourceId]);

        return $user->unfollow($object);
    }

    public function add_tmp_user($form)
    {
        $sender = O('user');
        $sender->creator = $form['creator'];
        $sender->ref_no = NULL;
        $sender->name = $form['name'];
        $sender->email = $form['email'];
        $sender->organization = $form['organization'];
        $sender->tax_no = $form['tax_no'];
        $sender->phone = $form['phone'];

        Event::trigger('signup.save_extra_field', $sender, $form);

        $sender->save();
        if (!$sender->id) {
            return ['id' => 0];
        }
        $sender->connect(Equipments::default_lab());
        Event::trigger('eq_sample.tmpuser_register', $sender, $form);
        return ['id' => $sender->id];
    }

}