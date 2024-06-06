<?php
/*
  
daemon 参数与 LIMS2/CF 中配置的对应
	
^ LIMS2/CF confs  ^ daemon opts ^ 
| key             | --name      |
| command         | --command   | 
| respawn => TRUE | --respawn   | 
| title           | # comment   |

*/

/*
$config['notification_extractor'] = array(
	'title' => '分发',
	'command' => ROOT_PATH . 'cli/notification/extractor.php',
	'respawn' => TRUE,
);

$config['notification_dispatcher'] = array(
	'title' => '发信',
	'command' => ROOT_PATH . 'cli/notification/dispatcher.php',
	'respawn' => TRUE,
);
*/