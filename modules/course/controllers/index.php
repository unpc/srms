<?php

class Index_Controller extends Base_Controller
{
    public function index()
    {
        if (!L('ME')->is_allowed_to('列表', 'course')) {
            URI::url('error/404');
        }
        $form = Lab::form();
        $sort_by   = $form['sort'] ? $form['sort'] : 'ctime';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector = "";
        // 获取当前学年
        
        $selector  .= 'course';

        if ($form['name'] != null) {
            $name = Q::quote($form['name']);
            $selector .= "[name*=$name|name_abbr*=$name]";
        }
        
        switch ($sort_by) {
            case 'name':
                $selector .= ":sort(name $sort_flag)";
                break;
            default:
                $selector .= ":sort(ctime D)";
                break;
        }

        $courses = Q($selector);

        $start      = (int) $form['st'];
        $per_page   = 20;
        $start      = $start - ($start % $per_page);
        $pagination = Lab::pagination($courses, $start, $per_page);
        $this->add_css('preview');
        $this->add_js('preview');

        $this->layout->body->primary_tabs
            ->select($tabs)
            ->content = V('index', [
                'form'       => $form,
                'pagination' => $pagination,
                'st'         => $start,
                'courses'    => $courses,
                'sort_asc'   => $sort_asc,
                'sort_by'    => $sort_by,
            ]);
    }

