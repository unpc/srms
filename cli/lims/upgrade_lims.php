<?php

require dirname(dirname(__FILE__)). '/base.php';

define('DISABLE_NOTIFICATION', TRUE);

$default_user = [];
$default_user['token'] = 'genee';
$default_user['name'] = '技术支持';
$default_user['pwd'] = '83719730';
$default_user['backend'] = 'database';
$default_user['email'] = 'support@geneegroup.com';

$root_path = "/path/to/lims/html/labs/consumer/";

$blog_path = "{$root_path}data/blog/";
$user_icon = "{$root_path}icons/profile/large/";
$publication_path = "{$root_path}data/publication/pdf/";
$schedule_path = "{$root_path}data/schedule/";
$note_path = "{$root_path}data/note/";
$task_path = "{$root_path}data/task/";


$IDS = [];

class Upgrade_Lims {
    //定义颜色
	const ANSI_RED = "\033[31m";
	const ANSI_GREEN = "\033[32m";
	const ANSI_RESET = "\033[0m";
	const ANSI_HIGHLIGHT = "\033[1m";

	//定义数据库信息
	static $HOST = 'localhost';
	static $USERNAME = 'genee';
	static $PASSWORD = '';

	//高亮默认色输出
	public static function echo_title($title='') {
		echo self::ANSI_HIGHLIGHT;
		echo "$title\n";
		echo self::ANSI_RESET;
	}

	// success 输出
	public static function echo_success($text='') {
		echo self::ANSI_GREEN;
		echo "$text\n";
		echo self::ANSI_RESET;
	}

	// fail 输出
	public static function echo_fail($text='') {
		Log::add($text, 'database');
		echo self::ANSI_RED;
		echo "$text\n";
		echo self::ANSI_RESET;
	}

	// separator 输出
	public static function echo_separator() {
		echo "\n".str_repeat('=', 30)."\n";
	}

    //保证程序能够从外部直接调用动态的方法
	function __call($method, $params) {
		if ($method == __CLASS__) return;

		if ($this->$method instanceof Closure) {
			$func = $this->$method;
			return call_user_func_array($func,$params);
		}

		return TRUE;
	}
}

$upgrade = new Upgrade_Lims();

$upgrade->grade_role = function() {
$tip = <<<TIP
升级Roles注意事项：
	1. perms已经尽数更名，lims原始的perms已经不适用，应该手动设置

TIP;
echo Upgrade_Lims::echo_fail($tip);

fgets(STDIN);

	$db = Database::factory('lims');

	$roles = $db->query('select * from role')->rows();
	$rids = [];
	foreach ($roles as $role) {
		$new_role = O('role');
		$new_role->id = $role->id;
		$new_role->name = $role->name;
		$new_role->weight = $role->priority;
		//$new_role->perms = (array)json_decode($role->perms);
		if ($new_role->save()) {
			$rids[$role->id] = $new_role->id;
			$success = sprintf("[%s]角色成功添加!", $role->name);
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf("[%s]角色添加失败!", $role->name);
			Upgrade_Lims::echo_fail($fail);
		}
	}
	Lab::set('lims_to_role_id', $rids);

};

