<?php

class Publications_Publication_Controller extends Base_Controller
{

    public function index($id = 0)
    {
        $publication = O('publication', $id);

        if (!$publication->id) {
            URI::redirect('error/404');
        }

        // TODO 成果权限调整
        if (!L('ME')->is_allowed_to('查看', $publication)) {
            URI::redirect('error/401');
        }

        $view_tab_opts = [
            'url' => $publication->url(null, null, null, 'view'),
        ];

        $title = $publication->title;

        if (mb_strlen($title) > 20) {
            $view_tab_opts['width'] = 300;
        }

        $view_tab_opts['title'] = I18N::T('achievements', '论文:') . H($title);

        $this->layout->body->primary_tabs
            ->add_tab('view', [
                'url'   => $publication->url(null, null, null, 'view'),
                'title' => I18N::T('achievements', '论文:') . H($publication->title),
            ])
            ->select('view');

        $content = V('publications/view');

        if (Module::is_installed('labs')) {
            $lab_project = Q("{$publication} lab_project");
            $equipments  = Q("{$publication} equipment");
            $relations   = V('publications/relations', [
                'achievement' => $publication,
                'projects'    => $lab_project,
                'equipments'  => $equipments,
            ]);
            $content->relations = $relations;
        }

        $content->publication = $publication;

        $this->layout->body->primary_tabs->content = $content;

    }

    public function edit($id = 0)
    {

        $publication = O('publication', $id);

        if (!$publication->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');

        if (!$me->is_allowed_to('修改', $publication)) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form
                ->validate('title', 'not_empty', I18N::T('achievements', '论文题目不能为空!'))
                ->validate('authors', 'not_empty', I18N::T('achievements', '论文作者不能为空!'))
                ->validate('journal', 'not_empty', I18N::T('achievements', '论文期刊不能为空!'));

            $labIds = json_decode($form['lab'], true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }
            if ($form['volume'] && (!is_numeric($form['volume']) || $form['volume'] < 0 || strpos($form['volume'], '.'))) {
                $form->set_error('volume', I18N::T('achievements', '论文卷号填写有误！'));
            }
            if ($form['issue'] && (!is_numeric($form['issue']) || $form['issue'] < 0 || strpos($form['issue'], '.'))) {
                $form->set_error('issue', I18N::T('achievements', '论文刊号填写有误！'));
            }

            if (strlen($form['impact']) > 0 && ($form['impact'] == '0' || (!is_numeric($form['impact']) || (double) $form['impact'] < 0))) {
                $form->set_error('impact', I18N::T('achievements', '影响因子填写有误, 请填写大于0的数值!'));
            }
            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }
            if ($form['authors'] && strlen(ltrim(rtrim($form['authors'], '}'), '{')) == 0) {
                $form->set_error('authors', I18N::T('achievements', '论文作者不能为空！'));
            }

            Event::trigger('achievements.publication.validate', $form, $publication);

            if ($form->no_error) {

                $publication->owner   = $me;
                $publication->title   = $form['title'];
                $publication->journal = $form['journal'];
                $publication->date    = $form['date'];
                $publication->volume  = $form['volume'];
                $publication->issue   = $form['issue'];
                $publication->page    = $form['page'];
                $publication->content = $form['content'];
                $publication->notes   = $form['notes'];
                $publication->impact  = $form['impact'];

                //将新添加的equipment和publication建立关系

                if ($publication->save()) {
                    Log::add(strtr('[achievements] %user_name[%user_id] 修改了论文:%publication_name[%publication_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%publication_name' => $publication->name, '%publication_id' => $publication->id]), 'journal');

                    Event::trigger('achievements.publication.save_access', $form, $publication);

                    $tags = @json_decode($form['tags'], true);
                    Tag_Model::replace_tags($publication, $tags, 'achievements_publication');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_publication');
                        $publication->connect($tag_root);
                    }
                    $authors    = @json_decode($form['authors'], true);
                    $position   = 1;
                    $ac_authors = Q("ac_author[achievement=$publication]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }

                    foreach ((array) $authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if (is_array($author)) {
                            $ac_author->name = $author['text'];
                            $ac_author->user = O("user", $author['user_id']);
                        } else {
                            $ac_author->name = $author;
                            $ac_author->user = null;
                        }
                        $ac_author->achievement = $publication;
                        $ac_author->position    = $position;
                        $ac_author->save();
                        $position++;
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '论文编辑成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '论文编辑失败!'));
                }
            }

            if (!$me->is_allowed_to('修改', $publication)) {
                URI::redirect('error/401');
            }
        }

