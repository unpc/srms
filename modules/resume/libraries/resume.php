<?php

class Resume {

	const INVESTIGATION = 1;
	const WAIT_FOR_AUDIT = 2;
	const ARRANGE_INTERVIEW = 3;
	const NO_QUALIFICATION = 4;
	const WAIT_FOR_INTERVIEW = 5;
	const CANCEL = 6;
	const PASS = 7;
	const NOT_PASS = 8;

	const FULL_TIME = 1;
	const PART_TIME = 2;

	const MALE = 1;
	const FEMALE = 2;

	const PHP_ENGINEER = 1;
	const C_CPP_ENGINEER = 2;
	const EMBEDDED_ENGINEER = 3;
	const SOFTWARE_TACH_SUPPORT = 4;
	const SELLER = 5;
	const TELEPHONIST = 6;

	const WITHIN_A_DAY = 86399;

	const JUNIOR_COLLEGE = 1;
	const UNDERGRADUATE_COLLEGE = 2;
	const MASTER_DEGREE = 3;
	const DOCTOR_DEGREE = 4;

	const TIAN_JIN = 1;
	const BEI_JING = 2;

	static $status = [
		self::INVESTIGATION => '了解情况',
		self::WAIT_FOR_AUDIT => '等待审核',
		self::ARRANGE_INTERVIEW => '安排面试',
		self::NO_QUALIFICATION => '未获面试资格',
		self::WAIT_FOR_INTERVIEW => '等待面试',
		self::CANCEL => '取消面试',
		self::PASS => '通过面试',
		self::NOT_PASS => '未通过面试',
		];

	static $employment_type = [
		self::FULL_TIME => '全职',
		self::PART_TIME => '兼职'
		];

	static $sex = [
		self::MALE => '男',
		self::FEMALE => '女'
		];

	static $education = [
		self::JUNIOR_COLLEGE => '专科',
		self::UNDERGRADUATE_COLLEGE => '本科',
		self::MASTER_DEGREE => '硕士',
		self::DOCTOR_DEGREE => '博士'
		];

	static $interview_place = [
		self::TIAN_JIN => '天津',
		self::BEI_JING => '北京'
		];

	static function & get_files($path){
		$files = [];
		$handle = @opendir($path);
		if ($handle) {
			while($file=(readdir($handle))){
				if ($file[0]==".") continue;
				$files[] = $file;
			}
			closedir($handle);
		}
		return $files;
	}

	static function save_abbr($object, $new_data) {
		if ($new_data['uname'] && class_exists('PinYin')) {
			$schema = ORM_Model::schema($object);
			if (isset($schema['fields']['uname_abbr']))
				$object->uname_abbr = PinYin::code($new_data['uname']);
		}
	}

	static function setup_update() {
		Event::bind('update.index.tab', 'Resume::_index_update_tab');
	}

	static function _index_update_tab($e, $tabs) {
		$tabs->add_tab('position', [
			'url'=>URI::url('!update/index.position'),
			'title'=>I18N::T('resume', '简历更新')
		]);
	}

	static $resume_info = [
		'status' => '当前进度',
		'interview_time' => '面试时间',
		'feedback' => '反馈信息'
	];

	static $actions = [
		'edit'
	];

	static function on_resume_saved($e, $resume, $old_data, $new_data) {
		$position = O('position', $resume->position_id);
		$datas = Event::trigger('get.datas', $resume, $old_data, $new_data);
		if ($datas)
			foreach ((array) $datas as $data) {
				if (!is_array($data)) continue;
				Update::add_update(L('ME'), $data['action'], $position, $data['old_data'], $data['new_data']);
			}
	}

	static function get_update_parameter($e, $object, $old_data, $new_data) {
		if ($object->name() != 'resume' || !$old_data) {
			return;
		}
		$difference = array_diff_assoc($new_data,$old_data);
		$old_difference = array_diff_assoc($old_data, $new_data);
	  	$arr = array_keys($difference);
	  	$info_keys = array_keys(Resume::$resume_info);
	  	$data = $e->return_value;
	  	if(!count($difference)) {
	  		return;
	  	}
		$delta = [];
	  	if (count(array_intersect($info_keys, $arr))) {
	  		$delta['action'] = 'edit.' . $object->id ;
	  	}
	  	else {
	  		return;
	  	}

		$delta['new_data'] = $difference;
		$delta['old_data'] = $old_difference;

  		$key = Misc::key((string)$subject, $delta['action'], (string)$object);
  		$data[$key] = (array)$data[$key];

  		Misc::array_merge_deep($data[$key], $delta);

  		$e->return_value = $data;
	}

	static function get_update_message($e, $update) {
		if ($update->object->name() !== 'position')
			return;
		$me = L('ME');
		$subject = $update->subject->name;
		$old_data = json_decode($update->old_data, TRUE);
		$arr = explode('.', $update->action);
		$action = array_shift($arr);
		$rid = array_shift($arr);
		$object = O('resume', $rid);
		if (!$object->id) return;
		switch($action) {
			case 'edit':
				$config = 'resume.info.msg.model';
				break;
			default:
				return;
		}
		$opt = Lab::get($config, Config::get($config));
		$msg = I18N::T('resume', $opt['body'], [
						'%subject'=>URI::anchor($update->subject->url(), $subject, 'class="blue label"'),
						'%date'=>'<strong>'.Date::fuzzy($update->ctime, 'TRUE').'</strong>',
						'%resume'=>URI::anchor($object->url(), $object->uname, 'class="blue label"')
						]);
		$e->return_value = $msg;
		return FALSE;
	}

	static function get_update_message_view($e, $update) {
		$arr = explode('.', $update->action);
		$action = array_shift($arr);
		$rid = array_shift($arr);
		$resume = O('resume', $rid);
		if(!$resume->id) return;
		if ($action == 'edit') {
			$properties = Resume::$resume_info;
		}
		$e->return_value = V('resume:update/show_msg', ['update'=>$update, 'properties'=>$properties]);
		return FALSE;
	}

	static function resume_ACL($e, $user, $perm, $object, $option) {
		switch( $perm ){
		case '查看':
		case '导出':
			if( $user->access('查看简历') ) {
				$e->return_value = TRUE;
				return FALSE;
			}	
			break;
		case '修改':
		case '添加':
		case '生成新员工':
			if ( $user->access('添加/修改简历') ) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '领导批示':
			if ( $user->access('添加/修改领导意见') ) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}

	static function is_accessible($e, $name ) {
		$me = L('ME');
		if( $me->is_allowed_to('查看', 'resume' ) ) {
			$e->return_value = TRUE;
			return FALSE;
		}else if( $me->is_allowed_to('查看', 'position' ) ) {
			$e->return_value = TRUE;
			return FALSE;
		}else{
			$e->return_value = FALSE;
		}

	}
}
