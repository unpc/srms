<?php

$config['sync_pictures'] = [
    'title' => '同步主站点头像到子站点',
    'cron' => '35 4 * * *',
    'job' => ROOT_PATH . 'cli/cli.php db_sync sync_pictures'
];
