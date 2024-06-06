#!/usr/bin/env php
<?php

# 1. 该脚本用来每周对 lims2/cf 的使用进行统计
# 2. 运行脚本时, 统计范围为 { } 时段:
#    { 1 2 3 4 5 6 7 } 8 (今天)
# 3. 脚本输出为 tab 分割, 可直接复制粘贴到
#    电子表格 (numbers, excel) 中;
# 4. 若脚本输出中有错误, 则需检查错误原因, 若
#    错误可忽略, 则可在脚本运行时用 2>/dev/null
#    过滤;
# 5. TODO 该脚本可转为 cron 运行, 并将输出直接
#    邮件给相关人员.
#
# (Xiaopei Li@2013-09-22)

require dirname(__FILE__) . '/base.php';

// 打开缓存, 这样 echo 会输出到缓存中,
// 而缓存是可以提取为字符串, 而不直接打印
ob_start();


// 初始化变量
$lab_title = Config::get('page.title_default');

$stat_end = strtotime('midnight');
$stat_start = $stat_end - 604800;
$date_range = date('Y-m-d', $stat_start) . ' - ' . date('Y-m-d', $stat_end - 1);

define('STAT_START', $stat_start);
define('STAT_END', $stat_end);

$new_stat = [];
$metrics = Config::get('stat.metrics');


// 对需要统计的指标 (metric) 做统计
foreach ($metrics as $metric) {
	if (is_string($metric)) {
		$func = $metric;
		$opts = NULL;
	}
	else if (is_array($metric)) {
		$func = $metric['func'];
		$opts = $metric['opts'];
	}
	else {
		$new_stat[] = tr_echo([json_encode($metric), '配置有误'], FALSE);
		continue;
	}

	$result = call_user_func($func, $opts);

	if (is_array($result[0])) {
		// 可能返回多行
		foreach ($result as $tr) {
			$new_stat[] = tr_echo($tr, FALSE);
		}
	}
	else {
		$tr = $result;
		$new_stat[] = tr_echo($tr, FALSE);
	}
}


// 剔除 array 中值为 NULL 的项
$new_stat = array_filter($new_stat);

// 读取历史统计数据
$old_data = Lab::get('old_stat');

// 检查历史统计数据
$periods_th = 'periods';
$n_old_data = count($old_data[$periods_th]);
$old_data_array_placeholder = [];
for ($i = 0; $i < $n_old_data; $i++) {
	$old_data_array_placeholder[] = '--';
}
$old_data_string_placeholder = join(',', $old_data_array_placeholder);

// 输出表头
echo "{$lab_title},{$periods_th},";
/// 历史表头
$old_period_cols = $old_data[$periods_th];
if ($old_period_cols) {
	echo join(',', $old_period_cols);
	$new_old_cols = $old_period_cols;
}
else {
	echo $old_data_string_placeholder;
	$new_old_cols = $old_data_array_placeholder;
}
echo ",{$date_range}\n";

// 将新数据加入历史数据
$new_old_cols[] = $date_range;
$old_data[$periods_th] = $new_old_cols;

// 输出数据
foreach ($new_stat as $row) {
	list($module, $title, $new_result_data) = $row;

	echo "{$module},{$title},";

	/// 历史数据
	$old_cols = $old_data[$title];
	if ($old_cols) {
		echo join(",", $old_cols);
		$new_old_cols = $old_cols;
	}
	else {
		echo $old_data_string_placeholder;
		$new_old_cols = $old_data_array_placeholder;
	}

	echo ",{$new_result_data}\n";

	// 将新数据加入历史数据
	$new_old_cols[] = $new_result_data;
	$old_data[$title] = $new_old_cols;

}

// 获得缓存
$output = ob_get_contents();

ob_end_clean();

// 保存历史数据
Lab::set('old_stat', $old_data);


// 输出
$subject = $lab_title . ' - 用户使用统计 - ' . $date_range;

$email = new Email;

$receiver = Config::get('stat.receiver');

$email->to($receiver);

$email->subject($subject);

$csv_file = Config::get('system.tmp_dir'). '/stat.csv';
@touch($csv_file);
file_put_contents($csv_file, $output);

$base_url = Config::get('system.base_url');

