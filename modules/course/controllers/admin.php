<?php

class Admin_Controller extends Layout_Controller
{

}

class Admin_AJAX_Controller extends AJAX_Controller
{
    public function index_add_school_term_click()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'school_term')) {
            URI::redirect('error/401');
        }

        $term= O('school_term');

        JS::dialog(V('course:school_term/admin/add', ['form' => $form, 'term' => $term]), [
            'title' => I18N::T('course', '添加学期'),
        ]);
    }

    public function index_add_school_term_submit()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'school_term')) {
            URI::redirect('error/401');
        }

        $term = O('school_term');

        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('year', 'not_empty', I18N::T('course', '请输入所属学年!'))
                ->validate('term', 'not_empty', I18N::T('course', '请输入所属学期!'))
                ->validate('dtstart', 'not_empty', I18N::T('course', '请输入学期开始时间!'))
                ->validate('dtend', 'not_empty', I18N::T('course', '请输入学期结束时间!'));

            if ($form->no_error) {
                $term->year = H($form['year']);
                $term->term = $form['term'];
                $term->dtstart = $form['dtstart'];
                $term->dtend = $form['dtend'];
                $term->save();
                if ($term->id) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '学期添加成功!'));
                    JS::redirect("admin/course");
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '学期添加失败! 请与系统管理员联系。'));
                }

            }
        }

        JS::dialog(V('course:school_term/admin/add', ['form' => $form, 'term' => $term]), [
            'title' => I18N::T('course', '添加学期'),
        ]);

    }

    public function index_edit_school_term_click()
    {
        $me = L('ME');

        $form = Input::form();
        $term = O('school_term', $form['id']);

        if (!$me->is_allowed_to('修改', $term)) {
            URI::redirect('error/401');
        }

        JS::dialog(V('course:school_term/admin/edit', ['form' => $form, 'term' => $term]), [
            'title' => I18N::T('course', '编辑学期'),
        ]);
    }

    public function index_edit_school_term_submit()
    {
        $me = L('ME');

        $form = Input::form();
        $term = O('school_term', $form['id']);

        if (!$me->is_allowed_to('修改', $term)) {
            URI::redirect('error/401');
        }

        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('year', 'not_empty', I18N::T('course', '请输入所属学年!'))
                ->validate('term', 'not_empty', I18N::T('course', '请输入所属学期!'))
                ->validate('dtstart', 'not_empty', I18N::T('course', '请输入学期开始时间!'))
                ->validate('dtend', 'not_empty', I18N::T('course', '请输入学期结束时间!'));

            if ($form->no_error) {
                $term->year = H($form['year']);
                $term->term = $form['term'];
                $term->dtstart = $form['dtstart'];
                $term->dtend = $form['dtend'];
                $term->save();
                if ($term->id) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '学期添加成功!'));
                    JS::redirect("admin/course");
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '学期添加失败! 请与系统管理员联系。'));
                }

            }
        }

        JS::dialog(V('course:school_term/admin/add', ['form' => $form, 'term' => $term]), [
            'title' => I18N::T('course', '添加学期'),
        ]);

    }

    public function  index_delete_school_term_click()
    {
        $me = L('ME');

        $form = Input::form();
        $term = O('school_term', $form['id']);

        if (!$me->is_allowed_to('修改', $term)) {
            URI::redirect('error/401');
        }

        if(JS::confirm(T('您确定要删除吗?删除后不可恢复!'))){
            // 该学期已关联数据，不可删除 TODO
            if (false) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '该学期已关联数据，不可删除!'));
                JS::refresh();
            } elseif ($term->delete()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '删除学期信息成功!'));
                Log::add(strtr('[course] %user_name[%user_id]删除了学期信息[%term_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%term_id' => $term->id
                ]), 'journal');
            }
            JS::refresh();
        }
    }

    public function index_get_course_session_click()
    {
        $form  = Input::form();
        Output::$AJAX['#' . $form['container_id'] . ' > div:eq(0)'] = [
            'data' => (string) V('course:course_session/admin/relate_view', ['key' => $form['key']]),
            'mode' => 'replace',
        ];
    }

    public function index_add_course_session_click()
    {
        $form = Input::form();

        JS::dialog(
            V('course:course_session/admin/item', [
            'form' => $form
        ]),
            ['title' => I18N::T('course', '添加课程节次信息')]
        );
    }

    public function index_add_course_session_submit()
    {
        $me   = L('ME');
        $form = Form::filter(Input::form());

        if (!$me->is_allowed_to('添加', "course_session")) {
            URI::redirect('error/401');
        }

        if ($form['submit']) {

            $form
                ->validate('session', 'not_empty', I18N::T('course', '请填写节次信息!'))
                ->validate('dtstart', 'not_empty', I18N::T('course', '请输入节次开始时间!'))
                ->validate('dtend', 'not_empty', I18N::T('course', '请输入节次结束时间!'))
                ;

            $course_session = O('course_session', ['term' =>  $from['type'], 'session' => $from['session']]);

            if ($course_session->id) {
                $form->set_error('name', I18N::T('credit', '课程节次重复, 请重新填写'));
            }

            if ($form->no_error) {

                $course_session->term = $form['type'];
                $course_session->session = $form['session'];
                $course_session->dtstart = $form['dtstart'];
                $course_session->dtend   = $form['dtend'];

                if ($course_session->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '添加课程节次成功!'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '添加课程节次失败!'));
                }
                JS::refresh();
            } else {
                JS::dialog(
                    V('course:course_session/admin/item', [
                    'form' => $form,
                ]),
                    ['title' => I18N::T('course', '添加课程节次信息')]
                );
            }
        }
    }

    public function index_edit_course_session_submit()
    {
        $me   = L('ME');
        $form = [];
        $data = [];
    
        if (!$me->is_allowed_to('修改', "course_session")) {
            URI::redirect('error/401');
        }

        //form是通过jquery serialize而来, 所以需要进行如下处理
        foreach (explode('&', urldecode(Input::form('form'))) as $form_item) {
            list($key, $value) = explode('=', $form_item);
            $form[$key]        = $value;
    
            $arr = explode('_', $key);
            if (!$arr[1]) {
                continue;
            }
    
            if ($arr[0] == 'session') {
                $data[$arr[1]]['session'] = $value;
            }
    
            if ($arr[0] == 'dtstart') {
                $data[$arr[1]]['dtstart'] = $value;
            }

            if ($arr[0] == 'dtend') {
                $data[$arr[1]]['dtend'] = $value;
            }
        }
    
        foreach ($data as $k => $v) {
            $course_session = O('course_session', $k);
            if (!$course_session->id) {
                continue;
            }
            $course_session->session   = $v['session'] ?: $course_session->session;
            $course_session->dtstart   = $v['dtstart'] ?: $course_session->dtstart;
            $course_session->dtend     = $v['dtend'] ?: $course_session->dtend;
            $course_session->save();
        }

        Output::$AJAX['#' . $form['message_uniqid']] = [
            'data' => (string) V('course:course_session/admin/message'),
            'mode' => 'append',
        ];
    }

    public function  index_delete_course_session_click()
    {
        $me = L('ME');

        $form = Input::form();
        $course_session = O('course_session', $form['id']);

        if (!$me->is_allowed_to('删除', $course_session)) {
            URI::redirect('error/401');
        }

        if(JS::confirm(T('您确定要删除吗?删除后不可恢复!'))){
            if (false) {
                // Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '该学期已关联数据，不可删除!'));
                // JS::refresh();
            } elseif ($course_session->delete()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '删除节次信息成功!'));
                Log::add(strtr('[course] %user_name[%user_id]删除了节次信息[%course_session_id]', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%course_session_id' => $course_session->id
                ]), 'journal');
            }
            JS::refresh();
        }
    }

}
