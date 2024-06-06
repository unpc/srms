<?php

class SMS {

	private $_database;
	private $_db;
	private $_sender;
	private $_receiver;
	private $_content;
	private $_number;
	private $_error;
	private $_msg_part;
	private $_udh;

	function __construct($sender = NULL) {
		$this->_udh = [
			'udh_length' => '05',
			'identifier' => '00',
			'header_length' => '03',
			'reference' => '00',
			'msg_count' => 1,
			'msg_part' => 1
		];
		$this->_sender = $sender->id ? $sender : L('ME');
		$this->_database = Config::get('system.sms.database', 'sms');
		$this->_db = Database::factory($this->_database);
		$this->_error = [];
		$this->_msg_part = [];

	}

	function to($receiver = NULL) {
		$this->_receiver = $receiver;
		$this->_number = (int)$this->conver_phone($receiver);
	}

	private function conver_phone($receiver) {
		/*
		 *	由于现在人的phone没有严格的检测标准,所以任何人都可以随意填写自己的phone为手机或者是电话信息，故无法正确查询出来手机号，此处应该纠正
		 */
		return H($receiver->phone);
	}

	/*
	function body($body) {

		$x = 1;
		if (strlen($body) <= 126) {
			$this->_msg_part[$x]['udh'] = '';
			$this->_msg_part[$x]['msg'] = $body;
		}
		else {
			$msg = str_split($body, 126);
			$ret = mt_rand(1, 255);
			$this->_udh['msg_count'] = $this->_dechex_str(count($msg));
			$this->_udh['reference'] = $this->_dechex_str($ret);

			foreach ($msg as $part) {
				$this->_udh['msg_part'] = $this->_dechex_str($x);
				$this->_msg_part[$x]['udh'] = join('', $this->_udh);
				$this->_msg_part[$x]['msg'] = $part;
				$x ++;
			}
		}

		krsort($this->_msg_part);

	}
	 */

	private function _dechex_str($ret) {
		return $ret <= 15 ? '0'.dechex($ret) : dechex($ret);
	}

	/*
	function send() {
		if (!$this->_number) {
			$this->error[] = '没有找到需要发送的手机号码';
			return false;
		}

		$db = $this->_db;

		foreach ($this->_msg_part as $id => $sms) {
			$query = strtr("INSERT INTO `outbox` (`DestinationNumber`,`TextDecoded`, `RelativeValidity`, `Coding`) VALUES ('%number','%msg', %relative, '%coding')", array(
					'%number' => $this->_number,
					'%msg' => $sms['msg'],
					'%relative' => 1,
					'%coding' => 'Unicode_No_Compression'
			));

			$db->query($query);
		}

		$this->clean();
	}
	 */

    static function send($phone, $body) {
        $database = Config::get('system.sms.database', 'sms');

        $db = Database::factory($database);
        $query = "INSERT INTO `outbox` (`DestinationNumber`,`TextDecoded`, `RelativeValidity`, `Coding`) VALUES ('%d','%s', %d, '%s')";

        foreach(self::body($body) as $text ) {
            $db->query($query, $phone, $text, 1, 'Unicode_No_Compression');
        }

    }

	static function body($body) {

        $body = trim($body);

        //中英混合的字数，两个英文算1，'中文1中2' 算4
        $strlen = (mb_strlen($body) + strlen($body))/4;
        $strcut_length = Config::get('sms.strcut_length');

        if ($strlen < $strcut_length) {
            return [$body];
        }
        else {

            $start = 0;
            $ret = [];
            while ($start < mb_strlen($body)) {


                $i = 0;
                $old_len = 0;
                $old_str = '';
                $tmp_len = 0;
                $tmp_str = '';

                //每次多截取一个字符，如果字数(英文两个算一个)不大于$strcut_length，则继续截取
                while(1) {
                    $old_len = $tmp_len;
                    $old_str = $tmp_str;

                    $tmp_str = mb_substr($body, $start, $strcut_length + $i);
                    $tmp_len = (mb_strlen($tmp_str) + strlen($tmp_str))/4;

                    if($tmp_len == $old_len) {
                        break;
                    }

                    if($tmp_len > $strcut_length) {
                        $tmp_str = $old_str;
                        break;
                    }

                    $i++;
                }

                $ret[] = $tmp_str;
                $start += $strcut_length + $i - 1;

            }
            return $ret;
        }
	}

	private function clean() {
		$this->_receiver = NULL;
		$this->_number = NULL;
	}

}
