<?php

/*
 * @file test_lock.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试project task note锁定功能
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/test_lock
 */
if (!Module::is_installed('treenote')) return true;
require_once(ROOT_PATH. 'unit_test/helpers/environment.php');
Unit_Test::echo_title('准备环境');
    Environment::init_site();
Unit_Test::echo_endl();

Unit_Test::echo_title('测试');

$p1 = Lock_Test_2::prepare_project();
$t1 = Lock_Test_2::prepare_task($p1);
Unit_Test::assert("$t1->title 是 $p1->title 的顶级任务", ($t1->project->id == $p1->id));// && (!$t1->parent_task));
$t2 = Lock_Test_2::prepare_task($t1);
Unit_Test::assert("$t2->title 是 $t1->title 的子任务", ($t2->parent_task->id == $t1->id) && ($t2->project->id = $p1->id));
$t3 = Lock_Test_2::prepare_task($p1);
Unit_Test::assert("$t3->title 是 $p1->title 的顶级任务", ($t3->project->id == $p1->id));// && (!$t3->parent_task));
$n1 = Lock_Test_2::prepare_note($t1);
Unit_Test::assert("$n1->title 是 $t1->title 的笔记", $n1->task->id == $t1->id);
Unit_Test::echo_endl();

//  p1
//  |- t1 - n1
//  |   | - t2
//  |- t3

$p1->lock();
Unit_Test::echo_title('lock a father will lock all his children');
$p1 = ORM_Model::refetch($p1);
Unit_Test::assert("p1($p1->id) is locked", $p1->is_locked);
$t1 = ORM_Model::refetch($t1);
Unit_Test::assert("t1($t1->id) is locked", $t1->is_locked);
$t2 = ORM_Model::refetch($t2);
Unit_Test::assert("t2($t2->id) is locked", $t2->is_locked);
$t3 = ORM_Model::refetch($t3);
Unit_Test::assert("t3($t3->id) is locked", $t3->is_locked);
$n1 = ORM_Model::refetch($n1);
Unit_Test::assert("n1($n1->id) is locked", $n1->is_locked);
Unit_Test::echo_endl();

Unit_Test::echo_title('unlock a father will only unlock himself');
$p1->unlock();
Unit_Test::assert('p1 is UNlocked', !$p1->is_locked);
$t1 = ORM_Model::refetch($t1);
Unit_Test::assert('t1 is still locked', $t1->is_locked);
$t2 = ORM_Model::refetch($t2);
Unit_Test::assert('t2 is still locked', $t2->is_locked);
$t3 = ORM_Model::refetch($t3);
Unit_Test::assert('t3 is still locked', $t3->is_locked);
$n1 = ORM_Model::refetch($n1);
Unit_Test::assert('n1 is still locked', $n1->is_locked);
Unit_Test::echo_endl();

Unit_Test::echo_title('lock the father again');
$p1->lock();
Unit_Test::echo_title('and unlock a note will unlock all his ancestors');
$n1->unlock();
Unit_Test::assert('n1 is UNlocked', !$n1->is_locked);
$t1 = ORM_Model::refetch($t1);
Unit_Test::assert('t1 is locked', $t1->is_locked);
$p1 = ORM_Model::refetch($p1);
Unit_Test::assert('p1 is locked', $p1->is_locked);
$t2 = ORM_Model::refetch($t2);
Unit_Test::assert('t2 is still locked', $t2->is_locked);
$t3 = ORM_Model::refetch($t3);
Unit_Test::assert('t3 is still locked', $t3->is_locked);
Unit_Test::echo_endl();

Unit_Test::echo_title('lock a task will lock all his descendents');
$t1->lock();
Unit_Test::assert('t1 is locked', $t1->is_locked);
$n1 = ORM_Model::refetch($n1);
Unit_Test::assert('n1 is locked', $n1->is_locked);
$t2 = ORM_Model::refetch($t2);
Unit_Test::assert('t2 is still locked', $t2->is_locked);
$p1 = ORM_Model::refetch($p1);
Unit_Test::assert('p1 is locked', $p1->is_locked);
$t3 = ORM_Model::refetch($t3);
Unit_Test::assert('t3 is still locked', $t3->is_locked);
Unit_Test::echo_endl();

Unit_Test::echo_title('lock the father again');
$p1->lock();
Unit_Test::echo_title('and unlock t2');
$t2->unlock();
Unit_Test::assert('t2 is locked', $t2->is_locked);
$t1 = ORM_Model::refetch($t1);
Unit_Test::assert('t1 is locked', $t1->is_locked);
$p1 = ORM_Model::refetch($p1);
Unit_Test::assert('p1 is locked', $p1->is_locked);
$t3 = ORM_Model::refetch($t3);
Unit_Test::assert('t3 is still locked', $t3->is_locked);
$n1 = ORM_Model::refetch($n1);
Unit_Test::assert('n1 is still locked', $n1->is_locked);

Unit_Test::echo_title("撤销环境");
	Lock_Test_2::destroy();
Unit_Test::echo_endl();

Unit_Test::echo_title("fin");

class Lock_Test_2 {

	private static $_objects = [];	

	static function enlist($object) {
		self::$_objects[] = $object;
	}

	static function destroy() {
		foreach(self::$_objects as $object) {
			$object->delete();
		}
		self::$_objects = NULL;
	}
	
	static function prepare_project() 
	{
		ORM_Model::db('tn_project');

		$project = O('tn_project');
		$project->title = '测试项目'.uniqid();
		$project->save();
		Unit_Test::assert("建立 $project->title", $project->id > 0);
		self::enlist($project);
		return $project;
	}

	static function prepare_task($parent_object)
	{
		
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

	static function prepare_note($task)
	{
		ORM_Model::db('tn_note');
		
		$note = O('tn_note');
		$note->title = '测试笔记'.uniqid();
		$note->task = $task;
		$note->save();
		Unit_Test::assert("建立 $note->title", $note->id > 0);
		self::enlist($note);
		return $note;
	}
}