    public function arrange() 
    {
        $this->layout->body->primary_tabs
        ->add_tab('arrange', [
            'url'   => URI::url('!course/arrange'),
            'title' => I18N::T('course', '课程排程'),
        ]);
        $this->layout->body->primary_tabs->delete_tab('index');

        $this->layout->body->primary_tabs
            ->select('arrange')
            ->content = V('arrange', [
            ]);
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_add_click()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'course')) {
            URI::redirect('error/401');
        }

        $course = O('course');

        JS::dialog(V('add', ['form' => $form, 'course' => $course]), [
            'title' => I18N::T('course', '添加课程'),
        ]);
    }

    public function index_add_submit()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'course')) {
            URI::redirect('error/401');
        }

        $course = O('course');

        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('school_term', 'not_empty', I18N::T('course', '请选择学年学期!'))
                ->validate('school_term', 'compare(>0) ', I18N::T('course', '请选择学年学期!'))
                ->validate('name', 'not_empty', I18N::T('course', '请输入课程名称!'))
                ->validate('ref_no', 'not_empty', I18N::T('course', '请输入课程代码!'))
                ->validate('teacher_ref_no', 'not_empty', I18N::T('course', '请输入教师工号!'))
                ->validate('teacher_name', 'not_empty', I18N::T('course', '请输入教师姓名!'))
                ->validate('course_session', 'not_empty', I18N::T('course', '请输入节次!'))
                ->validate('week_day', 'not_empty', I18N::T('course', '请输入星期几!'))
                ->validate('week', 'not_empty', I18N::T('course', '请输入教学周!'))
                ;

            if ($form->no_error) {
                $course->school_term = o('school_term', $form['school_term']);
                $course->name = H($form['name']);
                $course->ref_no = H($form['ref_no']);
                $course->teacher_ref_no = H($form['teacher_ref_no']);
                $course->teacher_name = H($form['teacher_name']);
                $course->course_session = H($form['course_session']);
                $teacher = o('user', ['ref_no' => $form['teacher_ref_no']]);
                if ($teacher->id) $course->teacher = $teacher;
                $course->week_day = H($form['week_day']);
                $course->week = H($form['week']);
                $course->ctime = Date::time();
                $course->classroom_ref_no = H($form['classroom_ref_no']);
                $course->classroom_name = H($form['classroom_name']);
                $course->classbuild_name = H($form['classbuild_name']);
                $classroom = o('meeting', ['ref_no' => $form['classroom_ref_no']]);
                if ($classroom->id) $course->classroom = $classroom;
                $course->save();

                if ($course->id) {
                    $weeks = explode(',', $course->week);
                    foreach (Q("course_week[course=$course]") as $connect){
                        if (!in_array($connect->week, $week)) 
                            $connect->delete();
                    }
                    $connects = Q("course_week[course=$course]")->to_assoc('week', 'week');
                    foreach ($weeks as $week) {
                        if (!in_array($week, $connects)) {
                            $course_week = O("course_week");
                            $course_week->course = $course;
                            $course_week->week = $week;
                            $course_week->save();
                        }
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '课程添加成功!'));
                    JS::redirect('!course/index');
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '课程添加失败! 请与系统管理员联系。'));
                }

            }
        }

        JS::dialog(V('add', ['form' => $form, 'course' => $course]), [
            'title' => I18N::T('course', '添加课程'),
        ]);

    }


    public function index_edit_click()
    {
        $me = L('ME');

        $form = Input::form();

        $course = O('course', $form['id']);

        if (!$me->is_allowed_to('修改', $course)) {
            URI::redirect('error/401');
        }

        JS::dialog(V('edit', ['form' => $form, 'course' => $course]), [
            'title' => I18N::T('course', '修改课程'),
        ]);
    }

    public function index_edit_submit()
    {
        $me = L('ME');

        $form = Input::form();

        $course = O('course', $form['id']);

        if (!$me->is_allowed_to('修改', $course)) {
            URI::redirect('error/401');
        }

        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('school_term', 'not_empty', I18N::T('course', '请选择学年学期!'))
                ->validate('school_term', 'compare(>0) ', I18N::T('course', '请选择学年学期!'))
                ->validate('name', 'not_empty', I18N::T('course', '请输入课程名称!'))
                ->validate('ref_no', 'not_empty', I18N::T('course', '请输入课程代码!'))
                ->validate('teacher_ref_no', 'not_empty', I18N::T('course', '请输入教师工号!'))
                ->validate('teacher_name', 'not_empty', I18N::T('course', '请输入教师姓名!'))
                ->validate('course_session', 'not_empty', I18N::T('course', '请输入节次!'))
                ->validate('week_day', 'not_empty', I18N::T('course', '请输入星期几!'))
                ->validate('week', 'not_empty', I18N::T('course', '请输入教学周!'))
                ;

            if ($form->no_error) {
                $course->school_term = o('school_term', $form['school_term']);
                $course->name = H($form['name']);
                $course->ref_no = H($form['ref_no']);
                $course->teacher_ref_no = H($form['teacher_ref_no']);
                $course->teacher_name = H($form['teacher_name']);
                $teacher = o('user', ['ref_no' => $form['teacher_ref_no']]);
                if ($teacher->id) $course->teacher = $teacher;
                $course->course_session = H($form['course_session']);
                $course->week_day = H($form['week_day']);
                $course->week = H($form['week']);
                $course->mtime = Date::time();
                $course->classroom_ref_no = H($form['classroom_ref_no']);
                $course->classroom_name = H($form['classroom_name']);
                $course->classbuild_name = H($form['classbuild_name']);
                $classroom = o('meeting', ['ref_no' => $form['classroom_ref_no']]);
                if ($classroom->id) $course->classroom = $classroom;
                if ($course->save()) {
                    $weeks = explode(',', $course->week);
                    foreach (Q("course_week[course=$course]") as $connect){
                        if (!in_array($connect->week, $week)) 
                            $connect->delete();
                    }
                    $connects = Q("course_week[course=$course]")->to_assoc('week', 'week');
                    foreach ($weeks as $week) {
                        if (!in_array($week, $connects)) {
                            $course_week = O("course_week");
                            $course_week->course = $course;
                            $course_week->week = $week;
                            $course_week->save();
                        }
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '课程修改成功!'));
                    JS::redirect('!course/index');
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('course', '课程修改失败! 请与系统管理员联系。'));
                }

            }
        }

        JS::dialog(V('add', ['form' => $form, 'course' => $course]), [
            'title' => I18N::T('course', '修改课程'),
        ]);

    }

    public function index_import_click()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'course')) {
            URI::redirect('error/401');
        }

        $course = O('course');

        JS::dialog(V('import', ['form' => $form, 'course' => $course]), [
            'title' => I18N::T('course', '导入课程'),
        ]);
    }

    public function index_import_submit()
    {

        $form = Form::filter(Input::form());

        $file = Input::file('file');

        if (!$file['tmp_name']) {
            $form->set_error('file', I18N::T('course', '请选择您要上传的课程文件!'));
        } else {
            $ext = File::extension($file['name']);
            if ($ext !== 'xls' && $ext !== 'xlsx') {
                $form->set_error('file', I18N::T('course', '文件类型错误!'));
            }
        }
        if (!$form['school_term']) {
            $form->set_error('school_term', I18N::T('course', '请选择学年学期!'));
        }

        if ($form->no_error) {
            $tmp_file_name = tempnam(Config::get('system.tmp_dir'), 'course_file_');
            move_uploaded_file($file['tmp_name'], $tmp_file_name);
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php Import_Course import ';
            $cmd .= "'".$form['school_term']."' '".$tmp_file_name."' >/dev/null 2>&1 &";
            $process = proc_open($cmd, [], $pipes);
            error_log($cmd);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('course', '课程导入成功!'));
            JS::redirect('!course/index');
        }

        JS::dialog(V('import', ['form' => $form, 'course' => $course]), [
            'title' => I18N::T('course', '导入课程'),
        ]);
    }
}
