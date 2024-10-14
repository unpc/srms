<?php

class CLI_Import_Course {

    // SITE_ID=room LAB_ID=szx php /var/lib/srms/cli/cli.php Import_Course import 2 '/var/lib/srms/sites/cf/labs/szx/course1.xlsx'
    static function import() {
        $params = func_get_args();
        $file_name = $params[1];
        $school_term = o('school_term', $params[0]);

        $PHPReader = new \PHPExcel_Reader_Excel2007;

        $errors == [];

        if(!$PHPReader->canRead($file_name)){
            $PHPReader = new \PHPExcel_Reader_Excel5;
            if(!$PHPReader->canRead($file_name)){
                $errors[] = "file error";
                return;
            }
        }

        $PHPExcel = $PHPReader->load($file_name);
        $sheet = $PHPExcel->getSheet(0);

        $allRow = $sheet->getHighestRow();

        $ColumnToKey = [
            0 => 'ref_no',
            1 => 'name',
            2 => 'teacher_ref_no',
            3 => 'teacher_name',
            4 => 'course_session',
            5 => 'course_session_dtstart',
            6 => 'course_session_dtend',
            7 => 'week_day',
            8 => 'week',
            9 => 'classroom_ref_no',
            10 => 'classroom_name',
            12 => 'classbuild_name',
        ];

        //必填列
        $require_columns = [
            0 => 'ref_no',
            1 => 'name',
            2 => 'teacher_ref_no',
            3 => 'teacher_name',
            4 => 'course_session',
            7 => 'week_day',
            8 => 'week',
        ];

        $no_error = true;
        $all_data = [];
        for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
            $data = [];
            foreach ($ColumnToKey as $k => $key) {
                $value = H(trim($sheet->getCellByColumnAndRow($k, $currentRow)->getValue()));
                if ($require_columns[$k] && !$value) {
                    $errors[] = "第{$currentRow}行数据 必填项({$require_columns[$k]})未填写";
                    break 2;
                }
                $data[$key] = $value;
            }
            $all_data[] = $data;
        }
        Q("course[school_term=$school_term]")->delete_all();
        if ($no_error) {
            foreach ($all_data as $item) {
                $course = O('course');
                $course->school_term = $school_term;
                $course->name = H($item['name']);
                $course->ref_no = H($item['ref_no']);
                $course->teacher_ref_no = H($item['teacher_ref_no']);
                $course->teacher_name = H($item['teacher_name']);
                $course->course_session = H($item['course_session']);
                $teacher = o('user', ['ref_no' => $item['teacher_ref_no']]);
                if ($teacher->id) $course->teacher = $teacher;
                $course->week_day = H($item['week_day']);
                $course->week = H($item['week']);
                $course->ctime = Date::time();
                $course->classroom_ref_no = H($item['classroom_ref_no']);
                $course->classroom_name = H($item['classroom_name']);
                $course->classbuild_name = H($item['classbuild_name']);
                $classroom = o('meeting', ['ref_no' => $item['classroom_ref_no']]);
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
                    $course_session = $course->school_term->course_session($course->course_session);
                    if (!$course_session->id) {
                        $course_session            = O('course_session');
                        $course_session->term      = $course->school_term->term;
                        $course_session->session   = $item['course_session'];
                        $course_session->dtstart   = strtotime("1970-01-01". $item['course_session_dtstart']);
                        $course_session->dtend     = strtotime("1970-01-01". $item['course_session_dtend']);
                        $course_session->save();
                    }
                } else {
                    echo "error \r\n";
                }
            }
        }

    }
}
