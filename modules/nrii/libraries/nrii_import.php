<?php
class Nrii_Import {

    function getFile($name){
        return $name !== null ? MODULE_PATH . 'nrii/' . PRIVATE_BASE . $name : null;
    }

    //仪器编号\所在单位仪器编号\所属单位\装置名称\英文名称\装置网站的网址\产地国别\建账日期\装置类别\主要仪器设备及技术指标\主要功能\主要学科领域\国内主要单位用户\服务典型成果\图片\运行状态\国外主要单位用户\参考收费标准\预约服务网址\安放地址-省\安放地址-市\安放地址-区县\安放地址-街道\共享模式\联系人\电话\电子邮箱\通讯地址\邮政编码
    function device($fullpath){
        if (!($fullpath && file_exists($fullpath))) {
            return;
        }

        //导入统计
        $eq_total = 0;
        $eq_new = 0;
        $eq_failed = 0;
        $eq_ok = [];
        $failed_eqs = [];

        //excel扩展
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);

        $PHPReader = new \PHPExcel_Reader_Excel2007;

        if(!$PHPReader->canRead($fullpath)){
           $PHPReader = new \PHPExcel_Reader_Excel5;
           if(!$PHPReader->canRead($fullpath)){
              echo "file error\n";
              return;
           }
        }

        $PHPExcel = $PHPReader->load($fullpath);
        $currentSheet = $PHPExcel->getSheet(0);

        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $ColumnToKey = [
            // 0 => '',
            1 => 'inner_id',
            2 => 'cname',
            3 => 'ename',
            4 => 'ename_short',
            5 => 'url',
            6 => 'worth',
            7 => 'begin_date',
            8 => 'technical',
            9 => 'function',
            10 => 'realmStr',
            11 => 'service_content',
            12 => 'requirement',
            13 => 'province',
            // 14 => 'city',
            // 15 => 'area',
            16 => 'street',
            17 => 'competent_dep',
            18 => 'sup_insname',
            19 => 'approval_dep',
            20 => 'video',
            21 => 'construction',
            22 => 'device_category',
            23 => 'service_content',
            24 => 'requirement',
            25 => 'achievement',
            26 => 'contact',
            27 => 'phone',
            28 => 'email',
            29 => 'fill_insname',
            30 => 'fill_position',
            31 => 'sci_contact',
            32 => 'sci_phone',
            33 => 'sci_email',
            34 => 'sci_insname',
            35 => 'sci_position',
            36 => 'run_contact',
            37 => 'run_phone',
            38 => 'run_email',
            39 => 'run_insname',
            40 => 'run_position',
            41 => 'layout_image',
            42 => 'key_image',
            43 => 'experiment_image',
            44 => 'organization_file',
            45 => 'open_file',
            46 => 'apply_file',
        ];