        $view = Event::trigger('achievements.publication.edit', $publication, $form);
        if (!$publication->impact) {
            $publication->impact = '';
        }

        $content = V('publications/edit', [
            'publication' => $publication,
            'form'        => $form,
            'view'        => $view,
        ]);

        //$opts = [
            //'url' => $publication->url(null, null, null, 'view'),
            // 'wrapper_height'=>'21',
        //];

        $title = $publication->title;

        if (mb_strlen($title) > 20) {
            $title         = mb_substr($title, 0, 20) . '...';
            //$opts['width'] = 180;
        }

        //$opts['title'] = $title;

        $content->edit_title = V('application:edit_title', ['name' => $title, 'url' => $publication->url(null, null, null, 'view')]);

        /*$breadcrumb = [
            $opts,
            [
                'url'   => $publication->url(null, null, null, 'edit'),
                'title' => I18N::T('achievements', '编辑'),
            ],
        ];*/
        $breadcrumbs = [
            [
                'url' => '!achievements/publications',
                'title' => I18N::T('equipments', '论文列表'),
            ],
            [
                'url' => $publication->url(),
                'title' => $publication->title,
            ],
            [
                'title' => '修改',
            ],
        ];

        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);

        $this->layout->body->primary_tabs
            ->select('view')
            ->set('content', $content);
    }

    public function delete($id = 0)
    {

        $publication = O('publication', $id);

        if (!$publication->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        成果管理模块应用权限判断新规则
         */
        if (!$me->is_allowed_to('删除', $publication)) {
            URI::redirect('error/401');
        }

        $publication_attachments_dir_path = NFS::get_path($publication, '', 'attachments', true);

        if ($publication->delete()) {

            Log::add(strtr('[achievements] %user_name[%user_id] 删除了论文:%publication_name[%publication_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%publication_name' => $publication->name, '%publication_id' => $publication->id]), 'journal');

            Lab::message(LAB::MESSAGE_NORMAL, I18N::T('achievements', '论文删除成功!'));
            File::rmdir($publication_attachments_dir_path);
        } else {
            Lab::message(LAB::MESSAGE_NORMAL, I18N::T('achievements', '论文删除失败!'));
        }

        URI::redirect(URI::url('!achievements/publications/index'));
    }

    function add($site = LAB_ID){

        $me          = L('ME');
        $publication = O('publication');
        $publication->site = $site;

        if (!$me->is_allowed_to('添加成果', 'lab')) {
            URI::redirect('error/401');
        }

        if (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('title', 'not_empty', I18N::T('achievements', '论文题目不能为空!'))
                ->validate('authors', 'not_empty', I18N::T('achievements', '论文作者不能为空!'))
                ->validate('journal', 'not_empty', I18N::T('achievements', '论文期刊不能为空!'));

            /* BUG #736::用户“论文”时，论文的“卷”、“刊号”、“页号”保存有问题
            原因：页号在数据库中以字符串保存，而卷和刊号则都是整形。
            解决：限制卷号和刊号为正整数。(kai.wu@2011.7.26) TODO*/

            $labIds = json_decode($form['lab'], true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }
            if ($form['volume'] && (!is_numeric($form['volume']) || $form['volume'] < 0 || strpos($form['volume'], '.'))) {
                $form->set_error('volume', I18N::T('achievements', '论文卷号填写有误！'));
            }
            if ($form['issue'] && (!is_numeric($form['issue']) || $form['issue'] < 0 || strpos($form['issue'], '.'))) {
                $form->set_error('issue', I18N::T('achievements', '论文刊号填写有误！'));
            }
            if (strlen($form['impact']) > 0 && ($form['impact'] == '0' || (!is_numeric($form['impact']) || (double) $form['impact'] < 0))) {
                $form->set_error('impact', I18N::T('achievements', '影响因子填写有误, 请填写大于0的数值!'));
            }
            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }
            if ($form['authors'] && strlen(ltrim(rtrim($form['authors'], '}'), '{')) == 0) {
                $form->set_error('authors', I18N::T('achievements', '论文作者不能为空！'));
            }

            Event::trigger('achievements.patent.validate', $form);

            if ($form->no_error) {

                $publication->owner   = $me;
                $publication->title   = $form['title'];
                $publication->journal = $form['journal'];
                $publication->date    = $form['date'];
                $publication->volume  = $form['volume'];
                $publication->issue   = $form['issue'];
                $publication->content = $form['content'];
                $publication->page    = $form['page'];
                $publication->notes   = $form['notes'];
                $publication->impact  = $form['impact'];

                if ($publication->save()) {
                    Log::add(strtr('[achievements] %user_name[%user_id] 添加 论文:%publication_name[%publication_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%publication_name' => $publication->name, '%publication_id' => $publication->id]), 'journal');
                    
                    Event::trigger('achievements.publication.save_access', $form, $publication);
                    
                    $tags = @json_decode($form['tags'], true);
                    Tag_Model::replace_tags($publication, $tags, 'achievements_publication');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_publication');
                        $publication->connect($tag_root);
                    }
                    $authors    = @json_decode($form['authors'], true);
                    $position   = 1;
                    $ac_authors = Q("ac_author[achievement=$publication]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }
                    foreach ((array) $authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if (is_array($author)) {
                            $ac_author->name = $author['text'];
                            $ac_author->user = O("user", $author['user_id']);
                        } else {
                            $ac_author->name = $author;
                            $ac_author->user = null;
                        }
                        $ac_author->achievement = $publication;
                        $ac_author->position    = $position;
                        $ac_author->save();
                        $position++;
                    }

                    if (count($tags)) {
                        Event::trigger('trigger_scoring_rule', $me, 'achivement');
                    }

                    Event::trigger('trigger_scoring_rule', $me, 'publication');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '新增论文成功!'));
                    URI::redirect($publication->url(null, null, null, 'edit'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '新增论文失败！'));
                }

            }
        }

        $view = Event::trigger('achievements.publication.edit', $publication, $form);

        $content = V('publications/edit', [
            'publication' => $publication,
            'form'        => $form,
            'view'        => $view,
        ]);

        $this->layout->body->primary_tabs
            ->add_tab('add', [
                'url'   => $publication->url(null, null, null, 'add'),
                'title' => I18N::T('achievements', '新添加论文'),
            ])
            ->select('add')
            ->set('content', $content);
    }
}