$lab_name = Config::get('lab.name');

$body = "用户使用统计({$lab_title})\t\t站点名称: {$lab_name}\t\t链接地址:{$base_url}\t\t统计结果见附件!\n\n";

$email->body($body);

$email->attachment($csv_file);

$email->send();
File::delete($csv_file);

// stat functions
function get_logs($log_type, $return_array = false) {

	$logs = [];

	$log_name = LAB_PATH . 'logs/' . $log_type . '.log';

	for ($d = STAT_START; $d <= STAT_END; $d += 86400) {
		$logs[] = $log_name . '-' . date('Ymd', $d) . '*';
		// 由于日志可能是普通文件, 可能 ,gz, 所以最后用 *
	}

	if ($return_array) {
		return $logs;
	}
	else {
		return join(' ', $logs);
	}

	// $logs 不能做 escapeshellarg(), 因为其中有 *, escape 后会加 ''

}

function get_logs_old($log_type, $return_array = false) {

	$logs = [];

	$log_path = LAB_PATH . 'logs/';
	$log_name = $log_type . '*';

	$logs = exec("find $log_path -name \"$log_name\" -mtime -7  | tr \"\n\" ' '");

	if ($return_array) {
		return explode(' ', $logs);
	}
	else {
		return $logs;
	}

	// $logs 不能做 escapeshellarg(), 因为其中有 *, escape 后会加 ''

}


// CF-成员
// 已激活成员总数
// （发送报告一刻系统内的绝对成员数量）
function get_n_active_user() {
	return ['成员', '已激活成员总数', Q('user[atime>0]')->total_count()];
}

// CF-成员
// 未激活成员总数
// （发送报告一刻系统内的绝对成员数量）
function get_n_inactive_user() {
	return ['成员', '未激活成员总数', Q('user[atime<=0]')->total_count()];
}

// CF-成员
// 新增已激活成员数量
// （在发送周期内的新注册的成员数量）
function get_n_new_active_user() {
	return ['成员', '新增已激活成员数量', Q('user[atime>0][ctime>' . STAT_START . '][ctime<=' . STAT_END . ']')->total_count()];
}

// CF-成员
// 新增未激活成员数量
// （在发送周期内的新注册的成员数量）
function get_n_new_inactive_user() {
	return ['成员', '新增未激活成员数量', Q('user[atime<=0][ctime>' . STAT_START . '][ctime<=' . STAT_END . ']')->total_count()];
}

// CF-成员
// 全部成员的总登录成功次数
// （在发送周期内的全部成员登录次数总和）
function get_n_logon_succ() {
	$logs = get_logs('logon');

	$value = 0;

	$cmd = 'find . -name "$logs"';

	if (exec($cmd)) {
		$cmd = "zgrep -s '登入成功' $logs | wc -l ";
		$value = exec($cmd);
	}
	
	return ['成员', '全部成员的总登录成功次数', $value];
}

function get_n_logon_succ_old() {
	$logs = get_logs_old('logon');

	$value = 0;
	$cmd = 'find . -name "$logs"';

	if ($logs && exec($cmd)) {
		for ($d = STAT_START; $d <= STAT_END; $d += 86400) {
			$date = date('Y/m/d', $d);

			$cmd = "zgrep -s '$date.*登入成功' $logs | wc -l ";

			$value += exec($cmd);
		}
	}

	return ['成员', '全部成员的总登录成功次数', $value];
}


// CF-成员
// 张三 #2 (中心管理员)
// (销售提供用户姓名, CF-ID, 用户备注列表, 运维需给出当周内登录成功次数)
function get_n_user_logon_succ($opts) {
	$logs = get_logs('logon');

	$result = [];
	foreach ($opts['user_ids'] as $user_id) {
		$user = O('user', $user_id);
		if (!$user->id) {
			return [$user_id, '错误 ID'];
		}

		$user_name = get_object_name($user);

		$value = 0;
		$cmd = 'find . -name "$logs"';

		if (exec($cmd)) {
			$cmd = "zgrep -sF '[$user_id]登入成功' $logs | wc -l ";
			// error_log($cmd);
			$value = exec($cmd);
		}

		$result[] = ['成员', "$user_name 周期内登录次数", $value];
	}

	return $result;
}

