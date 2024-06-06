<?php 
/*
 * @file test_anc.php
 * @author jinlin.li <jinlin.li@geneegroup.com>
 * @date 2013-06-07
 *
 * @brief 测试newsletter中邮件内容是否发送正确
 * @usage SITE_ID=lab LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/newsletter/test_newsletter
 */
require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

echo "开始环境自动生成:newsletter\n\n";
Environment::init_site();

$dtstart = Date::time() - 20;
$dtend = Date::time() + 20;

$now = Date::time();
$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('马睿');
$user3 = Environment::add_user('吴凯');
$user4 = Environment::add_user('吴天放');

$eq1 = Environment::add_equipment('测试仪器1', $user1, $user1);
$eq2 = Environment::add_equipment('测试仪器2', $user1, $user1);

Newsletter_test::add_equipment_fault($eq1);
Newsletter_test::add_equipment_fault($eq2);
Newsletter_test::add_equipment_recover($eq2);

$p1 = Newsletter_test::prepare_project('项目1');
$p2 = Newsletter_test::prepare_project('项目2');

$t1 = Newsletter_test::prepare_task($p1);
$t2 = Newsletter_test::prepare_task($p2);

$n1 = Newsletter_test::prepare_note($t1);
$n2 = Newsletter_test::prepare_note($t2);
$n3 = Newsletter_test::prepare_note($t2);

$temp_user1 = Newsletter_test::prepare_user('马金超');

$message1 = Newsletter_test::prepare_message($user1);
$message2 = Newsletter_test::prepare_message($user1);
$message3 = Newsletter_test::prepare_message($user1);

$order1 = Newsletter_test::prepare_requesting_order('订单1');
$order2 = Newsletter_test::prepare_requesting_order('订单2');
$order3 = Newsletter_test::prepare_requesting_order('订单3');

$order4 = Newsletter_test::not_received_order('订单4');
$order5 = Newsletter_test::not_received_order('订单5');

$order6 = Newsletter_test::received_stocks_order('订单6');
$order7 = Newsletter_test::received_stocks_order('订单7');

$stock = Newsletter_test::add_stock_order($order7, 3);

Newsletter_test::add_stock_use($user1, 1);
Newsletter_test::add_stock_use($user2, 3);
Newsletter_test::add_stock_use($user2, -10);

$node1 = Newsletter_test::add_node('node1');
$node2 = Newsletter_test::add_node('node2');

$sensor1 = Newsletter_test::add_node_sensor($node1, 'sensor1');
$sensor2 = Newsletter_test::add_node_sensor($node2, 'sensor2');

Newsletter_test::make_env_sensor_alarm($sensor1);
Newsletter_test::make_env_sensor_alarm($sensor2);

$grant1 = Newsletter_test::add_grant('经费1', '项目1', 200);
$grant2 = Newsletter_test::add_grant('经费2', '项目2', 400);

$user = O('user', ['id'=>1]);
$component1 = Newsletter_test::add_schedule($user, $dtstart, $dtend);
$component2 = Newsletter_test::add_lab_schedule($user->lab, $dtstart, $dtend);

$categories = Config::get('newsletter.categories');

foreach ($categories as $key => $value) {
	switch ($key) {
		case 'research':
			$content = Event::trigger('newsletter.get_contents['.$key.']', $user1);
			$view = file_get_contents(Unit_Test::data_path('view/research.html'));
			if ($content == $view) {
				$value = TRUE;
			}
			else {
				$value = FALSE;
			}
			Unit_Test::assert('科研进展', $value);
			break;
		case 'extra':
			$content = Event::trigger('newsletter.get_contents['.$key.']', $user1);
			$view = file_get_contents(Unit_Test::data_path('view/extra.html'));
			if ($content == $view) {
				$value = TRUE;
			}
			else {
				$value = FALSE;
			}
			Unit_Test::assert('其他更新', $value);
			break;
		case 'finance':
			$content = Event::trigger('newsletter.get_contents[finance]', $user1);
			$view = file_get_contents(Unit_Test::data_path('view/finance.html'));
			if ($content == $view) {
				$value = TRUE;
			}
			else {
				$value = FALSE;
			}
			Unit_Test::assert('财务管理', $value);
		break;
		case 'security':
			$content = Event::trigger('newsletter.get_contents[security]', $user1);
			$view = file_get_contents(Unit_Test::data_path('view/security.html'));
			if (trim($content) == trim($view)) {
				$value = TRUE;
			}
			else {
				$value = FALSE;
			}
			Unit_Test::assert('实验室安全', $value);

		break;
		case 'schedule':
			$content = Event::trigger('newsletter.get_contents[schedule]', $user);
			$view = '<li>今日您有<a href="http://./!schedule">1</a>个日程安排.</li><li>今日您的实验室有<a href="http://./!schedule/index.lab">1</a>个日程安排.</li><li>组会 ('.Date::format($dtstart).'~'.Date::format($dtend, 'H:i:s').'), 未设置会议室, 报告人 .</li>';
            if (trim($content) == trim($view)) {
				$value = TRUE;
			}
			else {
				$value = FALSE;
			}
			Unit_Test::assert('日程管理', $value);
		break;
	}
}

