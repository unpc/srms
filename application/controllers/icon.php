<?php

class Icon_Controller extends Controller {

	function _before_call($method, &$params) {
		$this->ignore_extensions['index'][] = 'png';
		parent::_before_call($method, $params);
	}

	function index($name='', $id=0, $size='128'){
		if ($name) O($name, $id)->show_icon($size);
	}

    function decode() {

        //删除src的data;:
        $code = trim(Input::form('code'), 'data;:');

        //获取header type
        list($header, $code) = explode(';', $code);

        //抛出header
        header("Content-type: $header");

        //decode
        echo base64_decode(trim($code, 'base64, '));
    }
}