function get_n_user_logon_succ_old($opts) {
	$logs = get_logs_old('logon');

	$result = [];
	foreach ($opts['user_ids'] as $user_id) {
		$user = O('user', $user_id);
		if (!$user->id) {
			return [$user_id, '错误 ID'];
		}

		$user_name = get_object_name($user);

		$value = 0;
		$cmd = 'find . -name "$logs"';

		if (exec($cmd)) {
			for ($d = STAT_START; $d <= STAT_END; $d += 86400) {
				$date = date('Y/m/d', $d);

				$cmd = "zgrep -s '$date.*\[$user_id\]登入成功' $logs | wc -l ";
				// error_log($cmd);
				$value += exec($cmd);
			}
		}

		$result[] = ['成员', "$user_name 周期内登录次数", $value];
	}

	return $result;
}


function get_n_all_user_except_genee_logon_succ($opts) {
	$logs = get_logs('logon');

	$result = [];

	foreach (Q('user') as $user) {

		if ($user->token == 'genee|database') continue;

		$user_name = get_object_name($user);

		$value = 0;

		$cmd = 'find . -name "$logs"';

		if (exec($cmd)) {
			$cmd = "zgrep -F '[{$user->id}]登入成功' $logs | wc -l ";
			$value = exec($cmd);
		}

		$result[] = ['成员', "$user_name 周期内登录次数", $value];
	}

	return $result;
}

function get_n_all_user_except_genee_logon_succ_old($opts) {
	$logs = get_logs_old('logon');

	$result = [];

	foreach (Q('user') as $user) {

		if ($user->token == 'genee|database') continue;

		$user_name = get_object_name($user);

		$value = 0;
		$cmd = 'find . -name "$logs"';

		if ($logs && exec($cmd)) {
			for ($d = STAT_START; $d <= STAT_END; $d += 86400) {
				$date = date('Y/m/d', $d);

				$cmd = "zgrep -s '$date.*\[{$user->id}\]登入成功' $logs | wc -l ";
				// error_log($cmd);
				$value += exec($cmd);
			}
		}


		$result[] = ['成员', "$user_name 周期内登录次数", $value];
	}

	return $result;
}

// CF-课题组
// 已激活课题组个数
// （绝对数量）
function get_n_active_lab() {
	return ['课题组', '已激活课题组总数', Q('lab[atime>0]')->total_count()];
}

// CF-课题组
// 未激活课题组个数
// （绝对数量）
function get_n_inactive_lab() {
	return ['课题组', '未激活课题组总数', Q('lab[atime<=0]')->total_count()];
}

// CF-课题组
// 新增已激活课题组数量
// （在发送周期内的新注册的课题组数量）
function get_n_new_active_lab() {
	return ['课题组', '新增已激活课题组数量', Q('lab[atime>0][ctime>' . STAT_START . '][ctime<=' . STAT_END . ']')->total_count()];
}

// CF-课题组
// 新增未激活课题组数量
// （在发送周期内的新注册的课题组数量）
function get_n_new_inactive_lab() {
	return ['课题组', '新增未激活课题组数量', Q('lab[atime<=0][ctime>' . STAT_START . '][ctime<=' . STAT_END . ']')->total_count()];
}

// CF-财务中心
// 财务中心数量
function get_n_billing_dept() {
	return ['财务中心', '财务中心数量', Q('billing_department')->total_count()];

}

// CF-财务中心
// 所有财务中心-财务账号数量
// （绝对数量）
function get_n_billing_acct() {
	return ['财务中心', '财务中心帐号数量', Q('billing_account')->total_count()];
}

// CF-财务中心
// 所有财务中心-余额
// （发送报告一刻的该财务中心余额）
function get_sum_billing_acct_balance() {
	$db = Database::factory();
	$value = $db->value('select sum(balance) from billing_account');
	return ['财务中心', '所有财务中心-余额', $value];
}

// CF-财务中心
// 所有财务中心-历史充值总额
// （发送报告周期内该财务中心的充值总额）
function get_sum_billing_acct_income() {
	$db = Database::factory();
	$value = $db->value('select sum(income) from billing_transaction');
	return ['财务中心', '所有财务中心-历史充值总额', $value];
}


