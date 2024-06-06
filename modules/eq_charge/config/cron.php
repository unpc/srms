<?php

$config['check_eq_charge'] = [
    'title'=> '检查仪器收费是否与财务明细相符',
    'cron'=> '25 1 * * *', //配置在miss_check之后
    'job'=> ROOT_PATH. 'cli/cli.php eq_charge check',
];
