<?php

class Index_Controller extends Base_Controller
{

    public function index()
    {

        if (!L('ME')->is_allowed_to('管理', 'cers')) {
            URI::redirect('error/401');
        }

        $this->layout->body->primary_tabs
            ->select('index')
            ->set('content', V('cers:list', ['form' => Input::form()]));
    }

    public function download($type)
    {

        if (!$type) {exit;return;}

        switch ($type) {
            case 'shareeffect':
                $fields   = (array) Config::get('equipment.share_fields');
                $autoload = ROOT_PATH . 'vendor/autoload.php';
                if (file_exists($autoload)) {
                    require_once $autoload;
                }

                $Excel  = new \PHPExcel;
                $Writer = new \PHPExcel_Writer_Excel5($Excel);
                $Excel->setActiveSheetIndex(0);
                $ActSheet = $Excel->getActiveSheet();
                $ActSheet->setTitle(T('大型科学仪器设备共享效益统计信息'));
                $ActSheet->setCellValue('A1', T('仪器编号'));
                $ActSheet->setCellValue('B1', T('仪器名称'));
                $ActSheet->setCellValue('C1', T('学年度'));

                $n = 3;
                foreach ($fields as $key => $value) {
                    $ActSheet->setCellValue(chr($n + 65) . '1', T($value));
                    $n++;
                }
                $ActSheet->setCellValue(chr($n + 65) . '1', T('备注信息'));

                $n               = 0;
                $cers_share_data = Q('cers_share_data');
                for ($start = 0, $per_page = 10;; $start += $per_page) {
                    $share_data = $cers_share_data->limit($start, $per_page);
                    if ($share_data->count() == 0) {
                        break;
                    }

                    $n = $start + 2;
                    foreach ($share_data as $data) {
                        $j         = 3;
                        $equipment = $data->equipment;
                        $ActSheet->setCellValueExplicit('A' . $n, H($equipment->ref_no), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $ActSheet->setCellValueExplicit('B' . $n, (string) H($equipment->name), \PHPExcel_Cell_DataType::TYPE_STRING);
                        $ActSheet->setCellValue('C' . $n, $data->to_year);
                        foreach ($fields as $key => $value) {
                            $ActSheet->setCellValue(chr($j + 65) . $n, $data->$key);
                            $j++;
                        }
                        $ActSheet->setCellValue(chr($j + 65) . $n, H($data->description));
                        $n++;
                    }
                }

                header("Content-Type: text/xls");
                header('Content-Disposition: attachment; filename="' . T('大型科学仪器设备共享效益统计信息') . '.xls"');
                header("Expires: Wed, 01 Jan 1970 00:00:00 GMT");
                header("Cache-Control: no-cache");

                $fh = fopen('php://output', 'w');
                $Writer->save($fh);
                fclose($fh);
                exit;
                break;
            case 'instrusandgroups':
                $fullpath = Cers::getLabPrivateFile('InstrusAndGroups.xml');
                break;
            case 'platform':
                $fullpath = Cers::getLabPrivateFile('Platform.xml');
                break;
        }

        Downloader::download($fullpath);
        exit;
    }

    public function upload()
    {
        $file = Input::file('Filedata');
        if (!$file['error']) {
            /*
            $fullpath = Cers::getLabPrivateFile($file['name']);
            File::check_path($sharefile);
            move_uploaded_file($file['tmp_name'], $fullpath);
             */
            $fullpath = $file['tmp_name'];

            $autoload = ROOT_PATH . 'vendor/os/php-excel/PHPExcel/PHPExcel.php';
            //$autoload = ROOT_PATH.'vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }

            $PHPReader = new \PHPExcel_Reader_Excel2007;

            if (!$PHPReader->canRead($fullpath)) {
                $PHPReader = new \PHPExcel_Reader_Excel5;
                if (!$PHPReader->canRead($fullpath)) {
                    echo "file error\n";
                    return;
                }
            }

            $PHPExcel     = $PHPReader->load($fullpath);
            $currentSheet = $PHPExcel->getSheet(0);

            $allColumn = $currentSheet->getHighestColumn();
            $allRow    = $currentSheet->getHighestRow();

            $cers = Config::get('cers');

            $ColumnToKey = [
                'D' => 'LSNUSEDHRS',
                'E' => 'RSCHUSEDHRS',
                'F' => 'SERUSEDHRS',
                'G' => 'OPENHRS',
                'H' => 'SAMPLENUM',
                'I' => 'TRNSTUD',
                'J' => 'TRNTEACH',
                'K' => 'TRNOTHERS',
                'L' => 'EDUPROJ',
                'M' => 'RSCHPROJ',
                'N' => 'SOCIALPROJ',
                'O' => 'RWDNATION',
                'P' => 'RWDPROV',
                'Q' => 'RWDTEACH',
                'R' => 'RWDSTUD',
                'S' => 'PAPERINDEX',
                'T' => 'PAPERKERNEL',
                'U' => 'CHARGEMAN',
            ];

            $instrusConfigs = [];

            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $configs = [];
                foreach ($ColumnToKey as $k => $key) {
                    $configs[$key] = $currentSheet->getCellByColumnAndRow(ord($k) - 65, $currentRow)->getValue();
                }
                $ref_no                = $currentSheet->getCellByColumnAndRow(ord('A') - 65, $currentRow)->getValue();
                $configs['SchoolCode'] = $cers['SchoolCode'];
                $configs['InnerID']    = O('equipment', ['ref_no' => $ref_no])->id;
                $configs['YEAR']       = $currentSheet->getCellByColumnAndRow(ord('C') - 65, $currentRow)->getValue();
                $configs['OtherInfo']  = $currentSheet->getCellByColumnAndRow(ord('V') - 65, $currentRow)->getValue() ?: ' ';

                $instrusConfigs[] = $configs;
            }

            $AllData = V('cers:api/shareeffect', ['instrusConfigs' => $instrusConfigs]);

            $sharefile = Cers::getLabPrivateFile('ShareEffect.xml');
            File::check_path($sharefile);
            @file_put_contents($sharefile, $AllData);
        }
        exit(0);
    }

}

