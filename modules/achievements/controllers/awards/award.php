<?php

class Awards_Award_Controller extends Base_Controller {

    function index($id=0) {
        $award = O('award', $id);

        if (!$award->id) URI::redirect('error/404');

        if (!L('ME')->is_allowed_to('查看', $award)) URI::redirect('error/401');
        $this->layout->body->primary_tabs
            ->add_tab('view', [
                    'url'=>$award->url(NULL,NULL,NULL,'view'),

                    /* BUG #1056::专利和获奖列表的标题没有用H转义(kai.wu@2011.08.19) */
                    'title'=>I18N::T('achievements','获奖：') . H($award->name),
                ])
            ->select('view');

        $content = V('awards/view');

        /* (xiaopei.li@2011.06.07) */
        if (Module::is_installed('labs')) {
            $lab_project = Q("{$award} lab_project");
            $equipments = Q("{$award} equipment");
            $relations = V('awards/relations', [
                               'achievement' => $award,
                               'projects' => $lab_project,
                               'equipments' => $equipments,
                               ]);
            $content->relations = $relations;
        }
        /* (xiaopei.li@2011.06.07) */

        $content->award = $award;

        $this->layout->body->primary_tabs->content = $content;

    }

    function edit($id=0) {
        $award = O('award',$id);
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        成果管理模块应用权限判断新规则
        */
        if (!$award->id) {
            URI::redirect('error/404');
        }
        $me = L('ME');

        if (!$me->is_allowed_to('修改', $award)) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());

        if($form['submit']){
            $form->validate('name', 'not_empty', I18N::T('achievements', '获奖名称不能为空！'));

            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }
            $labIds = json_decode($form['lab'],true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }

            Event::trigger('achievements.award.validate', $form);

            if ($form->no_error) {

                $award->owner = $me;
                $award->name = $form['name'];
                $award->date = $form['date'];
                $award->description = $form['description'];
                if ($award->save()) {

                    Log::add(strtr('[achievements] %user_name[%user_id] 修改了获奖:%award_name[%award_id]', ['%user_name'=> $me->name, '%user_id'=>  $me->id, '%award_name'=> $award->name, '%award_id'=> $award->id]), 'journal');

                    Event::trigger('achievements.award.save_access', $form, $award);
                    $authors = json_decode($form['people'],TRUE);
                    $position = 1;
                    $ac_authors = Q("ac_author[achievement=$award]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }
                    if (!count((array)$authors)) {
                        $author = O('ac_author');
                        $author->achievement = $award;
                        $author->save();
                    }
                    foreach ((array)$authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if(is_array($author)) {
                            $user = O("user",$author['user_id']);
                            $ac_author->achievement = $award;
                            $ac_author->user = $user;
                            $ac_author->position = $position;
                            $ac_author->name = $author['text'];
                            $ac_author->save();
                        }
                        else {
                            $ac_author->achievement = $award;
                            $ac_author->name = $author;
                            $ac_author->user = O('user');
                            $ac_author->position = $position;
                            $ac_author->save();
                        }
                        $position ++;


                    }
                    $tags = @json_decode($form['tags'], TRUE);
                    Tag_Model::replace_tags($award, $tags, 'achievements_award');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_award');
                        $award->connect($tag_root);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '获奖情况修改成功!'));
                }else{
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '获奖情况修改失败!'));
                }
            }

            if (!$me->is_allowed_to('修改', $award)) {
                URI::redirect('error/401');
            }
        }
        $view = Event::trigger('achievements.award.edit', $award, $form);
        $content = V('awards/edit',[
            'award'=>$award,
            'form'=>$form,
            'view'=>$view
        ]);

        $breadcrumbs = [
            [
                'url' => '!achievements/awards',
                'title' => I18N::T('equipments', '获奖列表'),
            ],
            [
                'url' => $award->url(),
                'title' => $award->name,
            ],
            [
                'title' => '修改',
            ],
        ];

        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);

        $this->layout->body->primary_tabs
                        //->add_tab('view',['*'=>$breadcrumb])
                        ->select('view')
                        ->set('content', $content);
    }

    function delete ($id) {
        $award = O('award', $id);

        if (!$award->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');

        if (!$me->is_allowed_to('删除', $award)) {
            URI::redirect('error/401');
        }

        $award_attachments_dir_path = NFS::get_path($award, '', 'attachments', TRUE);

        if ($award->delete()) {

            Log::add(strtr('[achievements] %user_name[%user_id] 删除了获奖:%award_name[%award_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%award_name'=> $award->name, '%award_id'=> $award->id]), 'journal');

            Lab::message(LAB::MESSAGE_NORMAL,I18N::T('achievements','获奖信息删除成功!'));
            File::rmdir($award_attachments_dir_path);
        }
        else {
            Lab::message(LAB::MESSAGE_NORMAL,I18N::T('achievements','获奖信息删除失败!'));
        }

        URI::redirect(URI::url('!achievements/awards/index'));
    }

    function add($site = LAB_ID){

        $me = L('ME');

        if (!$me->is_allowed_to('添加成果', 'lab')) {
            URI::redirect('error/401');
        }
        $award = O('award');
        $award->site = $site;
        
        $form = Form::filter(Input::form());
        if($form['submit']){
            $form->validate('name', 'not_empty', I18N::T('achievements', '获奖名称不能为空！'));

            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }
            $labIds = json_decode($form['lab'],true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }

            Event::trigger('achievements.award.validate', $form);

            if ($form->no_error) {

                $award->owner = $me;
                $award->name = $form['name'];
                $award->date = $form['date'];
                $award->description = $form['description'];

                if ($award->save()) {

                    /* 记录日志 */

                    Log::add(strtr('[achievements] %user_name[%user_id] 添加了获奖:%award_name[%award_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%award_name'=> $award->name, '%award_id'=> $award->id]), 'journal');

                    Event::trigger('achievements.award.save_access', $form, $award);
                    $authors = json_decode($form['people'],TRUE);
                    $position = 1;
                    $ac_authors = Q("ac_author[achievement=$award]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }
                    if (!count((array)$authors)) {
                        $author = O('ac_author');
                        $author->achievement = $award;
                        $author->save();
                    }
                    foreach ((array)$authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if(is_array($author)) {
                            $user = O("user",$author['user_id']);
                            $ac_author->achievement = $award;
                            $ac_author->user = $user;
                            $ac_author->position = $position;
                            $ac_author->name = $author['text'];
                            $ac_author->save();
                        }
                        else {
                            $ac_author->achievement = $award;
                            $ac_author->name = $author;
                            $ac_author->user = O('user');
                            $ac_author->position = $position;
                            $ac_author->save();
                        }
                        $position ++;


                    }
                    $tags = @json_decode($form['tags'], TRUE);
                    Tag_Model::replace_tags($award, $tags, 'achievements_award');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_award');
                        $award->connect($tag_root);
                    }else {
                        Event::trigger('trigger_scoring_rule', $me, 'achivement');
                    }
                    Event::trigger('trigger_scoring_rule', $me, 'publication');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '新增获奖成功!'));

                    URI::redirect($award->url(NULL, NULL, NULL, 'edit'));
                }
                else{
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '新增获奖失败!'));
                }
            }
        }

        $view = Event::trigger('achievements.award.edit', $award, $form);

        $content = V('awards/edit',[
                                        'award'=>$award,
                                        'form'=>$form,
                                        'view'=>$view
                                        ]);
        $this->layout->body->primary_tabs
                        ->add_tab('add',[
                                'url'=>$award->url(NULL, NULL, NULL, 'add'),
                                'title'=>I18N::T('achievements', '新添加获奖情况'),
                            ])
                        ->select('add')
                        ->set('content', $content);

    }

}

