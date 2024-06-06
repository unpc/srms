<?php

class Cers
{

    public static function setup_admin()
    {
        if (!L('ME')->is_allowed_to('管理', 'cers')) {
            return;
        }

        Event::bind('admin.index.tab', 'Cers::admin_primary_tab');
    }

    // static function equipment_setup() {
    // Event::bind('equipment.edit.tab', 'Cers::equipment_edit_tab');
    // }

    public static function admin_primary_tab($e, $tabs)
    {

        Event::bind('admin.index.content', 'Cers::admin_primary_content', 0, 'cers');
        $tabs->add_tab('cers', [
            'url'    => URI::url('admin/cers'),
            'title'  => I18N::T('cers', 'CERS信息'),
            'weight' => 80,
        ]);
    }

    public static function admin_primary_content($e, $tabs)
    {

        if (!L('ME')->is_allowed_to('管理', 'cers')) {
            return;
        }

        $tabs->content = V('cers:admin/body');

        Event::bind('admin.cers.content', 'Cers::secondary_info_content', 0, 'info');
        Event::bind('admin.cers.content', 'Cers::secondary_struct_content', 0, 'struct');

        $secondary_tabs = Widget::factory('tabs')
            ->add_tab('info', [
                'url'   => URI::url('admin/cers.info'),
                'title' => I18N::T('cers', '平台共享信息'),
            ])
            ->add_tab('struct', [
                'url'   => URI::url('admin/cers.struct'),
                'title' => I18N::T('cers', '平台机组信息'),
            ])
            ->set('class', 'secondary_tabs')
            ->tab_event('admin.cers.tab')
            ->content_event('admin.cers.content');

        $tabs->content->secondary_tabs = $secondary_tabs;

        $params = Config::get('system.controller_params');
        $tabs->content->secondary_tabs->select($params[1]);
    }

