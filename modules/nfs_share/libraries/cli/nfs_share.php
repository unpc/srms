<?php 
class CLI_NFS_Share {
	static function sync() {
		$root_dir = Config::get('nfs.root'). '/share/';
		$users_dir = $root_dir.'users/';
		$labs_dir = $root_dir.'labs/';
		$public_dir = $root_dir.'public/';
		
		$stat = [
			'users'=>[
				'used'=>0,
				'size'=> @disk_total_space($users_dir),
				'mtime'=> @filemtime($users_dir),
			],
			'labs'=>[
				'used'=>0,
				'size'=> @disk_total_space($labs_dir),
				'mtime'=> @filemtime($labs_dir),
			],
			'public'=>[
				'used'=>File::size($public_dir),
				'mtime'=> @filemtime($public_dir),
				'size'=> @disk_total_space($public_dir),
			],
			'total'=>[
				'size'=> @disk_total_space($root_dir),
				'mtime'=> @filemtime($root_dir),
			],
		];
		
		$stat['total']['used'] = $stat['public']['used'];
		
		$dh = @opendir($users_dir);
		if ($dh) {
			while ($name = readdir($dh)) {
				if ($name[0]=='.') continue;
				if (is_dir($users_dir.$name) && !is_link($users_dir.$name)) {
					$user = O('user', $name);
					if ($user->id) {
						$user_dir = $users_dir.$name;
						$user->nfs_used = File::size($user_dir);
						$user->nfs_size = @disk_total_space($user_dir);
						$user->nfs_mtime = @filemtime($user_dir);
						$user->save();
						$stat['users']['used'] += $user->nfs_used;
						$stat['total']['used'] += $user->nfs_used;
					}
				}
			}
			@closedir($dh);
		}
		
		$dh = @opendir($labs_dir);
		if ($dh) {
			while ($name = readdir($dh)) {
				if ($name[0]=='.') continue;
				if (is_numeric($name) && is_dir($labs_dir.$name) && !is_link($users_dir.$name)) {
					$lab = O('lab', $name);
					if ($lab->id) {
						$lab_dir = $labs_dir.$name;
						$lab->nfs_used = File::size($lab_dir);
						$lab->nfs_size = @disk_total_space($lab_dir);
						$lab->nfs_mtime = @filemtime($lab_dir);
						$lab->save();
						$stat['labs']['used'] += $lab->nfs_used;
						$stat['total']['used'] += $lab->nfs_used;
					}
				}
			}
			@closedir($dh);
		}
		
		Lab::set('nfs.total', $stat['total']);
		Lab::set('nfs.public', $stat['public']);
		Lab::set('nfs.labs', $stat['labs']);
		Lab::set('nfs.users', $stat['users']);
	}

	static function clean() {
		$clean = Lab::get('nfs_share.clean_seeting');

		if ($clean['clean_time'] && $clean['clean_time'] <= Date::time()) {
			//开始执行删除
			$dtstart = $clean['dtstart'];
			$dtend = $clean['dtend'];
			$py = dirname(__FILE__).'/'.'clear_filesystem.py';
						
			exec("python {$py} {$dtstart} $dtend " . " " .SITE_ID." ".LAB_ID);
			//调用脚本后恢复状态
			$setting = [];
			$setting['dtstart'] = 0;
			$setting['dtend'] = 0;
			$setting['clean_time'] = 0;

			Lab::set('nfs_share.clean_seeting', $setting);
		}
	}
}
