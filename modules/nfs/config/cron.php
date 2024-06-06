<?php
// 每小时执行一次
$config['delete_cache'] = [
    'title' => '删除过期文件缓存',
    'cron' => '0 * * * *',
    'job' => ROOT_PATH . 'cli/cli.php nfs_big_file delete_cache'
];