class Publications_Publication_AJAX_Controller extends AJAX_Controller
{
    public function index_add_publication_click()
    {

        $me          = L('ME');
        $publication = O('publication');

        if (!$me->is_allowed_to('添加成果', 'lab')) {
            URI::redirect('error/401');
        }

        if (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('title', 'not_empty', I18N::T('achievements', '论文题目不能为空!'))
                ->validate('authors', 'not_empty', I18N::T('achievements', '论文作者不能为空!'))
                ->validate('journal', 'not_empty', I18N::T('achievements', '论文期刊不能为空!'));

            /* BUG #736::用户“论文”时，论文的“卷”、“刊号”、“页号”保存有问题
            原因：页号在数据库中以字符串保存，而卷和刊号则都是整形。
            解决：限制卷号和刊号为正整数。(kai.wu@2011.7.26) TODO*/

            $labIds = json_decode($form['lab'], true);
            if (Module::is_installed('labs') && !count($labIds)) {
                $form->set_error('lab', I18N::T('achievements', '课题组不能为空!'));
            }
            if ($form['volume'] && (!is_numeric($form['volume']) || $form['volume'] < 0 || strpos($form['volume'], '.'))) {
                $form->set_error('volume', I18N::T('achievements', '论文卷号填写有误！'));
            }
            if ($form['issue'] && (!is_numeric($form['issue']) || $form['issue'] < 0 || strpos($form['issue'], '.'))) {
                $form->set_error('issue', I18N::T('achievements', '论文刊号填写有误！'));
            }
            if (strlen($form['impact']) > 0 && ($form['impact'] == '0' || (!is_numeric($form['impact']) || (double) $form['impact'] < 0))) {
                $form->set_error('impact', I18N::T('achievements', '影响因子填写有误, 请填写大于0的数值!'));
            }
            if (!$form['equipments'] && Config::get('achievements.equipments.require')) {
                $form->set_error('equipments', I18N::T('achievements', '请关联项目下的仪器!'));
            }
            if ($form['authors'] && strlen(ltrim(rtrim($form['authors'], '}'), '{')) == 0) {
                $form->set_error('authors', I18N::T('achievements', '论文作者不能为空！'));
            }

            Event::trigger('achievements.publication.validate', $form, $publication);
            
            if ($form->no_error) {

                $publication->owner   = $me;
                $publication->title   = $form['title'];
                $publication->journal = $form['journal'];
                $publication->date    = $form['date'];
                $publication->volume  = $form['volume'];
                $publication->issue   = $form['issue'];
                $publication->content = $form['content'];
                $publication->page    = $form['page'];
                $publication->notes   = $form['notes'];
                $publication->impact  = $form['impact'];

                if ($publication->save()) {
                    Log::add(strtr('[achievements] %user_name[%user_id] 添加 论文:%publication_name[%publication_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%publication_name' => $publication->name, '%publication_id' => $publication->id]), 'journal');

                    Event::trigger('achievements.publication.save_access', $form, $publication);

                    $tags = @json_decode($form['tags'], true);
                    Tag_Model::replace_tags($publication, $tags, 'achievements_publication');
                    if (!count($tags)) {
                        $tag_root = Tag_Model::root('achievements_publication');
                        $publication->connect($tag_root);
                    }
                    $authors    = @json_decode($form['authors'], true);
                    $position   = 1;
                    $ac_authors = Q("ac_author[achievement=$publication]");
                    foreach ($ac_authors as $ac) {
                        $ac->delete();
                    }
                    foreach ((array) $authors as $id => $author) {
                        $ac_author = O("ac_author");
                        if (is_array($author)) {
                            $ac_author->name = $author['text'];
                            $ac_author->user = O("user", $author['user_id']);
                        }
                        else {
                            $ac_author->name = $author;
                            $ac_author->user = null;
                        }
                        $ac_author->achievement = $publication;
                        $ac_author->position    = $position;
                        $ac_author->save();
                        $position++;
                    }

                    if (count($tags)) {
                        Event::trigger('trigger_scoring_rule', $me, 'achivement');
                    }

                    Event::trigger('trigger_scoring_rule', $me, 'publication');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('achievements', '新增论文成功!'));
                    URI::redirect($publication->url(null, null, null, 'edit'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('achievements', '新增论文失败！'));
                }

            }
        }

