<?php
class CLI_EQ_Stat{
	/*
   * @file      list_eq_stat_info_everyday.php
   * @author 	Rui Ma <rui.ma@geneegroup.com>
   * @date		2011.12.6
   *
   * @brief	    每天01:00执行该文件获取前一天的eq_stat的信息	
   * 
   * usage: SITE_LAB=cf LAB_ID=test ./list_eq_stat_info.php
   */
	static function save_data_everyday() {
		/* 
		 *	计算出来需要锁定并且统计的时间值
		 *	设定记录修改添加的deadline值 
		 */
		$times = Config::get('eq_stat.lock.time', ['value'=>4, 'format'=>'m']);

		$interval = Date::convert_interval($times['value'], $times['format']);

		$now = getdate(time());
		$dtend = mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']) - 1;

		$dtstart = $dtend - $interval + 1;

		$last_stat = Q("eq_stat:sort(time D)")->limit(1)->current();
		$stat_start  = $last_stat->time;

		/* 1、需要先锁定的这一段时间范围内的数据 */
		$db = Database::factory();

		$tran_query = "UPDATE billing_transaction SET status = 1 WHERE ctime <= %d";
		if (!file_exists(LAB_PATH . 'modules/eq_stat/config/oauth.php')) {
			$dtstart  = ($stat_start>0 && $stat_start < $dtstart) ? $stat_start : $dtstart;
		}

		$ret = $db->query($tran_query, $dtstart);

		// 如果有新统计我就不跑这个脚本了
		if (!file_exists(LAB_PATH . 'modules/eq_stat/config/oauth.php')) {
			/* 2、将该时间范围的数值进行统计 */
			$tmp = $dtstart;
			while ($tmp <= $dtend) {
				$date = Date::format($tmp, 'Y-m-d');
				error_log("正在同步更新 $date 数据....");
				EQ_Stat::do_stat_list_save($tmp, $tmp + 3600*24 - 1);
				$tmp += 3600*24;
			}
		}

		/* 3、设置系统记录锁定的deadline值 */
        Event::trigger('admin.support.submit.eq_record.transaction_locked_deadline', $dtstart);
	}

	static function create_data() {
		fwrite(STDOUT, '请输入开始时间xxxx-xx-xx：');

		$dtstart = fgets(STDIN);

		fwrite(STDOUT, '请输入结束时间xxxx-xx-xx：');

		$dtend = fgets(STDIN);

		$dtstart = strtotime($dtstart ?: 0);
		$dtend = strtotime($dtend);

		$tmp = $dtstart;
		while ($tmp <= $dtend) {
			$date = Date::format($tmp, 'Y-m-d');
			echo "正在同步更新 $date 数据....       ";
			EQ_Stat::do_stat_list_save($tmp, $tmp + 3600*24 - 1);
			Upgrader::echo_success(sprintf("数据同步更新成功!"));
			$tmp += 3600*24;
		}

		Upgrader::echo_success("数据更新完毕！^-^");		
	}

	static function create_data_equipment() {
		fwrite(STDOUT, '请输入仪器ID：');

		$id = fgets(STDIN);

		fwrite(STDOUT, '请输入开始时间xxxx-xx-xx：');

		$dtstart = fgets(STDIN);

		fwrite(STDOUT, '请输入结束时间xxxx-xx-xx：');

		$dtend = fgets(STDIN);

		$dtstart = strtotime($dtstart ?: 0);
		$dtend = strtotime($dtend);

		$tmp = $dtstart;
		while ($tmp <= $dtend) {
			$date = Date::format($tmp, 'Y-m-d');
			echo "正在同步更新 $date 数据....       ";
			EQ_Stat::do_stat_list_save_equipment($tmp, $tmp + 3600*24 - 1, FALSE, $id);
			Upgrader::echo_success(sprintf("数据同步更新成功!"));
			$tmp += 3600*24;
		}

		Upgrader::echo_success("数据更新完毕！^-^");
	}

	static function do_stat_list_save_by_config(){

		$times = Config::get('eq_stat.lock.time', ['value'=>4, 'format'=>'m']);
		$interval = Date::convert_interval($times['value'], $times['format']);

		$now = getdate(time());
		$dtend = mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']) - 1;
		$dtstart = $dtend - $interval + 1;

		$last_stat = Q("eq_stat:sort(time D)")->limit(1)->current();
		$stat_start  = $last_stat->time;

		$dtstart  = ($stat_start>0 && $stat_start < $dtstart) ? $stat_start : $dtstart;

		$tmp = $dtstart;

		while ($tmp <= $dtend) {
		    $date = Date::format($tmp, 'Y-m-d');
		    echo "正在同步更新 $date 数据....";
			EQ_Stat::do_stat_list_save($tmp, $tmp + 3600 * 24 - 1);
			Upgrader::echo_success(sprintf("数据同步更新成功!"));
			$tmp += 3600 * 24;
		}		
	}

	static function repair(){
        $dtstart = mktime(0, 0, 0, 1, 1, 2016) - 1;
        $tmp = $dtstart;
        $dtend = time();
        while ($tmp <= $dtend) {
            $date = Date::format($tmp, 'Y-m-d');
            error_log("正在同步更新 $date 数据....");
            EQ_Stat::do_stat_list_save($tmp, $tmp + 3600*24 - 1);
            $tmp += 3600*24;
        }
    }
}