$upgrade->grade_user = function() use ($default_user, &$IDS, $user_icon) {
	/*
		在开始升级lims用户的时候需要先将genee的默认用户添加。
	*/
	$admin_token = $default_user['token'].'|'.$default_user['backend'];
	$add_admin_txt = '';
	if (!O('user', ['token'=>$admin_token])->id) {
		$admin_title = '为系统创建默认管理帐号genee';
		Upgrade_Lims::echo_title($admin_title);

		$admin = O('user');
		$admin->name = $default_user['name'];
		$admin->token = $admin_token;
		$admin->email = $default_user['email'];
		$admin->ref_no = NULL;
		$admin->atime = 1;
		$admin->hidden = 1;
		$auth = new Auth($default_user['token']);
		if ($auth->create($default_user['pwd'])) {
			$admin->save();
			$success = sprintf('[%s]用户创建成功!', $admin->name);
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf('[%s]用户auth创建失败!', $admin->name);
			Upgrade_Lims::echo_fail($fail);
		}
		Upgrade_Lims::echo_title('==========================');
	}

	/*
		升级用户之前的提示信息
	*/
$tip = <<<TIP
升级People注意事项：
	1. lims用户的passwd已经进行了md5加密，lims2在升级的时候需要暂时关闭md5加密密码的功能
	2. lims2对用户的属性进行了调整，lims中的部分属性被划掉，被划掉的信息存放在lims2用户的扩展属性extra中
	3. 升级People依赖的模块
		3.1. Roles

TIP;
echo Upgrade_Lims::echo_fail($tip);
	fgets(STDIN);

	/*
		尝试从lims中获取数据来进行升级
	*/
	$db = Database::factory('lims');

	$users = $db->query('select * from user')->rows();
	$user_roles = $db->query('select * from user_role')->rows('assoc');

	$roles = [];
	foreach ($user_roles as $role) {
		$roles[$role['uid']][] = $role['rid'];
	}

	$lang = strtolower(Config::get('system.locale'));
	$bool = $lang=='zh_cn';

	$new_db = Database::factory();

	foreach ($users as $user) {

		$new_user = O('user');

		$extra = [];
		$extra['alt_name'] = $user->alt_name;
		$extra['degree'] = $user->degree;
		$extra['extra'] = $user->extra;
		$extra['fax'] = $user->fax;
		$extra['note'] = $user->note;
		$extra['notes'] = $user->notes;
		$extra['order'] = $user->order;
		$extra['title'] = $user->title;

		$new_user->extra = $extra;

		$new_user->ref_no = NULL;
		$new_user->dfrom = $user->dfrom;
		$new_user->dto = $user->dto;
		$new_user->email = $user->email;
		$new_user->mtime = $new_user->ctime = (int) $user->mtime ? : 1;
		if ($new_user->dto > time() || !$new_user->dto) {
			$new_user->atime = $new_user->mtime ? : 1; // lims_zhuxd 中有用户无 mtime
		}

		if ($bool) {
			$new_user->name = $user->alt_name ?: $user->name;
		}
		else {
			$new_user->name = $user->name;
		}

		$new_user->phone = $user->phone ?: '';
		$token = substr($user->email, 0, strpos($user->email, '@'));
		$new_user->member_type = $token == Config::get('lab.pi') ? 10 : 0;
		$new_user->token = $token . '|'  . Config::get('auth.default_backend');

			$auth = new Auth($new_user->token);
			if ($auth->create('123456')) { // 先随便设个密码, 保证 _auth 生成
				$new_db->query(sprintf('update _auth set password="%s" where token like "%s"', $user->passwd, $token)); // 再 sql 替换回原密码

				if ($new_user->save()) {
					$IDS[$user->id] = $new_user->id;
					$success = sprintf("OK, 【%s】用户成功创建!", $new_user->name);
					Upgrade_Lims::echo_success($success);
				}
				else {
					$auth->remove();
					$fail = sprintf("error, 【%s】用户创建失败!", $new_user->name);
					Upgrade_Lims::echo_fail($fail);
					continue;
				}
			}
			else {
				$fail = sprintf('error, 【%s】的auth未创建成功!退出本次更新操作!', $new_user->name);
				Upgrade_Lims::echo_fail($fail);
				continue;
			}
		if (!empty($roles[$user->id])) {
			$new_user->connect(['role', $roles[$user->id]]);
		}
	}
	$end = "==========用户信息更新成功========";
	Upgrade_Lims::echo_title($end);

	Lab::set('lims_to_user_id', $IDS);

	$IDS = array_flip($IDS);

	$users = Q('user');
	foreach ($users as $user) {
		$old_path = $user_icon."{$IDS[$user->id]}.png";

		if (is_file($old_path)) {
			Upgrade_Lims::echo_success("Upload [$old_path]");
			$image = Image::load($old_path, 'png');
			$user->save_icon($image);
		}
	}
};


$upgrade->grade_equipment = function() {

	Upgrade_Lims::echo_title("==开始尝试对仪器数据进行分析，进行升级工作!==");

$tip = <<<TIP
升级Equipments注意事项：
	1. lims2对equipment的属性进行了扩展，并取消了color和note属性
	2. in_charge的实现发生的变化
	3. 仪器预约的event部分数据不宜转换
		allday/repeat

TIP;
echo Upgrade_Lims::echo_fail($tip);
	fgets(STDIN);
	$userids = (array)Lab::get('lims_to_user_id');
	$eids = [];
	$db = Database::factory('lims');
	$equipments= $db->query('select * from equipment')->rows();
	foreach ($equipments as $equipment) {
		$new_equipment  = O('equipment');
		$new_equipment->name = $equipment->name;
		$new_equipment->tech_specs = $equipment->note;
		$new_equipment->ctime = $new_equipment->mtime = time();

		if ($new_equipment->save()) {
			$success = sprintf("[%s]仪器创建成功!", $new_equipment->name);
			Upgrade_Lims::echo_success($success);

			$in_charge = O('user', $userids[$equipment->in_charge]);
			$eids[$equipment->id] = $new_equipment->id;
			$new_equipment->connect($in_charge, 'incharge');
			$new_equipment->connect($in_charge, 'contact');
		}
		else {
			$fail = sprintf("[%s]仪器创建失败!", $new_equipment->name);
			Upgrade_Lims::echo_fail($fail);
		}

	}

	Lab::set('lims_to_equipment_id', $eids);

	$events = $db->query('select * from event where type="equipment"')->rows();

	Upgrade_Lims::echo_title("==处理仪器预约==");

	foreach ($events as $event) {
		$equipment = O('equipment', $eids[$event->sub_type]);
		// 仪器可能被删除
		if (!$equipment->id) continue;
		$calendar = O('calendar', ['parent'=>$equipment]);
		$calendar->name = $equipment->name;
		$calendar->parent = $equipment;
		$calendar->ctime = $calendar->mtime = time();
		$calendar->save();

		$equipment->accept_reserv = 1;
		$equipment->save();

		$component = O('cal_component');
		$component->calendar = $calendar;
		$component->name = $event->title ? $event->title : $equipment->name;
		$component->dtstart = $event->dfrom;
		$component->dtend = $event->dto;
		$component->description = $event->note;
		$organizer = O('user', $userids[$event->owner]);
		$component->organizer = $organizer;
		$component->save();
	}

	// 反馈正常(xiaopei.li@2012-02-03)
	$now = Date::time();
	$status = EQ_Record_Model::FEEDBACK_NOTHING;
	$records = Q("eq_record[dtend!=0][dtend<$now][status=$status]");
	foreach($records as $record) {
		$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
		$record->save();
	}

	Upgrade_Lims::echo_title("========仪器数据升级结束!========");
};

