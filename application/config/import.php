<?php
//用户导入字段
$config['users_fields'] = [
	''=>'--',
	'name'=>'用户姓名',
	'token'=>'登录账号',
	'password'=>'密码',
	'gender'=>'性别',
	'member_type'=>'人员类型',
	'ref_no'=>'学工号',
	'card_no'=>'物理卡号',
	'major' => '专业',
	'organization'=>'单位名称',
	'tag'=>'组织机构',
	'email'=>'电子邮箱',
	'phone'=>'联系电话',
	'address'=>'地址',
	'lab'=>'课题组',
	'role'=>'角色',

	/* 'purchase_note'=>'订购备注', */
];

//导入仪器
$config['equipments_fields'] = [
	''=>'--',
	/* 'catalog_no'=>'目录号', */
	/* 'name'=>'名称', */
	/* 'manufacturer'=>'生产商', */
	/* 'vendor'=>'供应商', */
	/* 'unit_price'=>'单价', */
	/* 'quantity'=>'数量', */
	/* 'unit'=>'单位', */
	/* 'price'=>'价格', */
	/* 'user'=>'订购人', */

	/* 'ctime'=>'日期' */

	'name'=>'仪器名称',
	'en_name' => '英文名称',
	'ref_no' => '仪器编号',
	'cat_name'=>'仪器分类',
	'location'=>'放置房间',
	'incharge_user'=>'负责人*',
	'contact_user'=>'联系人*',
	'phone'=>'联系电话',
	'email' => '联系邮箱',
	'group'=>'组织机构',
	'specification'=>'规格',
	'model_no'=>'型号',
	'price'=>'价格',
	'manufacturer'=>'生产厂家',
	'manu_at'=>'制造国家',
	'purchased_date' => '购置日期',
	'manu_date'=>'出厂日期',
	'cat_no'=>'分类号',
	'tech_specs'=>'主要规模与技术指标',
	'features'=>'主要功能及特色',
	'configs'=>'主要配件及配置',
    'domain'=>'主要测试和研究领域',
    'open_reserv'=>'开放预约',
    'charge_info'=>'计费信息',

	/* 'purchase_note'=>'订购备注', */
];

//导入实验室
$config['labs_fields'] = [
	''=>'--',
	'name'=>'课题组名称*',
    'owner'=>'负责人*',
	'contact'=>'课题组联系方式',
	'group'=>'组织机构',
	'ref_no'=>'课题组编号',
	'type'=>'课题组类型',
	'subject' => '所属学科',
	'util_area'=>'课题组使用面积',
	'location'=>'课题组所在地（楼宇）',
	'location2'=>'课题组所在地（房间号）',
	'description'=>'介绍',
];


//导入卡号
$config['cardnos_fields'] = [
	''=>'--',
	'name'=>'用户姓名',
	'ref_no'=>'学工号',
	'card_no'=>'物理卡号'
];