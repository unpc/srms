<?php

class NFS_Share {

	static function setup() {
		NFS::register_handler('share', 'NFS_Share::get_share_path'); // default file path
		// NFS::register_handler('share', 'NFS_Share::get_quarantine_path');
	}

	static function get_share_path($object, $path='', $use_full_path=TRUE) {
		$root = Config::get('nfs.root');
		File::check_path($root . 'share/users/foo');
		File::check_path($root . 'share/labs/foo');
		File::check_path($root . 'share/public/foo');

		if(is_object($object) && $object->name() == 'user') {
			$prefix = $root . 'share/users/'.$object->id.'/';
		}
		elseif(is_object($object) && $object->name() == 'lab') {
			$prefix = $root . 'share/labs/'.$object->id.'/';
		}
		else {
			$prefix = $root . 'share/public/';
		}

		$new_path = $prefix ? $prefix . $path : null;
		/* NOTICE!!! 这里不能创建目录 因为share目录是需要权限开通的 而且这里创建也比较低效
		 * by Jia Huang
		if ($new_path && !file_exists($new_path)) {
			File::check_path($new_path.'foo.bar');
		} */
		return $use_full_path ? $new_path : File::relative_path($new_path, $prefix);
    }
    
    /**
     * 文件上传隔离区
     */
    public static function get_quarantine_path($object, $path = "", $use_full_path = true) 
    {
        $root = Config::get('nfs.root');
		File::check_path($root . 'quarantine/users/foo');
		File::check_path($root . 'quarantine/labs/foo');
		File::check_path($root . 'quarantine/public/foo');

		if(is_object($object) && $object->name() == 'user') {
			$prefix = $root . 'quarantine/users/'.$object->id.'/';
		}
		elseif(is_object($object) && $object->name() == 'lab') {
			$prefix = $root . 'quarantine/labs/'.$object->id.'/';
		}
		else {
			$prefix = $root . 'quarantine/public/';
		}

		$new_path = $prefix ? $prefix . $path : null;
		return $use_full_path ? $new_path : File::relative_path($new_path, $prefix);
    }

	static function get_prefix_path($e, $object, $path, $path_type, $return_link_prefix = FALSE) {
		if ($path_type != 'share') return;
		if ($return_link_prefix) {
			list($head_file,$extra) = explode('/', $path, 2);
			if ($path == null) {
				$prefix = [];
				$prefix[] = 'share/users/'.$object->id.'/';
				$prefix[] = 'share/public/';
			}
			elseif ($head_file == 'private') {
				$prefix = 'share/users/'.$object->id.'/';
			}
			elseif ($head_file == 'public') {
				$prefix = 'share/public/';
			}
		}
		else {
			if(is_object($object) && $object->name() == 'user') {
				$prefix = 'share/users/'.$object->id.'/';
			}
			elseif(is_object($object) && $object->name() == 'lab') {
				$prefix = 'share/labs/'.$object->id.'/';
			}
			else {
				$prefix = 'share/public/';
			}
		}
		$e->return_value = $prefix;
	}

	/* 获取real_path所属对象的链接地址 */
	static function get_link_path($e, $object, $path_prefix, $path, $path_type) {
		if ($path_type != 'share') return;
		list($path_type,$type,$extra) = explode('/', $path_prefix);
		switch ($type) {
			case 'users':
				$link_path = $path;
				break;
			case 'labs':
				$link_path = 'lab/'.$path;
				break;
			case 'public':
				$link_path = 'public/'.$path;
				break;
		}
		$e->return_value = $link_path;
	}

