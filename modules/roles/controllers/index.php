<?php

class Index_Controller extends Base_Controller
{

    function _before_call($method, &$params)
    {

        parent::_before_call($method, $params);

        $this->layout->body = V('body');
        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->body->primary_tabs
            ->add_tab('roles', [
                'url' => URI::url('!roles/index/roles'),
                'title' => I18N::T('roles', '角色列表'),
            ]);
    }

    function index()
    {
        if (Module::is_installed('db_sync') && DB_SYNC::is_module_unify_manage('role')) {
            URI::redirect(Event::trigger('db_sync.transfer_to_master_url'));
        }

        if (!L('ME')->access('管理分组')) {
            URI::redirect('error/401');
        }

        URI::redirect(URI::url('!roles/index/roles'));

    }

    function delete($id = NULL)
    {
        if (L('ME')->access('管理分组') && $id) {
            $role = O('role', $id);
            if ($role->id > 0) {
                if ($role->delete()) {
                    /* 记录日志 */
                    Log::add(strtr('[roles] %user_name[%user_id]删除了角色%role_name[%role_id]', [
                        '%user_name' => L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%role_name' => $role->name,
                        '%role_id' => $role->id
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('roles', '角色 %name 删除成功!', ['%name' => $role->name]));
                }
            }
        }
        URI::redirect(URI::url('!roles/index/roles'));
    }

    function perms($id = NULL)
    {
        if (!(L('ME')->access('管理分组'))) {
            URI::redirect('error/401');
        }

        $rid = $id;
        $roles = L('ROLES');
        $role = $roles[$id];

        if (!$role->id) {
            URI::redirect('error/404');
        }

        $this->layout->body->primary_tabs
            ->add_tab('perms', [
                'url' => URI::url('!roles/perms.' . $role->id),
                'title' => I18N::HT('roles', '%name权限设置', ['%name' => $role->name])
            ])
            ->select('perms');

        $form = Form::filter(Input::form());
        if ($form['submit']) {
            if ($form->no_error) {
                $changed = false;
                // NO.BUG#249(xiaopei.li@2010.12.17)
                // 以前若未选中任何权限就提交，会出错
                $role_perms = [];
                $perm_form = (array)$form['perms'][$role->id];
                //shulei.li@20140520 如果用户组可以管理所有内容，则组下所有账号传到文件系统，创建管理员账号
                $is_fs_admin = false;
                foreach ($perm_form as $id => $access) {
                    $perm = O('perm', $id);
                    if (!$perm->id) continue;
                    $perm_name = $perm->name;
                    if ($perm_name == '管理所有内容' && !L('ME')->access('管理所有内容')) continue;
                    $role_perms[$id] = $perm_name;

                    if ($perm_name == '管理所有内容'
                        || $perm_name == '管理本实验室成员的文件'
                        || $perm_name == '管理文件系统') $is_fs_admin = true;
                }
                $perms = (array)Q("role[id={$role->id}] perm")->to_assoc('id', 'name');

                if ($perms != $role_perms) {
                    // 由于$role->id可能小于0 (虚拟角色: 当前成员, 过期成员, 访客)
                    foreach (Q("role[id={$role->id}] perm") as $perm) {
                        $role->disconnect($perm);
                    }

                    foreach ($role_perms as $id => $name) {
                        $perm = O('perm', $id);
                        if ($perm->id) $role->connect($perm);
                    }
                    if (Module::is_installed('nfs_windows') && $is_fs_admin) {
                        $_SESSION['fs_role_id'] = $role->id;
                        Event::trigger('role_update');
                    }
                    /* 记录日志 */
                    Log::add(strtr('[roles] %user_name[%user_id]修改了角色%role_name[%role_id]的权限', [
                        '%user_name' => L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%role_name' => $role->name,
                        '%role_id' => $role->id
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('roles', '相关权限设置保存成功！'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('roles', '您没有做任何修改!'));
                }
                Event::trigger('role_perm.connect', $role);
            }
        }
//        $this->add_css('roles:perms');
//        $this->layout->body->primary_tabs->content = V('perm');
//        $this->layout->set('role', $role);
        URI::redirect(URI::url('!roles/index/roles',['rid'=>$rid]));
    }

    function roles()
    {

        if (!L('ME')->access('管理分组')) {
            URI::redirect('error/401');
        }

        $roles = clone L('ROLES');
        $roles_arr = $roles->to_assoc('id', 'name');

        $form = Form::filter(Input::form());
        if ($form['submit']) {
            $changed = FALSE;
            try {
                foreach ($form['roles'] as $id => $name) {
                    $role = O('role', $id);
                    if ($role->weight < 0) continue;
                    $name = trim($name);
                    //如果有名称，判断
                    if ($name) {
                        //名称已经存在，提示错误
                        if (in_array($name, $roles_arr) && $role->name != $name) {
                            throw new Error_Exception('角色名已存在!');
                        }

                        //名称修改判断
                        if ($name != $role->name) {
                            $role->name = $name;
                            $role->save();
                            $roles->append(['id' => $role->id, 'name' => $role->name, 'weight' => $role->weight]);
                            $changed = TRUE;

                            //是否为新角色
                            $is_new_role = !array_key_exists($role->id, $roles_arr);

                            /* 记录日志 */
                            if ($is_new_role) {
                                Log::add(strtr('[roles] %user_name[%user_id]添加了角色%role_name[%role_id]', [
                                    '%user_name' => L('ME')->name,
                                    '%user_id' => L('ME')->id,
                                    '%role_name' => $role->name,
                                    '%role_id' => $role->id
                                ]), 'journal');
                            } else {
                                Log::add(strtr('[roles] %user_name[%user_id]修改了角色%role_name[%role_id]', [
                                    '%user_name' => L('ME')->name,
                                    '%user_id' => L('ME')->id,
                                    '%role_name' => $role->name,
                                    '%role_id' => $role->id
                                ]), 'journal');
                            }
                            Lab::message(Lab::MESSAGE_NORMAL, $is_new_role ? I18N::T('roles', '角色添加成功!') : I18N::T('roles', '角色修改成功!'));

                        }
                    }
                }
                if (!$changed) {
                    throw new Error_Exception(I18N::T('roles', '未做任何修改'));
                }
            } catch (Error_Exception $e) {
                Lab::message(LAB::MESSAGE_ERROR, I18N::T('roles', $e->getMessage()));
            }
        }

        $this->add_css('roles:role_sortable');
        $this->add_js('roles:role_sortable');

        $this->layout->body->primary_tabs->select('roles');
        $container_id = 'role_perm_' . uniqid();
        $this->layout->body->primary_tabs->content = V('roles', ['roles' => $roles, 'container_id' => $container_id]);
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{

    function index_role_change_weight()
    {
        $form = Form::filter(Input::form());
        $role = O('role', $form['role_id']);
        $uniqid = $form['uniqid'];
        if (!$role->id) return;
        $prev_weight = Q::quote($form['prev_index']);
        $next_weight = $prev_weight + 1;
        $current_weight = $role->weight;
        if ($prev_weight == $next_weight) return;

        $next_role = O('role', ['weight' => $next_weight]);
        $change_weight = $next_weight;

        if ($next_role->id) {
            $selector = "role[weight > %s][weight < %s]:sort(weight %s)";
            if ($prev_weight < $current_weight) {
                /*拖拽向上运动*/
                $tmp_weight = $change_weight = $next_weight;
                $selector = sprintf($selector, $prev_weight, $current_weight, 'A');
                $way = true;
            } else {
                /*拖拽向下运动*/
                $tmp_weight = $change_weight = $prev_weight;
                $selector = sprintf($selector, $current_weight, $next_weight, 'D');
                $way = false;
            }
            $roles = Q($selector);
            foreach ($roles as $r) {
                if ((int)$r->weight != $tmp_weight) {
                    continue;
                } else {
                    if ($way) {
                        $tmp_weight++;
                    } else {
                        $tmp_weight--;
                    }
                    $r->weight = $tmp_weight;
                    $r->save();
                }
            }
        }
        $role->weight = $change_weight;
        $role->save();

        /*
         * 2017.8.28 Cheng.liu 因为更改了Role 的规律，默认角色也算正式角色，所以需要更改查找顺序, 使用统一事件进行 role 获取
         */
        Event::trigger('role.set_roles');

        Output::$AJAX["#$uniqid > .role_root_container"] = [
            'data' => (string)V('role_list', ['roles' => L('ROLES')])
        ];
    }

    function index_role_select_click()
    {
        $form = Form::filter(Input::form());
        $role = O('role', $form['rid']);
        $container_id = $form['container_id'];
        $access = Q("perm[name=管理所有内容,管理组织机构]")->to_assoc('id', 'name');
        $select_perms = (array)Q("role[id={$role->id}] perm")->to_assoc('id', 'id');
        $default_perms = !Q("role#{$role->id}")->current()->connect_perms_time ? config::get('perms.default_roles')[$role->name]['default_perms'] : [];
        Output::$AJAX["#$container_id"] = [
            'data' => (string)V('perm', [
                'role' => $role,
                'access' => $access,
                'select_perms' => $select_perms,
                'default_perms' => $default_perms,
            ])
        ];
    }

    public function index_add_role_click()
    {
        $view = V('add');

        JS::dialog($view, ['title' => I18N::T('roles', '添加角色')]);
    }

    public function index_add_role_submit()
    {
        $roles = clone L('ROLES');
        $roles_arr = $roles->to_assoc('id', 'name');

        $form = Form::filter(Input::form());
        if (Input::form('submit')) {

            $form->validate('name', 'not_empty', I18N::T('roles', '请填写角色名称！'));

            //如果有名称，判断
            //名称已经存在，提示错误
            if (in_array($form['name'], $roles_arr)) {
                $form->set_error('name', I18N::T('roles', '角色已存在！'));
            }

            if ($form->no_error) {
                $role = O('role');
                $role->name = $form['name'];
                $role->weight = 0;
                $role->save();
                $roles->append(['id' => $role->id, 'name' => $role->name, 'weight' => $role->weight]);

                Log::add(strtr('[roles] %user_name[%user_id]添加了角色%role_name[%role_id]', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%role_name' => $role->name,
                    '%role_id' => $role->id
                ]), 'journal');

                JS::redirect();
            }

        }

        JS::dialog(V('add', ['form' => $form]), [
            'title' => I18N::T('roles', '添加角色'),
        ]);
    }

    public function index_edit_role_click()
    {
        $rid = Input::form()['rid'];
        $role = O('role',$rid);
        JS::dialog(V('edit',['role'=>$role]), ['title' => I18N::T('roles', '编辑角色')]);
    }

    public function index_edit_role_submit()
    {
        $roles = clone L('ROLES');
        $roles_arr = $roles->to_assoc('id', 'name');

        $form = Form::filter(Input::form());

        $rid = Input::form()['rid'];
        $role = O('role',$rid);

        if (Input::form('submit')) {

            $form->validate('name', 'not_empty', I18N::T('roles', '请填写角色名称！'));

            //如果有名称，判断
            //名称已经存在，提示错误
            if (in_array($form['name'], $roles_arr) && array_search($roles_arr) != $rid) {
                $form->set_error('name', I18N::T('roles', '角色已存在！'));
            }

            if ($form->no_error) {
                $role->name = $form['name'];
                $role->save();
                $roles->append(['id' => $role->id, 'name' => $role->name, 'weight' => $role->weight]);

                Log::add(strtr('[roles] %user_name[%user_id]修改了角色%role_name[%role_id]', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%role_name' => $role->name,
                    '%role_id' => $role->id
                ]), 'journal');

                JS::redirect();
            }

        }

        JS::dialog(V('edit', ['form' => $form]), [
            'title' => I18N::T('roles', '编辑角色'),
        ]);
    }
}
