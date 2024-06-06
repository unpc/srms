<?php

class Privacy {

	static function email($mail) {
		list($name, $address) = explode('@', $mail, 2);
		if (preg_match('/^(\w{2})(.+)(\w)$/', $name, $parts)) {
			$name = $parts[1] . str_repeat('*', strlen($parts[2])) . $parts[3];
		}
		return $name . '@' . $address;
	}

	static function phone($phone) {
		$phone = preg_replace('/[^\d]+/', '', $phone);
		$pad_len = strlen($phone) - 4;
		if ($pad_len > 0) {
			$phone = str_pad(substr($phone, -4), $pad_len, '*', STR_PAD_LEFT);
		}
		return $phone;
	}
}
