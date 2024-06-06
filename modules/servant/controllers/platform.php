<?php
class Platform_Controller extends Base_Controller {

    function index ($id = 0, $tab = '') {
        $me = L('ME');
        $pf = O('platform', $id);
        if (!$pf->id) URI::redirect('error/404');
        if (!$me->is_allowed_to('查看', $pf)) URI::redirect('error/401');

        $this->layout->body->primary_tabs->add_tab('index', ['*' => [
            [
                'url' => URI::url("!servant/platform/index.{$id}"),
                'title' => H($pf->name),
            ], [
                'url' => URI::url("!servant/platform/index.{$id}"),
                'title' => I18N::HT('servant', '信息'),
            ]
        ]]);

        $secondary = Widget::factory('tabs');
        $secondary
            ->set('pf', $pf)
            ->tab_event('servant.secondary.tab')
            ->content_event('servant.secondary.content')
            ->select($tab);

        $tab = $this->layout->body->primary_tabs->select('index');

        $tab->content = V('platform/view', [
            'pf' => $pf,
            'secondary' => $secondary,
        ]);
    }

    function add () {
        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'platform')) URI::redirect('error/401');
        $form = Form::filter(Input::form());

        $this->layout->body->primary_tabs->add_tab('add', [
            'url' => URI::url('!servant/platform/add'),
            'title' => I18N::T('servant', '添加机构'),
        ]);

        $tab = $this->layout->body->primary_tabs->select('add');
        $tab->content = V('platform/add', [
            'form' => $form
        ]);
    }

    function edit ($id = 0) {
        $me = L('ME');
        $pf = O('platform', $id);
        $owners = Q("{$pf} user.owner");
        if (!$pf->id) URI::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) URI::redirect('error/401');
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form->validate('owner', 'not_empty', I18N::T('equipments', '请选择负责人!'));

            if ($form->no_error) {
                $pf->contact = $form['contact'];
                $pf->address = $form['address'];
                $pf->description = $form['description'];
                $pf->creator = $me;

                if ($form['active']) {
                    $pf->atime = time();
                    Servant::enable_platform($pf);
                }
                else {
                    $pf->atime = 0;
                    Servant::disable_platform($pf);
                }

                if ($pf->save()) {
                    foreach ($owners as $user) {
                        $pf->disconnect($user, 'owner');
                    }

                    foreach (json_decode($form['owner']) as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) continue;
                        $pf->connect($user, 'owner');
                        $pf->connect($user);
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '机构修改成功'));
                    URI::redirect('!servant');
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('servant', '机构修改失败! 请与系统管理员联系。'));
                }
            }
        }

        $this->layout->body->primary_tabs->add_tab('edit', ['*' => [
            [
                'url' => '!servant',
                'title' => H($pf->name),
            ], [
                'url' => URI::url("!servant/platform/edit.{$id}"),
                'title' => I18N::HT('servant', '修改'),
            ]
        ]]);

        $tab = $this->layout->body->primary_tabs->select('edit');
        $tab->content = V('platform/edit', [
            'pf' => $pf,
            'form' => $form,
            'owners' => $owners,
        ]);
    }

}

