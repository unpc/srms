<?php

class Meeting_Controller extends Base_Controller
{

    public function index($id = 0, $tab = 'info')
    {
        $meeting = O('meeting', $id);
        $me      = L('ME');
        if (!$meeting->id) {
            URI::redirect('error/404');
        }

        if ($tab != 'announce' && Q("meeting_announce[meeting=$meeting]:not($me meeting_announce.read)")->total_count() > 0 && !$me->is_allowed_to('添加公告', $meeting)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '您需阅读过会议室公告，才可使用会议室!'));
            URI::redirect($meeting->url('announce'));
        }

        $content          = V('view');
        $content->meeting = $meeting;

        Event::bind('meeting.index.content', [$this, '_meeting_info'], 0, 'info');
        Event::bind('meeting.index.content', [$this, '_meeting_announce'], 0, 'announce');

        $this->layout->body->primary_tabs
        = Widget::factory('tabs')
            ->add_tab('info', [
                'url'    => $meeting->url(''),
                'title'  => I18N::T('meeting', '常规信息'),
                'weight' => 0,
            ])
            ->add_tab('announce', [
                'url'    => $meeting->url('announce'),
                'title'  => I18N::T('meeting', '公告'),
                'weight' => 30,
            ])
            ->set('meeting', $meeting)
            ->tab_event('meeting.index.tab')
            ->content_event('meeting.index.content')
            ->tool_event('meeting.index.tab.tool_box')
            ->select($tab);

        $breadcrumbs = [
            [
                'url' => '!meeting',
                'title' => I18N::T('meeting', '会议室列表'),
            ],
            [
                'title' => $meeting->name,
            ]
        ];

        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('meeting/header_content', ['meeting' => $meeting]);
        $this->layout->title = I18N::T('meeting', '');
    }

    public function _meeting_info($e, $tabs)
    {
        $meeting       = $tabs->meeting;
        $tabs->content = V('meeting/info', ['meeting' => $meeting]);
    }

    public function _meeting_announce($e, $tabs)
    {
        $me      = L('ME');
        $meeting = $tabs->meeting;

        if ($me->is_allowed_to('添加公告', $meeting)) {
            $panel_buttons[] = [
                'tip'   => I18N::T('meeting', '添加公告'),
                'text' => I18N::T('meeting', '添加公告'),
                'extra' => 'q-event="click" q-object="add_announce" q-static=' . H(["e_id" => $meeting->id]) . ' class="button button_add" q-src="' . H(URI::url('!meeting/announce')) . '" ',
            ];
            $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
        }

        $selector      = "meeting_announce[meeting={$meeting}]:sort(is_sticky D, mtime D)";
        $announces     = Q($selector);
        $tabs->content = V('meeting/announce', ['meeting' => $meeting, 'announces' => $announces, 'panel_buttons'=>$panel_buttons]);
    }

}

class Meeting_AJAX_Controller extends AJAX_Controller
{

    public function index_create_tag_click()
    {
        $id = Input::form('id');
        if (!$id) {
            return;
        }

        JS::dialog(V('meeting:meeting_tags/create_tag', ['id' => $id]),
            ['title' => '添加标签']);
    }

    public function index_admin_create_tag_click()
    {
        JS::dialog(V('meeting:admin/user_tags/create_tag'),
            ['title' => '添加标签']);
    }

    public function index_create_tag_submit()
    {
        $form = Form::filter(Input::form());
        $id   = $form['id'];
        if (!$id) {
            return;
        }

        $me       = L('ME');
        $tag_name = $form['name'];

        $form->validate('name', 'not_empty', I18N::T('meeting', '标签名称不能为空!'));

        if ($form->no_error) {
            $root = O('meeting', $id)->get_root();
            $tag  = O('tag_meeting_user_tags', ['root' => $root, 'name' => $tag_name]);
            if (!$tag->id) {
                $tag->name   = $tag_name;
                $tag->parent = $root;
                $tags        = Q("tag[root={$root}]:sort(weight D):limit(1)");
                $tag->weight = $tags->total_count() ? $tags->current()->weight + 1 : 0;
                // 设置权重 保持展示顺序
                $tag->update_root()->save();

                Log::add(strtr('[meeting] %user_name[%user_id]添加%meeting_name[%meeting_id]仪器的用户标签%tag_name[%tag_id]', [
                    '%user_name'    => $me->name,
                    '%user_id'      => $me->id,
                    '%meeting_name' => $meeting->name,
                    '%meeting_id'   => $meeting->id,
                    '%tag_name'     => $tag->name,
                    '%tag_id'       => $tag->id,
                ]), 'journal');

                JS::refresh();
            } else {
                $form->set_error('name', I18N::T('meeting', '该标签已存在，请输入其他名称!'));
            }
        }
        JS::dialog(V('meeting:meeting_tags/create_tag', ['id' => $id, 'form' => $form]),
            ['title' => '添加标签']);
    }

