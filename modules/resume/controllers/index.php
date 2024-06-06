<?php

class Index_Controller extends Base_Controller {

	function _before_call($method, $params) {
		parent::_before_call($method, $params);

		$me = L('ME');
		if(!$me->is_allowed_to('查看', 'resume' )
		&&  $me->is_allowed_to('查看', 'position' )
		) {
			URI::redirect('!resume/position');
		}
	}

	function index() { //enter into this function every time the page is loaded/freshed
		//$form = Lab::form();//when the reset_search == 1, the $form will be reset to empty
		$form = Lab::form(function(& $old_form, & $form) {
				if (isset($form['date_filter'])) {
					if (!$form['ctime_check']) unset($old_form['ctime_check']);
					if (!$form['interview_time_check']) unset($old_form['interview_time_check']);
					unset($form['date_filter']);
				}
			});

		$selector = 'resume';

		if ($form['uname']) {
			$uname = Q::quote($form['uname']);
			$selector .= "[uname*={$uname}]";
		}

		if ($form['sex']) {
			$sex = Q::quote($form['sex']);
			$selector .= "[sex={$sex}]";
		}

		if ($form['education']) {
			$education = Q::quote($form['education']);
			$selector .= "[education={$education}]";
		}

		if ($form['position_id']) {
			$position_id = Q::quote($form['position_id']);
			$selector .= "[position_id={$position_id}]";
		}

		if ($form['interview_place']) {
			$interview_place = Q::quote($form['interview_place']);
			$selector .= "[interview_place={$interview_place}]";
		}

		if ($form['interview_time_check']) {
			$interview_time = Q::quote($form['interview_time']);
			$d = getdate($interview_time);
			$interview_time = mktime(0, 0, 0, $d['mon'], $d['mday'], $d['year']);
			$interview_time_max = $interview_time + Resume::WITHIN_A_DAY;
			$selector .= "[interview_time={$interview_time}~{$interview_time_max}]";
		}

		if ($form['ctime_check']) {
			$ctime = Q::quote($form['ctime']);
			$d = getdate($ctime);
			$ctime = mktime(0, 0, 0, $d['mon'], $d['mday'], $d['year']);
			$ctime_max = $ctime + Resume::WITHIN_A_DAY;
			$selector .= "[ctime={$ctime}~{$ctime_max}]";
		}

		if ($form['status']) {
			$status = Q::quote($form['status']);
			$selector .= "[status={$status}]";
		}

		$sort_by = $form['sort'];
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';

		switch ($sort_by) {
		case 'uname':
			$selector .= ":sort(uname $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'sex':
			$selector .= ":sort(sex $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'education':
			$selector .= ":sort(education $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'position':
			$selector .= ":sort(position_id $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'interview_place':
			$selector .= ":sort(interview_place $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'interview_time':
			$selector .= ":sort(interview_time $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'ctime':
			$selector .= ":sort(ctime $sort_flag)";
			$form['sort'] = NULL;
			break;
		case 'status':
			$selector .= ":sort(status $sort_flag)";
			$form['sort'] = NULL;
			break;
		default:
			$selector .= ":sort(ctime $sort_flag)";
			break;
		}

		$resumes = Q($selector);
		$pagination = Lab::pagination($resumes, $form['st'], 25);

		$me = L('ME');
		$panel_buttons = [];
	
		if( $me->is_allowed_to('添加','resume') ) {
			$panel_buttons[] = [
				'text'  => I18N::T('resume', '添加简历'),
				'url' => URI::url('!resume/resume/add'),
				'extra' => 'class="button button_add"'
			];
		}
		if( $me->is_allowed_to('导出','resume') ) {
			$panel_buttons[] = [
				'text'  => I18N::T('resume', '导出简历'),
				'url' => URI::url('!resume/index/export', 'type=csv'),
				'extra' => 'class="button button_export"'
			];
		}
		$content = V('index', [
						 'resumes' => $resumes,
						 'form' => $form,
						 'pagination' => $pagination,
						 'sort_asc' => $sort_asc,
						 'sort_by' => $sort_by,
						 'panel_buttons' => $panel_buttons
						 ]);

		$this->layout->body->primary_tabs
			->select('resume')
			->set('content', $content);
	}

	function export($type='csv') {
		if ('csv' == $type)
			$this->_export_csv();
	}

	private function _export_csv() {

		$resumes = Q('resume');
		$file_name = date('Ymd') . rand(0, 1000);
		$csv = new CSV('php://output', 'w', $file_name);
		$csv->write([
						I18N::T('resume', '姓名'),
						I18N::T('resume', '联系方式'),
						I18N::T('resume', '性别'),
						I18N::T('resume', '出生日期'),
						I18N::T('resume', '目前所在地'),
						I18N::T('resume', '学历'),
						I18N::T('resume', '学校'),
						I18N::T('resume', '教育背景'),
						I18N::T('resume', '工作经验'),
						I18N::T('resume', '应聘地点'),
						I18N::T('resume', '应聘职位'),
						I18N::T('resume', '简历录入时间'),
						I18N::T('resume', '面试时间'),
						I18N::T('resume', '当前状态'),
						I18N::T('resume', '反馈信息'),
						I18N::T('resume', '备注'),
						I18N::T('resume', '领导意见')
						]);

		if ($resumes->total_count() > 0)
			foreach ($resumes as $resume) {
				$csv->write([
								H($resume->uname),
								$resume->phone ? H($resume->phone) : '--',
								$resume->sex ? H(Resume::$sex[$resume->sex]) : '--',
								Date::format($resume->birthday, T('Y/m/d')),
								$resume->current_location ? H($resume->current_location) : '--',
								$resume->education ? H(Resume::$education[$resume->education]) : '--',
								$resume->school ? H($resume->school) : '--',
								$resume->education_background ? H($resume->education_background) : '--',
								$resume->experience ? H($resume->experience) : '--',
								$resume->interview_place ? H(Resume::$interview_place[$resume->interview_place]) : '--',
								$resume->position_id ? H(O('position', $resume->position_id)->name) : '--',
								Date::format($resume->ctime, T('Y/m/d H:i:s')),
								$resume->interview_time ? Date::format($resume->interview_time, T('Y/m/d H:i:s')) : '--',
								$resume->status ? H(Resume::$status[$resume->status]) : '--',
								$resume->feedback ? H($resume->feedback) : '--',
								$resume->description ? H($resume->description) : '--',
								$resume->opinion ? H($resume->opinion) : '--'
								]);
			}
		$csv->close();
	}
}