// CF-财务中心
// 所有财务中心-周期内充值总额
// （发送报告周期内该财务中心的充值总额）
function get_sum_billing_acct_new_income() {
	$db = Database::factory();
	$value = $db->value('select sum(income) from billing_transaction where ctime > ' . STAT_START . ' and ctime <= ' . STAT_END . '');
	return ['财务中心', '所有财务中心-周期内充值总额', $value];
}

// CF-财务中心
// 所有财务中心-历史扣费总额
// （发送报告周期内该财务中心的扣费总额）
function get_sum_billing_acct_outcome() {
	$db = Database::factory();
	$value = $db->value('select sum(outcome) from billing_transaction');
	return ['财务中心', '所有财务中心-历史扣费总额', $value];
}

// CF-财务中心
// 所有财务中心-周期内扣费总额
// （发送报告周期内该财务中心的扣费总额）
function get_sum_billing_acct_new_outcome() {
	$db = Database::factory();
	$value = $db->value('select sum(outcome) from billing_transaction where ctime > ' . STAT_START . ' and ctime <= ' . STAT_END . '');
	return ['财务中心', '所有财务中心-周期内扣费总额', $value];
}

// CF-仪器管理
// 仪器台数
// CF-仪器管理
// 所有仪器所有设置的更新次数统计
// （在发送周期内）
// BUG #3461 修改仪器预约设置后未记录日志
function get_period_n_all_eq_modified() {
	$logs = get_logs('journal');
	$value = 0;
	$cmd = 'find . -name "$logs"';
	if (exec($cmd)) {
		$cmd = "zgrep -s '\[eq.*修改' $logs | grep -v '\]$' | wc -l ";
		// 由于符合 '\[eq.*修改' 的日志有很多是修改仪器记录而非设置的,
		// 且修改记录的日志皆以[记录ID]皆为, 所以增加 '\]$' 过滤这些日志
		$value = exec($cmd);
	}
	return ['仪器', '周期内所有仪器所有设置的更新次数统计', $value];
}




// CF-仪器管理
// 仪器A 所有设置的信息更新总数列表
// （在发送周期内）
function get_period_n_each_eq_use() {
	$logs = get_logs('journal');
	$dtstart = STAT_START;
	$dtend = STAT_END;
	$type_event = Cal_Component_Model::TYPE_VEVENT;

	$result = [];

	foreach (Q('equipment') as $equipment) {

		$eq_name = get_object_name($equipment);

		$value = 0;
		$cmd = 'find . -name "$logs"';

		if (exec($cmd)) {
			// 修改次数
			$cmd = "zgrep -s '\[eq.*修改.*\[{$equipment->id}\]' $logs | grep -v '\]$' | wc -l ";
			// 由于符合 '\[eq.*修改' 的日志有很多是修改仪器记录而非设置的,
			// 且修改记录的日志皆以[记录ID]皆为, 所以增加 '\]$' 过滤这些日志
			$value = exec($cmd);
		}
		
		$result[] = ['仪器', "$eq_name 周期内更新次数", $value];

		// 使用次数
		$value = Q("$equipment eq_record[dtstart={$dtstart}~{$dtend}]")->total_count();
		$result[] = ['仪器', "$eq_name 使用次数", $value];

		// 预约次数
		if ($equipment->accept_reserv) {
			$value = Q("calendar[parent_name=equipment][parent_id={$equipment->id}] cal_component[type=$type_event][ctime={$dtstart}~{$dtend}]")->total_count();
			$result[] = ['仪器', "$eq_name 周期内新建预约个数", $value];
		}


		// 送样次数
		if ($equipment->accept_sample) {
			$value = Q("$equipment eq_sample[ctime={$dtstart}~{$dtend}]")->total_count();
			$result[] = ['仪器', "$eq_name 周期内新建送样个数", $value];
		}


	}

	return $result;
}