    public function index_admin_create_tag_submit()
    {
        $form     = Form::filter(Input::form());
        $me       = L('ME');
        $tag_name = $form['name'];
        $form->validate('name', 'not_empty', I18N::T('meeting', '标签名称不能为空!'));
        if ($form->no_error) {
            //仪器用户标签
            $root = Tag_Model::root('meeting_user_tags');
            $tag = O('tag_meeting_user_tags', ['root' => $root, 'name' => $tag_name]);
            if (!$tag->id) {
                $tag->name   = $tag_name;
                $tag->parent = $root;
                $tags        = Q("tag[root={$root}]:sort(weight D):limit(1)");
                $tag->weight = $tags->total_count() ? $tags->current()->weight + 1 : 0;

                $tag->update_root()->save();

                JS::refresh();
            } else {
                $form->set_error('name', I18N::T('meeting', '该标签已存在，请输入其他名称!'));
            }
        }
        JS::dialog(V('meeting:admin/user_tags/create_tag', ['form' => $form]),
            ['title' => '添加标签']);
    }

    public function index_delete_tag_click()
    {
        if (!JS::confirm(I18N::T('meeting', '请谨慎操作!您确认要删除该标签？'))) {
            return;
        }
        $id = Input::form('tid');
        if (!$id) {
            return;
        }

        $tag = O('tag_meeting_user_tags', $id);
        $labs = Q("{$tag} lab");

        if (count($labs)) {
            foreach ($labs as $lab) {
                $tag->disconnect($lab);
            }
        }

        $root   = Tag_Model::root('group');
        $groups = Q("{$tag} tag_group[root={$root}]");
        if (count($groups)) {
            foreach ($groups as $group) {
                $tag->disconnect($group);
            }
        }

        if ($tag->delete()) {
            JS::refresh();
        } else {
            JS::alert(I18N::T('meeting', '请谨慎操作!您确认要删除该标签？'));
        }
    }

