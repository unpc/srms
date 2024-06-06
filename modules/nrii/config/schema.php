<?php
$config['nrii_center'] = [
    'fields' => [
        'centname' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],       //仪器中心名称
        'inner_id' => ['type'=>'varchar(150)', 'null'=>FALSE],                      //所在单位仪器中心编号
        'begin_date' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],               //成立日期
        'worth' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],                 //原值
        'research_area' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],         //科研用房面积（㎡）
        'realm' => ['type' => 'varchar(250)', 'null' => FALSE, 'default' => ''],    //主要科学领域
        'address' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>0],         //安放地址
        'instru_num' => ['type' => 'int', 'null'=>FALSE, 'default' => 0],           // 大型科研仪器数量
        'accept' => ['type'=>'int', 'default'=>0],                                  // 实验室认证认可 

        'service_content' => ['type'=>'varchar(600)', 'null'=>FALSE],                //服务内容
        'equrl' => ['type'=>'varchar(100)', 'null'=>FALSE],                          //仪器中心网址

        'contact' => ['type'=>'varchar(150)', 'null'=>FALSE],                        //联系人
        'phone' => ['type'=>'varchar(20)', 'null'=>FALSE],                          //联系人电话
        'email' => ['type'=>'varchar(50)', 'null'=>FALSE],                          //电子邮箱
        
        'contact_address' => ['type'=>'varchar(300)', 'null'=>FALSE],                 //通信地址填写部分
        'zip_code' => ['type'=>'int', 'null'=>FALSE],                                //邮政编码

        'nrii_status' => ['type'=>'int', 'default'=>0]
    ],
    'indexes' => [
        'centname' => ['fields'=>['centname']],
        'inner_id' => ['fields'=>['inner_id']],
        'begin_date' => ['fields'=>['begin_date']],
        'contact' => ['fields'=>['contact']],
        'nrii_status' => ['fields'=>['nrii_status']],
    ],
];
                
