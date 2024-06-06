<?php
class Resume_controller extends Base_controller {

	function index($id=0, $tab='regular'){
		$resume = O('resume', $id);
		if (!$resume->id) {
			URI::redirect('error/404');
		}

		$regular_content = V('resume/regular', ['resume' => $resume]);
		$track_content = V('resume/track', ['resume' => $resume]);

		$secondary_tabs	= Widget::factory('tabs');

		$secondary_tabs->add_tab('regular', [
						  'url'=>URI::url('!resume/resume/index.' . $id . '.regular'),
						  'title'=>I18N::T('resume', '面试情况')
						  ]);
		$secondary_tabs->add_tab('track', [
									 'url'=>URI::url('!resume/resume/index.' . $id . '.track'),
									 'title'=>I18N::T('resume', '跟踪信息')
									 ]);

		if ($tab == 'regular')
			$secondary_tabs->select('regular')->set('content', $regular_content);

		if ($tab == 'track')
			$secondary_tabs->select('track')->set('content', $track_content);

		$files = Resume::get_files($resume->get_path($file['name']));

		$primary_content = V('resume/detail', [
								 'resume' => $resume,
								 'secondary_tabs' => $secondary_tabs,
								 'files' => $files]);

		$this->layout->body->primary_tabs
			->add_tab('detail', [
						  'url'=>$resume->url(),
						  'title'=>I18N::T('resume', '%uname的简历', ['%uname' => $resume->uname])
						  ])->select('detail')->set('content', $primary_content);

		$form = Input::form();
		if ($form['submit']) {
			$file = Input::file('attachment');
			if ($file && $file['tmp_name']) {
				$full_path = $resume->get_path($file['name']);
 				move_uploaded_file($file['tmp_name'], $full_path);
 				URI::redirect('!resume/resume/index.' . $id . '.' . $tab);
			}
		}
	}

