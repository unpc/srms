<?php

use \Pheanstalk\Pheanstalk;
class CLI_YiQiKong_User {
	const ROUTINGKEY_USER = 'user';
    const EZ_Q = 'user[email*=@111.com|email*=@123.com|email*=@1.com|email*=@aaa.com|email*=@abc.com|email*=@a.com|email^=111|email^=123|email^=abc|email^=aa]';

	static function update_user_check ($id) {
        $rpc_conf = Config::get('rpc.servers')['yiqikong'];
        $url = $rpc_conf['url'];
        $rpc = new RPC($url);
        if (!$rpc->YiQiKong->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
            throw new RPC_Exception;
        }

        $user = O('user', $id);
        $status = $rpc->YiQiKong->User->status($user->email);

		$db = Database::factory();
        list($token, ) = Auth::parse_token($user->token);
		$password = $db->value("SELECT `password` FROM `%s` WHERE `token`='%s'", '_auth', $token);

        $gapper_id = 0;
        $data = [];
        $data['labId'] = LAB_ID;
        $data['siteId'] = SITE_ID;
        $data['name'] = $user->name;
        $data['email'] = $user->email;
        $data['password'] = uniqid();
        $data['institution'] = $user->group->name;
        $data['phone'] = $user->phone; 
        $data['crypt'] = $password;
        switch ($status['type']) {
            case 'yiqikong':
                echo "用户:{$user->name}, ID:{$user->id}为仪器控用户 \n";
                return FALSE;
                break;
            case 'gapper':
                $gapper_id = $rpc->YiQiKong->User->add($data, 'add');
                break;
            case 'none':
                $gapper_id = $rpc->YiQiKong->User->add($data, 'create');
                break;
            default:
                echo "用户:{$user->name}, ID:{$user->id}什么鬼 \n";
                return FALSE;
                break;
        }

        $user->gapper_id = $gapper_id;
        $user->outside = 1;
        $user->save();
	}

	static function update_users_check ($group_id) {
        if ($gapper_id) {
            $group = O('tag_group', $group_id);
            $users = Q("{$group} user");
        }
        else {
            $users = Q("user");
        }
        $emails = Q(EZ_Q)->to_assoc('email', 'email');
		$start = $num = 0;
		$step = 10;
		$total = $users->total_count();

		while ($start <= $total) {

			$people = $users->limit($start, $step);

			foreach ($people as $user) {
                if(in_array($user->email, $emails)) {
                    continue;
                }
				self::update_user($user->id);
                if ($num % 500 == 0) {
                    sleep(1);
                }
                $num ++;
				echo "Update User[".$user->id."]\n";
			}
			$start += $step;
		}

	}

    static function check_email () {
        $users = Q(self::EZ_Q);

        if ($users) foreach ($users as $user) {
            echo "[{$user->id}]{$user->name}的邮箱为{$user->email} \n";
        }
    }

    private static function update_user($user) {
        $after = [
            'from' => date('Y-m-d', $user->dfrom),
            'to' => date('Y-m-d', $user->dto),
        ];

        $config = Config::get('beanstalkd.opts');
		$mq = new Pheanstalk($config['host'], $config['port']);

		$payload = [
			'method' => 'PATCH',
			'header' => ['x-yiqikong-notify' => TRUE],
			'path' => "user/{$user->yiqikong_id}",
			'body' => $after,
		];
	
		$mq
			->useTube('user')
			->put(json_encode($payload, TRUE));
		
		return TRUE;
    }

    static function update_users() {
		$start = $num = 0;
		$step = 100;
		$total = $users->total_count();

		while ($start <= $total) {
			$users = Q("user[yiqikong_id]")->limit($start, $step);
			foreach ($users as $user) {
				self::update_user($user);
				echo "Push User[" . $user->id . "]\n";
			}
			$start += $step;
		}
    }

    private static function update_follow($follow) {

        $config = Config::get('beanstalkd.opts');
        $mq = new Pheanstalk($config['host'], $config['port']);

		$payload = [
			'method' => 'post',
			'header' => ['x-yiqikong-notify' => TRUE],
			'path' => "follow",
			'body' => [
                'user' => $follow->user->yiqikong_id,
                'source_name' => $follow->object_name,
                'source_uuid' => $follow->object->yiqikong_id
            ]
		];
	
		$mq
			->useTube('user')
			->put(json_encode($payload, TRUE));
		
		return TRUE;
    }

    static function update_follows($user_id) {
        $start = $num = 0;
        $step = 100;
        $user = O("user",$user_id);
        $total = Q("follow[user=$user][object_name=equipment]")->total_count();

		while ($start <= $total) {
			$follows = Q("follow[user=$user][object_name=equipment]:sort(id A)")->limit($start, $step);
			foreach ($follows as $follow) {
				self::update_follow($follow);
				echo "Push follow[" . $follow->id . "]\n";
			}
			$start += $step;
		}
    }
    //手动执行 该功能上线之前关注只有负责仪器。缺少数据
    static function update_follows_all() {
        $start = $num = 0;
        $step = 100;
        $total = Q("follow[object_name=equipment]")->total_count();

		while ($start <= $total) {
            $follows = Q("follow[object_name=equipment]:sort(id A)")->limit($start, $step);
			foreach ($follows as $follow) {
                if ($follow->user->yiqikong_id) {
                    self::update_follow($follow);
				    echo "Push follow[" . $follow->id . "]\n";
                }
			}
			$start += $step;
		}
    }

    private static function delete_follow($follow, $tmp_yiqikong_id) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $config = Config::get('beanstalkd.opts');
        $mq = new Pheanstalk($config['host'], $config['port']);

		$payload = [
			'method' => 'delete',
			'header' => ['x-yiqikong-notify' => TRUE],
			'path' => "follow/0",
			'body' => [
                'user' => $tmp_yiqikong_id,
                'source_uuid' => $follow->object->yiqikong_id
            ]
		];
	
		$mq
			->useTube('user')
            ->put(json_encode($payload, TRUE));
            
        return TRUE;
    }

    static function delete_follows($user_id, $tmp_yiqikong_id) {
        $start = $num = 0;
        $step = 100;
        $user = O("user",$user_id);
        $total = Q("follow[user=$user][object_name=equipment]")->total_count();

		while ($start <= $total) {
			$follows = Q("follow[user=$user][object_name=equipment]:sort(id A)")->limit($start, $step);
			foreach ($follows as $follow) {
				self::delete_follow($follow, $tmp_yiqikong_id);
				echo "Delete follow[" . $follow->id . "]\n";
			}
			$start += $step;
		}
    }
    
    //【通用】RQ191405 同步历史权限功能 功能上线前执行
    static function sync_user_tag() {
        
        $users = Q("user[yiqikong_id!='']");//所有有仪器控id的用户
        foreach ($users as $user) {
            list($token, $backend) = explode('|', $user->token);
            $tag_name = [];
            if ($token == 'genee') $tag_name[] = 'genee';
            if ($user->access('管理所有内容')) $tag_name[] = 'admin';
            if (count($tag_name) > 0 ) {
                $config = Config::get('beanstalkd.opts');
                $mq = new Pheanstalk($config['host'], $config['port']);
                $payload = [
                    'method' => "post",
                    'header' => ['x-yiqikong-notify' => TRUE],
                    'path' => "tag",
                    'body' => [
                        'user' => $user->yiqikong_id,
                        'tag_name' => json_encode($tag_name),
                        'tag_type' => 'role'
                    ]
                ];
                $mq
                    ->useTube('user')
                    ->put(json_encode($payload, TRUE));
            }
        }
    }
}
