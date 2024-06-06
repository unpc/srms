<?php
class CLI_Test {
	static function api() {
		//不可用，需要重写
		$rpc = new RPC('http://xiaopei.li.cf.gin.genee.cn/test/api');
		$greeting = $rpc->hello('world');
		$locations = $rpc->equipment->get_locations();
	}

	static function sms($sender_id=null, $receiver_id=null){
		if (!$sender_id) {
			die("usage:\n  test_sms.php sender_user_id [receiver_user_id]\n");
		}

		$sender = O('user', $sender_id);

		if (!$sender->id) {
			die('sender 不存在');
		}

		if ($receiver_id) {
			$receiver = O('user', $receiver_id);

			if (!$receiver->id) {
				die('receiver 不存在');
			}
		}
		else {
			$receiver = $sender;
		}

		Notification::send('sms.test', $sender, [$receiver]);
	}
}