	function add($id=0, $tab=0) {
		if ( !L('ME')->is_allowed_to('添加', 'resume') ) {
			URI::redirect('error/401');
		}

		$form = Form::filter(Input::form());
		if ($form['submit']) {
			$form->validate('uname', 'not_empty', I18N::T('resume', '姓名不能为空!'));
			if ($form['position_id'] == 0) {
				$form->set_error('position_id', I18N::T('resume', '应聘职位不能为空！'));
			}

			if ($form->no_error) {
				$resume = O('resume');
				$resume->uname = $form['uname'];//interviewer's name
				$resume->position = O('position', $form['position_id']);
				$resume->interview_place = $form['interview_place'];
				$resume->phone = $form['phone'];
				$resume->sex = $form['sex'];
				$resume->birthday = $form['birthday'];
				$resume->current_location = $form['current_location'];
				$resume->education = $form['education'];
				$resume->school = $form['school'];
				$resume->status = Resume::INVESTIGATION;
				$resume->education_background = $form['education_background'];
				$resume->experience = $form['experience'];
				$resume->description = $form['description'];

				if ($resume->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('resume', '简历添加成功!'));
					URI::redirect('!resume/resume/edit.' . $resume->id);
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('resume', '简历添加失败!'));
				}
			}
		}

		$content = V('resume/add', ['form' => $form]);
		$this->layout->body->primary_tabs
			->add_tab('add', [
						  'title' => I18N::T('resume', '添加简历'),
						  'url' => URI::url('!resume/resume/add')
						  ])
			->select('add')
			->set('content', $content);
	}

	function edit($id=0, $tab='info') {
		if ( !L('ME')->is_allowed_to('修改', 'resume') ) {
			URI::redirect('error/401');
		}
		$resume = O('resume', $id);
		if (!$resume->id) URI::redirect('error/404');
		$this->layout->body->primary_tabs
			->add_tab('edit', [
						  '*' => [
							  [
								  'url' => URI::url('!resume/resume/index.' . $resume->id),
								  'title' => I18N::T('resume', '%uname的简历', ['%uname' => $resume->uname])],
							  [
								  'url' => $resume->url(NULL, NULL, NULL, 'edit'),
								  'title' => I18N::T('resume', '修改')]]
						  ]);

		$form = Form::filter(Input::form());
		$file = Input::form();
		if ($form['submit'] && isset($file)) {
			$file = Input::file('attachment');
			if ($file && $file['tmp_name']) {
				$full_path = $resume->get_path($file['name']);
 				move_uploaded_file($file['tmp_name'], $full_path);
 				URI::redirect('!resume/resume/edit.' . $id . '.' . $tab);
			}
		}

		if ($form['submit'] && !isset($file)) {
			$form->validate('uname', 'not_empty', I18N::T('resume', '姓名不能为空!'));
			if ($form['position_id'] == 0) {
				$form->set_error('position_id', I18N::T('resume', '应聘职位不能为空！'));
			}

			if ($form->no_error) {
				$resume->uname = $form['uname'];//interviewer's name
				$resume->position = O('position', $form['position_id']);
				$resume->phone = $form['phone'];
				$resume->interview_place = $form['interview_place'];
				$resume->sex = $form['sex'];
				$resume->status = $form['status'];
				$resume->birthday = $form['birthday'];
				$resume->current_location = $form['current_location'];
				$resume->education = $form['education'];
				$resume->school = $form['school'];
				$resume->feedback = $form['feedback'];
				$resume->description = $form['description'];
				$resume->result = $form['result'];
				if( L('ME')->is_allowed_to('领导批示', 'resume') ) {
					$resume->opinion = $form['opinion'];
				}
				$resume->education_background = $form['education_background'];
				$resume->experience = $form['experience'];

				if ($form['status'] == Resume::WAIT_FOR_INTERVIEW) {
					$resume->interview_time = $form['interview_time'];
				}

				$tracks = $resume->track;
				if (!isset($tracks[$form['status']]) && $form['status'] != Resume::INVESTIGATION) {
					$tracks[$form['status']] = time();
					$resume->track = $tracks;
				}

				if ($resume->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('resume', '简历修改成功!'));
				}
				else
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('resume', '简历修改失败!'));
			}
		}

		$selector = 'resume';
		$resumes = Q($selector . ":sort()");

		$content = V('resume:resume/edit', ['form' => $form, 'resumes' => $resumes, 'resume' => $resume]);
		$content->secondary_tabs = Widget::factory('tabs');
		$content->secondary_tabs
			->add_tab('info', [
						  'url' => $resume->url('info', NULL, NULL, 'edit'),
						  'title' => I18N::T('resume', '个人信息')])
			->add_tab('attachment', [
						  'url' => $resume->url('attachment', NULL, NULL, 'edit'),
						  'title' => I18N::T('resume', '附件')]);

		$files = Resume::get_files($resume->get_path($file['name']));

		if ($tab == 'info') {
			$secondary_tabs_content = V('resume/info',
										['resume' => $resume]);
		}
		if ($tab == 'attachment') {
			$secondary_tabs_content = V('resume/attachment',
										['resume' => $resume, 'files' => $files]);
		}
		$content->secondary_tabs->set('class', 'secondary_tabs')
			->select($tab)->set('content', $secondary_tabs_content);
		$this->layout->body->primary_tabs
			->select('edit')
			->set('content', $content);
	}

	function delete($id = 0) {

		$resume = O('resume', $id);
		if (!$resume->id) URI::redirect('error/404');
		$uname = $resume->uname;

		if ($resume->delete_dir() && $resume->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('resume', '成功删除%uname的简历!', ['%uname' => $uname]));
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('resume', '删除%uname的简历失败!', ['%uname' => $uname]));
		}
		URI::redirect('!resume');
	}

	function delete_file($id = 0) { //use to delete a file

		$resume = O('resume', $id);
		if (!$resume->id) URI::redirect('error/404');
		$name = Input::form('name');
		$file_path = $resume->get_path($name);
		if (file_exists($file_path) and unlink($file_path)) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('resume', '成功删除文件【%name】!', ['%name' => $name]));
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('resume', '文件【%name】删除失败!', ['%name' => $name]));
		}
		URI::redirect('!resume/resume/edit.' . $id . '.attachment');
	}

	function download_file($id = 0) {

		$resume = O('resume', $id);
		if (!$resume->id) URI::redirect('error/404');
		$name = Input::form('name');
		$file_path = $resume->get_path($name);
		Downloader::download($file_path, TRUE);
		return;
	}
}
