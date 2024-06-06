<?php

/*
 * @file test_anc.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 测试treenote中project task note是否可正常关联
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/treenote/test_anc
 */
if (!Module::is_installed('treenote')) return true;
require_once(ROOT_PATH. 'unit_test/helpers/environment.php');
Unit_Test::echo_title('准备环境');
    Environment::init_site();
Unit_Test::echo_endl();

Unit_Test::echo_title('测试');

$p1 = Lock_Test_1::prepare_project();
$t1 = Lock_Test_1::prepare_task($p1);
$t2 = Lock_Test_1::prepare_task($t1);
$t3 = Lock_Test_1::prepare_task($t2);
$t4 = Lock_Test_1::prepare_task($t3);
$n1 = Lock_Test_1::prepare_note($t4);
$n2 = Lock_Test_1::prepare_note($t3);
$n3 = Lock_Test_1::prepare_note($t2);
$t5 = Lock_Test_1::prepare_task($p1);
$p2 = Lock_Test_1::prepare_project();
Unit_Test::echo_endl();

//  p1
//  |- t1
//  |   | - t2 - t3 - t4 - n1
//  |        |    | - n2
//  |        |
//  |        | - n3
//  |- t5
//
//  p2
Unit_Test::assert("t4 -> t1", $t1->has_descendant($t4));
Unit_Test::assert("n1 -> t4", in_array($n1->id, $t4->all_note_ids()));
Unit_Test::assert("n1 -> t1", in_array($n1->id, $t1->all_note_ids()));
Unit_Test::assert("t1 -> p1", $t1->project->id == $p1->id);
Unit_Test::assert("n1 -> p1", $n1->project->id == $p1->id);
Unit_Test::assert("t4 !> t5", !$t5->has_descendant($t4));
Unit_Test::assert("n1 !> t5", !in_array($n1->id, $t5->all_note_ids()));
Unit_Test::assert("t1 !> p2", $t1->project->id != $p2->id);
Unit_Test::assert("n1 !> p2", !in_array($n1->id, $p2->all_note_ids()));
Unit_Test::echo_title("switch t2's parent to t5");
//  p1
//  |- t1
//  |- t5
//  |   | - t2 - t3 - t4 - n1
//  |        |    | - n2
//  |        |
//  |        | - n3
//
//  p2
$t2->parent_task = $t5;
$t2->save();
$p1 = ORM_Model::refetch($p1);
$t1 = ORM_Model::refetch($t1);
$t2 = ORM_Model::refetch($t2);
$t3 = ORM_Model::refetch($t3);
$t4 = ORM_Model::refetch($t4);
$n1 = ORM_Model::refetch($n1);
$n2 = ORM_Model::refetch($n2);
$n3 = ORM_Model::refetch($n3);
$t5 = ORM_Model::refetch($t5);
$p2 = ORM_Model::refetch($p2);
Unit_Test::assert("t4 !> t1", !in_array($t1->id, $t4->ancestor_ids()));
Unit_Test::assert("n1 -> t4", in_array($n1->id, $t4->all_note_ids()));
Unit_Test::assert("n1 !> t1", !in_array($n1->id, $t1->all_note_ids()));
Unit_Test::assert("t1 -> p1", $t1->project->id == $p1->id);
Unit_Test::assert("n1 -> p1", $n1->project->id == $p1->id);
Unit_Test::assert("t4 -> t5", in_array($t5->id, $t4->ancestor_ids()));
Unit_Test::assert("n1 -> t5", in_array($n1->id, $t5->all_note_ids()));
Unit_Test::assert("t1 !> p2", $t1->project->id != $p2->id);
Unit_Test::assert("n1 !> p2", !in_array($n1->id, $p2->all_note_ids()));



//  p1
//  |- t1
//
//  p2
//  |- t5
//  |   | - t2 - t3 - t4 - n1
//  |        |    | - n2
//  |        |
//  |        | - n3
Unit_Test::echo_title("switch t5's project to p2");
$t5->project = $p2;
$t5->save();
$p1 = ORM_Model::refetch($p1);
$t1 = ORM_Model::refetch($t1);
$t2 = ORM_Model::refetch($t2);
$t3 = ORM_Model::refetch($t3);
$t4 = ORM_Model::refetch($t4);
$n1 = ORM_Model::refetch($n1);
$n2 = ORM_Model::refetch($n2);
$n3 = ORM_Model::refetch($n3);
$t5 = ORM_Model::refetch($t5);
$p2 = ORM_Model::refetch($p2);

/* print($t1->project); */
/* print($t5->project); */
/* print($t2->project); */


Unit_Test::assert("t4 -> t5", in_array($t5->id, $t4->ancestor_ids()));
Unit_Test::assert("n1 -> t5", in_array($n1->id, $t5->all_note_ids()));
Unit_Test::assert("t5 -> p2", $t5->project->id == $p2->id);
Unit_Test::assert("t4 -> p2", $t4->project->id == $p2->id);
Unit_Test::assert("n1 !> p1", !in_array($n1->id, $p1->all_note_ids()));
Unit_Test::assert("n1 -> p2", $n1->project->id, $p2->id);

Unit_Test::echo_title("撤销环境");
	Lock_Test_1::destroy();
Unit_Test::echo_endl();

Unit_Test::echo_title("fin");

class Lock_Test_1{

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
