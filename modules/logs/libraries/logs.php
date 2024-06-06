<?php 
class Logs {
	//获取日志文件的路径
	static function get_log_path($name=NULL) {
		$root_path = Config::get('log_path.path');
		if ( isset($name) ) {
			return $root_path.'/'.$name;
		}
		return $root_path;
	}
	
	//获取日志目录下所有的日志文件
	static function log_list($full_path,$path=NULL) {
		
		if(!is_dir($full_path)) return null;
		
		$full_path = preg_replace('/[\/\s]+$/', '', $full_path);
		$dh = opendir($full_path);
		if(!$dh) {
			return null;
		}
		
		while(FALSE!==($name=readdir($dh))) {
			if ($name[0] == '.') continue;
			$log = Logs::log_info($full_path.'/'.$name);
			if ($log) {
				$logs[$name] = $log + ['name'=>$name, 'path'=>($path ? $path.'/' : '').$name];
			}
		}

		closedir($dh);
		
		$logs = (array) $logs;
		
		if (count($logs)>0) usort($logs, 'Logs::log_sort');
		return $logs;
	}
	
	//返回此全路经下文件的相关信息
	static function log_info($full_path) {
		$s = @stat($full_path);
		if (!$s) return NULL;
		$info =  [
			'mtime' => $s['mtime'],
			'atime' => $s['atime'],
			'ctime' => $s['ctime'],
			'size' => $s['size'],
			'log' => @is_file($full_path),
			'dir'  => @is_dir($full_path),
			'link' => @is_link($full_path),
		];

		if ($info['dir']) {
			$info['type'] = 'dir';
		}
		elseif ($info['log']) {
			$ext = File::extension($full_path);
			switch ($ext) {
			case 'log':
				$info['type'] = 'file';
			 	break;
			default:
				$info['type'] = 'default';
			}
		}
		else {
			$info['type'] = 'link';
		}
		
		return $info;
	}
	//对文件进行排序
	static function log_sort($log1,$log2) {
		if ($log1['dir'] && !$log2['dir']) return FALSE;
		if (!$log1['dir'] && $log2['dir']) return TRUE;
		if ($log1['mtime'] == $log2['mtime']) {
			return strcmp($log1['name'], $log2['name']) > 0;
		}
		else {
			return $log1['mtime'] < $log2['mtime'];
		}
	}

}