class Platform_AJAX_Controller extends AJAX_Controller {
    function index_pf_create_click() {
        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'platform')) JS::redirect('error/401');

        $view = V('servant:platform/add');

        JS::dialog($view, ['title' => I18N::T('servant', '添加下属机构')]);
    }

    function index_pf_create_submit() {
        $form = Form::filter(Input::form());
        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('servant', '请填写机构名称!'));
            $form->validate('code', 'not_empty', I18N::T('servant', '请填写机构代码!'));
            $owners = @json_decode($form['owner'], true);
            if (!count($owners)) {
                $form->set_error('owner', I18N::T('servant', '请选择负责人!'));
            }
            if ($message = Servant::disable_code($form['code'])) {
                $form->set_error('code', $message);
            }

            if ($form->no_error) {
                $pf = O('platform');
                $pf->name = $form['name'];
                $pf->code = $form['code'];
                $pf->contact = $form['contact'];
                $pf->address = $form['address'];
                $pf->description = $form['description'];
                $pf->creator = $me;
                $enable_modules = [];
                foreach ($form['modules'] as $key => $value) {
                    if ($value == 'on') $enable_modules[] = $key;
                }
                $pf->modules = $enable_modules;

                if ($form['active']) {
                    $pf->atime = time();
                }

                if ($pf->save()) {
                    foreach ($owners as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) continue;
                        $pf->connect($user, 'owner');
                        $pf->connect($user);
                    }

                    $pid = Servant::create_platform($pf);

                    JS::dialog(V('servant_wait', [
                        'platform' => $pf,
                        'action' => 'create',
                        'pid' => $pid
                    ]), [
                        'title' => I18N::T('servant', '建站等待')
                    ]);
                }
                else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('servant', '机构添加失败! 请与系统管理员联系。'));
                }
            }
            else {
                $view = V('servant:platform/add', ['form' => $form]);
                JS::dialog($view, ['title' => I18N::T('servant', '添加下属机构')]);
            }
        }
    }

    function index_pf_step_check () {
        $cache = Cache::factory();
        $form = Input::form();

        $pid = $form['pid'];
        $action = $form['action'];
        $platform_id = $form['platform'];
        $process = $cache->get('servant.' . $action . '.'.$platform_id);
        $msg = Servant::$step_str[$process['step']] ? : '处理中';
        $ret = [
            'msg' => I18N::T('servant', $msg),
        ];
        if ($process['step'] == $action . '_done_msg') {
            $ret['done'] = TRUE;
        }
        Output::$AJAX = $ret;
    }

    function index_pf_step_stop() {
        $cache = Cache::factory();
        $form = Input::form();

        $pid = $form['pid'];
        $action = $form['action'];
        $platform_id = $form['platform'];
        if ($action != 'create') return;

        $cache->set('servant.create.'.$platform_id, NULL);
        proc_close(proc_open('kill -9 '.$pid, [], $pipes));
    }

    function index_pf_delete_click () {
        $me = L('ME');
        $id = Input::form()['id'];
        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('删除', $pf)) JS::redirect('error/401');

        if ($pf->id && JS::confirm(I18N::T('servant', "您确定删除{$pf->name}机构吗?"))) {
            $pid = Servant::delete_platform($pf);

            JS::dialog(V('servant_wait', [
                'platform' => $pf,
                'action' => 'delete',
                'pid' => $pid
            ]), [
                'title' => I18N::T('servant', '删除站点')
            ]);
        }
    }

    function index_equ_add_click () {
        $me = L('ME');
        $id = Input::form()['id'];
        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        $message = I18N::T('servant', '增加的仪器其他下属机构将无法使用');

        $view = V('servant:equipment/add', [
            'pf' => $pf,
            'message' => $message,
        ]);

        JS::dialog($view, ['title' => I18N::T('servant', '增加仪器')]);
    }

    function index_equ_add_submit () {
        $me = L('ME');
        $form = Input::form();
        $id = $form['id'];

        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        foreach (json_decode($form['equipments']) as $id => $name) {
            $equipment = O('equipment', $id);
            if (!$equipment->id) continue;
            $pf->connect($equipment);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '仪器添加成功'));
        }
        JS::refresh();
    }

    function index_equ_delete_click () {
        $me = L('ME');
        $pf_id = Input::form()['pf_id'];
        $pf = O('platform', $pf_id);
        if (!$pf->id) JS::redirect('error/404');
        $equ_id = Input::form()['equ_id'];
        $equipment = O('equipment', $equ_id);
        if (!$equipment->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        if ($pf->id && JS::confirm(I18N::T('servant', "您确定要删除该下属仪器吗?"))) {
            $pf->disconnect($equipment);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '下属仪器删除成功'));
        }
        JS::refresh();
    }

    function index_lab_add_click () {
        $me = L('ME');
        $id = Input::form()['id'];
        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        $view = V('servant:lab/add', [
            'pf' => $pf,
        ]);

        JS::dialog($view, ['title' => I18N::T('servant', '增加课题组')]);
    }

    function index_lab_add_submit () {
        $me = L('ME');
        $form = Input::form();
        $id = $form['id'];

        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');
        if (JS::confirm(I18N::T('servant', '是否需要同时将课题组下成员，增加至此站点下属成员？'))) {
            $connect_user = TRUE;
        }

        foreach (json_decode($form['labs']) as $id => $name) { 
            $lab = O('lab', $id);
            if (!$lab->id) continue;
            $pf->connect($lab);
            if ($connect_user) {
                foreach (Q("$lab user") as $user) {
                    $pf->connect($user);
                }
            }
        }
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '课题组添加成功'));
        JS::refresh();
    }

    function index_lab_delete_click () {
        $me = L('ME');
        $pf_id = Input::form()['pf_id'];
        $pf = O('platform', $pf_id);
        if (!$pf->id) JS::redirect('error/404');
        $lab_id = Input::form()['lab_id'];
        $lab = O('lab', $lab_id);
        if (!$lab->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        if ($pf->id && JS::confirm(I18N::T('servant', "您确定要删除该下属课题组吗?"))) {
            $pf->disconnect($lab);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '下属课题组删除成功'));
        }
        JS::refresh();
    }

    function index_user_add_click () {
        $me = L('ME');
        $id = Input::form()['id'];
        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        $view = V('servant:user/add', [
            'pf' => $pf,
        ]);

        JS::dialog($view, ['title' => I18N::T('servant', '增加成员')]);
    }

    function index_user_add_submit () {
        $me = L('ME');
        $form = Input::form();
        $id = $form['id'];

        $pf = O('platform', $id);
        if (!$pf->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        foreach (json_decode($form['users']) as $id => $name) { 
            $user = O('user', $id);
            if (!$user->id) continue;
            $pf->connect($user);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '成员添加成功'));
        }
        JS::refresh();
    }

    function index_user_delete_click () {
        $me = L('ME');
        $pf_id = Input::form()['pf_id'];
        $pf = O('platform', $pf_id);
        if (!$pf->id) JS::redirect('error/404');
        $user_id = Input::form()['user_id'];
        $user = O('user', $user_id);
        if (!$user->id) JS::redirect('error/404');
        if (!$me->is_allowed_to('修改', $pf)) JS::redirect('error/401');

        if ($pf->id && JS::confirm(I18N::T('servant', "您确定要删除该下属课题组吗?"))) {
            $pf->disconnect($user);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('servant', '下属课题组删除成功'));
        }
        JS::refresh();
    }
}