class Newsletter_test {

	private static $_objects = [];
	
	static function enlist($object) {
		self::$_objects[] = $object;
	}

	static function add_lab_schedule($lab, $dtstart, $dtend) {
		
		$calendar = O('calendar', ['parent'=>$lab]);
		$calendar->parent = $lab;
		$calendar->save();
		$component = O('cal_component');
		$component->calendar = $calendar;
		$component->dtstart = $dtstart;
		$component->dtend = $dtend;
		$component->organizer = $lab->creator;
		$component->save();
		Unit_Test::assert("component 实验室日程 $component->id  创建成功", $component->id > 0);
		self::enlist($component);
		return $component;	
	}

	static function add_schedule($user, $dtstart, $dtend) {
		
		$calendar = O('calendar', ['parent'=>$user]);
		$calendar->type = 'schedule';
		$calendar->parent = $user;
		$calendar->save();
		$component = O('cal_component');
		$component->calendar = $calendar;
		$component->dtstart = $dtstart;
		$component->type = 'schedule';
		$component->dtend = $dtend;
		$component->organizer = $user;
		$component->save();
		Unit_Test::assert("component 个人日程 $component->id  创建成功", $component->id > 0);
		self::enlist($component);
		return $component;	
	}

	static function make_env_sensor_alarm($sensor) {
		ORM_Model::db('env_sensor_alarm');
		$time= Date::time() - 86400;
		$env_sensor_alarm = O('env_sensor_alarm');
		$env_sensor_alarm->sensor_id = $sensor->id;
		$env_sensor_alarm->dtstart = $time;
		$env_sensor_alarm->dtend = Date::time();
		$env_sensor_alarm->save();
		Unit_Test::assert("env_sensor_alarm $env_sensor_alarm->id  创建成功", $env_sensor_alarm->id > 0);
		self::enlist($env_sensor_alarm);
		return $env_sensor_alarm;	
	}

	static function add_node($name) {

		ORM_Model::db('env_node');
		$time= Date::time() - 86400;
		$node = O('env_node');
		$node->ctime = $time;
		$node->name = $name;
		$node->save();

		Unit_Test::assert("node $node->id  创建成功", $node->id > 0);
		self::enlist($node);
		return $node;	
	}

	static function add_node_sensor($node, $name) {

		ORM_Model::db('env_sensor');
		$time= Date::time() - 86400;
		$sensor = O('env_sensor');
		$sensor->node_id = $node->id;
		$sensor->name = $name;
		$sensor->save();

		Unit_Test::assert("sensor $sensor->id  创建成功", $sensor->id > 0);
		self::enlist($sensor);
		return $sensor;	

	}

	static function add_equipment_fault($equipment) {
		ORM_Model::db('eq_status');
		$time= Date::time() - 86400;
		$equipment->status = 1;
		$equipment->save();
		$eq_status = O('eq_status');
		$eq_status->dtstart = $time;
		$eq_status->status = 1;
		$eq_status->equipment_id = $equipment->id;
		$eq_status->save();

		Unit_Test::assert("仪器故障记录 $eq_status->id  创建成功", $eq_status->id > 0);
		self::enlist($eq_status);
		return $eq_status;	
	}

	static function add_equipment_recover($equipment) {

		$equipment->status = 0;
		$equipment->save();
		Unit_Test::assert("仪器 $equipment->name  恢复", $equipment->id > 0);
		self::enlist($equipment);
		return $equipment;	
	}


