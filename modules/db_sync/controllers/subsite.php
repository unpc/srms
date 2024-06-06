<?php

class Subsite_Controller extends Layout_Controller
{
    public function delete($id)
    {
        $me      = L('ME');
        $subsite = O('subsite', (int) $id);
        if ($me->is_allowed_to('删除', $subsite)) {
            $name = $subsite->name;
            if ($subsite->delete()) {
                Lab::message(Lab::MESSAGE_NORMAL, T("分站点 [{$name}] 删除成功！"));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, T("分站点 [{$name}] 删除失败！"));
            }
        } else {
            Lab::message(Lab::MESSAGE_ERROR, T("您无权删除分站点!"));
        }

        URI::redirect('admin/groups.db_sync');
    }
}

class Subsite_AJAX_Controller extends AJAX_Controller
{
    public function index_add_subsite_click()
    {
        JS::dialog(V('db_sync:admin/subsite/add', [
            'form' => [],
        ]), [
            'title' => I18N::T('db_sync', '添加分站信息'),
        ]);
    }

    public function index_add_subsite_submit()
    {
        $me   = L('ME');
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('db_sync', '请填写分站点名称!'));
            $form->validate('links', 'not_empty', I18N::T('db_sync', '请填写分站点地址!'));
            $incharges = (array) @json_decode($form['incharges'], true);
            if (count($incharges) == 0) {
                $form->set_error('incharges', I18N::T('db_sync', '请指定至少指定一名分站管理员!'));
            }
            if (strlen($form['description']) > 60) {
                $form->set_error('description', I18N::T('db_sync', '描述字符过长，请削减后提交!'));
            }

            if ($form->no_error) {
                $subsite              = O('subsite');
                $subsite->name        = H($form['name']);
                $subsite->links       = H($form['links']);
                $subsite->ref_no      = H($form['ref_no']);
                $subsite->ctime       = Date::time();
                $subsite->description = H($form['description']);
                if ($subsite->save()) {
                    foreach ($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $subsite->connect($user, 'incharge');
                    }

                    Log::add(strtr('[db_sync] %user_name[%user_id]添加了分站点%subsite[%ref_no]', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%subsite'   => $subsite->name,
                        '%ref_no'    => $subsite->ref_no,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, T("添加分站点 [{$subsite->name}] 成功！"));
                    JS::refresh();
                }
            } else {
                JS::dialog(V('db_sync:admin/subsite/add', [
                    'form' => $form,
                ]), [
                    'title' => I18N::T('db_sync', '添加分站信息'),
                ]);
            }
        }
    }

    public function index_edit_subsite_click()
    {
        $form    = Input::form();
        $subsite = O('subsite', (int) $form['id']);
        JS::dialog(V('db_sync:admin/subsite/edit', [
            'form'    => $form,
            'subsite' => $subsite,
        ]), [
            'title' => I18N::T('db_sync', '添加分站信息'),
        ]);
    }

    public function index_edit_subsite_submit()
    {
        $me      = L('ME');
        $form    = Form::filter(Input::form());
        $subsite = O('subsite', (int) $form['id']);

        if (!$subsite->id) {
            Lab::message(Lab::MESSAGE_ERROR, T("数据提交有误，请联系管理员！"));
            JS::refresh();
            exit(0);
        }

        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('db_sync', '请填写分站点名称!'));
            if ($subsite->status == Subsite_Model::UNCONNECTED) {
                $form->validate('links', 'not_empty', I18N::T('db_sync', '请填写分站点地址!'));
            }
            if (strlen($form['description']) > 60) {
                $form->set_error('description', I18N::T('db_sync', '描述字符过长，请削减后提交!'));
            }

            $incharges = (array) @json_decode($form['incharges'], true);
            if (count($incharges) == 0) {
                $form->set_error('incharges', I18N::T('db_sync', '请指定至少指定一名分站管理员!'));
            }

            if ($form->no_error) {
                // $subsite->user        = O('user', (int) $form['user_id']);
                $subsite->name = H($form['name']);

                if ($form['links']) {
                    $subsite->links = H($form['links']);
                }
                $subsite->ref_no      = H($form['ref_no']);
                $subsite->description = H($form['description']);

                if (isset($form['incharges'])) {
                    foreach (Q("$subsite<incharge user") as $incharge) {
                        $subsite->disconnect($incharge, 'incharge');
                    }

                    foreach ($incharges as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }

                        $subsite->connect($user, 'incharge');
                    }
                }

                if ($subsite->save()) {
                    Log::add(strtr('[db_sync] %user_name[%user_id]修改了分站点%subsite[%ref_no]', [
                        '%user_name' => $me->name,
                        '%user_id'   => $me->id,
                        '%subsite'   => $subsite->name,
                        '%ref_no'    => $subsite->ref_no,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, T("编辑分站点 [{$subsite->name}] 成功！"));
                    JS::refresh();
                }
            } else {
                JS::dialog(V('db_sync:admin/subsite/edit', [
                    'form'    => $form,
                    'subsite' => $subsite,
                ]), [
                    'title' => I18N::T('db_sync', '编辑分站信息'),
                ]);
            }
        }
    }
}
