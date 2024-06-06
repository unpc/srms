<?php

class Patents_patent_Controller extends Base_Controller
{

    public function index($id = 0)
    {
        $patent = O('patent', $id);

        if (!$patent->id) {
            URI::redirect('error/404');
        }

        // TODO 成果权限调整
        if (!L('ME')->is_allowed_to('查看', $patent)) {
            URI::redirect('error/401');
        }

        $this->layout->body->primary_tabs
            ->add_tab('view', [
                'url'   => $patent->url(null, null, null, 'view'),

                /* BUG #1056::专利和获奖列表的标题没有用H转义(kai.wu@2011.08.19) */
                'title' => I18N::T('achievements', '专利：') . H($patent->name),
            ])
            ->select('view');

        $content = V('patents/view');

        /* (xiaopei.li@2011.06.07) */
        if (Module::is_installed('labs')) {
            $lab_project = Q("{$patent} lab_project");
            $equipments  = Q("{$patent} equipment");
            $relations   = V('patents/relations', [
                'achievement' => $patent,
                'projects'    => $lab_project,
                'equipments'  => $equipments,
            ]);
            $content->relations = $relations;
        }
        /* (xiaopei.li@2011.06.07) */

        $content->patent = $patent;

        $this->layout->body->primary_tabs->content = $content;

    }

    public function edit($id = 0)
    {
        $patent = O('patent', $id);

        if (!$patent->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        成果管理模块应用权限判断新规则
         */

        if (!$me->is_allowed_to('修改', $patent)) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());
        if ($form['submit']) {

            $form
                ->validate('name', 'not_empty', I18N::T('achievements', '专利名称不能为空!'))
                ->validate('ref_no', 'not_empty', I18N::T('achievements', '专利号不能为空!'));

            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }

            $labIds = json_decode($form['lab'], true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }

            Event::trigger('achievements.patent.validate', $form);

            if ($form->no_error) {
                $patent->owner  = $me;
                $patent->name   = $form['name'];
                $patent->date   = $form['date'];
                $patent->ref_no = trim($form['ref_no']);

                if ($patent->save()) {

                    Log::add(strtr('[achievements] %user_name[%user_id] 修改了专利:%patent_name[%patent_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%patent_name' => $patent->name, '%patent_id' => $patent->id]), 'journal');
                }

                if ($patent->id) {
                    Event::trigger('achievements.patent.save_access', $form, $patent);
                    $authors    = json_decode($form['people'], true);
                    $position   = 1;
                    $ac_authors = Q("ac_author[achievement=$patent]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }

                    if (!count((array) $authors)) {
                        $author              = O('ac_author');
                        $author->achievement = $patent;
                        $author->save();
                    }
                    foreach ((array) $authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if (is_array($author)) {
                            $user                   = O("user", $author['user_id']);
                            $ac_author->achievement = $patent;
                            $ac_author->user        = $user;
                            $ac_author->position    = $position;
                            $ac_author->name        = $author['text'];
                            $ac_author->save();
                        } else {
                            $ac_author->achievement = $patent;
                            $ac_author->name        = $author;
                            $ac_author->user        = O('user');
                            $ac_author->position    = $position;
                            $ac_author->save();
                        }
                        $position++;
                    }

                    $tags = @json_decode($form['tags'], true);
                    Tag_Model::replace_tags($patent, $tags, 'achievements_patent');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_patent');
                        $patent->connect($tag_root);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '修改专利信息成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '修改专利信息失败!'));
                }
            }

            if (!$me->is_allowed_to('修改', $patent)) {
                URI::redirect('error/401');
            }
        }

        $view = Event::trigger('achievements.patent.edit', $patent, $form);