	static function setup_share($object) {

		$root_dir = Config::get('nfs.root') . 'share/';
		$users_dir = $root_dir . 'users/';
		$labs_dir = $root_dir . 'labs/';
		$public_dir = $root_dir . 'public/';

		if (!$object->id) return FALSE;

		//默认认为开通nfs分区失败
		$opened = FALSE;

		if ($object->name() == 'lab') {
			$dir = self::get_share_path($object, 'foo');
			if (File::check_path($dir)) $opened = TRUE;
		}
		elseif ($object->name() == 'user') {
			$dir = self::get_share_path($object, 'private/foo');
			if (File::check_path($dir)) {
				$opened = TRUE;
			}
			else {
				return;
			}

            File::rmdir($users_dir. $object->id. '/lab');
            File::check_path($users_dir. $object->id. '/lab/.');
            foreach (Q("$object lab") as $lab) {
				$target = '../../labs/'.$lab->id;
                $target = NFS_Share::get_share_path($lab);
                $link = NFS_Share::get_share_path($object, 'lab/' . $lab->name . '-' . $lab->id);
                file_exists($link) and @unlink($link);
                @symlink($target, $link);
            }

			$target = '../../public';
			$link = self::get_share_path($object, 'public');

			file_exists($link) and @unlink($link);
			@symlink($target, $link);
		}
		/*
			BUG#98
			2010.11.04 by cheng.liu
			之前在if后面有个else，直接return出去了
			所有不是user的$object都会推出，不会保存。
			删掉之后就能对lab开通文件系统处理了
		 */
		if ($opened) {
			if ($object->name() == 'lab') {
				$labs = Lab::get('nfs.labs');
				$labs['size'] = @disk_total_space($labs_dir);
				Lab::set('nfs.labs', $labs);
			}
			elseif($object->name() == 'user') {
				$users = Lab::get('nfs.users');
				$users['size'] = @disk_total_space($users_dir);
				Lab::set('nfs.users', $users);
			}

			$total = Lab::get('nfs.total');
			$total['size'] = @disk_total_space($root_dir);
			Lab::set('nfs.total', $total);

			$public = Lab::get('nfs.public');
			$public['size'] = @disk_total_space($public_dir);
			Lab::set('nfs.public', $public);

			$object->nfs_size = @disk_total_space(dirname($dir));
			$object->nfs_used = 0;
			$object->nfs_mtime = time();
			if ($object->save()) {
				return TRUE;
			}
		}
	}

	static function change_share($old, $object) {
		$root_dir = Config::get('nfs.root') . 'share/';
		$users_dir = $root_dir . 'users/';
		$labs_dir = $root_dir . 'labs/';
		$public_dir = $root_dir . 'public/';

		if (!$object->id) return FALSE;

		if ($object->name() == 'user') {
			$old_path = self::get_share_path($old, '');
			$new_path = self::get_share_path($object, '', TRUE);
			rename($old_path, $new_path);
			$full_path = $new_path;

			// unlink掉前一个课题组
			$lab = Q("$old lab")->current();
			$link = self::get_share_path($object, "lab/{$lab->name}-{$lab->id}");
			is_link($link) and @unlink($link);
		}
		
		if ($object->name() == 'lab') {
			$labs = Lab::get('nfs.labs');
			$labs['size'] = @disk_total_space($labs_dir);
			Lab::set('nfs.labs', $labs);
		}
		elseif ($object->name() == 'user') {
			$object->replacement = FALSE;
			$users = Lab::get('nfs.users');
			$users['size'] = @disk_total_space($users_dir);
			Lab::set('nfs.users', $users);
		}

		$total = Lab::get('nfs.total');
		$total['size'] = @disk_total_space($root_dir);
		Lab::set('nfs.total', $total);

		$public = Lab::get('nfs.public');
		$public['size'] = @disk_total_space($public_dir);
		Lab::set('nfs.public', $public);

		$object->nfs_size = @disk_total_space(dirname($full_path));
		$object->nfs_used = File::size($full_path);
		$object->nfs_mtime = time();

		if ($object->save()) {
			return TRUE;
		}
	}

