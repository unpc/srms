<?php

class Import
{

    static $tmp_file_name;
    static $import_type = 'users';
    static $tmp_file_type;
    static $currentSheet;
    static $real_col_num;
    static $allRow;
    static $fields;
    static $real_fields;

    public static function index($e, $tabs)
    {
        $params            = Config::get('system.controller_params');
        self::$import_type = $params[1];
        self::$real_fields = Config::get('import.' . self::$import_type . '_fields');
        self::$fields      = array_keys(self::$real_fields);
        array_shift(self::$fields);
        self::$tmp_file_name = self::tmp_file_name(self::$import_type);
        $me                  = L('ME');
        $tabs->content       = V('import/index');
        $file_name           = self::tmp_file_name(self::$import_type);

        if (Input::form('submit') == '上传') {
            self::uploadfile();
            Event::bind('import.layout', 'Import::preview', 0, 'preview');
            Event::trigger('import.layout', $tabs->content);
        }

        if (Input::form('submit') == "导入") {
            Event::bind('import.layout', 'Import::preview', 0, 'preview');
            Event::bind('import.layout', 'Import::import_data', 0, 'results');
            Event::trigger('import.layout', $tabs->content);
        }

    }

    public static function preview($e, $tabs)
    {

        if (!file_exists(self::$tmp_file_name)) {
            URI::redirect('');
        }

        $preview = self::excel_preview();

        $tabs->preview = V('import/preview', array_merge($preview, [
            'form'        => Input::form(),
            'import_type' => self::$import_type,
        ]));

    }
    public static function import_data($e, $tabs)
    {
        Event::bind('import.add_users', 'User_Import::add_user', 0, 'import_data');
        Event::bind('import.add_equipments', 'Equipment_Import::add_equipment', 0, 'import_data');
        Event::bind('import.add_labs', 'Lab_Import::add_lab', 0, 'import_data');
        Event::bind('import.add_cardnos', 'User_Import::add_cardno', 0, 'import_data');

        $currentSheet   = self::$currentSheet;
        $form           = Input::form();
        $import_columns = $form['import_columns'];
        $row = [];
        $results = [];
        $success_count = 0;
        $warning_count = 0;
        $error_count = 0;
        $error = [];
        $warning = [];

        $start_row = $form['skip_rows'] ? $form['skip_rows_count'] : 2;
        $root = Tag_Model::root('group');
        $weight = Q("tag_group[root=$root]:sort(weight D):limit(1)")->current()->weight + 1;
        // 为导入的组织机构加入默认权重
        
        for ($i = $start_row; $i <= self::$allRow; $i++) {
            for ($j = 0; $j <= self::$real_col_num; $j++) {
                if ($import_columns[$j] == "") {
                    $import_columns[$j] = self::$fields[$j];
                }
                $row[$import_columns[$j]] = $currentSheet->getCellByColumnAndRow($j, $i)->getValue();
            }
            $import_msg  = Event::trigger('import.add_' . self::$import_type, $row, self::$fields, $form);
            $results[$i] = $import_msg;
            if ($import_msg['error']) {
                $error_count++;
                $error[$i] = $import_msg['error'];
            } else {
                $success_count++;
            }
            if ($import_msg['warning']) {
                $warning_count += count($import_msg['warning']);
                $warning[$i] = $import_msg['warning'];
            }
        }
        $tabs->results = V('import/results', [
            'success_count' => $success_count,
            'error_count'   => $error_count,
            'error'         => $error,
            'warning_count' => $warning_count,
            'warning'       => $warning,
            'fields'        => self::$fields,
            'form'          => $form,
        ]
        );
        // 删除上次上传的文件
        if (file_exists(self::$tmp_file_name)) {
            File::delete(self::$tmp_file_name);
        }
    }

    public static function results($e, $tabs)
    {
        $tabs->results = V('import/results');
    }

    //预览excel文件
    public static function excel_preview()
    {
        //excel扩展
        $autoload = ROOT_PATH . 'vendor/autoload.php';
        if (file_exists($autoload)) {require_once $autoload;}

        $PHPReader = new PHPExcel_Reader_Excel2007;
        if (!$PHPReader->canRead(self::$tmp_file_name)) {
            $PHPReader = new PHPExcel_Reader_Excel5;
            if (!$PHPReader->canRead(self::$tmp_file_name)) {
                echo "file error\n";
                return false;
            }
        }

        $PHPExcel     = $PHPReader->load(self::$tmp_file_name);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn    = $currentSheet->getHighestColumn();
        $allRow       = $currentSheet->getHighestRow();
        $column_count = PHPExcel_Cell::columnIndexFromString($allColumn);
        $fields       = Config::get('import.' . self::$import_type . '_fields');
        $fields_count = count($fields);
        $title_count  = 0;
        if (Input::form('has_title')) {
            $title_count = Input::form('title_count');
        }
        $start_row = 1 + $title_count;

        $end_row = min($allRow, $start_row + 5);

        $preview_data = [];
        $real_col_num = 0;

        //根据表头判断最大列数
        for ($j = $start_row; $j <= $end_row; $j++) {
            for ($i = 0; $i <= $column_count; $i++) {
                $tmp = $currentSheet->getCellByColumnAndRow($i, $j)->getValue();
                if ($j == $start_row) {
                    if (is_null($tmp)) {
                        $real_col_num = $i - 1;
                        break;
                    }
                } elseif ($i > $real_col_num) {
                    break;
                }
                $preview_data[$j][$i] = $tmp;
            }
        }
        self::$currentSheet = $currentSheet;
        self::$real_col_num = $real_col_num;
        self::$allRow       = $allRow;
        return [
            'preview_data' => $preview_data,
            'start_row'    => $start_row,
            'real_col_num' => $real_col_num,
            'allRow'       => $allRow,
        ];
    }

    //上传文件
    protected static function uploadfile()
    {

        //删除上次上传的文件
        if (file_exists(self::$tmp_file_name)) {
            File::delete(self::$tmp_file_name);
        }

        $file = Input::file('file');

        //进行文件上传
        if ($file['tmp_name']) {

            self::$tmp_file_type = File::extension($file['name']);

            if (self::$tmp_file_type != 'xls' && self::$tmp_file_type != 'xlsx') {
                Lab::message(LAB::MESSAGE_NORMAL, T('文件类型错误, 请上传Excel文件'));
                self::$tmp_file_name = '';
                return false;
            } else {
                File::check_path(self::$tmp_file_name);
                if (move_uploaded_file($file['tmp_name'], self::$tmp_file_name)) {
                    Lab::message(LAB::MESSAGE_NORMAL, T('文件上传成功'));
                    return true;
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, T('文件上传失败'));
                    self::$tmp_file_name = '';
                    return false;
                }
            }

        } else {
            Lab::message(Lab::MESSAGE_ERROR, T('请选择您要上传的文件。'));
            self::$tmp_file_name = '';
            return false;
        }
    }

    //生成上传的路径
    public static function tmp_file_name($key)
    {
        return Config::get('system.tmp_dir') . L('ME')->id . $key . 'import';
    }

}
