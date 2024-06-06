<?php

class Wiki_Preview {

	private static $handlers = [
		'jpg' => 'Wiki_Preview::show_image',
		'png' => 'Wiki_Preview::show_image',
		'gif' => 'Wiki_Preview::show_image',
	];

	// 返回预览文件路径 
	static function url($path, $size=NULL) {
	
		$path = File::relative_path($path);
		$salt = Config::get('wiki.preview.crypt_salt');
		$path = Cipher::encrypt($path, $salt, TRUE);
		$url = Config::get('wiki.preview.url');

		return URI::url(strtr($url, ['%file'=>URI::encode($path), '%size'=>$size]));
	}

	// 返回值: 返回缓冲key
	static function show($path, $size=NULL) {

		$salt = Config::get('wiki.preview.crypt_salt');
		$path = Cipher::decrypt($path, $salt, TRUE);

		try {

			if (FALSE!==strpos($path, '://')) {
				// 外部链接, 将文件先抓取到本地临时目录
				$tmp_file = Config::get('system.tmp_dir').md5($path).'.'.File::extension($path);
				if (!file_exists($tmp_file)) {
					cURL::download($path, $tmp_file);
				}
				$path = $tmp_file;
			} 
			else {
				$base_path = File::relative_path(Wiki::media_base_dir());
				if(!File::in_paths($path, [$base_path])) {
					//非法路径
					throw new Error_Exception;
				}
				$path = ROOT_PATH.$path; //补全绝对路径
			}
			
			//获取文件格式, 根据扩展名
			$format = strtolower(File::extension($path));
			
			if (preg_match('/^(\d+)(?:x(\d+))?$/', $size, $parts)) {
				$size = [$parts[1], $parts[2]];
			} else {
				//$size格式不对 直接设置为空 显示默认尺寸
				$size = NULL;
			}
			
			if(!self::$handlers[$format]) throw new Exception;
			
			call_user_func(self::$handlers[$format], $path, $size);
				
		} catch (Error_Exception $e) {
			echo T('%file无法预览!', ['%file'=>File::basename($path)]);			
		}
		
		//if($tmp_file)@unlink($tmp_file);
		
		exit;
	}

	static function show_image($path, $size=NULL) {

		$image = Image::load($path);
		if($size) {
			$image->resize($size[0], $size[1] ?: ($size[0]*5), TRUE);
			//$image->crop_center($size[0], $size[1]);
		}
		$image->show('png');

	}

}