$upgrade->grade_achivements = function() use($publication_path) {
$tip = <<<TIP
升级Achievement注意事项：

TIP;
echo Upgrade_Lims::echo_fail($tip);
fgets(STDIN);

	$db = Database::factory('lims');
	$userids = (array)Lab::get('lims_to_user_id');
	$pids = [];
	$publications = $db->query('select * from publication')->rows();
	foreach ($publications as $publication) {
		$new_publication = O('publication');
		$new_publication->pubmed = $publication->pubmed;
		$new_publication->title = $publication->title;
		$new_publication->journal = $publication->journal;
		$new_publication->date = $publication->date;
		$new_publication->volume = $publication->volume;
		$new_publication->issue = $publication->issue;
		$new_publication->page = $publication->pages;
		$new_publication->author = $publication->authors; // 写在 achievement 里的 json authors 已过时(xiaopei.li@2012-02-03)
		$new_publication->aumap = $publication->aumap;
		$new_publication->lab = Lab_Model::default_lab();
		if ($new_publication->save()) {
			$pids[$publication->id] = $new_publication->id;

			// 处理作者(xiaopei.li@2012-02-03)
			$author_names = json_decode($publication->authors, TRUE);
			$pub_authors = $db->query("select * from pub_author where pid={$publication->id}")->rows();
			foreach ($pub_authors as $author) {
				$new_author = O('ac_author');
				$new_author->achievement = $new_publication;
				$new_author->user = O('user', $userids[$author->uid]);
				$new_author->position = $author->pos;
				$new_author->name = $author_names[$author->pos];
				unset($author_names[$author->pos]);
				$new_author->save();
			}

			foreach ($author_names as $pos => $name) {
				$new_author = O('ac_author');
				$new_author->achievement = $new_publication;
				$new_author->position = $pos;
				$new_author->name = $name;
				$new_author->save();
			}
			// end 处理作者

			$old_path = $publication_path."pub_{$publication->id}.pdf";
			$new_path = NFS::get_path($new_publication);

			File::check_path($new_path."foo");

			if (is_file($old_path)) {
				@copy($old_path, $new_path."download.pdf");
				Upgrade_Lims::echo_success("move [$old_path] to [$new_path]");
			}

			$success = sprintf("[%s]->[%s]文献已经升级成功!", $publication->id, $new_publication->id);
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf("[%s]->[%s]文献已经升级失败!", $publication->id, $new_publication->id);
			Upgrade_Lims::echo_fail($fail);
		}
	}
	Lab::set('lims_to_publication_id', $pids);
};


$upgrade->grade_schedule = function() use($schedule_path) {

$tip = <<<TIP
升级Schedule注意事项：
	1. 日程的分类
		0: SCHEDULE_TYPE_LABMEETING=>lab_meeting,
		1: SCHEDULE_TYPE_JOURNALCLUB=>journal_club,
		2: SCHEDULE_TYPE_OTHER=>other
	2. 实验室日程分类
		0: LAB_MEETING Schedule::TYPE_LABMEETING
		1: JOURNALCLUB Schedule::TYPE_JOURNALCLUB
		2: OTHERS      Schedule::TYPE_OTHERS
		3: REPORT	   Schedule::TYPE_OTHERS

	* 目前所有type为schedule的日程全部同步到日程的calendar中。

TIP;
echo Upgrade_Lims::echo_fail($tip);
fgets(STDIN);

	$db = Database::factory('lims');

	$userids = (array)Lab::get('lims_to_user_id');
	$types = [
		'0' => Schedule::TYPE_LABMEETING,
		'1' => Schedule::TYPE_JOURNALCLUB,
		'2' => Schedule::TYPE_OTHERS
	];


	//为系统里的每一个用户创建calendar
	$users = Q('user');
	foreach ($users as $user) {
		$calendar = O('calendar', [
			'type'=>'schedule',
			'parent'=>$user,
		]);
		if ($calendar->id) continue;
		$calendar->name = $user->name;
		$calendar->parent = $user;
		$calendar->ctime = $calendar->mtime = time();
		$calendar->type = 'schedule';
		$calendar->save();
	}

	$calendar = O('calendar', [
		'parent'=> lab_Model::default_lab(),
		'type'=>'schedule'
	]);
	if (!$calendar->id) {
		$calendar->name = Lab_Model::default_lab()->name;
		$calendar->parent = Lab_Model::default_lab();
		$calendar->ctime = $calendar->mtime = time();
		$calendar->type = 'schedule';
		$calendar->save();
	}

	$events = $db->query('select * from event where type="schedule"')->rows();
	//将历史的所有event同步到实验室日程
	foreach ($events as $event) {
		$component = O('cal_component');
		$component->calendar = $calendar;
		$component->name = $event->title ?: '会议主题';
		$component->dtstart = $event->dfrom;
		$component->dtend = $event->dto;
		$component->description = $event->note;
		$organizer = O('user', $userids[$event->owner]);
		$component->organizer = $organizer;
		$component->subtype = $types[$event->sub_type];
		if ($component->save()) {
			$schedule_speaker = O('schedule_speaker');
			$schedule_speaker->component = $component;
			$schedule_speaker->user = $organizer;
			$schedule_speaker->name = $organizer->name;
			$schedule_speaker->save();

			$new_path = NFS::get_path($component);
			$old_path = $schedule_path.$event->id.'/';

			File::copy_r($old_path, $new_path);

			Upgrade_Lims::echo_success("move [$old_path] to [$new_path]");

			$success = sprintf('[%s]日程更新成功!', $component->name);
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf('[%s]日程更新失败!', $component->name);
			Upgrade_Lims::echo_fail($fail);
		}
	}
};

