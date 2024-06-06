<?php
/*
 * @file datapoint.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 环境监控模块生成数据测试脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-envmon/datapoint
 */

echo "env_mon模块生成点数据\n";

$db      = Database::factory();

$node    = O('env_node', $_SERVER['NODE_ID'] );
$sensors = Q("env_sensor[node_id={$node->id}]");

$day        = 60*60*24;
$end_time   = time();
$start_time = time() - $day*7; 


foreach($sensors as $sensor){
	$delete_sql = "DELETE FROM env_datapoint WHERE sensor_id={$sensor->id} AND ctime>$start_time AND ctime<$end_time";
	$db->query($delete_sql);

	$interval   = rand(60,180);
	$freqency   = rand(3,7);
	$amplitude  = rand(3,7);
	$offset     = rand(0,5);

	for($i = $start_time; $i<=$end_time ; $i +=$interval){
		$point = O('env_datapoint');
		$point->sensor_id = $sensor->id;
		$point->ctime = $i;
		$point->value = $offset + $amplitude*sin(($i/$day)*M_PI*$freqency);
		$point->save();
	}
	$count = ceil($day*7/$interval);
	echo "为{$sensor}生成数据点$count 个\n";
}

echo "生成成功\n";