        for ($currentRow = 3; $currentRow <= $allRow;$currentRow++) {
            $eq_total++;
            foreach ($ColumnToKey as $k => $key) {
                $data[$key] = $currentSheet->getCellByColumnAndRow($k, $currentRow)->getValue() ?: ''; // 不能为 null，这里默认值设为空字符串吧
            }
            $data['begin_date'] = strtotime($data['begin_date']);
            $realm = [];
            $data['realmStr'] = str_replace('，', ',', $data['realmStr']);
            $data['realmStr'] = explode(',', $data['realmStr']);
            foreach ($data['realmStr'] as $value) {
                $key = array_search($value, Config::get('subject'));
                if ($key){
                    $realm[$key] = $value;
                }
            }
            if (count($realm) > 4 || count($realm) == 0){
                $eq_failed++;
                $name = $data['realmStr'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的主要学科领域不存在",
                    ];
                continue;
            }
            $data['realm'] = json_encode($realm, true);

            $data['province'] = array_search($data['province'], Config::get('address.n0'));
            if ($data['province']) {
                $city = $currentSheet->getCellByColumnAndRow(14 , $currentRow)->getValue();
                $data['city'] = array_search($city, Config::get('address.' . $data['province']));
            }
            if ($data['city']) {
                $area = $currentSheet->getCellByColumnAndRow(15, $currentRow)->getValue();
                $data['area'] = array_search($area, Config::get('address.' . $data['city']));
                $address = O('address', ['level' => 'area', 'name' => $area]);
                if ($address->id) {
                    $data['address'] = $address->adcode;
                }
            }

            if (!$data['address']){
                $eq_failed++;
                $name = $data['address'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的大型仪器设施地址填写错误",
                    ];
                continue;
            }

            if ($data['device_category'] && ($data['device_category'] !=1 || $data['device_category'] !=2 || $data['device_category'] !=3)) {
                $name = $data['device_category'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 设施类别",
                    ];
                continue;
            }

            if ($data['construction'] && ($data['construction'] !=1 || $data['construction'] !=2)) {
                $name = $data['construction'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的建设情况填写错误",
                    ];
                continue;
            }

            $device = O('nrii_device', ['inner_id' => $data['inner_id']]);
            if (!$device->id) $device = O('nrii_device');

            $device->cname = mb_substr($data['cname'], 0, 50, 'utf-8');
            $device->ename = mb_substr($data['ename'], 0, 100, 'utf-8');
            $device->ename_short = mb_substr($data['ename_short'], 0, 100, 'utf-8');
            $device->inner_id = $data['inner_id'];
            $device->worth = (double)round($data['worth'], 2);
            $device->begin_date = $data['begin_date'];
            $device->address = $address->adcode;
            $device->street = mb_substr($data['street'], 0, 100, 'utf-8');
            $device->realm = $data['realm'];
            $device->url = mb_substr($data['url'], 0, 100, 'utf-8');
            $device->technical = mb_substr($data['technical'], 0, 500, 'utf-8');
            $device->function = mb_substr($data['function'], 0, 300, 'utf-8');
            $device->requirement = mb_substr($data['requirement'], 0, 500, 'utf-8');
            $device->service_content = mb_substr($data['service_content'], 0, 200, 'utf-8');

            $device->contact = mb_substr($data['contact'], 0, 20, 'utf-8');
            $device->phone = mb_substr($data['phone'], 0, 20, 'utf-8');
            $device->email = mb_substr($data['email'], 0, 50, 'utf-8');

            $device->competent_dep = $data['competent_dep'];
            $device->sup_insname = $data['sup_insname'];
            $device->device_category = $data['device_category'];
            $device->construction = $data['construction'];
            $device->approval_dep = $data['approval_dep'];
            $device->video = mb_substr($data['video'], 0, 100, 'utf-8');
            $device->sci_contact = $data['sci_contact'];
            $device->sci_position = $data['sci_position'];
            $device->sci_insname = $data['sci_insname'];
            $device->sci_phone = $data['sci_phone'];
            $device->sci_email = $data['sci_email'];
            $device->run_contact = $data['run_contact'];
            $device->run_position = $data['run_position'];
            $device->run_insname = $data['run_insname'];
            $device->run_phone = $data['run_phone'];
            $device->run_email = $data['run_email'];
            $device->fill_position = $data['fill_position'];
            $device->fill_insname = $data['fill_insname'];
            $device->achievement = mb_substr($data['achievement'], 0, 2500, 'utf-8');

            $device->layout_image = $data['layout_image'];
            $device->key_image = $data['key_image'];
            $device->experiment_image = $data['experiment_image'];
            $device->organization_file = $data['organization_file'];
            $device->open_file = $data['open_file'];
            $device->apply_file = $data['apply_file'];

            if ($device->save()) {
                $eq_new++;
                $eq_ok[] = $data['cname'];
            }
            else {
                $eq_failed++;
                $failed_eqs[] = ['name' => $data['cname'], 'reason' => '数据库保存失败，请稍后再试'];
            }
        }
        return [$eq_total, $eq_new, $eq_failed, $eq_ok, $failed_eqs];
    }

    //仪器编号（不可修改、新增仪器请空缺不填）\所在单位仪器编号\所属单位\仪器中心名称（全称，不可简写）\仪器中心级别\仪器中心网址\成立日期（例：2016-01-01）\科学仪器中心类别（通用/专用）\主要学科领域（如有多个，用逗号分隔，最多填写4个）\服务内容（最多200字）\服务的典型成果（最多500字）\图片（仪器图片的文件名称，jpg格式、1M以内大小）\对外开放共享规定（最多500字）\参考收费标准\预约服务网址\共享模式\联系人\电话\电子邮箱\通讯地址-省份\通讯地址-城市\通讯地址-区县\通讯地址-街道\邮政编码\审核状态\驳回原因
    function center($fullpath){
        if (!($fullpath && file_exists($fullpath))) {
            return;
        }

        //导入统计
        $eq_total = 0;
        $eq_new = 0;
        $eq_failed = 0;
        $eq_ok = [];
        $failed_eqs = [];

        //excel扩展
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);

        $PHPReader = new \PHPExcel_Reader_Excel2007;

        if(!$PHPReader->canRead($fullpath)){
           $PHPReader = new \PHPExcel_Reader_Excel5;
           if(!$PHPReader->canRead($fullpath)){
              echo "file error\n";
              return;
           }
        }

        $PHPExcel = $PHPReader->load($fullpath);
        $currentSheet = $PHPExcel->getSheet(0);

        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $ColumnToKey = [
            // 0 => '',
            1 => 'inner_id',
            2 => 'centname',
            3 => 'instru_num',
            4 => 'worth',
            5 => 'equrl',
            6 => 'begin_date',
            7 => 'realmStr',
            8 => 'accept',
            9 => 'research_area',
            10 => 'service_content',
            11 => 'province',
            // 12 => 'city',
            // 13 => 'area',
            // 14 => 'image',
            15 => 'contact',
            16 => 'phone',
            17 => 'email',
            18 => 'contact_address',
            19 => 'zip_code'
        ];

        for ($currentRow = 3; $currentRow <= $allRow;$currentRow++) {

            $eq_total++;
            foreach ($ColumnToKey as $k => $key) {
                $data[$key] = $currentSheet->getCellByColumnAndRow($k, $currentRow)->getValue() ?: '';
            }

            $data['begin_date'] = strtotime($data['begin_date']);
            $realm = [];
            $data['realmStr'] = str_replace('，', ',', $data['realmStr']);
            $data['realmStr'] = explode(',', $data['realmStr']);
            foreach ($data['realmStr'] as $value) {
                $key = array_search($value, Config::get('subject'));
                if ($key){
                    $realm[$key] = $value;
                }
            }
            if (count($realm) > 4 || count($realm) == 0){
                $eq_failed++;
                $name = $data['centname'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的主要学科领域不存在",
                    ];
                continue;
            }
            $data['realm'] = json_encode($realm);


            $data['province'] = array_search($data['province'], Config::get('address.n0'));
            if ($data['province']) {
                $city = $currentSheet->getCellByColumnAndRow(12 , $currentRow)->getValue();
                $data['city'] = array_search($city, Config::get('address.' . $data['province']));
            }
            if ($data['city']) {
                $area = $currentSheet->getCellByColumnAndRow(13, $currentRow)->getValue();
                $data['area'] = array_search($area, Config::get('address.' . $data['city']));
                $address = O('address', ['level' => 'area', 'name' => $area]);
                if ($address->id) {
                    $data['address'] = $address->adcode;
                }
            }

            if (!$data['address']){
                $eq_failed++;
                $name = $data['centname'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的科学中心地址填写错误",
                    ];
                continue;
            }

            $data['accept'] = array_search($data['accept'], Nrii_Center_Model::$accept_status);

            $center = O('nrii_center', ['inner_id' => $data['inner_id']]);
            if (!$center->id) $center = O('nrii_center');
            $center->centname = mb_substr($data['centname'], 0, 50, 'utf-8');
            $center->worth = (double)round($data['worth'], 2);
            $center->inner_id = $data['inner_id'];
            $center->begin_date = $data['begin_date'];
            $center->realm = $data['realm'];
            $center->instru_num = (int)$data['instru_num'];
            $center->accept = (int)$data['accept'];
            $center->research_area = (double)round($data['research_area'], 2);

            $center->service_content = mb_substr($data['service_content'], 0, 200, 'utf-8');
            $center->equrl = mb_substr($data['equrl'], 0, 100, 'utf-8');
            $center->address = $data['address'];

            $center->contact = mb_substr($data['contact'], 0, 20, 'utf-8');
            $center->phone = mb_substr($data['phone'], 0, 20, 'utf-8');
            $center->email = mb_substr($data['email'], 0, 50, 'utf-8');
            $center->contact_address = mb_substr($data['contact_address'], 0, 100, 'utf-8');
            $center->zip_code = $data['zip_code'];

            if ($center->save()) {
                $eq_new++;
                $eq_ok[] = $data['centname'];
            }
            else {
                $eq_failed++;
                $failed_eqs[] = ['name' => $data['centname'], 'reason' => '数据库保存失败，请稍后再试'];
            }
        }

        return [$eq_total, $eq_new, $eq_failed, $eq_ok, $failed_eqs];
    }
    /*
    *   Unpc (cheng.liu@geneegroup.com) 2019.2.21
    *   貌似目前仪器单元这块功能从国家科技部已经剔除掉了，故此功能暂时遗弃
    */
    //仪器编号（不可修改、新增仪器请空缺不填）\所在单位仪器编号\所属单位\服务单元名称\成立日期（例：2016-01-01）\服务单元类别（通用/专用）\主要功能（最多300字）\主要学科领域（如有多个，用逗号分隔，最多填写4个）\服务内容（最多200字）\服务的典型成果（简介）\图片（仪器图片的文件名称，jpg格式、1M以内大小）\运行状态\对外开放共享规定（最多500字）\参考收费标准（最多500字）\预约服务网址\安放地址-省\安放地址-市\安放地址-区县\安放地址-街道（乡镇）\共享模式\联系人\电话\电子邮箱\通讯地址\邮政编码\审核状态\审核原因
    function unit($fullpath){
        if (!($fullpath && file_exists($fullpath))) {
            return;
        }

        //导入统计
        $eq_total = 0;
        $eq_new = 0;
        $eq_failed = 0;
        $eq_ok = [];
        $failed_eqs = [];

        //excel扩展
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);

        $PHPReader = new \PHPExcel_Reader_Excel2007;

        if(!$PHPReader->canRead($fullpath)){
           $PHPReader = new \PHPExcel_Reader_Excel5;
           if(!$PHPReader->canRead($fullpath)){
              echo "file error\n";
              return;
           }
        }

        $PHPExcel = $PHPReader->load($fullpath);
        $currentSheet = $PHPExcel->getSheet(0);

        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $ColumnToKey = [
            // 0 => '',
            1 => 'inner_id',
            2 => 'org',
            3 => 'unitname',
            4 => 'begin_date',
            5 => 'type',
            6 => 'function',
            7 => 'realmStr',
            8 => 'service_content',
            9 => 'achievement',
            // 10 => '',
            11 => 'status',
            12 => 'requirement',
            13 => 'fee',
            14 => 'service_url',
            15 => 'province',
            // 16 => 'city',
            // 17 => 'area',
            18 => 'street',
            19 => 'share_mode',
            20 => 'contact',
            21 => 'phone',
            22 => 'email',
            23 => 'contact_street',
            24 => 'zip_code',
        ];

        for ($currentRow = 2; $currentRow <= $allRow;$currentRow++) {

            $eq_total++;
            foreach ($ColumnToKey as $k => $key) {
                $data[$key] = $currentSheet->getCellByColumnAndRow($k, $currentRow)->getValue() ?: '';
            }

            $data['begin_date'] = strtotime($data['begin_date']);
            $data['type'] = array_search($data['type'], Nrii_Unit_Model::$type_status);
            $realm = [];
            $data['realmStr'] = explode('，',$data['realmStr']);
            foreach ($data['realmStr'] as $value) {
                $key = array_search($value, Config::get('subject'));
                if ($key){
                    $realm[$key] = $value;
                }
            }
            if (count($realm) > 4 || count($realm) == 0){
                $eq_failed++;
                $name = $data['unitname'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的主要学科领域不存在",
                    ];
                continue;
            }
            $data['realm'] = json_encode($realm);
            $data['status'] = array_search($data['status'], Nrii_Unit_Model::$status_unit);
            $data['share_mode'] = array_search($data['share_mode'], Nrii_Unit_Model::$share_status);

            $addressName = $currentSheet->getCellByColumnAndRow(17, $currentRow)->getValue();
            $address = O('address', ['name' => $addressName]);

            if (!$address->id){
                $eq_failed++;
                $name = $data['unitname'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的仪器地址填写有误",
                    ];
                continue;
            }

            $unit = O('nrii_unit', ['inner_id' => $data['inner_id']]);
            if (!$unit->id) $unit = O('nrii_unit');
            $unit->unitname = mb_substr($data['unitname'], 0, 50, 'utf-8');
            $unit->org = $data['org'];
            $unit->category = $data['type'];
            $unit->inner_id = $data['inner_id'];
            $unit->status = $data['status'];
            $unit->begin_date = $data['begin_date'];
            $unit->share_mode = $data['share_mode'];
            $unit->realm = $data['realm'];
            $unit->service_url = $data['service_url'];
            $unit->address = $address->adcode;
            $unit->street = mb_substr($data['street'], 0, 100, 'utf-8');
            $unit->function = mb_substr($data['function'], 0, 300, 'utf-8');
            $unit->requirement = mb_substr($data['requirement'], 0, 500, 'utf-8');
            $unit->service_content = mb_substr($data['service_content'], 0, 200, 'utf-8');
            $unit->achievement = mb_substr($data['achievement'], 0, 500, 'utf-8');
            $unit->fee = mb_substr($data['fee'], 0, 500, 'utf-8');

            $unit->contact = mb_substr($data['contact'], 0, 20, 'utf-8');
            $unit->phone = mb_substr($data['phone'], 0, 20, 'utf-8');
            $unit->email = mb_substr($data['email'], 0, 50, 'utf-8');
            $unit->contact_street = mb_substr($data['contact_street'], 0, 100, 'utf-8');
            $unit->zip_code = $data['zip_code'];

            if ($unit->save()) {
                $eq_new++;
                $eq_ok[] = $data['unitname'];
            }
            else {
                $eq_failed++;
                $failed_eqs[] = ['name' => $data['unitname'], 'reason' => '数据库保存失败，请稍后再试'];
            }
        }

        return [$eq_total, $eq_new, $eq_failed, $eq_ok, $failed_eqs];
    }

    //仪器编号（不可修改、新增仪器请空缺不填）\所在单位仪器编号\所属单位\仪器设备名称\英文名称\所属仪器类型\所属仪器内部编号\仪器分类编码\设备仪器来源\海关监管情况（是/否）\原值（单位万元、保留小数点后两位）\产地国别\生产制造商\建账日期（例：2016-01-01）\仪器设备类别（通用/专用）\规格型号\科学技术中心（最多500字）\主要功能（最多300字）\主要学科领域（如有多个，用逗号分隔，最多填写4个）\服务内容（最多200字）\服务典型成果（最多500字）\图片（仪器图片的文件名称，jpg格式、1M以内大小）\运行状态\对外开放共享规定（最多500字）\参考收费标准（最多500字）\预约服务网址\安放地址-省\安放地址-市\安放地址-区县\安放地址-街道（乡镇）\共享模式\联系人\电话\电子邮箱\通讯地址\邮政编码\征免税证明编号（12位）\征免税证明序号\进口报关单编号（18位）\合同号\进口口岸\主管海关\进口日期（例：2016\01\01）\申报共享标志（是\否）\收费标准已评议标志（是\否）\HS编码（税号）\后续管理记录（最多200字）
    function equipment($fullpath){
        if (!($fullpath && file_exists($fullpath))) {
            return;
        }

        //导入统计
        $eq_total = 0;
        $eq_new = 0;
        $eq_failed = 0;
        $eq_ok = [];
        $failed_eqs = [];

        //excel扩展
        $autoload = ROOT_PATH.'vendor/autoload.php';
        if(file_exists($autoload)) require_once($autoload);

        $PHPReader = new \PHPExcel_Reader_Excel2007;

        if(!$PHPReader->canRead($fullpath)){
           $PHPReader = new \PHPExcel_Reader_Excel5;
           if(!$PHPReader->canRead($fullpath)){
              echo "file error\n";
              return;
           }
        }

        $PHPExcel = $PHPReader->load($fullpath);
        $currentSheet = $PHPExcel->getSheet(0);

        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();

        $ColumnToKey = [
            0 => 'ref_no',
            1 => 'inner_id',
            2 => 'eq_name',
            3 => 'ename',
            4 => 'affiliate',
            5 => 'affiliate_name',
            6 => 'inside_depart',
            7 => 'class',
            8 => 'eq_source',
            9 => 'customs',
            10 => 'worth',
            11 => 'nation',
            12 => 'manufacturer',
            13 => 'begin_date',
            14 => 'type_status',
            15 => 'model_no',
            16 => 'technical',
            17 => 'function',
            18 => 'realmStr',
            19 => 'fundsStr',
            20 => 'service_content',
            // 21 => 'image',
            22 => 'run_machine',
            23 => 'requirement',
            24 => 'fee',
            // 25 => 'service_url',
            26 => 'province',
            // 27 => 'city',
            // 28 => 'area',
            29 => 'street',
            30 => 'service_machine',
            31 => 'contact',
            32 => 'phone',
            33 => 'email',
            34 => 'contact_street',
            35 => 'zip_code',
            36 => 'cus_declaration_number',
            37 => 'cus_item_number',
            38 => 'cus_import_date',
            39 => 'cus_form_name'
        ];

        for ($currentRow = 3; $currentRow <= $allRow;$currentRow++) {

            $eq_total++;
            foreach ($ColumnToKey as $k => $key) {
                $data[$key] = $currentSheet->getCellByColumnAndRow($k, $currentRow)->getValue() ?: '';
            }
            $eqOrigin = O("equipment" ,['ref_no' => $data['ref_no']]);
            if (!$eqOrigin->id) {
                $eq_failed++;
                $name = $data['eq_name'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => "未找到 $name 的关联仪器（仪器编号{$data['ref_no']}）",
                ];
                continue;
            }
            $data['affiliate'] = array_search($data['affiliate'], Nrii_Equipment_Model::$affiliate_type);
            $data['class'] = str_pad($data['class'], 6, '0', STR_PAD_LEFT);
            $data['class'] = trim($data['class']);
            // 找不到相应仪器分类则设置为其他
            $class_md = substr($data['class'], 0, 4) . '00';
            $class_lg = substr($data['class'], 0, 2) . '0000';
            if (!array_key_exists($data['class'], Config::get('class.'.$class_md, [])) && !array_key_exists($data['class'], Config::get('class.' . $class_lg, []))) {
                // $data['class'] = '999999';
                $eq_failed++;
                $name = $data['eq_name'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的设备分类填写错误",
                ];
                continue;
            }
            $data['eq_source'] = array_search($data['eq_source'], Nrii_Equipment_Model::$eq_source);
            if (!$data['eq_source']) {
                $eq_failed++;
                $name = $data['eq_name'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的设备来源填写错误",
                ];
                continue;
            }
            $data['customs'] = array_search($data['customs'], [0 => '否', 1 => '是']);

            $data['nation'] = trim($data['nation']);
            if (!in_array($data['nation'], (array)Config::get('nation'))) {
                $eq_failed++;
                $name = $data['eq_name'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的产地填写错误",
                ];
                continue;
            }

            $data['begin_date'] = strtotime($data['begin_date']);
            $data['type_status'] = array_search($data['type_status'], Nrii_Equipment_Model::$type_status);
            $data['type'] = array_search($data['type'], Nrii_Equipment_Model::$type_status);
            $realm = [];
            $data['realmStr'] = str_replace('，', ',', $data['realmStr']);
            $data['realmStr'] = explode(',',$data['realmStr']);
            foreach ($data['realmStr'] as $value) {
                $key = array_search($value, Config::get('subject'));
                if ($key){
                    $realm[$key] = $value;
                }
            }
            if (count($realm) > 4 || count($realm) == 0){
                $eq_failed++;
                $name = $data['eq_name'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的主要学科领域填写错误",
                ];
                continue;
            }
            $data['realm'] = json_encode($realm);

            $funds = [];
            $data['fundsStr'] = str_replace('，', ',', $data['fundsStr']);
            $data['fundsStr'] = explode(',',$data['fundsStr']);
            foreach ($data['fundsStr'] as $value) {
                $key = array_search($value, Nrii_Equipment_Model::$funds);
                if ($key){
                    $funds[$key] = $value;
                }
            }
            $data['funds'] = json_encode($funds);

            $data['province'] = array_search($data['province'], Config::get('address.n0'));
            if ($data['province']) {
                $city = $currentSheet->getCellByColumnAndRow(27 , $currentRow)->getValue();
                $data['city'] = array_search($city, Config::get('address.' . $data['province']));
            }
            if ($data['city']) {
                $area = $currentSheet->getCellByColumnAndRow(28, $currentRow)->getValue();
                $data['area'] = array_search($area, Config::get('address.' . $data['city']));
                // 地址存在同名
                $address_city = O('address', ['level' => 'city', 'name' => $city]);
                if ($address_city->adcode) {
                    $address = Q("address[level=area][name={$area}][adcode^=".substr($address_city->adcode, 0, 4).']')->current();
                }
                if (!$address->id) {
                    $address = O('address', ['level' => 'area', 'name' => $area]);
                }
                if ($address->id) {
                    $data['address'] = $address->adcode;
                }
            }

            if (!$data['address']){
                $eq_failed++;
                $name = $data['eq_name'];
                $failed_eqs[] = [
                    'name' => $name,
                    'reason' => " $name 的仪器地址填写错误",
                    ];
                continue;
            }

            if ($data['customs'] == 1) {
                $customs = O('nrii_customs', ['inner_id' => $data['inner_id']]);
                if (!$customs->id) $customs = O('nrii_customs');
                $customs->inner_id = $data['inner_id'];
                $customs->ins_code = Config::get("nrii")[LAB_ID];
                $customs->declaration_number = $data['cus_declaration_number'];
                $customs->import_date = $data['cus_import_date'];
                $customs->item_number = mb_substr($data['cus_item_number'], 0, 2, 'utf-8');
                $customs->form_name = mb_substr($data['cus_form_name'], 0, 30, 'utf-8');
                $customs->save();
            }
            else {
                $equipment = O('nrii_equipment', ['inner_id' => $data['inner_id']]);
                if ($equipment->id) {
                    if ($equipment->customs->id) $equipment->customs->delete();
                    else {
                        $customs = O ('nrii_customs');
                        $equipment->customs = $customs;
                        $equipment->save();
                    }
                }
            }

            $equipment = O('nrii_equipment', ['inner_id' => $data['inner_id']]);
            if (!$equipment->id) $equipment = O('nrii_equipment');

            $serviceUrl = 'http://17kong.com/?oauth-sso=nrii&site=' . LAB_ID . '&id=' . $eqOrigin->id;

            $equipment->eq_id = $eqOrigin->id;
            $equipment->eq_name = mb_substr($data['eq_name'], 0, 100, 'utf-8');
            $equipment->ename = mb_substr($data['ename'], 0, 100, 'utf-8');
            $equipment->inner_id = $data['inner_id'];
            $equipment->affiliate = $data['affiliate'];
            if (in_array((int)$data['affiliate'], Nrii_Equipment_Model::$affiliate_resource_type)) {
                $equipment->resource_name = $data['affiliate_name'] ?: '无';
                $equipment->affiliate_name = '无';
            }
            else {
                $equipment->affiliate_name = $data['affiliate_name'] ?: '无';
                $equipment->resource_name = '无';
            }
            $equipment->class = $data['class'];
            $equipment->address = $data['address'];
            $equipment->street = mb_substr($data['street'], 0, 100, 'utf-8');
            $equipment->worth = sprintf('%.2f', (double)$data['worth']);


            $equipment->eq_source = $data['eq_source'];
            $equipment->type_status = $data['type_status'];
            $equipment->realm = $data['realm'];
            $equipment->nation = $data['nation'];
            $equipment->model_no = mb_substr($data['model_no'], 0, 100, 'utf-8');
            $equipment->manufacturer = mb_substr($data['manufacturer'], 0, 50, 'utf-8');
            $equipment->begin_date = $data['begin_date'];

            $equipment->technical = mb_substr($data['technical'], 0, 500, 'utf-8');
            $equipment->function = mb_substr($data['function'], 0, 300, 'utf-8');
            $equipment->requirement = mb_substr($data['requirement'], 0, 500, 'utf-8');
            $equipment->fee = mb_substr($data['fee'], 0, 500, 'utf-8');
            $equipment->service_content = mb_substr($data['service_content'], 0, 200, 'utf-8');
            $equipment->service_url = $serviceUrl;
            $equipment->customs = $customs;

            $equipment->run_machine = (double)round($data['run_machine'], 2);
            $equipment->service_machine = (double)round($data['service_machine'], 2);
            $equipment->funds = $data['funds'];
            $equipment->inside_depart = mb_substr($data['inside_depart'], 0, 100, 'utf-8');

            $equipment->contact = mb_substr($data['contact'], 0, 20, 'utf-8');
            $equipment->phone = mb_substr($data['phone'], 0, 20, 'utf-8');
            $equipment->email = mb_substr($data['email'], 0, 50, 'utf-8');
            $equipment->contact_address = mb_substr($data['contact_street'], 0, 100, 'utf-8');
            $equipment->zip_code = $data['zip_code'];

            $equipment->yiqikong_id = $eqOrigin->yiqikong_id;

            if ($equipment->save()) {
                $eq_new++;
                $eq_ok[] = $data['eq_name'];
            }
            else {
                $eq_failed++;
                $failed_eqs[] = ['name' => $data['eq_name'], 'reason' => '数据库保存失败，请稍后再试'];
            }
        }

        return [$eq_total, $eq_new, $eq_failed, $eq_ok, $failed_eqs];
    }
}
