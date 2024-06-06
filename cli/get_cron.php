<?php
/*
   输出指定 lab 的 crontab(xiaopei.li@2012-06-29)

   usage: SITE_ID=cf LAB_ID=test php get_cron.php -u|--user=www-data
 */

//防止错误输出
ini_set('display_errors', FALSE);

require dirname(__FILE__) . '/base.php';

$shortopts = 'u:r::p::';
$longopts = [
    'user:root::prefix::',
];

$opts = getopt($shortopts, $longopts);

/*
// 测试用例:

$ SITE_ID=cf LAB_ID=sdu php get_cron.php
# 无
$ SITE_ID=cf LAB_ID=sdu php get_cron.php -u
当前用户
$ SITE_ID=cf LAB_ID=sdu php get_cron.php --user
当前用户
$ SITE_ID=cf LAB_ID=sdu php get_cron.php -u=root
root # 运行脚本必须 opt=value
$ SITE_ID=cf LAB_ID=sdu php get_cron.php -u root
当前用户 # 不能这样用
$ SITE_ID=cf LAB_ID=sdu php get_cron.php --user=root
root # 运行脚本必须 opt=value
$ SITE_ID=cf LAB_ID=sdu php get_cron.php --user root
当前用户 # 不能这样用
 */
if (isset($opts['u']) || isset($opts['user'])) {
    $user = $opts['u'] ? : $opts['user'];
}
else {
    die("usage: SITE_ID=cf LAB_ID=test php get_cron.php -u|--user=www-data\n");
}

if (isset($opts['r']) || isset($opts['root'])) {
    $root = $opts['r'] ? : $opts['root'];
}
else {
    $root = ROOT_PATH;
}

if (isset($opts['p']) || isset($opts['prefix'])) {
    $prefix = $opts['p'] ? : $opts['prefix'];
}
else {
    $prefix = '%s';
}

// 测试读取 user:
// echo $user;
// echo "\n";
// die;


echo '# lims2 crontabs of SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID;
echo  "\n";

$cron_jobs = Config::get('cron');

$envs = 'Q_ROOT_PATH=' . $root . ' SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID;

if ($cron_jobs) foreach ($cron_jobs as $job) {
    if ($job) {
        echo "# " . $job['title'] . "\n";

        $cron_command = strtr('env %envs %command', [
            '%envs'=> $envs,
            '%command'=> $job['job'],
        ]);

        $full_command = sprintf($prefix, $cron_command);

        echo strtr("%time %user docker exec lims2-env sh -c '%command' > /dev/null\n", [
                '%time'=> $job['cron'],
                '%user'=> $user,
                '%command'=> $full_command,
        ]);
    }
}