// CF-仪器管理
// 仪器A 所有设置的信息更新总数列表
// （在发送周期内）
function get_period_n_each_eq_modified() {
	$logs = get_logs('journal');

	$result = [];

	foreach (Q('equipment') as $equipment) {
		$value = 0;
		$cmd = 'find . -name "$logs"';

		if (exec($cmd)) {
			$cmd = "zgrep -s '\[eq.*修改.*\[{$equipment->id}\]' $logs | grep -v '\]$' | wc -l ";
			// 由于符合 '\[eq.*修改' 的日志有很多是修改仪器记录而非设置的,
			// 且修改记录的日志皆以[记录ID]皆为, 所以增加 '\]$' 过滤这些日志
			$value = exec($cmd);
		}
		
		$eq_name = get_object_name($equipment);

		$result[] = ['仪器', "$eq_name 周期内更新次数", $value];
	}

	return $result;
}


// CF-仪器管理
// 仪器A 预约次数列表
// （发送周期内的预约次数）
// TODO 2.8 以后, eq_reverv 应该更易得到
function get_period_n_each_eq_reserv() {
	$result = [];

	foreach (Q('equipment') as $equipment) {
		$dtstart = STAT_START;
		$dtend = STAT_END;
		$type = Cal_Component_Model::TYPE_VEVENT;
		$value = Q("calendar[parent_name=equipment][parent_id={$equipment->id}] cal_component[type=$type][ctime={$dtstart}~{$dtend}]")->total_count();

		$eq_name = get_object_name($equipment);
		$result[] = ['仪器', "$eq_name 周期内新建预约个数", $value];
	}

	return $result;
}


// CF-仪器管理
// 仪器A 送样次数列表
// （在发送周期内的送样次数）
function get_period_n_each_eq_sample() {
	$result = [];

	foreach (Q('equipment') as $equipment) {
		$dtstart = STAT_START;
		$dtend = STAT_END;
		$value = Q("$equipment eq_sample[ctime={$dtstart}~{$dtend}]")->total_count();

		$eq_name = get_object_name($equipment);
		$result[] = ['仪器', "$eq_name 周期内新建送样个数", $value];
	}

	return $result;
}

// CF-仪器管理
// 仪器A 使用次数列表
// （在发送周期内的使用次数）
function get_period_n_each_eq_record() {
	$result = [];

	foreach (Q('equipment') as $equipment) {
		$dtstart = STAT_START;
		$dtend = STAT_END;
		$value = Q("$equipment eq_record[dtstart={$dtstart}~{$dtend}]")->total_count();

		$eq_name = get_object_name($equipment);
		$result[] = ['仪器', "$eq_name 使用次数", $value];
	}

	return $result;
}

// CF-文件系统
// 系统内所有成员上传文件占用空间量的统计, 只要 NFS_SHARE 中的内容, 不算各对象的附件
// （历史总量）MB
function get_sum_nfs_share() {
	$nfs_share_base = Config::get('nfs.root') . 'share/';

	$sum = 0;
	if (@is_dir($nfs_share_base)) {
		$sum = (int) exec('du -sm ' . escapeshellarg($nfs_share_base) . " | awk '{print $1}'");
	}

	return ['文件系统', '文件系统 (nfs_share) 占空间总量 (MB)', $sum];
}


// CF-权限管理
// 角色总数（只包括管理员添加的角色, 不包括 学生, 教师, 过期成员, 目前成员 等默认角色）
function get_n_roles() {
	$value = Q('role')->total_count();
	return ['权限管理', '角色总数', $value];
}


// CF-权限管理
// 角色权限更新次数统计（周期内, 包含所有默认/自定义角色的更新）
function get_period_n_role_modified() {
	$logs = get_logs('journal');
	$value = 0;
	$cmd = 'find . -name "$logs"';
	if (exec($cmd)) {
		$cmd = "zgrep -sF '修改了角色' $logs | wc -l ";
		$value = exec($cmd);
	}
	return ['权限管理', '周期内角色权限更新次数统计', $value];
}

// 新增订单数
// （在发送周期内新增加的订单数）
function get_period_new_order() {
	$dtstart = STAT_START;
	$dtend = STAT_END;
	$value = Q("order[ctime={$dtstart}~{$dtend}]")->total_count();
	return ['订单', '新增订单数', $value];
}

// 新增已订出订单数
// （在发送周期内新增加的已订出订单数）
function get_period_new_ordered_order() {
	$dtstart = STAT_START;
	$dtend = STAT_END;
	$status_not_received = Module::is_installed('orders') ? Order_Model::NOT_RECEIVED : 0;
	$value = Q("order[status=$status_not_received][ctime={$dtstart}~{$dtend}]")->total_count();
	return ['订单', '新增已订出订单数', $value];
}