	static function add_grant($name, $project, $balance) {
		ORM_Model::db('grant');
		$time= Date::time() - 86400;

		$grant = O('grant');

		$grant->ctime = $time;
		$grant->project = $project;
		$grant->balance = $balance; 
		$grant->save();

		Unit_Test::assert("经费 $grant->project  创建成功", $grant->id > 0);
		self::enlist($grant);
		return $grant;	
	}

	static function add_stock_use($user, $quantity) {
		ORM_Model::db('stock_use');
		$time= Date::time() - 86400;

		$stock_use = O('stock_use');

		$stock_use->ctime = $time;
		$stock_use->user_id = $user->id;
		$stock_use->quantity = $quantity; 
		$stock_use->save();

		Unit_Test::assert("$user->name 使用库存中的货品", $stock_use->id > 0);
		self::enlist($stock_use);
		return $stock_use;	
	}
	static function add_stock_order($order, $status) {
		ORM_Model::db('stock');
		$time= Date::time() - 86400;

		$stock = O('stock');
		$stock->order_id = $order->id;
		$stock->ctime = $time;
		$stock->status = $status;
		$stock->content = uniqid();
		$stock->save();

		Unit_Test::assert("已入库订单 $order->product_name", $stock->id > 0);
		self::enlist($stock);
		self::enlist($order);
		return $stock;	
	}
	static function received_stocks_order($name) {
		ORM_Model::db('order');
		$time= Date::time() - 86400;
		$order = O('order');
		$order->product_name = $name;
		$order->receive_date = $time;
		$order->status = 3;
		$order->save();
		Unit_Test::assert("已到货订单 $order->product_name", $order->id > 0);
		self::enlist($order);
		return $order;		
	}

	static function not_received_order($name) {
		ORM_Model::db('order');
		$time= Date::time() - 86400;
		$order = O('order');
		$order->product_name = $name;
		$order->purchase_date = $time;
		$order->price = 10;
		$order->save();
		Unit_Test::assert("已订出订单 $order->product_name", $order->id > 0);
		self::enlist($order);
		return $order;		
	}

	static function prepare_requesting_order($name) {
		ORM_Model::db('order');
		$time= Date::time() - 86400;
		$order = O('order');
		$order->product_name = $name;
		$order->request_date = $time;
		$order->save();
		Unit_Test::assert("提交订单 $order->product_name", $order->id > 0);
		self::enlist($order);
		return $order;
	}

	static function prepare_project($name) {
			ORM_Model::db('tn_project');
			$time= Date::time() - 86400;
			$project = O('tn_project');
			$project->title = $name;
			$project->ctime = $time;
			$project->save();
			Unit_Test::assert("建立 $project->title", $project->id > 0);
			self::enlist($project);
			return $project;
	}

	static function prepare_task($parent_object) {

		ORM_Model::db('tn_task');

		$task = O('tn_task');
		$task->title = '测试任务'.uniqid();

		if (get_class($parent_object) == 'Tn_Project_Model') {
			$task->project = $parent_object;
		}
		else if (get_class($parent_object) == 'Tn_Task_Model') {
			$task->parent_task = $parent_object;
			$task->project = $parent_object->project;
		}

		$task->save();
		Unit_Test::assert("建立 $task->title", $task->id > 0);

		self::enlist($task);
		return $task;
	}

	static function prepare_note($task) {

		$time= Date::time() - 86400;
		ORM_Model::db('tn_note');

		$note = O('tn_note');
		$note->title = '测试笔记'.uniqid();
		$note->task = $task;
		$note->ctime = $time;
		$note->save();
		Unit_Test::assert("建立 $note->title", $note->id > 0);
		self::enlist($note);
		return $note;
	}

	static function prepare_message($user) {

		ORM_Model::db('message');

		$message = O('message');
		$message->is_read = 0;
		$message->receiver_id = $user->id;
		$message->title = '消息提醒'.uniqid();
		$message->save();
		Unit_Test::assert("建立 $message->title", $message->id > 0);
		self::enlist($message);
		return $message;
	}

	static function prepare_user($name) {

		$time= Date::time() - 86400;
		ORM_Model::db('user');

		$user = O('user');
		$user->name = $name;
		$user->ctime = $time;
		$user->save();
		Unit_Test::assert("新建用户 $user->name", $user->id > 0);
		self::enlist($user);
		return $user;
	}

}
?>