	static function destroy_share($object, $ret=FALSE){

		if (!$object->id) return FALSE;

		$oname = $object->name();

		$dir = self::get_share_path($object);
		if(!$dir) return FALSE;

		//某些情况下出现了指向错误的symlink文件需要删除
        $link = NFS_Share::get_share_path($object, 'lab');
        is_link($link) and @unlink($link);
		@unlink(substr($dir, 0, -1));

		File::rmdir($dir);

		if(!$ret){
			$total = Lab::get('nfs.total');
			$users = Lab::get('nfs.users');
			$labs = Lab::get('nfs.labs');

			if($oname == 'lab'){
				(int)$total['used'] = (int)$total['used'] - (int)$object->nfs_used;
				(int)$labs['used'] = (int)$labs['used'] - (int)$object->nfs_used;
			}elseif($oname == 'user'){
				(int)$total['used'] = (int)$total['used'] - (int)$object->nfs_used;
				(int)$users['used'] = (int)$users['used'] - (int)$object->nfs_used;
			}

			Lab::set('nfs.total', $total);
			Lab::set('nfs.labs', $labs);
			Lab::set('nfs.users', $users);

			$object->nfs_size = 0;
			$object->nfs_used = 0;
			$object->nfs_mtime = 0;
			$object->save();

			return TRUE;
		}
	}

	static function user_access($e, $user, $perm_name, $object, $opt) {
		static $valid_dirs = ['lab', 'private', 'public'];
		$path = $opt['path'];
		$path_type = $opt['type'];
		if ($path_type !== 'share' || $object->name() !== 'user') return;

		$path_pattern = '/^\/?(.+?)(\/|$)/';
		switch ($perm_name) {
		case "下载文件":
		case "上传文件":
		case "创建目录":
			if (!preg_match($path_pattern, $path, $matches)) {
				$e->return_value = FALSE;
				return FALSE;
			}
			if (!in_array($matches[1], $valid_dirs)) {
				$e->return_value = FALSE;
				return FALSE;
			}

			break;
		case "删除文件":
		case "修改文件":
		case "修改目录":
		case "删除目录":
			if (in_array($path, $valid_dirs)) {
				$e->return_value = FALSE;
				return FALSE;
			}
			if (!preg_match($path_pattern, $path, $matches)) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if (!in_array($matches[1], $valid_dirs)) {
				$e->return_value = FALSE;
				return FALSE;
			}

			break;
		}

	}

