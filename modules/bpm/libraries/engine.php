<?php
namespace Gini\BPM;
// 和gini模块同步使用了命名空间 同时qf框架不支持直接引入命名空间的文件
// 所以手动引入一下…………

require __DIR__ . '/driver/decision.php';
require __DIR__ . '/driver/engine.php';
require __DIR__ . '/driver/execution.php';
require __DIR__ . '/driver/group.php';
require __DIR__ . '/driver/process.php';
require __DIR__ . '/driver/processInstance.php';
require __DIR__ . '/driver/task.php';
require __DIR__ . '/driver/user.php';

require __DIR__ . '/camunda/decision.php';
require __DIR__ . '/camunda/engine.php';
require __DIR__ . '/camunda/execution.php';
require __DIR__ . '/camunda/group.php';
require __DIR__ . '/camunda/process.php';
require __DIR__ . '/camunda/processInstance.php';
require __DIR__ . '/camunda/task.php';
require __DIR__ . '/camunda/user.php';

class Engine {
    public static function of($name) {
        $conf = \Config::get("bpm.$name");
        $conf['@name'] = $name;
        $driver = $conf['driver'] ?? 'Unknown';

        $rc = new \ReflectionClass('\Gini\BPM\\'.$driver.'\Engine');
        return $rc->newInstanceArgs([$conf]);
    }
}
