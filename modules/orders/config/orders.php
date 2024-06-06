<?php

$config['order.info.msg.model'] = [
	'description'=>'设置订单基本信息修改之后的更新提示',
	'body'=>'%subject 于 %date 更新了订单 %order 的基本信息',
	'strtr'=>[
		'%subject'=>'修改者',
		'%order'=>'订单名称',
		'%date'=>'时间'
	],
];

$config['export_columns'] = [
	'product_name' => '产品名称',
	'manufacturer' => '生产商',
	'catalog_no'=>'目录号',
	'vendor' => '供应商',
	'unit_price' => '单价',
	'quantity' => '数量',
    'fare' => '运费',
    'price' => '总价',
	'spec' => '规格',
	'order_status' => '订单状态',
	'requester' => '申购人',
	'request_date'=>'申购日期',
	'purchaser' => '订购人',
	'purchase_date' => '订购日期',
    'grant'=> '关联经费',
	];
//order 自动完成功能
//添加新自动完成item时，请放至unit_price之前。
$config['autocomplete_order_items'] = [
	'product_name',
	'manufacturer',						//生产商
	'catalog_no',						//目录号
	'spec',								//规格
	'vendor',						//供应商
	'quantity',							//数量
	'unit_price',						//单价
];

//导入订单时候的
$config['import_fields'] = [
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

	'product_name'=>'产品名称',
	'manufacturer'=>'生产商',
	'catalog_no'=>'目录号',
	'vendor'=>'供应商',
	'unit_price'=>'单价',
	'quantity'=>'数量',
	'fare' => '运费',
	'price'=>'总价',
	'spec'=>'规格',
	'order_no'=>'订单编号',
	'requester'=>'申购人',
	'request_date'=>'申购日期',
	'purchaser'=>'订购人',
	'purchase_date'=>'订购日期',

	/* 'purchase_note'=>'订购备注', */
];

$config['at_comment_content'] = '%user添加了订单%order %at_users';

//send vendor mail
$config['send_vendor_mail'] = FALSE;

//orders 的二维码配置
//key为系统中对应的属性, value为客户可配信息
$config['qr'] = [
    'id'=> 'id', //order->id
    'requester'=> 'requester', //order->requester->name
    'lab_name'=> 'lab_name', //Config::get('lab.name')
    'product_name'=> 'product_name', //order->product_name
    'quantity'=> 'quantity', //order->quantity
    'unit_price'=> 'unit_price', //order->unit_price
    'fare'=> 'fare', //order->fare
    'price'=> 'price', //order->price
    'manufacturer'=> 'manufacturer', //order->manufacturer
    'catalog_no'=> 'catalog_no', //order->catalog_no
    'package'=> 'package', //order->package
    'model'=> 'model', //order->model
    'spec'=> 'spec', //order->spec
    'quantity'=> 'quantity', //order->quantity
    'vendor'=> 'vendor', //order->vendor->name
    'request_date'=> 'request_date', //order->request_date
    'request_note'=> 'request_note', //order->request_note
    'approver'=> 'approver', //order->approver->name
    'approve_date'=> 'approve_date', //order->approve_date
    'approve_node'=> 'approve_node', //order->approve_node
    'purchaser'=> 'purchaser', //order->purchaser->name
    'purchase_date'=> 'purchase_date', //order->purchase_date
    'purchase_note'=> 'purchase_note', //order->purchase_note
    'receiver'=> 'receiver', //order->receiver->name
    'receive_date'=> 'receive_date', //order->receive_date
    'receive_note'=> 'receive_note', //order->receive_note
    'canceler'=> 'canceler', //order->canceler->name
    'cancel_date'=> 'cancel_date', //order->cancel_date
    'cancel_note'=> 'cancel_note', //order->cancel_note
    'stock'=> 'stock', //order->stock->product_name
    'souce'=> 'souce', //order->souce
    'status'=> 'status', //order->status
];

$config['contact_verify'] = TRUE;
$config['require'] = [
	'product_name'=> ['name'=> '产品名称','isRequire'=>TRUE,'action'=>[2,3]],
	'manufacturer'=> ['name'=> '生产商','isRequire'=> FALSE,'action'=>[2,3]],
	'model'=> 		 ['name'=> '型号','isRequire'=> FALSE,'action'=>[2,3]],
	'catalog_no'=>   ['name'=> '目录号','isRequire'=> FALSE,'action'=>[2,3]],
	'spec'=> 		 ['name'=> '规格','isRequire'=> FALSE,'action'=>[2,3]],
	'order_no'=>     ['name'=> '订单编号','isRequire'=> FALSE,'action'=>[1,2,3]],
	'quantity'=>	 ['name'=> '数量','isRequire'=>TRUE,'action'=>[2,3]],
	'unit_price'=>	 ['name'=> '单价','isRequire'=>TRUE,'action'=>[2,3]],
	'fare'=>	 	 ['name'=> '运费','isRequire'=>FALSE,'action'=>[2,3]],
	'price'=>	 	 ['name'=> '总价','isRequire'=>FALSE,'action'=>[2,3]],
	'tags'=> 		 ['name'=> '标签','isRequire'=> FALSE,'action'=>[2]],
	'link'=> 		 ['name'=> '链接地址','isRequire'=> FALSE,'action'=>[2,3]],
];