class Index_AJAX_Controller extends AJAX_Controller
{

    public function index_refresh_cers_click()
    {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        putenv('SITE_ID=' . SITE_ID);
        putenv('LAB_ID=' . LAB_ID);

        $this->_close_connection_and_go_on('');

        $cmd = 'php ' . ROOT_PATH . 'cli/cli.php cers refresh ' . Input::form('type') . ' >/dev/null 2>&1 &';

        exec($cmd);
    }

    public function _close_connection_and_go_on($output)
    {
        ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true); // optional
        ob_start();
        print $output;
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush(); // Strange behaviour, will not work
        flush(); // Unless both are called !
        ob_end_clean();
        sleep(1);
    }

    public function index_check_status_click()
    {

        $now                          = time();
        $platform_is_complete         = (Lab::get('cers.platform_refresh_last_time') && $now - Lab::get('cers.platform_refresh_last_time') > 60) || !Lab::get('cers.platform_refresh_pid');
        $shareeffect_is_complete      = (Lab::get('cers.shareeffect_refresh_last_time') && $now - Lab::get('cers.shareeffect_refresh_last_time') > 60) || !Lab::get('cers.shareeffect_refresh_pid');
        $instrusandgroups_is_complete = (Lab::get('cers.instrusandgroups_refresh_last_time') && $now - Lab::get('cers.instrusandgroups_refresh_last_time') > 60)
        || !Lab::get('cers.instrusandgroups_refresh_pid');

        Output::$AJAX['complete'] = [
            'platform'         => $platform_is_complete ? 1 : 0,
            'shareeffect'      => $shareeffect_is_complete ? 1 : 0,
            'instrusandgroups' => $instrusandgroups_is_complete ? 1 : 0,
        ];
    }

    public function index_import_share_data_click()
    {
        JS::dialog((string) V('cers:share/upload'));
    }
}