	//传入对象$object为user/lab
	static function regular_ACL($e, $user, $perm_name, $object, $opt) {
		if ($opt['type'] != 'share') return;
		if ($object->name() == 'user' 
			&& $user->id == $object->id // 自己肯定能修改自己的分区
			|| (Q("($object, $user<pi) lab")->total_count() // 实验室管理员可修改成员的分区
				&& $user->access('管理负责实验室成员的文件分区'))
			|| (Q("($object, $user) lab")->total_count() // 实验室管理员可修改成员的分区
				&& $user->access('管理本实验室成员的文件分区'))) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if ($object->name() == 'lab' && Q("$user<pi $object")->total_count()) {
			// 用户能够修改本实验室的内容
			$e->return_value = TRUE;
			return FALSE;
		}

		switch ($perm_name) {
		case "列表文件":
			if ($user->access('查看所有成员的附件')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case "下载文件":
			if ($user->access('下载所有成员的附件')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case "上传文件":
		case "创建目录":
			if ($user->access('上传/创建所有成员的附件')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case "删除文件":
		case "修改文件":
		case "修改目录":
		case "删除目录":
			if ($user->access('更改/删除所有成员的附件')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}

	}

	//$object为user对象
	static function admin_ACL($e, $me, $perm_name, $object, $opt) {
		if (is_object($object)
			&& $object->name() == 'user' 
			&& Q("$me lab")->total_count()
			&& Q("($me, $object) lab")->total_count()
			&& $me->access('管理本实验室成员的文件分区')) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if (is_object($object)
			&& $object->name() == 'user' 
			&& Q("$me lab")->total_count()
			&& Q("($me<pi, $object) lab")->total_count()
			&& $me->access('管理负责实验室成员的文件分区')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($me->access('管理文件分区')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		switch ($perm_name) {
			case "查看各实验室分区":
				if ($me->access('管理所有内容') || Q("$me<pi lab")->total_count() || $me->access('管理下属组织机构成员的分区')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case "查看文件系统所有":
				if ($me->access('管理所有内容')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			}
	}

	static function on_enumerate_user_perms($e, $user, $perms) {
		if (!$user->id) return;
        //取消现默认赋予给pi的权限
//		if (Q("$user<pi lab")->total_count()) {
//			$perms['管理负责实验室成员的文件分区'] = 'on';
//		}
	}

	static function on_user_connect_lab($e, $user, $lab, $type) {
		$root = Config::get('nfs.root');

		$target = self::get_share_path($lab);
		$link = self::get_share_path($user, 'lab/' . $lab->name . '-' . $lab->id);
		if($lab->nfs_size) is_dir($target) and @symlink($target, $link);
	}

	static function on_user_disconnect_lab($e, $user, $lab) {

		$link = self::get_share_path($user, 'lab/' . $lab->name . '-' . $lab->id);
		is_link($link) and @unlink($link);
	}

	static function filter_no_lab_files($e, $object, $path, $full_path, $path_type) {
		if (Module::is_installed('labs') || $path_type != 'share' || $path) return FALSE;

		if(!is_dir($full_path)) return FALSE;

		$full_path = preg_replace('/[\/\s]+$/', '', $full_path);
		$dh = opendir($full_path);
		if(!$dh) {
			return null;
		}
		while(FALSE!==($name=readdir($dh))) {
			if ($name[0] == '.' || $name == 'lab') continue;
			$file = NFS::file_info($full_path.'/'.$name);
			if ($file) {
				$files[$name] = $file + ['name'=>$name, 'path'=>($path ? $path.'/' : '').$name];
			}
		}

		closedir($dh);

		$files = (array) $files;

		if (count($files)>0) usort($files, 'NFS_Share::file_sort');

		$e->return_value = $files;
	}

	//返回$file1和$file2的文件对比结果
	static function file_sort($file1,$file2) {
		if ($file1['dir'] && !$file2['dir']) return FALSE;
		if (!$file1['dir'] && $file2['dir']) return TRUE;
		if ($file1['mtime'] == $file2['mtime']) {
			return strcmp($file1['name'], $file2['name']) > 0;
		}
		else {
			return $file1['mtime'] < $file2['mtime'];
		}
	}

    /* NFS_share中的空间使用情况显示问题修正，增加实时显示功能(kai.wu@2011.10.19) */
	//上传文件时更新使用情况
	static function nfs_stat($e, $object, $path, $full_path, $path_type, $stat_type) {

		$oname = $object->name();
		$root_dir = Config::get('nfs.root') . 'share/';
		$users_dir = $root_dir . 'users/';
		$labs_dir = $root_dir . 'labs/';
		$public_dir = $root_dir . 'public/';

        //check_path
        File::check_path($users_dir. '/foo');
        File::check_path($labs_dir. '/foo');
        File::check_path($public_dir. '/foo');

		if ($stat_type == 'upload') {
			$size = @filesize($full_path);
		}
		elseif ($stat_type == 'delete') {
			if (is_dir($full_path)) {
				$size = -NFS_Share::size($full_path);
			}
			else {
				$size = -@filesize($full_path);
			}
		}

		$path_arr = explode('/', $path);
		$path = $path_arr[0];

		if ($size != 0) {
			if (in_array($path, ['private', 'lab', 'public']) && $oname == 'user') {
				switch ($path) {
				case 'private':
					$object->nfs_used = max($object->nfs_used, 0);
					$object->nfs_used += $size;
					$object->save();
					$users = Lab::get('nfs.users');
					$users['used'] += $size;
					$users['used'] = max((int)$users['used'], 0);
					Lab::set('nfs.users', $users);
					break;
				case 'lab':
					foreach (Q("$object lab") as $lab) {
						if ($lab->id) {
							$lab->nfs_used = max($lab->nfs_used, 0);
							$lab->nfs_used += $size;
							$lab->save();
							$labs = Lab::get('nfs.labs');
							$labs['used'] += $size;
							Lab::set('nfs.labs', $labs);
						}
					}
					break;
				case 'public':
					$public = Lab::get('nfs.public');
					$public['used'] += $size;
					Lab::set('nfs.public', $public);
					break;
				}
			}
			else {
				if ($object->id) {
					$object->nfs_used = max($object->nfs_used, 0);
					$object->nfs_used += $size;
					$object->save();
					$labs = Lab::get('nfs.labs');
					$labs['used'] += $size;
					Lab::set('nfs.labs', $labs);
				}
			}
			$total = Lab::get('nfs.total');
			$total['used'] += $size;
			Lab::set('nfs.total', $total);
		}
	}

	static function size($dir) {

        //check_path
        File::check_path($dir. '/foo');

		$handle = opendir($dir);
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				if (is_dir("$dir/$file")) {
					$size += NFS_Share::size("$dir/$file");
				}
				else {
					$size += filesize("$dir/$file");
				}
			}
		}
		closedir($handle);
		return $size;
	}

	// open lab's nfs space
	static function auto_open_lab($e, $lab)	{
		$auto_open = Config::get('nfs_share.auto_open_lab');
		if ($auto_open) {
			if (0 == $lab->nfs_size) {
				if (self::destroy_share($lab) && self::setup_share($lab) ) {
					Log::add(strtr('[nfs_share] %user_name[%user_id]开通了实验室%lab_name[%lab_id]的文件分区', [
								   '%user_name' => L('ME')->name,
								   '%user_id' => L('ME')->id,
								   '%lab_name' => $lab->name,
								   '%lab_id' => $lab->id
								   ]), 'journal');
					$users = Q("user[lab={$lab}]");
					foreach ($users as $user) {
						if ($user->nfs_size > 0) {
							self::setup_share($user);
						}
					}
				}
			}
		}
	}
	
	// open user's nfs space
	static function auto_open_people($e, $user) {
		$auto_open = Config::get('nfs_share.auto_open_user');
		if ($auto_open) {
			if (0 == $user->nfs_size) {
				if (self::setup_share($user)) {
					Log::add(strtr('[nfs_share] %operator_name[%operator_id]开通了用户%user_name[%user_id]的文件分区', [
								   '%operator_name' => L('ME')->name,
								   '%operator_id' => L('ME')->id,
								   '%user_name' => $user->name,
								   '%user_id' => $user->id,
								   ]), 'journal');
				}
			}
		}
	}

	static function auto_open_all_people() {
		$auto_open = Config::get('nfs_share.auto_open_user');
		if ($auto_open && $auto_open != Lab::get('system.nfs_share.auto_open.people')) {
			$users = Q('user[nfs_size=0]');
			if (count($users)) {
				foreach ($users as $user) {
					if (self::setup_share($user)) {
						Log::add(strtr('[nfs_share] %operator_name[%operator_id]开通了用户%user_name[%user_id]的文件分区', [
									   '%operator_name' => L('ME')->name,
									   '%operator_id' => L('ME')->id,
									   '%user_name' => $user->name,
									   '%user_id' => $user->id,
									   ]), 'journal');
					}
				}
			}
			Lab::set('system.nfs_share.auto_open.people', TRUE);
		}
	}

	static function auto_open_all_lab() {
		$auto_open = Config::get('nfs_share.auto_open_lab');
		if ( $auto_open && $auto_open != Lab::get('system.nfs_share.auto_open.lab')) {
			$labs = Q('lab[nfs_size=0]');
			if (count($labs)) {
				foreach ($labs as $lab) {
					if (self::destroy_share($lab) && self::setup_share($lab) ) {
						Log::add(strtr('[nfs_share] %user_name[%user_id]开通了实验室%lab_name[%lab_id]的文件分区', [
									   '%user_name' => L('ME')->name,
									   '%user_id' => L('ME')->id,
									   '%lab_name' => $lab->name,
									   '%lab_id' => $lab->id
									   ]), 'journal');
						$users = Q("user[lab={$lab}]");
						foreach ($users as $user) {
							if ($user->nfs_size > 0) {
								self::setup_share($user);
							}
						}
					}
				}
			}
			Lab::set('system.nfs_share.auto_open.lab', TRUE);
		}
	}

	static function show_nfs_tips($e, $sub_path) {
		if ($sub_path == 'private') {
			$view = V('nfs_share:tips/private', ['sub_path'=>$sub_path]);
		}
		elseif ($sub_path == 'public') {
			$view = V('nfs_share:tips/public', ['sub_path'=>$sub_path]);
		}
		elseif ($sub_path == 'people') {
			$view = V('nfs_share:tips/people', ['sub_path'=>$sub_path]);	
		}
		else {
			$view = V('nfs_share:tips/default', ['sub_path'=>$sub_path]);
		}
		$e->return_value = (string)$view;
	}

    static function list_dir($e, $object, $path, $path_type) {
        switch($object->name()) {
            case 'user' :
                //如果开通了模块
                if ($object->nfs_size && ! is_dir(NFS::get_path($object, $path, $path_type, TRUE))) {
                    self::setup_share($object);
                }
                break;
            case 'lab' :
                self::setup_share($object);
                break;
            default :
        }
    }

    static function nfs_people_operate_links($user) {
    	$me = L('ME');
    	$links = [];
    	if (L('ME')->is_allowed_to('管理文件分区', $user)) {
    		if ($user->nfs_size) {
    			$links['close'] = URI::anchor(
					'!nfs_share/people/close.'.$user->id,
					I18N::T('nfs_share', '关闭'),
					'title="'.I18N::T('nfs_share', '关闭').'" class="blue" confirm="'.I18N::T('nfs_share', '您确定要关闭该用户的文件系统？\n一旦关闭，该用户的所有文件将被删除，且无法恢复！请您谨慎操作。').'"'
				);
    		}
    		else {
    			$links['open'] = URI::anchor(
					'!nfs_share/people/open.'.$user->id, 
					I18N::T('nfs_share', '开通'),
					'title="'.I18N::T('nfs_share', '开通').'" class="blue" confirm="'.I18N::T('nfs_share', '您确定要开通该用户的文件系统？').'"'
				);
    		}
    	}

    	$newLinks = Event::trigger('NFS_Share.people.operate.links', $user, $links);

    	$links = count((array) $newLinks) ? (array) $newLinks : $links;

    	return $links;
	}
	
	static function sort_condition_selector($e, $selector, $pre_selector, $type) {
        $user = L('ME');

        switch($type){
            case 'lab':
                if($user->access('管理所有内容')) return;
                $pre_selector['nfs_lab'] = "$user<pi";
                if($user->access('管理下属组织机构成员的分区')){
                    $group_root = Tag_Model::root('group');
                    $pre_selector['nfs_lab'] .= "|{$user}<group tag_group[root=$group_root]";
                }
            break;
            case 'user':
                $form = Lab::form();
                if($form['lab']){
                    $lab = Q::quote($form['lab']);
                    if($user->access('管理所有内容')) {
                        $pre_selector['nfs_user'] = "lab[name*=$lab|name_abbr*=$lab]";
                        return;
                    }
                    $pre_selector['nfs_user'] = "$user<pi lab[name*=$lab|name_abbr*=$lab]";
                    if($user->access('管理下属组织机构成员的分区')){
                        $group_root = Tag_Model::root('group');
                        $pre_selector['nfs_user'] .= "|{$user}<group tag_group[root=$group_root] user lab[name*=$lab|name_abbr*=$lab]";
                    }
                } else {
                    if($user->access('管理所有内容')) return;
                    $pre_selector['nfs_user'] = "$user<pi lab";
                    if($user->access('管理下属组织机构成员的分区')){
                        $group_root = Tag_Model::root('group');
                        $pre_selector['nfs_user'] .= "|{$user}<group tag_group[root=$group_root]";
                    }
                }

            break;
        }
            
        $e->return_value = $selector;
        return FALSE;
    }
}