        $view = Event::trigger('achievements.publication.edit', $publication, $form);

        JS::dialog(V('publications/add', ['publication' => $publication, 'form' => $form, 'view' => $view]), [
            'title' => I18N::T('labs', '添加新论文'),
        ]);

    }

    public function index_site_logo_click($operator, $id = 0)
    {
        $form  = Input::form();
        $type  = $form['type'];
        $sites = (array) Config::get('achievements.publication.collect.sites');
        if (!isset($sites[$type])) {
            return;
        }

        $params = [
            'type'                => $type,
            'basic_info_tbody_id' => $form['basic_info_tbody_id'],
        ];
        $view = (string) V("publications/edit.dialog/{$type}", $params);
        JS::dialog($view);
    }

    public function index_submit_info_click($operator, $id = 0)
    {
        $form  = Input::form();
        $type  = $form['type'];
        $sites = (array) Config::get('achievements.publication.collect.sites');
        if (!isset($sites[$type])) {
            return;
        }

        $args = [
            $id,
            $form,
        ];
        call_user_func_array([self, "_submit_{$type}_info"], $args);
    }

    private static function _submit_pubmed_info($id, $form)
    {
        $form = Form::filter($form);
        $pmid = trim($form['pmid']);
        if (!$pmid) {
            return;
        }

        $publication   = O('publication', $id);
        $original_pmid = $publication->pmid;
        if ($pmid == $original_pmid) {
            return;
        }

        $info = (array) $publication->get_info_by_pmid($pmid);
        if (empty($info)) {
            JS::alert(I18N::T('achievements', '无法根据PMID (%pmid) 获取文献信息!', ['%pmid' => $pmid]));
        } else {
            foreach ($info as $key => $value) {
                $publication->$key = $value;
            }
            $container_id = $form['basic_info_tbody_id'];
            $view         = (string) V('publications/edit.basic.info', ['publication' => $publication, 'form' => $form]);
            JS::close_dialog();
            Output::$AJAX["#{$container_id}"] = [
                'data' => $view,
            ];
        }
    }

    public function index_export_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        $type       = $form['type'];
        $columns    = Config::get('achievements.export_columns.publication');

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns'    => $columns,
            'type'       => $type,
            'url'        => 'publications/publication',
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
        $valid_columns   = Config::get('achievements.export_columns.publication');
        $visible_columns = $form['columns'] ?: [];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        if (isset($_SESSION[$me->id . '-export_publication'])) {
            foreach ($_SESSION[$me->id . '-export_publication'] as $old_pid => $old_form) {
                $new_valid_form = $form['columns'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export_publication'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        Log::add(strtr('[achievements] %user_name[%user_id]以Excel导出了论文', [
            '%user_name' => $me->name,
            '%user_id'   => $me->id,
        ]), 'journal');

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_publication export ';
        $cmd .= "'" . $selector . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid        = intval($var['pid']) + 1;
        $valid_form = $form['columns'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export_publication'][$pid] = $valid_form;
        return $pid;
    }
}