// 项目总数
function get_n_projects() {
	$value = Q("tn_project")->total_count();
	return ['项目管理', '项目总数', $value];
}

// 新增任务数量
// （在发送周期内）
function get_period_n_tasks() {
	$dtstart = STAT_START;
	$dtend = STAT_END;
	$value = Q("tn_task[ctime={$dtstart}~{$dtend}]")->total_count();
	return ['项目管理', '新增任务数量', $value];
}

// 成员提交记录的数量
// （在发送周期内）
function get_period_n_notes() {
	$dtstart = STAT_START;
	$dtend = STAT_END;
	$value = Q("tn_note[ctime={$dtstart}~{$dtend}]")->total_count();
	return ['项目管理', '新增记录数量', $value];
}


// 新加成员日程个数
// （在发送周期内）
function get_period_n_member_event() {
	$dtstart = STAT_START;
	$dtend = STAT_END;

	$value = Q("calendar[type=schedule] cal_component[ctime={$dtstart}~{$dtend}]")->total_count();
	return ['日程管理', '新增成员日程', $value];
}

// 新加课题组日程个数
// （在发送周期内）
function get_period_n_lab_event() {
	$dtstart = STAT_START;
	$dtend = STAT_END;

	$value = Q("calendar[parent_name=lab] cal_component[ctime={$dtstart}~{$dtend}]")->total_count();
	return ['日程管理', '新增实验室日程数量', $value];
}

function get_n_user() {
	return ['成员', '成员总数', Q('user')->total_count()];
}

function get_n_lab() {
	return ['课题组', '课题组总数', Q('lab')->total_count()];
}

function get_n_eq() {
	return [
		['仪器', '仪器总数', Q('equipment')->total_count()],
		['仪器', '被控制仪器总数', Q('equipment[control_mode=computer,power]')->total_count()],
		];

}

function get_eq_brand() {
	$result = [];
	$db = Database::factory();
	foreach ($db->query("select replace(manufacturer, ' ', '') as m, count(*) as c from equipment  where manufacturer!='' group by m;")->rows() as $row) {
		$result[] = ['仪器', $row->m, $row->c];

	}
	return $result;
}

function get_eq_record_time_sum() {

	$db = Database::factory();
	return ['仪器', '所有仪器使用总时长',
		$db->value("select sum(dtend-dtstart) as  sum_duration from eq_record where dtstart>1275300000 and dtend>1275300000;")];


}

function get_eq_reserv_time_sum() {

	$db = Database::factory();
	return ['仪器', '所有仪器预约总时长',
		$db->value("select sum(dtend-dtstart) as  sum_duration from eq_reserv where dtstart>1275300000 and dtend>1275300000;")];


}

function get_eq_sample_sum() {
	return ['仪器', '所有仪器送样总数', Q('eq_sample')->total_count()];
}


function get_eq_value() {
	$db = Database::factory();

	$n_eq_with_price = Q('equipment[price>0]')->total_count();
	$n_eq_price_sum = $db->value('select sum(price) from equipment where price>0');
	$n_eq_avg_price = (int) $n_eq_price_sum / (int) $n_eq_with_price;

	return [
		['仪器', '填价值的仪器总数', $n_eq_with_price],
		['仪器', '填价值的仪器总价', $n_eq_price_sum],
		['仪器', '填价值的仪器平均价格', $n_eq_avg_price],
		];
}
// output functions

function td_echo() {
	$content = join(' ', func_get_args());
	// echo '"' . $content . '"' . "\t";
	echo $content . ",";
}

function tr_echo($tr, $print = TRUE) {

	if (!$tr) return;

	if ($print) {

		foreach ($tr as $td) {
			td_echo($td);
		}
		echo "\n";

	}

	return $tr;
}

function get_objects_names($objects) {
	$names = [];

	foreach ($objects as $object) {
		$names[] = get_object_name($object);
	}

	return join(' ', $names);
}

function get_object_name($object) {
	return $object->name . '[' . $object->id . ']';
}