class Awards_Award_AJAX_Controller extends AJAX_Controller {

	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$columns = Config::get('achievements.export_columns.award');

		JS::dialog(V('export_form',[
			'form_token' => $form_token,
			'columns' => $columns,
			'type' => $type,
			'url' => 'awards/award'
		]),[
			'title' => I18N::T('achievements','请选择要导出Excel的列')
		]);
	}

	function index_export_submit() {
		$form = Input::form();
		$form_token = $form['form_token'];
		if (!$_SESSION[$form_token]) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '操作超时, 请重试!'));
			URI::redirect($_SESSION['system.current_layout_url']);
		}

		$form = $_SESSION[$form_token] + $form;
		
		$file_name_time = microtime(TRUE);
		$file_name_arr = explode('.', $file_name_time);
		$file_name = $file_name_arr[0].$file_name_arr[1];

		$pid = $this->_export_excel($form['selector'], $form, $file_name);
		JS::dialog(V('export_wait', [
			'file_name' => $file_name,
			'pid' => $pid
		]), [
			'title' => I18N::T('achievements', '导出等待')
		]);
	}

	function _export_excel($selector, $form, $file_name) {
		$me = L('ME');
		$valid_columns = Config::get('achievements.export_columns.award');
		$visible_columns = $form['columns'] ? : [];

		foreach ($valid_columns as $p => $p_name ) {
			if ($visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}

		if (isset($_SESSION[$me->id.'-export_award'])) {
			foreach ($_SESSION[$me->id.'-export_award'] as $old_pid => $old_form) {
				$new_valid_form = $form['columns'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export_award'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}

		Log::add(strtr('[achievements] %user_name[%user_id]以Excel导出了获奖', [
			'%user_name' => $me->name,
			'%user_id' => $me->id
		]),'journal');

		putenv('Q_ROOT_PATH=' . ROOT_PATH);
		$cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_award export ';
		$cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
		$process = proc_open($cmd, [], $pipes);
		$var = proc_get_status($process);
		proc_close($process);
		$pid = intval($var['pid']) + 1;
		$valid_form = $form['columns'];
		unset($valid_form['form_token']);
		unset($valid_form['selector']);
		$_SESSION[$me->id.'-export_award'][$pid] = $valid_form;
		return $pid;
	}
}