$config['nrii_device'] = [
    'fields' => [
        'cname' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],          //中文名称
        'ename' => ['type'=>'varchar(150)', 'null'=>FALSE],                          //英文名称
        'inner_id' => ['type'=>'varchar(150)', 'null'=>FALSE],                       //所属单位科学装置编号
        'worth' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],                 //原值
        'begin_date' => ['type' => 'int', 'null' => FALSE, 'default' => 0],               //建账日期
        'address' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>0],         //安放地址
        'street' => ['type'=>'varchar(500)', 'null'=>FALSE],                         //安放地址填写部分
        'realm' => ['type' => 'varchar(250)', 'null' => FALSE, 'default' => ''],    //主要科学领域
        'url' => ['type'=>'varchar(100)', 'null'=>FALSE],                            //装置网站网址
        'technical' => ['type'=>'varchar(1500)', 'null'=>FALSE],                      //科学技术中心
        'function' => ['type'=>'varchar(900)', 'null'=>FALSE],                       //主要功能及技术指标
        'requirement' => ['type'=>'varchar(1500)', 'null'=>FALSE],                    //国外主要单位用户
        'service_content' => ['type'=>'varchar(600)', 'null'=>FALSE],                //国内主要单位用户
        'contact' => ['type'=>'varchar(150)', 'null'=>FALSE],                        //联系人
        'phone' => ['type'=>'varchar(20)', 'null'=>FALSE],                                   //联系人电话
        'email' => ['type'=>'varchar(50)', 'null'=>FALSE],                          //联系人电子邮箱
        'fill_position' => ['type'=>'varchar(500)', 'null'=>FALSE],                          //联系人职务
        'fill_insname' => ['type'=>'varchar(500)', 'null'=>FALSE],                          //联系人单位
        'ename_short' => ['type'=>'varchar(150)', 'null'=>FALSE],                     //英文简称
        'competent_dep' => ['type'=>'varchar(500)', 'null'=>FALSE],                   //主管部门
        'sup_insname' => ['type'=>'varchar(500)', 'null'=>FALSE],                   //依托单位
        'device_category' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 1], //设施类别
        'construction' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 1], //建设情况
        'approval_dep' => ['type'=>'varchar(500)', 'null'=>FALSE],                   //批复部门
        'video' => ['type'=>'varchar(100)', 'null'=>FALSE],                            //科普视频网址
        'sci_contact' => ['type'=>'varchar(150)', 'null'=>FALSE],                        //首席科学家-姓名
        'sci_phone' => ['type'=>'varchar(20)', 'null'=>FALSE],                           //席科学家-电话
        'sci_email' => ['type'=>'varchar(50)', 'null'=>FALSE],                           //席科学家-邮箱
        'sci_position' => ['type'=>'varchar(500)', 'null'=>FALSE],                           //首席科学家-职务
        'sci_insname' => ['type'=>'varchar(500)', 'null'=>FALSE],                           //首席科学家-单位

        'run_contact' => ['type'=>'varchar(150)', 'null'=>FALSE],                        //运行负责人-姓名
        'run_phone' => ['type'=>'varchar(20)', 'null'=>FALSE],                           //运行负责人-电话
        'run_email' => ['type'=>'varchar(50)', 'null'=>FALSE],                           //运行负责人-邮箱
        'run_position' => ['type'=>'varchar(500)', 'null'=>FALSE],                           //运行负责人-职务
        'run_insname' => ['type'=>'varchar(500)', 'null'=>FALSE],                           //运行负责人-单位

        'achievement' => ['type'=>'varchar(1500)', 'null'=>FALSE],                           //首席科学家-单位
        'layout_image' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //布局图
        'key_image' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //关键部件图
        'experiment_image' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //实验操作图
        'organization_file' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //组织管理制度
        'open_file' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //开放收费制度
        'apply_file' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //设施申请制度
        'research_file_one' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //研究成果附件1
        'research_file_two' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //研究成果附件2
        'research_file_three' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //研究成果附件3
        'research_file_four' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //研究成果附件4
        'research_file_five' => ['type'=>'varchar(100)', 'null'=>FALSE],                           //研究成果附件5

        'nrii_status' => ['type'=>'int', 'default'=>0]                      //支撑国家重大科研任务、产生经济社会效益、国际合作成果等
    ],
    'indexes' => [
        'cname' => ['fields'=>['cname']],
        'ename' => ['fields'=>['ename']],
        'inner_id' => ['fields'=>['inner_id']],
        'worth' => ['fields'=>['worth']],
        'begin_date' => ['fields'=>['begin_date']],
        'address' => ['fields'=>['address']],
        'contact' => ['fields'=>['contact']],
        'sci_contact' => ['fields'=>['sci_contact']],
        'run_contact' => ['fields'=>['run_contact']],
        'device_category' => ['fields'=>['device_category']],
        'construction' => ['fields'=>['construction']],
        'nrii_status' => ['fields'=>['nrii_status']],
    ],
];
$config['nrii_unit'] = [
    'fields' => [
        'unitname' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],       //服务单元名称
        'org' => ['type'=>'varchar(150)', 'null'=>FALSE],                            //所属单位
        'category' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 1],       //服务单元类别
        'inner_id' => ['type'=>'varchar(150)', 'null'=>FALSE],                       //所在单位服务单元编号
        'status' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 1],         //运行状态
        'begin_date' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],               //成立日期
        'share_mode' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 1],     //共享模式
        'realm' => ['type' => 'varchar(250)', 'null' => FALSE, 'default' => ''],    //主要科学领域
        'address' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>0],         //安放地址
        'street' => ['type'=>'varchar(500)', 'null'=>FALSE],                         //安放地址填写部分

        'service_url' => ['type'=>'varchar(150)', 'null'=>FALSE],                    //预约服务网址
        'function' => ['type'=>'varchar(900)', 'null'=>FALSE],                       //主要功能
        'service_content' => ['type'=>'varchar(600)', 'null'=>FALSE],                //服务内容
        'achievement' => ['type'=>'varchar(1500)', 'null'=>FALSE],                   //服务典型成果
        'requirement' => ['type'=>'varchar(500)', 'null'=>FALSE],                    //对外开放共享规定
        'fee' => ['type'=>'varchar(500)', 'null'=>FALSE],                            //参考收费标准

        'contact' => ['type'=>'varchar(150)', 'null'=>FALSE],                        //联系人
        'phone' => ['type'=>'varchar(20)', 'null'=>FALSE],                          //联系人电话
        'email' => ['type'=>'varchar(150)', 'null'=>FALSE],                          //电子邮箱
        'contact_street' => ['type'=>'varchar(300)', 'null'=>FALSE],                 //通信地址
        'zip_code' => ['type'=>'int', 'null'=>FALSE],                                //邮政编码

        'nrii_status' => ['type'=>'int', 'default'=>0]
    ],
    'indexes' => [
        'unitname' => ['fields'=>['unitname']],
        'org' => ['fields'=>['org']],
        'category' => ['fields'=>['category']],
        'inner_id' => ['fields'=>['inner_id']],
        'status' => ['fields'=>['status']],
        'begin_date' => ['fields'=>['begin_date']],
        'share_mode' => ['fields'=>['share_mode']],
        'address' => ['fields'=>['address']],
        'contact' => ['fields'=>['contact']],
        'zip_code' => ['fields'=>['zip_code']],
        'nrii_status' => ['fields'=>['nrii_status']],
    ],
];
$config['nrii_equipment'] = [
    'fields' => [
        'eq_id' => ['type'=>'int', 'null'=>TRUE, 'default' => 0],                    //关联仪器id
        'eq_name' => ['type'=>'varchar(300)', 'null'=>FALSE, 'default'=>''],         //中文名称
        'ename' => ['type'=>'varchar(100)', 'null'=>TRUE],                          //英文名称
        'org' => ['type'=>'varchar(150)', 'null'=>FALSE],                            //所属单位
        'inner_id' => ['type'=>'varchar(150)', 'null'=>FALSE],                       //所属单位科学装置编号
        'affiliate' => ['type'=>'int', 'null'=>FALSE],                               //是否附属其他设备
        'affiliate_name' => ['type'=>'varchar(150)', 'null'=>FALSE],                 //附属仪器名称
        'class' => ['type'=>'varchar(150)', 'null'=>FALSE],                          //设备分类
        'address' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>0],          //安放地址
        'street' => ['type'=>'varchar(500)', 'null'=>FALSE],                         //安放地址填写部分
        'worth' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],                  //原值
        'eq_source' => ['type'=>'int', 'null'=>FALSE],                               //设备来源
        'type_status' => ['type'=>'int', 'null'=>FALSE],                             //仪器类别
        'status' => ['type'=>'int', 'null'=>FALSE],                                  //运行状态
        'share_status' => ['type'=>'int', 'null'=>FALSE],                            //共享模式
        'realm' => ['type' => 'varchar(250)', 'null' => FALSE, 'default' => ''],     //主要科学领域
        'nation' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => 1],     //产地国别
        'model_no' => ['type'=>'varchar(300)', 'null'=>FALSE],                       //规格型号
        'manufacturer' => ['type'=>'varchar(150)', 'null'=>FALSE],                   //生产制造商
        'begin_date' => ['type' => 'int', 'null' => FALSE, 'default' => 0],                //建账日期

        'technical' => ['type'=>'varchar(1500)', 'null'=>FALSE],                      //科学技术中心
        'function' => ['type'=>'varchar(900)', 'null'=>FALSE],                       //主要功能
        'requirement' => ['type'=>'varchar(1500)', 'null'=>FALSE],                    //对外开放共享规定
        'fee' => ['type'=>'varchar(1500)', 'null'=>FALSE],                            //参考收费标准
        'service_content' => ['type'=>'varchar(600)', 'null'=>FALSE],                //服务内容
        'achievement' => ['type'=>'varchar(1500)', 'null'=>FALSE],                    //服务典型成果
        'service_url' => ['type'=>'varchar(100)', 'null'=>FALSE],                    //预约服务网址
        'customs' => ['type'=>'object', 'oname'=>'nrii_customs'],                    //海关监管情况

        'run_machine' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],                  //年总运行机时
        'service_machine' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],              //年服务机时
        'funds' => ['type'=>'varchar(100)', 'null'=>FALSE, 'default'=>0],                  //主要购置经费来源
        'worth' => ['type'=>'varchar(100)', 'null'=>FALSE, 'default'=>0],                  //原值

        'contact' => ['type'=>'varchar(150)', 'null'=>FALSE],                        //联系人
        'phone' => ['type'=>'varchar(20)', 'null'=>FALSE],                          //联系人电话
        'email' => ['type'=>'varchar(50)', 'null'=>FALSE],                          //电子邮箱
        'contact_address' => ['type'=>'varchar(300)', 'null'=>FALSE],                //通信地址
        'zip_code' => ['type'=>'int', 'null'=>FALSE],                                //邮政编码

        'nrii_status' => ['type'=>'int', 'default'=>0],
        'shen_status' => ['type'=>'int', 'default'=>0]//审核状态，默认未审核
    ],
    'indexes' => [
        'eq_id' => ['fields'=>['eq_id']],
        'eq_name' => ['fields'=>['eq_name']],
        'ename' => ['fields'=>['ename']],
        'org' => ['fields'=>['org']],
        'inner_id' => ['fields'=>['inner_id']],
        'affiliate' => ['fields'=>['affiliate']],
        'class' => ['fields'=>['class']],
        'address' => ['fields'=>['address']],
        'worth' => ['fields'=>['worth']],
        'eq_source' => ['fields'=>['eq_source']],
        'type_status' => ['fields'=>['type_status']],
        'status' => ['fields'=>['status']],
        'share_status' => ['fields'=>['share_status']],
        'nation' => ['fields'=>['nation']],
        'begin_date' => ['fields'=>['begin_date']],
        'contact' => ['fields'=>['contact']],
        'zip_code' => ['fields'=>['zip_code']],
        'nrii_status' => ['fields'=>['nrii_status']],
        'shen_status' => ['fields'=>['shen_status']],
    ],
];

