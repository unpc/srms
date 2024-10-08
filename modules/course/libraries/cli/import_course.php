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
            5 => '开始时间',
            6 => '结束时间',
            7 => 'week_day',
            8 => 'week',
            9 => '教室编号',
            10 => '教室名称',
            12 => '教学楼名称',
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
                $course->week_day = H($item['week_day']);
                $course->week = H($item['week']);
                $course->ctime = Date::time();
                if ($course->save()) {

                }
            }
        }

    }
}
