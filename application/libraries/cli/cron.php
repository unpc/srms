<?php
class CLI_Cron{
	static function get_config($user=null, $root=null){

		if(!$user){
			die("usage: SITE_ID=cf LAB_ID=test php cli.php cron get_config root|www-data\n");
		}

		if(!$root){
			$root = ROOT_PATH;
		}
		
		echo '# lims2 crontabs of SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID;
		echo  "\n";

		$cron_jobs = Config::get('cron');

		$envs = 'Q_ROOT_PATH=' . $root . ' SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID;

		if ($cron_jobs) foreach ($cron_jobs as $job) {
			if ($job) {
				echo "# " . $job['title'] . "\n";
				echo $job['cron'] . ' ' . $user . ' ' . $envs .  ' ' . strtr($job['job'], [ROOT_PATH => $root]) . "\n";
			}
		}
	}

	static function get_all_config($user=null,$path=null) {
		/*
		给出一个目录, 扫描该目录下所有 lab, 并遍历对其调用 get_cron.php(xiaopei.li@2012-06-29)

		usage: php get_all_cron.php /usr/share/lims2
		*/

		if (!$user || !$path) {
			echo "遍历生成 crontab\n";
			echo "命令格式: php cli.php cron get_all_config [root|www-data] /usr/share/lims2\n";
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

		$crontabs = [];

		$get_cron = ROOT_PATH . 'cli/cli.php cron get_config';

		$get_cron_opts = '';
		$get_cron_opts .= $user ? " $user" : '';
		$get_cron_opts .= $root ? " $root" : '';

		foreach ($labs as $lab) {

			if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

			$site_id = $matches[1];
			$lab_id = $matches[2];
			$cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script %opts 2>/dev/null %then', [
							 '%site_id' => $site_id,
							 '%lab_id' => $lab_id,
							 '%script' => "php $get_cron",
							 '%opts' => $get_cron_opts,
							 '%then' => " | grep '^[0-9*@#]'",
							 ]);
			
			ob_start();
			// passthru("SITE_ID=$site_id LAB_ID=$lab_id php $get_cron 2>/dev/null", $ret);
			passthru($cmd, $ret); // 用 grep 去除非 cron 或 注释 的行
			$content_grabbed = '';
			if ($ret == 0) {
				$content_grabbed = ob_get_contents();
			}
			ob_end_clean();

			// exec() returns **the last line from the result of the command**
			// If you need to execute a command and have all the data from the command passed directly back without any interference, use the passthru() function.

			echo $content_grabbed;
			// $crontabs[$site_id . '_' . $lab_id] = $content_grabbed;
		}
	}
}