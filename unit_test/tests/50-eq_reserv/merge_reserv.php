<?php

/*
 * @file merge_reserv.php 
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器预约模块仪器预约合并功能测试脚本(环境架设辅助脚本)
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_reserv/merge_reserv
 */

define('DISABLE_NOTIFICATION', TRUE);
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
class Test_Merge_Reserv {

	private $db;
	private $backup_file;
	private $equipment;
	private $calendar;
	private $incharger;
	private $user;

	public  function init() {
		Unit_Test::echo_text('预约合并，测试开始');
		$this->backup_file = tempnam('/tmp','database');
		$this->db = Database::factory();
		$ret = $this->db->snapshot($this->backup_file);
		Unit_Test::assert('备份数据库',$ret);


		$this->db->empty_database();
		Database::reset();
		Unit_Test::assert('清空数据库',true);

		ORM_Model::destroy('role');
		ORM_Model::destroy('lab');
		ORM_Model::destroy('user');
		ORM_Model::destroy('equipment');
		ORM_Model::destroy('calendar');
		ORM_Model::destroy('cal_component');
		
		Environment::add_user('genee');
		$user = Environment::add_user('unit_test使用者');
		$this->user = $user;

		$incharger = Environment::add_user('unit_test负责人');
		$this->incharger = $incharger;
		
		$equipment = Environment::add_equipment('Unit_test仪器',$incharger,$incharger);

		$equipment->accept_reserv = true;
		$equipment->accept_merge_reserv = true;
		$equipment->merge_reserv_interval = 60*40;
		$equipment->save();
		$this->equipment = $equipment;

		$calendar = O('calendar');
		$calendar->parent = $equipment;
		$calendar->name = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
		Unit_Test::assert('添加日历', $calendar->save());
		$this->calendar = $calendar;

		//关闭邮件
		Config::set('notification.handlers',[]);

	}

	public function tear_down() {
		$ret = $this->db->restore($this->backup_file);
		Unit_Test::assert('恢复数据库',$ret);
		Unit_Test::echo_text('预约合并，测试结束');
	}

	public function new_com($dtstart, $dtend) {
		$component = O('cal_component');
		$component->organizer = $this->user;
		$component->calendar = $this->calendar;
		$component->dtstart = $this->time($dtstart);
		$component->dtend = $this->time($dtend);
        $component->type = Cal_Component_Model::TYPE_VEVENT;
		return $component;
	}
	/*
	 * 断言方法
	 * 此方法会自动对使用记录进行判断
	 * $title：标题
	 * $components：初始化的components数据
	 * $code:测试代码
	 * $changes:应该改变的数据,设空表示此条记录应该以被删除。
	 */
	static $NUM=1;
	public  function assert($title, $components, $code, $changes, $filp=FALSE) {
		// 按components的属性建立对象,organizer和calendar自动赋值
		foreach($components as $name=>$vals ) {
			$component = O('cal_component');
			$component->organizer = $this->user;
			$component->calendar = $this->calendar;
            //设定类型
            $component->type = Cal_Component_Model::TYPE_VEVENT;

			foreach($vals as $key=>$val){
				$component->$key = $val;
			}
			$component->save();
			// 因为记录id会在合并预约时被更改，所以需要单独记录id。
			$component->o_id = $component->id;
			//预约使用中
			if( $vals['using'] ) {
				$record = O('eq_record',['reserv'=>$component]);
				$record->dtend = 0;
				$record->user = $this->user;
				$record->is_reserv = 0;
				$record->reserv_id = 0;
				$record->save();
				$this->equipment->is_using = TRUE;
				$this->equipment->save();
			}
			//记录锁定
			if( $vals['lock'] ) {
				$record = O('eq_record',['reserv'=>$component]);
				$record->is_locked = true;
				$record->save();
			}
			$$name = $component;
		}
		// 执行测试代码
		eval($code);

		// 开始断言
		$assert = true;
		foreach($components as $name=>$vals ) {
			//从数据库中提取数据
			$component = O('cal_component',$$name->o_id);
			// 检查应该被删除的记录
			if($changes[$name] === NULL){
				if( $component->id || Q("eq_record[reserv_id={$$name->o_id}]")->total_count() ) {
					$assert = false;
                    break;
				} else {
					continue;
				}
			}
			// 检查属性是否按期望的改变
            if ($filp) {
                $vals = array_merge($vals, (array) $changes[$name]);
            }
            else {
                $vals = array_merge((array) $changes[$name], $vals);
            }
			foreach($vals as $key=>$val){
				if( $component->$key != $val) {
					$assert = false;
                    break 2;
				}
			}
			// 检查使用记录
			$records = Q("eq_record[reserv={$component}]");
			$record  = $records->current();
			if ($vals['using'] ) {
				if( $records->total_count() != 1
				||  $record->dtstart != $component->dtstart ) {
					$assert = false;
                    break;
				}
			} else {
				if ($records->total_count() != 1
				||  $record->dtstart != $component->dtstart
				||  $record->dtend != $component->dtend ) {
					$assert = false;
                    break;
				}
			}
		}
		echo str_pad((self::$NUM++).'.',4,'0',STR_PAD_LEFT);
		Unit_Test::assert($title,$assert);

		// 清除数据
        $this->db->query('TRUNCATE TABLE eq_record');
        $this->db->query('TRUNCATE TABLE cal_component');
	}

	// 整理时间
	static $time;
	public  function time($hour) {
		if( !self::$time ) {
			self::$time = time();
		}
		return self::$time + 60*60*$hour;
	}

}
