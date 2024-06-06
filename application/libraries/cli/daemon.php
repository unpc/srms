<?php
class CLI_Daemon {
	static function get_config($root=null) {

		if (!$root) {
			$root = ROOT_PATH;
		}

		echo '# daemons for SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID;
		echo  "\n";

		$daemons = Config::get('daemon');

		$envs = [
			'Q_ROOT_PATH' => $root,
			'SITE_ID' => SITE_ID,
			'LAB_ID' => LAB_ID,
			];

		$daemon_envs = "";
		foreach ($envs as $env_key => $env_value) {
			// $daemon_envs .= "--env=\"$env_key=$env_value\" ";
			// TODO 由于加 " 后在 daemon 运行时可能出现重复 " 问题, 所以这里暂时不加 " 了
			$daemon_envs .= "-e $env_key=$env_value ";
		}

		foreach ((array)$daemons as $name => $opts) {
			// 期望 daemon 运行时的格式如下:
			// /usr/bin/daemon  --name="lims2_daemon_dispatcher" --env="SITE_ID=com" --env="LAB_ID=genee" --command="/home/xiaopei.li/lims2/cli/notification/dispatcher.php" --respawn

			echo "# " . $opts['title'] . "\n";

			echo $daemon_envs;

			$daemon_opts = [
				'name' => 'lims2_daemon_' . SITE_ID . '_' . LAB_ID . '_' . $name,
				'command' => strtr($opts['command'], [ROOT_PATH => $root]),
				// 'respawn' => $opts['respawn'],
				// respawn 选项可使 daemon client 进程被 kill 后重启,
				// 该项一般为期望设置, 故以防开发人员在 daemon 配置中忘加,
				// 强制设 TRUE
				'respawn' => TRUE,
				];
			foreach ($daemon_opts as $daemon_opt => $daemon_opt_value) {
				if (is_bool($daemon_opt_value) && $daemon_opt_value) {
					echo "--$daemon_opt ";
				}
				else {
					// echo "--$daemon_opt=\"$daemon_opt_value\" ";
					// TODO 由于加 " 后在 daemon 运行时可能出现重复 " 问题, 所以这里暂时不加 " 了
					echo "--$daemon_opt=$daemon_opt_value ";
				}
			}
			echo "\n";
		}
	}

	static function get_all_config($path=null) {
		/*

		给出一个路径, 扫描该路径下所有 lab, 并遍历对其调用 get_daemon.php (xiaopei.li@2013-03-12)

		usage: php cli.php daemon get_all_config /usr/share/lims2

		*/

		if (!$path) {
			echo "遍历生成 daemon\n";
			echo "命令格式: php cli.php daemon get_all_config /usr/share/lims2\n";
			exit;
		}

		if (is_dir($path)) {
			define('ROOT_PATH', realpath($path).'/');
		}
		else {
			define('ROOT_PATH', dirname(__FILE__).'/');
		}

		$root = ROOT_PATH;

		$labs = glob(ROOT_PATH.'sites/*/labs/*');

		$daemons = [];

		$get_daemon = ROOT_PATH . 'cli/cli.php daemon get_config';
		$get_daemon_opts = '';
		$get_daemon_opts .= $root ? " $root" : '';

		foreach ($labs as $lab) {

			if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

			$site_id = $matches[1];
			$lab_id = $matches[2];
			$cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script %opts 2>/dev/null %then', [
							 '%site_id' => $site_id,
							 '%lab_id' => $lab_id,
							 '%script' => "php $get_daemon",
							 '%opts' => $get_daemon_opts,
							 '%then' => " | grep '^[0-9#\-]'",
							 ]);

			ob_start();
			// passthru("SITE_ID=$site_id LAB_ID=$lab_id php $get_daemon 2>/dev/null", $ret);
			passthru($cmd, $ret); // 用 grep 去除非 daemon 或 注释 的行
			$content_grabbed = '';
			if ($ret == 0) {
				$content_grabbed = ob_get_contents();
			}
			ob_end_clean();

			// exec() returns **the last line from the result of the command**
			// If you need to execute a command and have all the data from the command passed directly back without any interference, use the passthru() function.

			echo $content_grabbed;
			// $daemons[$site_id . '_' . $lab_id] = $content_grabbed;
		}

		// echo join($daemons, "\n");
	}
}