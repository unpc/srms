<?php
	
class Upload_API_Controller extends Controller 
{
	public function index()
	{
		/*
		* file: file_data
		* uid : '用户卡号/用户ID/用户token',
		* eid : '上传到仪器的当前正在使用的使用记录，仪器control_address/仪器id',
		* nfs_root : '上传到nfs中的目录路径 public/lab/private',
		* nfs_path : '可能存在的nfs中的文件路径 path/文件夹/'
		* client_id: 67649afd-6bbb-479f-a6ff-5060a4376b9b
		* client_secret: c2a0c82f-645f-408c-a425-a19d917c2e1f
		*/
		
		$formDataParser = FormData_Parser::parser();
		$form   = $formDataParser['form'];
		
		$file = count($_FILES) ? $_FILES['file'] : $form['file'];
		$options = count($_POST) ? $_POST : $form;

		$response = [
			'status'  => 'error',
			'code'	  => 500,
			'message' => 'Bad Request'
		];
		
		try {
			
			if (!$options['client_id'] || !$options['client_secret']) {
				throw new Error_Exception(I18N::T('nfs_share', '参数错误, 无法信任服务器!'));
			}
			$sources = Config::get('nfs_share.auth_keys', []);
			$auth = false;
			foreach ($sources as $key => $secret) {
				if ($key == $options['client_id'] && $secret == $options['client_secret']) {
					$auth = true;
					break;
				}
			}
			if ( !$auth ) {
				throw new Error_Exception(I18N::T('nfs_share', '参数错误, 无法信任服务器!'));
			}
			
			if (!$file || !$file['tmp_name']) {
				throw new Error_Exception(I18N::T('nfs_share', '参数错误, 请选择上传的文件!'));
			}
			
			$post_size = ini_get('post_max_size');

			if ($file['error']) {
				throw new Error_Exception(I18N::T('nfs_share', '您上传的文件发生异常错误或大于%size!', ['%size'=>$post_size]));
			}
			
			$user = $this->_getUser($options['uid']);
			if (!$user->id) {
				throw new Error_Exception(I18N::T('nfs_share', '参数错误, 未知用户!'));
			}
				
			if (!in_array($options['nfs_root'], ['private', 'lab', 'public'])) {
				throw new Error_Exception(I18N::T('nfs_share', '参数错误, 未知根路径!'));
			}
			
			$path = NFS::fix_path($options['nfs_root']);
			if (!NFS::user_access($user, '上传文件', $user, ['path' => $path.'/foo', 'type' => 'share'])) {
				throw new Error_Exception(I18N::T('nfs_share', '参数错误, 未知根路径!'));
			}
			
			$ret = Event::trigger('NFS.validate.size', $user, $file);

			if ( $ret ) {
				throw new Error_Exception(I18N::T('nfs_share', '您的个⼈分区剩余空间不足, 无法上传文件! 具体情况请联系管理员!'));
			}
			
			@exec('clamscan --quiet '.escapeshellarg($file['tmp_name']), $output, $ret);
			if ($ret != 0 ) {
				throw new Error_Exception(I18N::T('nfs_share', '您上传的文件经检测存在病毒，上传失败!'));
			}
			
			
			$file_name = $file['name'];
			
			$path_type = 'share';
			$file_name = NFS::fix_name($file_name);
			$file_path = ($path ? $path . '/': '').$options['nfs_path'].'/'.$file_name;
			$full_path = NFS::get_path($user, $file_path, $path_type);
	
			if (file_exists($full_path)) {
				$dirname = dirname($file_path).'/';
				$full_dirname = dirname($full_path).'/';
	
				$info = NFS::pathinfo($full_path);
				$extension = $info['extension'] ? '.'.$info['extension'] : '';
				$name = substr($file_name, 0, strrpos($file_name,'.') ? : strlen($file_name));
				/* BUG #839::重复上传.开头的文件后文件名丢失 */
	
				$suffix_count = 2;
	
				do {
					$file_name = $name.'('.$suffix_count.')'.$extension;
					$file_path = $dirname . $file_name;
					$full_path = $full_dirname . $file_name;
					$suffix_count++;
				}
				while (file_exists($full_path));
	
			}
	
			File::check_path($full_path);
	
			move_uploaded_file($file['tmp_name'], $full_path);
	
			Event::trigger('nfs.stat', $user, $path, $full_path, $path_type, 'upload');
	
			/* 记录日志 */
			// Search_NFS::update_nfs_indexes($user, $path, $path_type, TRUE);
			
			Log::add(strtr('[nfs] %user_name[%user_id] 上传了 %path', [
					'%user_name' => $use->name,
					'%user_id' => $user->id,
					'%path' => $full_path,
					]),'journal');
	
			$info = NFS::file_info($full_path);
			if (!$info) throw new Error_Exception(I18N::T('nfs_share', '异常错误, 文件上传失败!'));
	
			$info += ['name' => $file_name, 'path' => $file_path];
			
			$response = [
				'status'  => 'success',
				'code'	  => 200,
				'message' => $info
			];
		}
		catch(Error_Exception $e) {
			$response['message'] = $e->getMessage();
        }
        
        echo @json_encode($response);
        exit();

	}
	
	private function _getUser($uuid)
	{
		$user = Event::trigger('get_user_from_sec_card', $uuid) ? : O('user', ['card_no' => $uuid]);

        if (!$user->id) {
            $card_no_s = (string)(($uuid + 0) & 0xffffff);
            $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);
        }
        
        if (!$user->id) {
	        $user = O('user', $uuid);
        }
        
        if (!$user->id) {
	        $user = O('user', ['token' => Auth::normalize($uuid)]);
        }
        
        return $user;
	}
}
