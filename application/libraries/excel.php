<?php

class Excel {

    private $_file_name;

    private $_suffix;

    private $_excel;

    private $_sheet;

    private $_highest_row;

    public $header_row = 1;

    public function __construct($file_name, $suffix = 'xlsx') {
        $this->set_file_name($file_name, $suffix);
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);
        if (!file_exists(Config::get('system.excel_path'))) {
            File::check_path(Config::get('system.excel_path').'foobar');
        }
        $excel = new PHPExcel();
        $excel->setActiveSheetIndex(0);
        $this->_excel = $excel;
        $this->_sheet = $excel->getActiveSheet();
        $this->_highest_row = 1;
    }

    public function set_row($number) {
        $this->_highest_row = $number;
    }

    private function set_file_name($file_name, $suffix) {
        $this->_suffix = $suffix;
        $this->_file_name = Config::get('system.excel_path') . $file_name . '.' . $suffix;
    }

    public function write($data, $height = FALSE, $auto_wrap_column = FALSE) {
        if ($height) {
            $this->_sheet->getDefaultRowDimension($this->_highest_row)->setRowHeight($height);
        }
        if ($auto_wrap_column) {
            $this->_sheet->getStyle(self::get_alpha_num($auto_wrap_column) . $this->_highest_row)->getAlignment()->setWrapText(TRUE);
        }
        foreach ($data as $index => $cell) {
            // self::set_style($index, $highest_row, $sheet);
            $this->_sheet->setCellValue(self::get_alpha_num($index) . $this->_highest_row, $cell);
        }
        $this->_highest_row += 1;
    }

    // private static function set_style($index, $row, $sheet, $header_row) {
    //     $style = [
    //         'font' => [
    //             'bold' => TRUE
    //         ],
    //         'borders' => [
    //             'top' => [
    //                 'style' => PHPExcel_Style_Border::BORDER_THIN,
    //                 'color' => ['argb' => 'AAAAAA']
    //             ],
    //             'right' => [
    //                 'style' => PHPExcel_Style_Border::BORDER_THIN,
    //                 'color' => ['argb' => 'AAAAAA']
    //             ],
    //             'left' => [
    //                 'style' => PHPExcel_Style_Border::BORDER_THIN,
    //                 'color' => ['argb' => 'AAAAAA']
    //             ],
    //             'bottom' => [
    //                 'style' => PHPExcel_Style_Border::BORDER_THIN,
    //                 'color' => ['argb' => '404040']
    //             ]
    //         ]
    //     ];

    //     $this->_sheet->getStyle(self::get_alpha_num($index) . $header_row)->applyFromArray($style);
    //     $this->_sheet->getStyle(self::get_alpha_num($index) . $header_row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    //     $this->_sheet->getStyle(self::get_alpha_num($index) . $header_row)->getFill()->getStartColor()->setARGB('BDC0BF');
    //     $this->_sheet->getStyle(self::get_alpha_num($index) . $row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
    // }

    public function addAndSetSheet($cnt) {
        $this->_excel->createSheet();
        $this->_excel->setactivesheetindex($cnt);
        $this->_sheet = $this->_excel->getActiveSheet();
        $this->_highest_row = 1;
    }

    public function setCurrentSheetTitle($name) {
        $this->_sheet->setTitle($name);
    }

    public function save() {
        $writer = PHPExcel_IOFactory::createWriter($this->_excel, $this->_suffix == 'xlsx' ? 'Excel2007' : 'Excel5');
        $writer->save($this->_file_name);
    }

    public static function get_alpha_num($num) {
        $exchange_num = ord('A') + intval($num);
        if ($exchange_num < 91) {
            $char = chr($exchange_num);
        } else if ($exchange_num >= 91 && $exchange_num < 117) {
            $char = 'A'.chr(ord('A') + intval($num - 26));
        } else {
            $char = 'B'.chr(ord('A') + intval($num - 52));
        }
        return $char;
    }
}
