<?php

$config['stock.info.msg.model'] = [
	'description'=>'设置存货基本信息修改之后的更新提示',
	'body'=>'%subject 于 %date 更新了 %stock 的基本信息',
	'strtr'=>[
		'%subject'=>'修改者',
		'%stock'=>'存货名称',
		'%date'=>'时间'
	],
	
];

$config['export_columns.stock'] = [
	'ctime'=>'日期',
	'stock'=>'存货',
	'user'=>'用户',
	'operate'=>'操作',
    'unit_price'=> '单价',
    'total_price'=> '总价',
	'note'=>'备注',
];

$config['export_columns.shocks'] = [
    '-1' => '货品信息',
    'product_name' => '产品名称',
    'ref_no' => '自定义编号',
    'catalog_no' => '目录号',
    'vendor' => '供应商',
    'manufacturer' => '生产商',
    'barcode' => '条形码',
    'model' => '型号',
    'spec' => '规格',
    'unit_price' => '单价',
    'type'=> '类型',
    '-2' => '库存信息',
    'quantity' => '存量',
    'location' => '存放位置',
    'status' => '库存状态',
    'tags' => '标签',
    'note' => '备注',
];