$upgrade->grade_blog = function() use($blog_path) {
$tip = <<<TIP
升级 Blog 注意事项：
	1. lims1 的 blog 附件存放于 lims/html/labs/xxx/data/blog/blog_id;
	2. 升级会将附件移至 nfs_share 的 public/ blog 下;
	3. blog 内容将会丢失;
TIP;
	echo Upgrade_Lims::echo_fail($tip);
	fgets(STDIN);

	$db = Database::factory('lims');
	$blogs = $db->query('SELECT * FROM  `blog`')->rows();

	// 将 blog 的附件导入为 public/blog/blog_title 目录
	$new_blog_path = Config::get('nfs.root') . 'share/public/blog';
	File::check_path($new_path);

	foreach ($blogs as $blog) {
		$blog_title = date('Y-m-d', $blog->date) . '_' . $blog->title;

		$old_path = $blog_path.$blog->id;
		if (File::exists($old_path)) {

			$new_path = trim($new_blog_path . '/' . sanitize_file_name($blog_title));
			File::copy_r($old_path, $new_path);
			$success = sprintf('[blog] %s => %s', $old_path, $new_path);
			Upgrade_Lims::echo_success($success);

		}
	}

};

$upgrade->grade_grant = function() {
$tip = <<<TIP
升级Grant注意事项：
	1、lims中存在三种数据库类型：grant、grant_use、expense
	2、lims2中同时存在三种数据类型：grant、grant_portion、grant_expense
	3、虽然上述三种于下列三种存在相应的联系，但是由于数据不是很相同，所以需要一定的修正。
TIP;
echo Upgrade_Lims::echo_fail($tip);
fgets(STDIN);

	$db = Database::factory('lims');
	$uids = Lab::get('lims_to_user_id');
	$gids = [];
	$grants = $db->query('SELECT * FROM  `grant`')->rows();

	foreach ($grants as $g) {
		$grant = O('grant');
		$grant->source = $g->source;
		$grant->project = $g->project;
		$grant->ref = $g->ref;
		$grant->amount = $g->amount;
		$grant->description = $g->abstract;
		$grant->user = O('user', $uids[$g->pi]);
		$grant->expense = $g->expense;
		$grant->balance = $g->balance;
		$grant->avail_balance = $g->balance;
		$grant->ctime = $g->ctime;
		$grant->mtime = $g->mtime;
		$grant->dtstart = $g->dfrom;
		$grant->dtend = $g->dto;
		if ($grant->save()) {
			$success = sprintf("[%s]升级成功!", $grant->project);
			$gids[$g->id] = $grant->id;
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf("[%s]升级失败!", $grant->project);
			Upgrade_Lims::echo_fail($fail);
		}
	}
	Lab::set('lims_to_grant_id', $gids);

	Upgrade_Lims::echo_title("===========================\n经费升级结束，升级经费中各项的分配数据！\n===========================");

	$pids = [];

	$grant_uses = $db->query('select * from grant_use')->rows();
	foreach ($grant_uses as $u) {
		$portion = O('grant_portion');
		$portion->name = $u->name;
		$portion->grant = O('grant', $gids[$u->gid]);
		$portion->parent = O('grant_portion', $pids[$u->parent]);
		$portion->amount = $u->amount;
		$portion->expense = $u->expense ?: 0;
		$portion->balance = $u->balance;
		$portion->avail_balance = $u->avail_balance;
		$portion->ctime = $portion->grant->ctime ?: time();
		$portion->mtime = time();
		if ($portion->save()) {
			$success = sprintf("[%s]升级成功!", $portion->name);
			$pids[$u->id] = $portion->id;
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf("[%s]升级失败!", $portion->name);
			Upgrade_Lims::echo_fail($fail);
		}
	}
	Lab::set('lims_to_grant_use_id', $pids);

	Upgrade_Lims::echo_title("===========================\n经费分配额升级结束，开始升级其中的支出明细\n===========================");
	$eids = [];
	$expenses = $db->query('select * from expense')->rows();

	$expenses_order_link = [];

	foreach ($expenses as $e) {
		$expense = O('grant_expense');
		$expense->summary = $e->summary;
		$expense->portion = O('grant_portion', $pids[$e->guid]);
		$expense->grant = $expense->portion->grant;
		$expense->user = O('user', $uids[$e->owner]);
		$expense->amount = $e->amount;
		$expense->invoice_no = $e->invoice_no;
		$expense->ctime = $e->date;
		$expense->mtime = $e->mtime ?: time();
		$expense->oid = $e->oid;

		if ($expense->save()) {
			$success = sprintf("[%s]支出明细升级成功!", $e->id);
			$eids[$u->id] = $expense->id;
			Upgrade_Lims::echo_success($success);

			if ($e->oid) {
				// lims2_expense_id => lims1_order_id
				$expenses_order_link[$expense->id] = $e->oid;
			}

		}
		else {
			$fail = sprintf("[%s]支出明细升级失败!", $e->id);
			Upgrade_Lims::echo_fail($fail);
		}
	}
	Lab::set('lims_to_expense_id', $eids);

$tip = <<<TIP
升级inventroy注意事项：
	1、LIMS中存在delear、orders、product、stock、stuff、stuff_type等相关数据库.
	2、LIMS2中存在order、stock、distributor等数据库
	3、application、app_peptides_peptide、app_plasmids_plasmid、app_primers_primer、app_reagents_regent、app_sequences_sequence、app_strains_strain等数据当作备注信息存放起来。
TIP;
echo Upgrade_Lims::echo_fail($tip);
fgets(STDIN);

	$uids = Lab::get('lims_to_user_id');
	$eids = Lab::get('lims_to_expense_id');
	$db = Database::factory('lims');

	$oids = [];
	$orders = $db->query('SELECT * FROM orders')->rows();

	$status = [
		1 => Order_Model::REQUESTING,	// 尚未处理 -> 申购中
		2 => Order_Model::NOT_RECEIVED, // 已订出 -> 已订出
		3 => Order_Model::NOT_RECEIVED, // 尚无现货 -> 已订出
		4 => Order_Model::RECEIVED,		// 已收到 -> 已到货
		0 => Order_Model::NOT_RECEIVED,	// 自定义 -> 已订出
		-1 => Order_Model::RECEIVED,	// 已报销 -> 已到货
	];

	$oids = [];


	foreach ($orders as $key => $oo) {
		$user = O('user', $uids[$oo->owner]);
		$order = O('order');
		$order->product_name = $oo->name;
		$order->manufacturer = $oo->manufacturer;
		$order->catalog_no = $oo->catalog_no;
		$order->spec = $oo->unit;
		$order->quantity = $oo->quantity;
		$order->distributor = $oo->dealer;
		$order->unit_price = $oo->unit_price;
		$order->price = $oo->price;
		$order->requester = $user;
		$order->request_date = $oo->date ? : time(); //lims中的order的date对应的为lims2中的request_date
		$order->request_note = $oo->note;
		$order->purchaser = $user;
		$order->purchase_date = $oo->time_ordered ?: time();
		$order->purchase_note = $oo->funding ? : '';

		if ($status[$oo->status] == Order_Model::RECEIVED) {
			$order->receiver = $user;
			$order->receive_date = ($oo->time_received ?: $oo->time_reimbursed) ?: 0;
			$order->receive_status = 1;
		}
		$order->status = $status[$oo->status];
		$order->ctime = $oo->date ?: time();
		$order->mtime = $oo->mtime ?: time();
		//$order->expense = O('grant_expense', $eids[$o->grant_use]);
		if ($order->save()) {
			$success = sprintf('[%s]订单升级成功!', $oo->name);
			$oids[$oo->id] = $order->id;
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf('[%s]订单升级失败!', $oo->name);
			Upgrade_Lims::echo_fail($fail);
		}
	}
	Lab::set('lims_to_order_id', $oids);

	foreach ($expenses_order_link as $expense_id => $oid) {
		$expense = O('grant_expense', $expense_id);
		$order = O('order', $oids[$oid]);
		if ($order->id && $expense->id) {
			$order->expense = $expense;
			if ($order->save()) {
				$expense->pre_summary = I18N::T('orders', '相关订单 %order', [
											'%order' => Markup::encode_Q($order),
										]);
				$expense->save();
			}
		}
	}

};

