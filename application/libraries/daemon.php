<?php

class Daemon {
	
	private static $name;
	
	static function start($name) {
		
		$pid = pcntl_fork();
		if ($pid == -1) {
			die ("无法Fork进程\n");
		}
		elseif ($pid) {
			exit(0);
		}
		
		if (posix_setsid() == -1) {
			die ("无法与Terminal脱离\n");
		}
		
		$pid_file = self::get_pid_file($name);
		
		register_shutdown_function('Daemon::remove_pid_file', $name);

		if (FALSE == @file_put_contents($pid_file, posix_getpid())) {
			die ("无法建立pid文件\n");
		}
		
		self::$name = $name;
	}

	static function kill_process($pid, $sig) {
		exec("ps -ef| awk '$3 == $pid { print $2 }'", $output); 
		posix_kill($pid, $sig);
		foreach ($output as $cpid) {
			if ($cpid != $pid) {
				self::kill_process($cpid, $sig);
			}
		}
	}

	static function stop($name) {
		$pid_file = self::get_pid_file($name);

		// 停止已启动进程
		$prev_pid = @file_get_contents($pid_file);

		if ($prev_pid > 0) {
        	self::kill_process($prev_pid, SIGTERM);
		}
		
		self::remove_pid_file($name);
		
		return FALSE;
	}
	
	static function get_pid_file($name) {
		$tpl_path = Config::get('daemon.'.$name.'_pid_file');
		if (!$tpl_path) $tpl_path = Config::get('daemon.pid_file' , '/tmp/daemon_%name.pid');
		return strtr($tpl_path, ['%name'=>$name]);
	}
	
	static function remove_pid_file($name) {
		file_exists($name) and  @unlink(self::get_pid_file($name));

	}

}