        $content = V('patents/edit', [
            'patent' => $patent,
            'form'   => $form,
            'view'   => $view,
        ]);
        $breadcrumbs = [
            [
                'url' => '!achievements/patents',
                'title' => I18N::T('equipments', '专利列表'),
            ],
            [
                'url' => $patent->url(),
                'title' => $patent->name,
            ],
            [
                'title' => '修改',
            ],
        ];

        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);

        $this->layout->body->primary_tabs
            //->add_tab('view', ['*' => $breadcrumb])
            ->select('view')
            ->set('content', $content);
    }

    public function delete($id)
    {
        $patent = O('patent', $id);

        if (!$patent->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('删除', $patent)) {
            URI::redirect('error/401');
        }

        $patent_attachments_dir_path = NFS::get_path($patent, '', 'attachments', true);
        if ($patent->delete()) {

            Log::add(strtr('[achievements] %user_name[%user_id] 删除了专利:%patent_name[%patent_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%patent_name' => $patent->name, '%patent_id' => $patent->id]), 'journal');

            Lab::message(LAB::MESSAGE_NORMAL, I18N::T('achievements', '专利信息删除成功!'));
            File::rmdir($patent_attachments_dir_path);
        } else {
            Lab::message(LAB::MESSAGE_NORMAL, I18N::T('achievements', '专利信息删除失败!'));
        }

        URI::redirect(URI::url('!achievements/patents/index'));
    }

    function add($site = LAB_ID){

        $me = L('ME');

        if (!$me->is_allowed_to('添加成果', 'lab')) {
            URI::redirect('error/401');
        }

        $patent = O('patent');
        $patend->site = $site;
        $form = Form::filter(Input::form());

        if ($form['submit']) {

            $form
                ->validate('name', 'not_empty', I18N::T('achievements', '专利名称不能为空!'))
                ->validate('ref_no', 'not_empty', I18N::T('achievements', '专利号不能为空!'));

            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }

            $labIds = json_decode($form['lab'], true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }

            Event::trigger('achievements.patent.validate', $form);

            if ($form->no_error) {

                $lab            = $form['lab'] ? $lab            = O('lab', $form['lab']) : Q("$me lab")->current();
                $patent->lab    = $lab;
                $patent->owner  = $me;
                $patent->name   = $form['name'];
                $patent->date   = $form['date'];
                $patent->ref_no = trim($form['ref_no']);

                if ($patent->save()) {

                    Log::add(strtr('[achievements] %user_name[%user_id] 添加了专利:%patent_name[%patent_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%patent_name' => $patent->name, '%patent_id' => $patent->id]), 'journal');

                }

                if ($patent->id) {
                    Event::trigger('achievements.patent.save_access', $form, $patent);
                    $authors    = json_decode($form['people'], true);
                    $position   = 1;
                    $ac_authors = Q("ac_author[achievement=$patent]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }

                    if (!count((array) $authors)) {
                        $author              = O('ac_author');
                        $author->achievement = $patent;
                        $author->save();
                    }
                    foreach ((array) $authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if (is_array($author)) {
                            $user                   = O("user", $author['user_id']);
                            $ac_author->achievement = $patent;
                            $ac_author->user        = $user;
                            $ac_author->position    = $position;
                            $ac_author->name        = $author['text'];
                            $ac_author->save();
                        } else {
                            $ac_author->achievement = $patent;
                            $ac_author->name        = $author;
                            $ac_author->user        = O('user');
                            $ac_author->position    = $position;
                            $ac_author->save();
                        }
                        $position++;

                    }

                    $tags = @json_decode($form['tags'], true);
                    Tag_Model::replace_tags($patent, $tags, 'achievements_patent');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_patent');
                        $patent->connect($tag_root);
                    }else {
                        Event::trigger('trigger_scoring_rule', $me, 'achivement');
                    }
                    Event::trigger('trigger_scoring_rule', $me, 'publication');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '新增专利成功!'));
                    URI::redirect($patent->url(null, null, null, 'edit'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '新增专利失败!'));
                }
            }
        }

        $view = Event::trigger('achievements.patent.edit', $patent, $form);

        $content = V('patents/edit', [
            'patent' => $patent,
            'form'   => $form,
            'view'   => $view,
        ]);
        $this->layout->body->primary_tabs
            ->add_tab('add', [
                'url'   => $patent->url(null, null, null, 'add'),
                'title' => I18N::T('achievements', '新添加专利情况'),
            ])
            ->select('add')
            ->set('content', $content);
    }
}

class Patents_patent_AJAX_Controller extends AJAX_Controller
{

    public function index_export_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        $type       = $form['type'];
        $columns    = Config::get('achievements.export_columns.patent');

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns'    => $columns,
            'type'       => $type,
            'url'        => 'patents/patent',
        ]), [
            'title' => I18N::T('achievements', '请选择要导出Excel的列'),
        ]);
    }

    public function index_export_submit()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }

        $form = $_SESSION[$form_token] + $form;

        $file_name_time = microtime(true);
        $file_name_arr  = explode('.', $file_name_time);
        $file_name      = $file_name_arr[0] . $file_name_arr[1];

        $pid = $this->_export_excel($form['selector'], $form, $file_name);
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid'       => $pid,
        ]), [
            'title' => I18N::T('achievements', '导出等待'),
        ]);
    }

    public function _export_excel($selector, $form, $file_name)
    {
        $me              = L('ME');
        $valid_columns   = Config::get('achievements.export_columns.patent');
        $visible_columns = $form['columns'] ?: [];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        if (isset($_SESSION[$me->id . '-export_patent'])) {
            foreach ($_SESSION[$me->id . '-export_patent'] as $old_pid => $old_form) {
                $new_valid_form = $form['columns'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export_patent'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        Log::add(strtr('[achievements] %user_name[%user_id]以Excel导出了专利', [
            '%user_name' => $me->name,
            '%user_id'   => $me->id,
        ]), 'journal');

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_patent export ';
        $cmd .= "'" . $selector . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid        = intval($var['pid']) + 1;
        $valid_form = $form['columns'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export_patent'][$pid] = $valid_form;
        return $pid;
    }
}