$upgrade->fix_inventory = function() {
$tip = <<<TIP
升级order之后需要对app属性值来进行修正：
	APP的数据全部需要同步到更新stock中。
TIP;
echo Upgrade_Lims::echo_fail($tip);
fgets(STDIN);

	$db = Database::factory('lims');

	$stuffs = $db->query('SELECT * FROM `stuff`')->rows();
	$oids = Lab::get('lims_to_order_id');
	$soids = [];


    //创建 "实验手册" 目录
    $root_dir = Config::get('nfs.root'). 'share/';
    $public_dir = $root_dir. 'public/';

    $lab_note_dir = $public_dir. '实验手册/';

    File::check_path($lab_note_dir. 'foo');

	foreach ($stuffs as $s) {
        //针对实验手册进行特殊处理
        //类型为实验手册
        if ($s->type == 'protocol') {
            $title = $s->name;
            $file = $lab_note_dir. $title. '.txt';
            $content = $s->description;
            //把类型为实验手册记录，变为txt文档
            //举例
            //实验手册标题为a，无附件，则直接在public/实验手册/中创建a.txt,内容为实验手册的description
            @file_put_contents($file, $content);
            Upgrade_Lims::echo_success(sprintf('[%s]升级成功!', $s->id));
            continue;
        }

		$extra = [];
		$extra['ext'] = $s->ext;
		$extra['type'] = $s->type;
		$extra['mime'] = $s->mime;
		$extra['app'] = $s->app;

		// 存货相关(xiaopei.li@2012-02-02)
		// 1. LIMS 中, 从订单加为存货的, 就会既在 stock 又在 stuff 中各加一笔, stock 中会指明 order_id;
		// 2. 如果加为存货时选了类型, 就会再在相应的 app 中加一笔;
		// 3. stock/stuff/具体的app 的 id 全相同

		// 以下 order 相关的错了就是因为弄混了 id 间的关系, 暂时注释掉(xiaopei.li@2012-02-02)
		// $os = $db->query('SELECT * FROM stock where id='.$s->id)->row();
		// $order = O('order', $oids[$os->id]);

		$stock = O('stock');
		$stock->product_name = $s->name;
		$stock->location = $os->id ? ($os->location ?: ''): '';
		$stock->barcode = $os->id ? ($os->barcode ?: '') : '';
		$stock->note = $s->description;
		/*
		if ($order->id) {
			$stock->manufacturer = $order->manufacturer ?: '';
			$stock->catalog_no = $order->catalog_no ?: '';
			$stock->spec = $order->spec ?: '';
			$stock->quantity = $order->quantity ?: 0;
			$stock->distributor = $order->distributor ?: '';
			$stock->unit_price = $order->unit_price ?: 0;
			$stock->order = $order;
		}
		*/

		$strain = $db->query('SELECT * FROM app_strains_strain where id='.$s->id)->row();
		if ($strain->id) {

			$stock->spec = $stock->spec ?: ($strain->specie ?: '');
			$strai_arr = [];
			$strai_arr['genetic'] = $strain->genetic;
			$strai_arr['parent_strain'] = $strain->parent_strain;
			$strai_arr['db_link'] = $strain->db_link;
			$strai_arr['exp_protein'] = $strain->exp_protein;
			$strai_arr['growth'] = $strain->growth;
			$strai_arr['temperature'] = $strain->temperature;
			$strai_arr['link'] = $strain->link;
			$strai_arr['note'] = $strain->note;
			$strai_arr['label'] = $strain->label;
			$extra['app_strains_strain'] = $strai_arr;
		}

		$reagent = $db->query('SELECT * FROM app_reagents_reagent WHERE id='.$s->id)->row();
		if ($reagent->id) {
			$reagent_arr = [];
			$stock->catalog_no = $stock->catalog_no ?: ($reagent->catalog_no ?: '');
			$stock->spec = $stock->spec ?: ($reagent->spec ?: '');
			$stock->quantity = $stock->quantity ?: ($reagent->quantity ?: '');

			$reagent_arr['catalog_no'] = $reagent->catalog_no;
			$reagent_arr['name'] = $reagent->name;
			$reagent_arr['company'] = $reagent->company;
			$reagent_arr['unit'] = $reagent->unit;
			$reagent_arr['quantity'] = $reagent->quantity;
			$reagent_arr['note'] = $reagent->note;
			$reagent_arr['label'] = $reagent->label;
			$extra['app_reagents_reagent'] = $reagent_arr;
		}

		$primer = $db->query('SELECT * FROM app_primers_primer WHERE id='.$s->id)->row();
		if ($primer->id) {
			$primer_arr = [];
			$primer_arr['name'] = $primer->name;
			$primer_arr['length'] = $primer->length;
			$primer_arr['target'] = $primer->target;
			$primer_arr['sequence'] = $primer->sequence;
			$primer_arr['location'] = $primer->location;
			$primer_arr['pair_with'] = $primer->pair_with;
			$primer_arr['use'] = $primer->use;
			$primer_arr['enz_size'] = $primer->enz_size;
			$primer_arr['modification'] = $primer->modification;
			$primer_arr['purification'] = $primer->purification;
			$primer_arr['target_seq'] = $primer->target_seq;
			$primer_arr['note'] = $primer->note;
			$primer_arr['btime'] = $primer->btime;
			$primer_arr['label'] = $primer->label;
			$stock->ref_no = $primer->label ? : NULL;
			$stock->catalog_no = $primer->sequence ? : '';
			$stock->note = $primer->note ? : '';

			$extra['app_primers_primer'] = $primer_arr;
		}

		$plasmid = $db->query('SELECT * FROM app_plasmids_plasmid WHERE id='.$s->id)->row();
		if ($plasmid->id) {
			$plasmid_arr = [];
			$plasmid_arr['name'] = $plasmid->name;
			$plasmid_arr['length'] = $plasmid->length;
			$plasmid_arr['total_size'] = $plasmid->total_size;
			$plasmid_arr['backbone'] = $plasmid->backbone;
			$plasmid_arr['sequence'] = $plasmid->sequence;
			$plasmid_arr['geneinfo'] = $plasmid->geneinfo;
			$plasmid_arr['plasmid_db'] = $plasmid->plasmid_db;
			$plasmid_arr['sele_mark'] = $plasmid->sele_mark;
			$plasmid_arr['host'] = $plasmid->host;
			$plasmid_arr['fusion_tags'] = $plasmid->fusion_tags;
			$plasmid_arr['terminal'] = $plasmid->terminal;
			$plasmid_arr['site5'] = $plasmid->site5;
			$plasmid_arr['site3'] = $plasmid->site3;
			$plasmid_arr['sequ_prim5'] = $plasmid->sequ_prim5;
			$plasmid_arr['sequ_prim3'] = $plasmid->sequ_prim3;
			$plasmid_arr['hign_low'] = $plasmid->hign_low;
			$plasmid_arr['growth'] = $plasmid->growth;
			$plasmid_arr['note'] = $plasmid->note;
			$plasmid_arr['btime'] = $plasmid->btime;
			$plasmid_arr['label'] = $plasmid->label;
			$extra['app_plasmids_plasmid'] = $plasmid_arr;
		}

		$peptide = $db->query('SELECT * FROM app_peptides_peptide WHERE id='.$s->id)->row();
		if ($peptide->id) {
			$peptide_arr = [];
			$peptide_arr['name'] = $peptide->name;
			$peptide_arr['length'] = $peptide->length;
			$peptide_arr['target'] = $peptide->target;
			$peptide_arr['sequence'] = $peptide->sequence;
			$peptide_arr['location'] = $peptide->location;
			$peptide_arr['pair_with'] = $peptide->pair_with;
			$peptide_arr['use'] = $peptide->use;
			$peptide_arr['amount'] = $peptide->amount;
			$peptide_arr['modification'] = $peptide->modification;
			$peptide_arr['protein_seq'] = $peptide->protein_seq;
			$peptide_arr['note'] = $peptide->note;
			$peptide_arr['btime'] = $peptide->btime;
			$peptide_arr['label'] = $peptide->label;
			$extra['app_peptides_peptide'] = $peptide_arr;
		}

		// 由于在 LIMS2 中置为 ref_no 的值在 LIMS 中不一定为 key, 所以在保存前先检查 ref_no ? 若重复则修改之?
		if ($stock->ref_no) {
			$stock->ref_no = check_stock_ref_no($stock->ref_no);
		}

		$stock->extra = $extra;
		if ($stock->save()) {
			$success = sprintf('[%s]升级成功!', $s->id);

			if ($stock->extra['type'] != 'unknown') {
				Tag_Model::replace_tags($stock, [$stock->extra['type']], 'inventory', TRUE);
			}

			/*
			if ($stock->order->id) {
				$order->stock = $stock;
				$order->save();
			}
			*/
			Upgrade_Lims::echo_success($success);
			$soids[$s->id] = $stock->id;
		}
		else {
			$fail = sprintf('[%s]升级失败!', $s->id);

			Upgrade_Lims::echo_fail($fail);
		}
	}


};

