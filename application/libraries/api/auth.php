<?php

class API_Auth {

	private static function _cache_table() {
		return Config::get('auth.rpc_token_cache') ?: '_auth_rpc_token_cache';
	}

    function verify($token, $password) {

        $auth = new Auth($token);
		if (TRUE == $auth->verify($password)) {
			$db = Database::factory();
			$table = self::_cache_table();
			//确认表结构存在
			$db->prepare_table($table,
				[
					'fields' => [
						'key'=>['type'=>'varchar(32)', 'null'=>FALSE, 'default' => 0],
						'token'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default' => ''],
						'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
					], 
					'indexes' => [ 
						'PRIMARY'=>['type'=>'primary', 'fields'=>['key']],
						'unique' => ['type'=>'unique', 'fields'=>['token']],
						'mtime'=>['fields'=>['mtime']],
					]
				]
			);

			$now = time();
			//清理表, 有效期默认15min 就是900秒
			$db->query('DELETE FROM `%s` WHERE mtime<%d', $table, $now - intval(Config::get('auth.rpc_token_lifetime')?:900));

			//查看表中是否有现成的key
			$key = $db->value('SELECT `key` FROM `%s` WHERE `token`="%s"', $table, $token);
			if (!$key) {
				$key = md5($token.':'.mt_rand().':'.microtime());
				$sql = sprintf('INSERT INTO `%s` (`key`,`token`,`mtime`) VALUES ("%s", "%s", %d)', $table, $key, $token, $now);
				$result = $db->query($sql);
			}
			else {
				self::touch($key);
			}

		}
		else {
			$key = NULL;
		}

		return $key;
	}

	// 用于更新记录的mtime 保证其短期内不过期
	function touch($key) {
		$db = Database::factory();
		$table = self::_cache_table();
		$now = time();
		$db->query('UPDATE `%s` SET mtime=%d WHERE `key`="%s"', $table, $now, $key);
	}		

	function get_user_info($key) {
		$db = Database::factory();
		$table = self::_cache_table();
		$sql = sprintf('SELECT `token` FROM `%s` WHERE `key`="%s"', $table, $key);
		$token = $db->value($sql);
		$token = Auth::normalize($token);
		$user = O('user', ['token'=>$token]);
		if ($user->id) {
			return [
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
		}
		else {
			return [];
		}
	}
	
    function get_backends() {
        $backends_raw = Config::get('auth.backends', []);
        $backends = [];
        foreach($backends_raw as $key=>$value) {
            $backends[$key]  = $value['title'];
        }
        return $backends;
    }
	
	function get_default_backend() {
		return Config::get('auth.default_backend');
	}
}
