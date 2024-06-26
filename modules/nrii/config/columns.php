<?php
$config['export_columns.device'] = [
    'cname' => '装置名称（中文）',
    'ename' => '英文名称',
    'inner_id' => '所属单位科学内部编号',
    'worth' => '建设经费（万元）',
    'begin_date' => '验收通过日期',
    'location' => '安放地址',
    'realm' => '主要学科领域',
    'street' => '街道地址',
    'url' => '装置网站的网址',
    'service_content' => '国内主要单位用户',
    'technical' => '科学技术中心',
    'function' => '主要功能及技术指标',
    'requirement' => '国外主要单位用户',
    'contact' => '联系人',
    'phone' => '联系人电话',
    'email' => '联系人电子邮箱',
    'fill_position' => '联系人-职务',
    'fill_insname' => '联系人-单位',
    'ename_short' => '英文简称',
    'competent_dep' => '主管部门',
    'sup_insname' => '依托单位',
    'device_category' => '设施类别',
    'construction' => '建设情况',
    'approval_dep' => '批复部门',
    'video' => '科普视频网址',
    'sci_contact' => '首席科学家-姓名',
    'sci_position' => '首席科学家-职务',
    'sci_insname' => '首席科学家-单位',
    'sci_phone' => '首席科学家-电话',
    'sci_email' => '首席科学家-邮箱',
    'run_contact' => '运行负责人-姓名',
    'run_position' => '运行负责人-职务',
    'run_phone' => '运行负责人-电话',
    'run_email' => '运行负责人-邮箱',
    'achievement' => '支撑国家重大科研任务、产生经济社会效益、国际合作成果等',
    'layout_image' => '布局图下载地址',
    'key_image' => '关键部件图下载地址',
    'experiment_image' => '实验操作图下载地址',
    'organization_file' => '组织管理制度下载地址',
    'open_file' => '开放收费制度下载地址',
    'apply_file' => '设施申请制度下载地址',
    'research_file_one' => '研究成果附件1下载地址',
    'research_file_two' => '研究成果附件2下载地址',
    'research_file_three' => '研究成果附件3下载地址',
    'research_file_four' => '研究成果附件4下载地址',
    'research_file_five' => '研究成果附件5下载地址'
];

$config['export_columns.center'] = [
    'centname' => '仪器中心名称',
    'inner_id' => '所属单位中心内部编码',
    'equrl' => '仪器中心网址',
    'location' => '安放地址',
    'worth' => '仪器总值（万元）',
    'research_area' => '科研用房面积（㎡）',
    'begin_date' => '成立日期',
    'instru_num' => '大型科研仪器数量',
    'realm' => '主要学科领域',
    'accept' => '实验室认证认可',
    'service_content' => '中心简介',
    'contact' => '联系人',
    'phone' => '联系人电话',
    'email' => '电子邮箱',
    'contact_address' => '通讯地址',
    'zip_code' => '邮政编码'
];

$config['export_columns.equipment'] = [
    'eq_id' => '仪器名称',
    'eq_name' => '仪器设备名称',
    'ename' => '英文名称',
    'inside_depart' => '所属单位内部门',
    'inner_id' => '所属单位科学装置编号',
    'affiliate' => '所属资源载体',
    'affiliate_name' => '隶属资源载体名称',
    'class' => '设备分类',
    'location' => '安放地址',
    'worth' => '原值（万元）',
    'eq_source' => '仪器设备来源',
    'type_status' => '仪器设备类别',
    'realm' => '主要学科领域',
    'nation' => '产地国别',
    'model_no' => '规格型号',
    'manufacturer' => '生产制造商',
    'beginDate' => '建账日期',
    'technical' => '主要技术指标',
    'function' => '主要功能',
    'requirement' => '用户须知',
    'fee' => '参考收费标准',
    'service_content' => '服务内容',
    'funds' => '主要购置经费来源',
    'run_machine' => '年总运行机时',
    'service_machine' => '年服务机时',
    'cus_inner_id' => '单位内部编号',
    'cus_ins_code' => '所属单位标识',
    'cus_declaration_number' => '进口报关单编号',
    'cus_item_number' => '进口报关单项号',
    'cus_import_date' => '海关放行日期',
    'cus_form_name' => '仪器设备进口报关单名称',
    'contact' => '联系人',
    'phone' => '联系人电话',
    'email' => '电子邮箱',
    'contact_address' => '通讯地址',
    'zip_code' => '邮政编码'
];

$config['export_columns.record'] = [
    'id' => '记录编号',
    'eq_name' => '仪器名称',
    'inner_id' => '所在单位仪器编号',
    'source_id' => '服务记录内部编号',
    'amounts' => '服务金额(元)',
    // 'subject_content' => '课题研究内容',
    'start_time' => '服务开始时间', // add
    'end_time' => '服务结束时间',
    'service_content' => '实际服务内容',
    'service_way' => '服务方式',
    'service_type' => '服务对象',
    'service_direction' => '服务类型',
    'tax_record' => '补税记录',
    'service_time' => '服务机时', // modify
    // 'service_amount' => '服务量',
    'subject_name' => '服务名称',
    'subject_income' => '服务经费来源',
    'subject_area' => '课题主要科学领域',
    'sign_agreement' => '本次服务是否签订协议',
    'address_type' => '是否在单位内使用',
    'move_address' => '对外服务地址',
    'service_code' => '非适用简易程序海关《通知书》编号',
    'applicant' => '申请人',
    'applicant_phone' => '申请人电话',
    'applicant_email' => '申请人电子邮箱',
    'applicant_unit' => '申请人单位',
    'comment' => '用户评价及意见',
    // 'comment2' => '用户意见',
];