$config['nrii_customs'] = [
    'fields' => [
        'inner_id' => ['type'=>'varchar(150)', 'null'=>FALSE],                          //单位内部编号
        'ins_code' => ['type'=>'varchar(150)', 'null'=>FALSE],                                   //所属单位标识
        'declaration_number' => ['type'=>'varchar(150)', 'null'=>FALSE],                         //进口报关单编号
        'import_date' => ['type'=>'int', 'null'=>FALSE],                                //进口时间
        'item_number' => ['type'=>'varchar(20)', 'null'=>FALSE],                        // 进口报关单项号 2位
        'form_name' => ['type'=>'varchar(50)', 'null'=>FALSE],                          // 仪器在进口报关单上的名称 30
        'nrii_status' => ['type'=>'int', 'default'=>0]
    ],
    'indexes' => [
        'inner_id' => ['fields'=>['inner_id']],
        'declaration_number' => ['fields'=>['declaration_number']],
        'import_date' => ['fields'=>['import_date']],
        'item_number' => ['fields' => ['item_number']],
        'nrii_status' => ['fields'=>['nrii_status']],
    ],
];

$config['nrii_record'] = [
    'fields' => [
        'eq_name' => ['type'=>'varchar(300)', 'null'=>FALSE],                            //仪器名称
        'inner_id' => ['type'=>'varchar(150)', 'null'=>FALSE],                           //设备编号
        'amounts' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],                    //服务金额
        'service_time' => ['type'=>'varchar(60)', 'null'=>FALSE],                        //实际服务时间
        'start_time' => ['type'=>'varchar(60)', 'null'=>FALSE],                          //服务开始时间
        'end_time' => ['type'=>'varchar(60)', 'null'=>FALSE],                            //服务结束时间
        'service_content' => ['type'=>'varchar(600)', 'null'=>TRUE],                     //实际服务内容
        'service_way' => ['type'=>'varchar(100)', 'null'=>FALSE],                        //服务方式
        'service_amount' => ['type'=>'double', 'null'=>FALSE],                           //服务量
        'subject_name' => ['type'=>'varchar(600)', 'null'=>FALSE],                       //课题名称
        'subject_income' => ['type'=>'varchar(600)', 'null'=>TRUE],                      //课题经费来源
        'subject_area' => ['type'=>'varchar(600)', 'null'=>FALSE],                       //课题主要科学领域
        // 'subject_content' => ['type'=>'varchar(600)', 'null'=>TRUE],                     //课题研究内容

        'applicant' => ['type'=>'varchar(60)', 'null'=>FALSE],                           //申请人
        'applicant_phone' => ['type'=>'varchar(20)', 'null'=>TRUE],                      //申请人电话
        'applicant_email' => ['type'=>'varchar(50)', 'null'=>TRUE],                      //申请人电子邮箱
        'applicant_unit' => ['type'=>'varchar(150)', 'null'=>TRUE],                      //申请人单位
        'comment' => ['type'=>'int', 'null'=>TRUE],                                      //用户评价及意见
        'comment2' => ['type'=>'varchar(600)', 'null'=>TRUE],                            //用户评价及意见填写部分
        'service_type' => ['type'=>'int', 'null'=>TRUE],                                 //服务对象
        'service_direction' => ['type'=>'int', 'null'=>TRUE],                            //服务类型
        'tax_record' => ['type'=>'varchar(300)', 'null'=>TRUE],                          //用户评价及意见填写部分

        'nrii_status' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],                   //科技部返回值
        'nrii_eq' => ['type'=>'object', 'oname'=>'nrii_equipment'],                      //关联仪器
        'user' => ['type'=>'object', 'oname'=>'user'],                                   //关联用户
        'record' => ['type'=>'object', 'oname'=>'eq_record'],                            //关联使用记录
        'source' => ['type' => 'object'],                                               // 关联使用、送样记录


        'address_type' => ['type' => 'tinyint', 'default' => 0],                          // 是否在单位内使用
        'move_address' => ['type' => 'varchar(150)', 'null' => TRUE],                    // 对外服务地址
        'service_code' => ['type' => 'varchar(50)', 'null' => TRUE],                     // 非适用简易程序海关《通知书》编号
        'sign_agreement' => ['type' => 'tinyint', 'default' => 0]                        // 本次服务是否签订协议
    ],
    'indexes' => [
        'inner_id' => ['fields'=>['inner_id']],
        'record' => ['fields'=>['record']],
        'source' => ['fields'=>['source']],
        'nrii_status' => ['fields'=>['nrii_status']],
    ],
];

$config['address'] = [
    'fields' => [
        'name' => ['type'=>'varchar(300)', 'null'=>FALSE],                            //仪器名称
        'adcode' => ['type'=>'varchar(150)', 'null'=>FALSE],                          //设备编号
        'level' => ['type'=>'varchar(100)', 'null'=>FALSE],                         //服务方式
    ],
    'indexes' => [
        'name' => ['fields'=>['name']],
        'adcode' => ['fields'=>['adcode']],
        'level' => ['fields'=>['level']],
    ],
];