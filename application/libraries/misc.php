<?php

class Misc extends _Misc {

	static function byte_swap32($num) {
		list(, $num) = unpack('V', pack('N', $num + 0));
		return $num;
	}

	static function byte_swap16($num) {
		list(, $num) = unpack('v', pack('n', $num + 0));
		return $num;
	}
	
	static function uint32_to_string($dw) {
		return (string)sprintf('%u', $dw);
	}

}