$upgrade->grade_treenote = function() use($note_path, $task_path) {
$tip = <<<TIP


TIP;
echo Upgrade_Lims::echo_fail($tip);
fgets(STDIN);

	$ids = Lab::get('lims_to_user_id');
	$pids = [];
	$tids = [];
	$nids = [];

	$db = Database::factory('lims');

	$projects = $db->query("SELECT * FROM project")->rows();


	foreach ($projects as $project) {
		$extra = [];
		$p = O("tn_project");
		$p->title = $project->title;
		$p->description = Output::HTML_brief($project->description);
		$p->user = O('user', $ids[$project->owner]);
		$p->is_complete = $project->status >= 2 ? 1 : 0;
		$p->is_locked = $project->locked;
		$p->ctime = $project->ctime;
		$p->mtime = $project->mtime;
		$extra['status'] = $project->status;
		$extra['progress'] = $project->progress;
		$extra['dfrom'] = $project->dfrom;
		$extra['dto'] = $project->dto;
		$p->extra = $extra;
		if ($p->save()) {
			$pids[$project->id] = $p->id;
			$success = sprintf('[%s]项目升级成功!', $project->title);
			Upgrade_Lims::echo_success($success);

			/* 开始操作项目下的task任务 */
			$tasks = $db->query("SELECT * FROM task WHERE pid = %d", $project->id)->rows();

			foreach ($tasks as $task) {
				$extra = [];
				$t = O('tn_task');
				$t->title = $task->title;
				$t->description = Output::HTML_brief($task->description);
				$t->user = O('user', $ids[$task->owner]);
				$t->project = $p;
				$t->parent = $task->parent;
				$t->deadline = (int) $task->dto;
				$t->priority = $task->priority;
				$t->is_complete = $task->finished ? 1 : 0;
				$t->is_locked = $task->locked;
				$t->ctime = $task->ctime;
				$t->mtime = $task->mtime;
				$t->reviewer = O('user', $ids[$task->added]);
				$t->expected_time = (int) $task->dto - (int) $task->dfrom;
				$t->status = $task->status;
				$extra['weight'] = $task->weight;
				$extra['dfrom'] = $task->dfrom;
				$extra['dto'] = $task->dto;
				$extra['dfinish'] = $task->dfinish;
				$t->extra = $extra;
				if ($t->save()) {
					$success = sprintf("\t[%s]任务升级成功!", $task->title);
					$tids[$task->id] = $t->id;
					Upgrade_Lims::echo_success($success);
				}
				else {
					$fail = sprintf("\t[%s]任务升级失败!", $task->title);
					Upgrade_Lims::echo_fail($fail);
				}

			}
		}
		else {
			$fail = sprintf('[%s]项目升级失败!', $project->title);
			Upgrade_Lims::echo_fail($fail);
		}
	}

	Lab::set('lims_to_project_id', $pids);
	Lab::set('lims_to_task_id', $tids);


	Upgrade_Lims::echo_fail("\n\n开始尝试给task增加parent属性");
	$tasks = Q('tn_project');
	foreach ($tasks as $task) {
		if (!$task->parent) continue;

		$task->parent_task = O('tn_task', $tids[$task->parent]);
		if ($task->save()) {
			Upgrade_Lims::echo_success(sprintf("[%s]升级父级task成功!", $task->title));
		}
		else {
			Upgrade_Lims::echo_fail(sprintf("[%s]升级父级task失败!", $task->title));
		}
	}

	Upgrade_Lims::echo_fail("\n\n开始尝试将task中的附件挪到下属note中");
	$tasks = Q('tn_task');
	$tids = array_flip($tids);
	foreach ($tasks as $task) {
		$path = $task_path.$tids[$task->id].'/';
		if (!$tids[$task->id] || !is_dir($path) || !File::size($path)) continue;

		$note = O('tn_note');
		$note->ctime = $task->ctime;
		$note->mtime = $task->mtime;
		$note->is_locked = $task->is_locked;
		$note->task = $task;
		$note->user = $task->user;
		$note->project = $task->project;
		$note->title = $note->content = '['.$task->title.']附件';

		if ($note->save()) {
			$success = sprintf("[%s]附属附件记录升级成功!", $note->title ?: $note->id);

			$new_path = NFS::get_path($note);
			File::copy_r($path, $new_path);
			Upgrade_Lims::echo_success("\tmove [$path] to [$new_path]");

			$nids[$note->id] = $n->id;
			Upgrade_Lims::echo_success($success);
		}
		else {
			$fail = sprintf('[%s]附属附件记录升级失败!', $note->title ?: $note->id);
			Upgrade_Lims::echo_fail($fail);
		}

 	}



	$notes = $db->query("SELECT * FROM note")->rows();

	foreach ($notes as $note) {
		// lims1 中 note 和 task 是没有对象关联的!!!!!
		// 所以要根据 note 的 project / task 创建 tn_project / tn_task

		$note_project = trim($note->project);
		$note_task = trim($note->experiment);

		$user = O('user', $ids[$note->owner]);
		if (!$user->id) { // lims_baigang 中有 note owner 被删, 将这些 note 指给其他用户
			$user = O('user', 2); // id 为 1 的是 技术支持, id 为 2 的是 PI
		}

		Upgrade_Lims::echo_success("$note_project\t$note_task\t$user->name");
		// continue;

		$tn_project = O('tn_project', ['title' => $note_project]);
		if ( !$tn_project->id ) {
			$tn_project->title = $note_project;
			if (!$tn_project->save()) {
				Upgrade_Lims::echo_fail('project 保存失败');
				continue;
			}
		}

		$tn_task = O('tn_task', [
						 'title' => $note_task,
						 'project' => $tn_project,
						 'user' => $user,
						 ]);
		if ( !$tn_task->id ) {
			$tn_task->title = $note_task;
			$tn_task->project = $tn_project;
			$tn_task->user = $user;
			$tn_task->priority = 3; // 优先级"一般"
			if (!$tn_task->save()) {
				Upgrade_Lims::echo_fail('task 保存失败');
				continue;
			}
		}

		$n = O('tn_note');

		$n->content = Output::HTML_brief($project->body);
		if ($note_repeat) {
			$n->content = sprintf('<p>第 %n 次重复</p>', $note_repeat) . $n->content;
		}

		$n->user = $user;
		$n->task = $tn_task;
		$n->project = $tn_project;

		$n->is_locked = $note->locked;
		$n->ctime = $note->ctime;
		$n->mtime = $note->mtime;

		//$n->actual_time = $note->is_locked ? ($note->mtime - $note->date) : 0;
		$extra['repeat'] = $note->repeat;
		$extra['date'] = $note->date;
		$n->extra = $extra;

		if ($n->save()) {
			$success = sprintf("[%s]记录升级成功!", $note->title ?: $note->id);


			$old_path = $note_path."{$note->id}/";
			$new_path = NFS::get_path($n);

			if (is_dir($old_path)) {
				File::copy_r($old_path, $new_path);
				Upgrade_Lims::echo_success("\tmove [$old_path] to [$new_path]");
			}

			$nids[$note->id] = $n->id;
			Upgrade_Lims::echo_success($success);


			$note_comments = $db->query("SELECT * FROM comment where oname='note' and oid=$note->id ")->rows();
			foreach ($note_comments as $comment) {
				$new_comment = O('comment');
				$new_comment->object = $n->task;
				$new_comment->content = $comment->content;
				$new_comment->author = O('user', $ids[$comment->owner]);
				$new_comment->save();
			}
		}
		else {
			$fail = sprintf('[%s]记录升级失败!', $note->title ?: $note->id);
			Upgrade_Lims::echo_fail($fail);
		}

	}
	Lab::set('lims_to_note_id', $nids);

};


function check_stock_ref_no($ref_no) {
	if (O('stock', ['ref_no' => $ref_no])->id) {
		return check_stock_ref_no( $ref_no . '.' );
	}
	else {
		return $ref_no;
	}
}

function sanitize_file_name( $filename ) {
    $filename_raw = $filename;
    $special_chars = ["?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}"];
    $filename = str_replace($special_chars, '', $filename);
    $filename = preg_replace('/[\s-]+/', '-', $filename);
    $filename = trim($filename, '.-_');
    return $filename;

}

$upgrade->grade_role();
$upgrade->grade_user();
$upgrade->grade_equipment();
$upgrade->grade_achivements();
$upgrade->grade_schedule();
$upgrade->grade_blog();
$upgrade->grade_grant();
$upgrade->fix_inventory();
$upgrade->grade_treenote();