    public static function secondary_info_content($e, $tabs)
    {

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $configs = Cers::getConfig('cers');
            $values  = Input::form();

            if ($form['Introduction'] && self::length($form['Introduction']) > 500) {
                $form->set_error('Introduction', I18N::T('cers', '校级平台简介字数不得超过500!'));
            }

            if ($form['OtherInfo'] && self::length($form['OtherInfo']) > 100) {
                $form->set_error('OtherInfo', I18N::T('cers', '校级平台备注字数不得超过100!'));
            }

            if ($form->no_error) {
                foreach ($configs as $key => $config) {
                    $configs[$key] = isset($values[$key]) ? $values[$key] : $config;
                }
                Lab::set('cers', $configs);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('cers', '设置成功!'));
            }
        }

        $tabs->content = V('cers:admin/info', [
            'configs' => Cers::getConfig('cers'),
            'form'    => $form,
        ]);
    }

    public static function secondary_struct_content($e, $tabs)
    {

        if (!L('ME')->is_allowed_to('管理', 'cers')) {
            return;
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            if ($form['struct']) {
                $structs = $form['struct'];
                foreach ($structs as $key => $struct) {
                    if (!$struct['name']) {
                        $form->set_error("struct[$key][name]", I18N::T('cers', '机组名称不能为空!'));
                    }
                    if (!$struct['contacts'] || $struct['contacts'] == '{}' || $struct['contacts'] == '[]') {
                        $form->set_error("struct[$key][contacts]", I18N::T('cers', '机组负责人不能为空!'));
                    }
                }
            }

            $ids = [];
            if ($form->no_error) {
                foreach ((array) $structs as $data) {
                    $struct = O('eq_struct', ['name' => trim($data['name'])]);
                    if (!$struct->id) {
                        $struct = O('eq_struct');
                    } else {
                        $users = Q("$struct<incharge user");
                        foreach ($users as $user) {
                            $struct->disconnect($user, 'incharge');
                        }
                    }
                    $struct->name        = trim($data['name']);
                    $struct->type        = (int) $data['type'];
                    $struct->description = $data['description'];

                    if ($struct->save()) {
                        $ids[] = $struct->id;
                        foreach ((array) json_decode($data['contacts'], true) as $id => $name) {
                            $u = O('user', $id);
                            $struct->connect($u, 'incharge');
                        }
                    }
                }
                $ids = count($ids) ? join(',', $ids) : 0;
                foreach (Q("eq_struct:not([id={$ids}])") as $s) {
                    $s->delete();
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('cers', '设置成功!'));
            }
        }

        $eq_structs = Q('eq_struct');

        $tabs->content = V('cers:admin/struct', [
            'form'    => $form,
            'structs' => $eq_structs,
        ]);
    }

    // static function equipment_edit_tab($e, $tabs) {
    //     $equipment = $tabs->equipment;
    //     $me = L('ME');
    //     if (!$me->is_allowed_to('修改', $equipment)) return;

    //     Event::bind('equipment.edit.content', 'Cers::equipment_edit_content', 0, 'cers');

    //     $tabs->add_tab('cers', [
    //         'url' => $equipment->url('cers', NULL, NULL, 'edit'),
    //         'title' => I18N::T('cers', 'CERS信息'),
    //         'weight' => 200
    //     ]);
    // }

    // static function equipment_edit_content($e, $tabs) {
    //     $equipment = $tabs->equipment;
    //     $me = L('ME');
    //     if (!$me->is_allowed_to('修改', $equipment)) return;

    //     $form = Form::filter(Input::form());

    //     if ($form['submit']) {

    //         $form
    //             ->validate('ClassificationCode', 'not_empty', I18N::T('cers', '共享分类编码不能为空!'))
    //             ->validate('ManuCertification', 'not_empty', I18N::T('cers', '生产厂商资质不能为空!'));

    //         if ($form['ManuCertification'] && self::length($form['ManuCertification']) > 100) {
    //             $form->set_error('ManuCertification', I18N::T('cers', '生产厂商资质字数不得超过100!'));
    //         }

    //         if ($form['ServiceUsers'] && self::length($form['ServiceUsers']) > 200) {
    //             $form->set_error('ServiceUsers', I18N::T('cers', '知名用户字数不得超过200!'));
    //         }

    //         if ($form['OtherInfo'] && self::length($form['OtherInfo']) > 100) {
    //             $form->set_error('ManuCertification', I18N::T('cers', '备注字数不得超过100!'));
    //         }

    //            if (!$form['ManuCountryCode']) $form->set_error('ManuCountryCode', I18N::T('cers', '产地国别（代码）不能为空!'));

    //            $count = count(Config::get('equipment.ShareLevel'));

    //            if (!count($form['ShareLevel'])) $form->set_error("ShareLevel[$count]", I18N::T('cers', '共享特色代码不能为空!'));

    //         if ($form->no_error) {
    //             $equipment->Alias = $form['Alias'];
    //             $equipment->ENGName = $form['ENGName'];
    //             $equipment->ClassificationCode = $form['ClassificationCode'];
    //             $equipment->ManuCertification = $form['ManuCertification'] ?: Config::get('cers.default_manucertification');
    //             $equipment->ManuCountryCode = $form['ManuCountryCode'];
    //             $equipment->PriceUnit = $form['PriceUnit'];
    //             $equipment->PriceOther = $form['PriceOther'];
    //             $equipment->ShareLevel = (array)$form['ShareLevel'];
    //             $equipment->ServiceUsers = $form['ServiceUsers'];
    //             $equipment->OtherInfo = $form['OtherInfo'];
    //             $equipment->save();
    //             Lab::message(Lab::MESSAGE_NORMAL, I18N::T('cers', '设置成功!'));
    //         }

    //     }

    //     $tabs->content = V('cers:equipment/info', [
    //         'equipment' => $equipment,
    //         'form' => $form
    //     ]);
    // }

    public static function getSchoolInfo()
    {

        $schoolinfos = [];

        $schoolinfos = Event::trigger('cers.getschoolinfo.data');

        if (!$schoolinfos) {
            $schoolinfos = (array) Cers::getConfig('cers');
        }

        foreach ($schoolinfos as $key => $value) {
            if (!$value) {
                $schoolinfos[$key] = '无';
            }
        }

        return V('cers:api/schoolinfo', ['config' => $schoolinfos]);
    }

    public static function getSchoolRoot($need_data = false)
    {

        $instrusConfigs = $groupsConfigs = [];

        $instrusConfigs = Event::trigger('cers.getschoolroot.data');

        if (!count($instrusConfigs)) {

            $configs = Config::get('eq_charge.template', []);

            $config = Cers::getConfig('cers');

            $structs = Q('eq_struct');

            foreach ($structs as $struct) {
                $groupsConfigs[] = [
                    'SchoolCode'    => $config['SchoolCode'],
                    'InnerID'       => $struct->id,
                    'Type'          => $struct->type ?: 0,
                    'Name'          => $struct->name ? substr($struct->name, 0, 100) : '无',
                    'ShortName'     => '',
                    'Principal'     => substr(Q("$struct<incharge user:limit(1)")->name, 0, 10),
                    'ContactPerson' => substr(Q("$struct<incharge user:limit(1)")->name, 0, 10),
                    'OtherInfo'     => mb_substr($struct->description, 0, 200),
                    'UpdateDate'    => Date::format($struct->mtime, 'Y-m-d') . 'T' . Date::format($struct->mtime, 'H:i:s'),
                    'Address'       => '',
                    'ZIPCode'       => substr($config['ZIPCode'], 0, 6),
                    'Telephone'     => '',
                    'Fax'           => '',
                    'Email'         => '',
                    'StartDate'     => Date::format($struct->ctime, 'Y-m-d'),
                    'ImageURL'      => '',
                    'Introduction'  => mb_substr($struct->description, 0, 200),
                    'Strength'      => '',
                    'Expert'        => '',
                    'Instrument'    => join(',', (array) Q("equipment[struct={$struct}]")->to_assoc('id', 'name')),
                    'Achievement'   => '',
                    'WebSite'       => '',
                ];
            }

            $sql = Event::trigger('cers.getshareeffect.query.sql');

            if (!$sql) {
                $sql = "equipment[share=1]";
            }

            $equipments = Q($sql);

            foreach ($equipments as $equipment) {
                if ($instrusConfigs[$equipment->id]) {
                    continue;
                }

                if ($equipment->status == 0) {
                    $State = 1;
                } elseif ($equipment->status == 1) {
                    $State = 2;
                } else {
                    $State = 0;
                }

                $icon_file = Core::file_exists(PRIVATE_BASE . 'icons/equipment/128/' . $equipment->id . '.png', '*');
                if (!$icon_file) {
                    $icon_file = Core::file_exists(PRIVATE_BASE . 'icons/equipment/128.png', '*');
                }

                if (!$icon_file) {
                    $icon_file = Core::file_exists(PRIVATE_BASE . 'icons/128.png', '*');
                }

                if ($icon_file) {
                    $icon_url = Config::get('system.base_url') . 'icon/equipment.' . $equipment->id . '.128';
                }

                $equ_url = Config::get('cers.WebSite') . '/equipment?id=' . $equipment->id;

                $instrusConfigs[$equipment->id] = [
                    'SchoolCode'          => $config['SchoolCode'],
                    'InnerID'             => $equipment->id ? substr($equipment->id, 0, 15) : 0,
                    'AssetsCode'          => $equipment->AssetsCode ? H(substr($equipment->AssetsCode, 0, 8)) : '无',
                    'ClassificationCode'  => $equipment->ClassificationCode ? H(substr($equipment->ClassificationCode, 0, 6)) : '无',
                    'CHNName'             => $equipment->name ? H(substr($equipment->name, 0, 100)) : '无',
                    'ENGName'             => H(substr($equipment->ENGName, 0, 150)),
                    'Alias'               => H(substr($equipment->Alias, 0, 100)),
                    'Model'               => H($equipment->model_no . $equipment->specification) ? H(substr($equipment->model_no . ' ' . $equipment->specification, 0, 100)) : '无',
                    'TechParameter'       => $equipment->tech_specs ? H(substr($equipment->tech_specs, 0, 200)) : '无',
                    'ApplicationArea'     => $equipment->domain ? H(substr($equipment->domain, 0, 400)) : '无',
                    'ApplicationCode'     => $equipment->ApplicationCode,
                    'Accessory'           => H($equipment->configs),
                    'Certification'       => $equipment->Certification ? H(substr($equipment->Certification, 0, 200)) : '无',
                    'Manufacturer'        => $equipment->manufacturer ? H(substr($equipment->manufacturer, 0, 100)) : '无',
                    'ManuCertification'   => $equipment->ManuCertification ? H(substr($equipment->ManuCertification, 0, 200)) : '无',
                    'ManuCountryCode'     => $equipment->ManuCountryCode ? H(substr($equipment->ManuCountryCode, 0, 4)) : 'A156',
                    'PriceRMB'            => sprintf('%.2f', $equipment->price),
                    'PriceUnit'           => H(substr($equipment->PriceUnit, 0, 20)),
                    'Price'               => sprintf('%.2f', $equipment->PriceOther),
                    'ProduceDate'         => Date::format($equipment->manu_date, 'Y-m-d'),
                    'PurchaseDate'        => Date::format($equipment->purchased_date, 'Y-m-d'),
                    'ServiceDate'         => Date::format($equipment->ctime, 'Y-m-d'),
                    'ImageURL'            => $icon_url ? H(substr($icon_url, 0, 200)) : ' ',
                    'Organization'        => $equipment->group->name ? H(substr($equipment->group->name, 0, 100)) : '无',
                    'GroupID'             => $equipment->struct->id ? H(substr($equipment->struct->id, 0, 15)) : '0',
                    'Location'            => H(substr($equipment->location . $equipment->location2, 0, 100)),
                    'ZIPCode'             => substr($config['ZIPCode'], 0, 6),
                    'ContactPerson'       => mb_substr(Q("{$equipment} user.contact:limit(1)")->name, 0, 6),
                    'Telephone'           => H(substr($equipment->phone, 0, 20)),
                    'Email'               => H(substr($equipment->email, 0, 50)),
                    'ShareLevel'          => $equipment->ShareLevel ? array_keys($equipment->ShareLevel) : 0,
                    'OpenCalendar'        => $equipment->OpenCalendar ? H(substr($equipment->OpenCalendar, 0, 800)) : '无',
                    'ServiceCharge'       => $equipment->ReferChargeRule ? substr($equipment->ReferChargeRule, 0, 200) : '无',
                    'ServiceUsers'        => H(substr($equipment->ServiceUsers, 0, 400)),
                    'ServiceAchievements' => substr(join(', ', Q("{$equipment} publication")->to_assoc('id', 'title')), 0, 400),
                    'State'               => $State,
                    'URL'                 => $equ_url,
                    'UpdateDate'          => Date::format(time(), 'Y-m-d') . 'T' . Date::format(time(), 'H:i:s'),
                    'OtherInfo'           => $equipment->OtherInfo ? H(mb_substr($equipment->OtherInfo, 0, 100)) : H($equipment->OtherInfo),
                ];
            }
        }

        if ($need_data) {
            return ['groupsconfigs' => $groupsConfigs, 'instrusconfigs' => $instrusConfigs];
        }

        return V('cers:api/root', [
            'groupsConfigs'  => $groupsConfigs,
            'instrusConfigs' => $instrusConfigs,
        ]);
    }

    public static function getShareEffect($start = null, $end = null, $need_data = false)
    {
        $instrusConfigs = [];

        $instrusConfigs = Event::trigger('cers.getshareeffect.data', $start, $end);

        if (!count($instrusConfigs)) {

            $sql = Event::trigger('cers.getshareeffect.query.sql');

            if (!$sql) {
                $sql = "equipment[share=1]";
            }

            $equipments = Q($sql);

            $configs    = Cers::getConfig('cers');
            $SchoolCode = $configs['SchoolCode'];
            $year       = $end ? Date::format('Y', $end) : $configs['Year'];
            $start      = $start ?: mktime(0, 0, 0, $configs['MonthFrom'], 1, $year - 1);
            $end        = $end ?: mktime(0, 0, 0, $configs['MonthTo'] + 1, 1, $year) - 1;

            foreach ($equipments as $equipment) {
                $instrusConfigs[] = [
                    'SchoolCode'  => $SchoolCode,
                    'InnerID'     => $equipment->id,
                    'YEAR'        => $year,
                    'LSNUSEDHRS'  => (int) round(Eq_stat::data_point('teaching_time', $equipment, $start, $end) / 3600, 0),
                    'RSCHUSEDHRS' => (int) round(Eq_stat::data_point('research_time', $equipment, $start, $end) / 3600, 0),
                    'SERUSEDHRS'  => (int) round(Eq_stat::data_point('social_services_time', $equipment, $start, $end) / 3600, 0),
                    'OPENHRS'     => (int) round(Eq_stat::data_point('time_total', $equipment, $start, $end) / 3600, 0),
                    'SAMPLENUM'   => (int) Eq_stat::data_point('record_sample', $equipment, $start, $end),
                    'TRNSTUD'     => (int) Eq_stat::data_point('student_trainees', $equipment, $start, $end),
                    'TRNTEACH'    => (int) Eq_stat::data_point('teacher_trainees', $equipment, $start, $end),
                    'TRNOTHERS'   => (int) Eq_stat::data_point('other_trainees', $equipment, $start, $end),
                    'EDUPROJ'     => (int) Eq_stat::data_point('projects_teaching', $equipment, $start, $end),
                    'RSCHPROJ'    => (int) Eq_stat::data_point('projects_research', $equipment, $start, $end),
                    'SOCIALPROJ'  => (int) Eq_stat::data_point('projects_public_service', $equipment, $start, $end),
                    'RWDNATION'   => (int) Eq_stat::data_point('national_awards', $equipment, $start, $end),
                    'RWDPROV'     => (int) Eq_stat::data_point('provincial_awards', $equipment, $start, $end),
                    'RWDTEACH'    => (int) Eq_stat::data_point('teacher_patents', $equipment, $start, $end),
                    'RWDSTUD'     => (int) Eq_stat::data_point('student_patents', $equipment, $start, $end),
                    'PAPERINDEX'  => (int) Eq_stat::data_point('top3_pubs', $equipment, $start, $end),
                    'PAPERKERNEL' => (int) Eq_stat::data_point('core_pubs', $equipment, $start, $end),
                    'CHARGEMAN'   => Q("{$equipment}<incharge user:limit(1)")->current()->name ?: ' ',
                    /* TODO 这个是需要增加后续处理的吧。 */
                    'OtherInfo'   => ' ',
                ];
            }

        }

        if ($need_data) {return $instrusConfigs;}

        return V('cers:api/shareeffect', ['instrusConfigs' => $instrusConfigs]);

    }

    /* 获取该模块私有模块的文件 */
    public static function getPrivateFile($name = null)
    {
        return $name !== null ? MODULE_PATH . 'cers/' . PRIVATE_BASE . $name : null;
    }

    /* 获取Lab下存放文件的位置 */
    public static function getLabPrivateFile($name = null)
    {
        return $name !== null ? LAB_PATH . PRIVATE_BASE . 'cers/' . $name : null;
    }

    /* 获取配置信息文件 */
    public static function getConfig($name)
    {
        if ($name == 'cers') {
            return [
                'InstruAmount'             => Q('equipment')->total_count(),
                'PlatformDescXmlURL'       => URI::url('!cers/xmlapi/platformDesc'),
                'InstrusShareXmlURL'       => URI::url('!cers/xmlapi/instrusShare'),
                'InstrusShareEffectXmlURL' => URI::url('!cers/xmlapi/instrusShareEffect'),
            ] + (array) Lab::get($name) + (array) Config::get($name);
        }
        return (array) Lab::get($name) + (array) Config::get($name);
    }

    /* 对接口信息中的XML的用户名和密码的验证 */
    public static function verifyXMLAPI($username, $password)
    {
        return $username && $username == Config::get('cers.api-username')
        && $password && $password == md5(Config::get('cers.api-password'));
    }

    //cers模块开启时，在添加、修改仪器时收集额外信息 lastEditBy yusheng.wang
    public static function equipment_edit_info_view($e, $form, $equipment)
    {
        $e->return_value .= V('cers:equipment/edit.info', [
            'form'      => $form,
            'equipment' => $equipment,
        ]);
        return true;
    }

    public static function equipment_edit_view($e, $form, $equipment)
    {
        $e->return_value .= V('cers:equipment/edit', [
            'form'      => $form,
            'equipment' => $equipment,
        ]);
        return true;
    }

    public static function equipment_post_submit_validate($e, $form)
    {
        if ($form['share'] == 0) {
            return true;
        }
        $form
            ->validate('AssetsCode', 'not_empty', I18N::T('cers', '固定资产分类编码不能为空!'))
            ->validate('Certification', 'not_empty', I18N::T('cers', '仪器认证情况不能为空!'))
            ->validate('OpenCalendar', 'not_empty', I18N::T('cers', '开放机时安排不能为空!'))
            ->validate('ReferChargeRule', 'not_empty', I18N::T('cers', '参考收费标准不能为空!'))
            ->validate('ClassificationCode', 'not_empty', I18N::T('cers', '共享分类编码不能为空!'))
            ->validate('ManuCertification', 'not_empty', I18N::T('cers', '生产厂商资质不能为空!'));

        if ($form['ManuCertification'] && self::length($form['ManuCertification']) > 100) {
            $form->set_error('ManuCertification', I18N::T('cers', '生产厂商资质字数不得超过100!'));
        }

        if ($form['ServiceUsers'] && self::length($form['ServiceUsers']) > 200) {
            $form->set_error('ServiceUsers', I18N::T('cers', '知名用户字数不得超过200!'));
        }

        if ($form['OtherInfo'] && self::length($form['OtherInfo']) > 100) {
            $form->set_error('ManuCertification', I18N::T('cers', '备注字数不得超过100!'));
        }

        if (!$form['ManuCountryCode']) {
            $form->set_error('ManuCountryCode', I18N::T('cers', '产地国别（代码）不能为空!'));
        }

        $count = count(Config::get('equipment.ShareLevel'));

        if (!count($form['ShareLevel'])) {
            $form->set_error("ShareLevel[1]", I18N::T('cers', '共享特色代码不能为空!'));
        }

        $count = count(Config::get('equipment.domain'));

        if (!count($form['domain'])) {
            $form->set_error("domain[A]", I18N::T('cers', '主要测试和研究领域不能为空!'));
        }

        if ($form['AssetsCode'] && self::length($form['AssetsCode']) > 8) {
            $form->set_error('AssetsCode', I18N::T('cers', '固定资产分类编码字数不得超过8!'));
        }

        if ($form['tech_specs'] && self::length($form['tech_specs']) > 100) {
            if (Event::trigger('validate_equipment_tech_specs', $form)) {
                $form->set_error('tech_specs', I18N::T('cers', '主要规格及技术指标字数不得超过100!'));
            }
        }

        if ($form['configs'] && self::length($form['configs']) > 200) {
            $form->set_error('configs', I18N::T('cers', '主要附件及配置字数不得超过200!'));
        }

        if ($form['Certification'] && self::length($form['Certification']) > 100) {
            $form->set_error('Certification', I18N::T('cers', '仪器认证情况字数不得超过100!'));
        }

        if ($form['OpenCalendar'] && self::length($form['OpenCalendar']) > 400) {
            $form->set_error('OpenCalendar', I18N::T('cers', '开放机时字数不得超过400!'));
        }

        if ($form['ReferChargeRule'] && self::length($form['ReferChargeRule']) > 100) {
            $form->set_error('ReferChargeRule', I18N::T('cers', '参考收费标准字数不得超过100!'));
        }
        return true;
    }

    public static function equipment_post_submit($e, $form, $equipment)
    {

        $domain = Config::get('equipment.domain');

        if (count($form['domain'])) {
            foreach ($form['domain'] as $key => $value) {
                $applicationarea .= $domain[$key] . ',';
                $applicationcode .= $key;
            }
            $applicationarea = rtrim($applicationarea, ',');
        }

        $equipment->domain = $applicationarea;
        $equipment->ApplicationCode = $applicationcode;
        if (isset($form['ReferChargeRule'])) $equipment->ReferChargeRule = $form['ReferChargeRule'];
        if (isset($form['OpenCalendar'])) $equipment->OpenCalendar = $form['OpenCalendar'];
        if (isset($form['AssetsCode'])) $equipment->AssetsCode = $form['AssetsCode'];
        if (isset($form['Certification'])) $equipment->Certification = $form['Certification'];
        // if((int)$form['Struct']){
        //     $equipment->struct = O('eq_struct', (int)$form['Struct']);
        // }
        if (isset($form['Alias'])) $equipment->Alias = $form['Alias'];
        if (isset($form['ENGName'])) $equipment->ENGName = $form['ENGName'];
        if (isset($form['ClassificationCode'])) $equipment->ClassificationCode = $form['ClassificationCode'];
        $equipment->ManuCertification = $form['ManuCertification'] ?: Config::get('cers.default_manucertification');
        if (isset($form['ManuCountryCode'])) $equipment->ManuCountryCode = $form['ManuCountryCode'];
        if (isset($form['PriceUnit'])) $equipment->PriceUnit = $form['PriceUnit'];
        if (isset($form['PriceOther'])) $equipment->PriceOther = $form['PriceOther'];
        if (isset($form['ShareLevel'])) $equipment->ShareLevel = (array)$form['ShareLevel'];
        if (isset($form['ServiceUsers'])) $equipment->ServiceUsers = $form['ServiceUsers'];
        if (isset($form['OtherInfo'])) $equipment->OtherInfo = $form['OtherInfo'];
    }

    public static function length($string = null)
    {
        // 将字符串分解为单元
        preg_match_all("/./us", $string, $match);
        // 返回单元个数
        return count($match[0]);
    }

}