    public function index_tag_relate_data_submit()
    {
        $form = Form::filter(Input::form());
        $tid  = $form['tid'];
        if (!$tid) {
            return;
        }

        $users  = $form['users'];
        $labs   = $form['labs'];
        $groups = $form['groups'];
        $tid    = $form['tid'];
        $uniqid = $form['uniqid'];

        $me      = L('ME');
        $meeting = O('meeting', $form['id']);
        $tag = O('tag_meeting_user_tags', $tid);

        if ($users) {
            $users           = json_decode($users, true);
            $connected_users = Q("{$tag} user");
            foreach ($users as $uid => $name) {
                //给标签关联新的user,并删除不存在的user
                $user = O('user', $uid);
                if (!$user->id) {
                    continiue;
                }

                if (!isset($connected_users[$user->id])) {
                    $tag->connect($user);
                } else {
                    unset($connected_users[$user->id]);
                }
            }
            if (count($connected_users)) {
                foreach ($connected_users as $user) {
                    $tag->disconnect($user);
                }
            }

        }

        if ($labs) {
            $labs           = json_decode($labs, true);
            $connected_labs = Q("{$tag} lab");
            foreach ($labs as $lid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $lab = O('lab', $lid);
                if (!$lab->id) {
                    continiue;
                }

                if (!isset($connected_labs[$lab->id])) {
                    $tag->connect($lab);
                } else {
                    unset($connected_labs[$lab->id]);
                }
            }
            if (count($connected_labs)) {
                foreach ($connected_labs as $lab) {
                    $tag->disconnect($lab);
                }
            }

        }

        if ($groups) {
            $root             = Tag_Model::root('group');
            $groups           = json_decode($groups, true);
            $connected_groups = Q("{$tag} tag_group[root={$root}]");
            foreach ($groups as $gid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $group = O('tag_group', $gid);
                if (!$group->id) {
                    continiue;
                }

                if (!isset($connected_groups[$gid])) {
                    $tag->connect($group);
                } else {
                    unset($connected_groups[$gid]);
                }
            }
            if (count($connected_groups)) {
                foreach ($connected_groups as $group) {
                    $tag->disconnect($group);
                }
            }

        }

        Log::add(strtr('[meeting] %user_name[%user_id]修改%meeting_name[%meeting_id]仪器的用户标签%tag_name[%tag_id]',
            [
                '%user_name'    => $me->name,
                '%user_id'      => $me->id,
                '%meeting_name' => $meeting->name,
                '%meeting_id'   => $meeting->id,
                '%tag_name'     => $tag->name,
                '%tag_id'       => $tag->id,
            ]), 'journal');

        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string) V('meeting:meeting_tags/message',
                ['type' => Lab::MESSAGE_NORMAL, 'message' => I18N::T('meeting', '用户标签设置成功!')]),
            'mode' => 'append',
        ];

    }

    public function index_admin_tag_relate_data_submit()
    {
        $form = Form::filter(Input::form());
        $tid  = $form['tid'];
        if (!$tid) {
            return;
        }

        $users  = $form['users'];
        $labs   = $form['labs'];
        $groups = $form['groups'];
        $tid    = $form['tid'];
        $uniqid = $form['uniqid'];

        $me  = L('ME');
        $tag = O('tag_meeting_user_tags', $tid);

        if ($users) {
            $users           = json_decode($users, true);
            $connected_users = Q("{$tag} user");
            foreach ($users as $uid => $name) {
                //给标签关联新的user,并删除不存在的user
                $user = O('user', $uid);
                if ($user->id) {
                    if (!isset($connected_users[$user->id])) {
                        $tag->connect($user);
                    } else {
                        unset($connected_users[$user->id]);
                    }
                }
            }
            if (count($connected_users)) {
                foreach ($connected_users as $user) {
                    $tag->disconnect($user);
                }
            }

        }

        if ($labs) {
            $labs           = json_decode($labs, true);
            $connected_labs = Q("{$tag} lab");
            foreach ($labs as $lid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $lab = O('lab', $lid);
                if ($lab->id) {
                    if (!isset($connected_labs[$lab->id])) {
                        $tag->connect($lab);
                    } else {
                        unset($connected_labs[$lab->id]);
                    }
                }
            }
            if (count($connected_labs)) {
                foreach ($connected_labs as $lab) {
                    $tag->disconnect($lab);
                }
            }

        }
        #ifdef (equipment.enable_group_specs)
        if (Config::get('equipment.enable_group_specs')) {
            $groups = $form['groups'];
            if ($groups) {
                $root             = Tag_Model::root('group');
                $groups           = json_decode($groups, true);
                $connected_groups = Q("{$tag} tag_group[root={$root}]");
                foreach ($groups as $gid => $name) {
                    //给标签关联新的lab,并删除不存在的lab
                    $group = O('tag_group', $gid);
                    if ($group->id) {
                        if (!isset($connected_groups[$gid])) {
                            $tag->connect($group);
                        } else {
                            unset($connected_groups[$gid]);
                        }
                    }
                }
                if (count($connected_groups)) {
                    foreach ($connected_groups as $group) {
                        $tag->disconnect($group);
                    }
                }

            }
        }
        #endif

        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string) V('equipments:admin/user_tags/message'),
            'mode' => 'append',
        ];
    }

    public function index_edit_tag_blur()
    {
        $form          = Form::filter(Input::form());
        $tid           = $form['tid'];
        $name          = $form['tname'];
        $uniqid        = $form['uniqid'];
        $relate_uniqid = $form['relate_uniqid'];
        if (!$tid) {
            return;
        }

        if ($name) {
            $tag       = O('tag_meeting_user_tags', $tid);
            $tag->name = $name;
            $tag->save();
        }

        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string) V('meeting:meeting_tags/tag', [
                'tid'           => $tid,
                'id'            => $tid,
                'relate_uniqid' => $relate_uniqid,
            ]),
            'mode' => 'replace',
        ];

    }

    public function index_replace_tag_click()
    {
        $form   = Form::filter(Input::form());
        $tid    = $form['tid'];
        $uniqid = $form['uniqid'];
        if (!$tid) {
            return;
        }

        Output::$AJAX["#$uniqid > .relate_view"] = [
            'data' => (string) V('meeting:meeting_tags/relate_view', [
                'tid' => $tid,
            ]),
            'mode' => 'replace',
        ];
    }

    public function index_admin_replace_tag_click()
    {
        $form   = Form::filter(Input::form());
        $tid    = $form['tid'];
        $uniqid = $form['uniqid'];
        if (!$tid) {
            return;
        }

        Output::$AJAX["#$uniqid > .relate_view"] = [
            'data' => (string) V('meeting:admin/user_tags/relate_view', ['tid' => $tid]),
            'mode' => 'replace',
        ];
    }

}
