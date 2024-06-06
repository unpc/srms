<?php

class Device_File {

	private $_hash;
	private $_base;

	private $_meta;

	public $is_error;	//readonly
	public $is_empty;	//readonly
	public $is_uploaded; //readonly

	function __construct($hash) {
		$this->_hash = $hash;
		$this->_base = self::_get_base($hash);
		$this->is_error = !is_dir($this->_base);
		$this->is_uploaded = FALSE;
		$this->load_meta();	
	}

	function load_meta() {
		$this->_meta = @json_decode(@file_get_contents($this->_base.'meta'), TRUE);
		if (!$this->_meta['md5'] || !$this->_meta['size']) {
			$this->is_empty = TRUE;
		}
		else {
			$this->is_empty = FALSE;
		}
	}

	function save_meta() {
		@file_put_contents($this->_base.'meta', @json_encode($this->_meta));
	}

	function set_meta($key, $value) {
		$this->_meta[$key] = $value;
	}

	function start_upload($size, $md5) {
		$this->_meta['size'] = $size;
		$this->_meta['md5'] = $md5;
		$this->_meta['offset'] = 0;
		$this->save_meta();
	}

	function check_upload($size, $md5) {
		if ($this->_meta['size'] != $size || $this->_meta['md5'] != $md5) return FALSE;
		return TRUE;
	}

	private function chunk_write(&$data) {
		$fh = fopen($this->_base . 'part');
		if ($fh) {
			$size = strlen($data);
			fseek($fh, $this->_meta['offset']);
			fwrite($fh, $data, $size);
			$this->_meta['offset'] += $size;

			fclose($fh);

			if ($this->_meta['offset'] == $this->_meta['size']) {
				$this->is_uploaded = TRUE;
			}

			$this->save_meta();

			return TRUE;
		}

		return FALSE;
	}

	function write($offset, &$data, $md5) {
		if (!$data) return FALSE;
		if ($offset != $this->_meta['offset']) return FALSE;
		if (md5($data) != $md5) return FALSE;

		return $this->chunk_write($data);
	}

	function finish() {
		//check md5
		$file = $this->_base . 'part';
		$md5 = md5_file($file);
		if ($md5 == $this->_meta['md5']) {
			@rename($file, $this->_meta['target']);
		}
		//删除目录
		File::rmdir($this->base);
	}

	private static function _get_base($hash) {
		return (Config::get('system.tmp_dir')?:'/tmp/') . 'device_file/'.$hash . '/';
	}

	static function register($file) {
		do {
			$hash = md5(rand(0,microtime()));
			$path = self::_get_base($hash);
		}
		while (!@mkdir($path));

		$this->_hash = $hash;
		$this->_base = $path;

		$this->_meta['target'] = $file;
		$this->save_meta();
	}

}
