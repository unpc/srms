<?php
$config['stock'] = [
	'fields' => [
		'product_name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], // 产品名称
		'manufacturer'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], // 生产商
		'catalog_no'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''], // 目录号
		'model' => ['type'=>'varchar(80)', 'null'=>FALSE, 'default'=>''], //型号
		'spec'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''], // 规格
		'vendor'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], // 供应商
		'unit_price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0], // 单价
        'summation'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],  //总量
		'quantity'=>['type'=>'double', 'null'=>FALSE, 'default'=>0], // 存量
		'location'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''], // 存放地点
		'status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>1], // 存货状态
		'note'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''], // 备注
		'content'=>['type'=>'text', 'null'=>TRUE], // 内容
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], // 创建时间
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], // 修改时间
		'is_collection'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0], //是否为存货集合
		'parent'=>['type'=>'object', 'oname'=>'stock'],
		'barcode'=>['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
		'ref_no'=>['type'=>'varchar(255)', 'null'=>TRUE],
		'creator'=>['type'=>'object', 'oname'=>'user'],
		'expire_status' => ['type' => 'tinyint', 'default' => 4],//存货过期状态 //默认为未设置
		'expire_time' => ['type'=>'int', 'null'=>FALSE, 'default' => 0],//存货过期时间
		'expire_notice_time' => ['type' => 'int', 'null' => false, 'default' => 0 ],//存货过期提前提醒天数
        'type'=> ['type'=> 'varchar(255)', 'null'=> false, 'default'=> ''],
		],
	'indexes' => [
		'product_name'=>['fields'=>['product_name']],
		'manufacturer'=>['fields'=>['manufacturer']],
		'catalog_no'=>['fields'=>['catalog_no']],
		'vendor'=>['fields'=>['vendor']],
		'location'=>['fields'=>['location']],
		'parent'=>['fields'=>['parent']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
		'barcode' => ['fields'=>['barcode']],
        'type'=> ['fields'=> ['type']],
		'ref_no' => ['fields'=>['ref_no'], 'type'=>'unique'],
		],
	];

$config['stock_use'] = [
	'fields' => [
		'stock' => ['type' => 'object', 'oname' => 'stock'], // 物品
		'quantity' => ['type' => 'double', 'null' => FALSE, 'default' => 0], // 数量
		'user' => ['type' => 'object', 'oname' => 'user'], // 领用人
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], // 创建时间
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], // 修改时间
		'note' => ['type'=>'text', 'null'=>TRUE], // 备注
		],
	'indexes' => [
		'stock' => ['fields' => ['stock']],
		'user' => ['fields' => ['user']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
		],
	];

/*
$config['requisition_item'] = array(
	'fields' => array(
		'stock' => array('type' => 'object', 'oname' => 'stock'), // 物品
		'quantity' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // 数量
		'requisition' => array('type' => 'object', 'oname' => 'requisition'), // 领用单
		'ctime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 创建时间
		'mtime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 修改时间
		),
	'indexes' => array(
		'stock' => array('fields' => array('stock')),
		'requisition' => array('fields' => array('requisition')),
		),
	);

$config['requisition'] = array(
	'fields' => array(
		'requisition_by' => array('type' => 'object', 'oname' => 'user'), // 领用人
		'approver' => array('type' => 'object', 'oname' => 'user'), // 批准人
		'ctime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 创建时间
		'mtime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0), // 修改时间
		'note' => array('type'=>'text', 'null'=>TRUE), // 备注
		),
	'indexes' => array(
		'requisition_by' => array('fields' => array('requisition_by')),
		'approver' => array('fields' => array('approver')),
		'ctime'=>array('fields'=>array('ctime')),
		'mtime'=>array('fields'=>array('mtime')),
		),
	);
*/

/*
  xiaopei.li@2011.03.01 旧的存货
*/
/*
$config['stock'] = array(
	'fields' => array(
		'catalog_no'=>array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'name'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'manufacturer'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'vendor'=>array('type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'unit'=>array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'unit_price'=>array('type'=>'double', 'null'=>FALSE, 'default'=>0),
		'quantity'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'lab'=>array('type'=>'object', 'oname'=>'lab'),
		'note'=>array('type'=>'varchar(250)', 'null'=>TRUE),
		'location'=>array('type'=>'varchar(250', 'null'=>FALSE, 'default'=>''),
		'barcode'=>array('type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''),
		'ctime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
		'mtime'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
	),
	'indexes' => array(
		'catalog_no'=>array('fields'=>array('catalog_no')),
		'manufacturer'=>array('fields'=>array('manufacturer')),
		'vendor'=>array('fields'=>array('vendor')),
		'quantity'=>array('fields'=>array('quantity')),
		'location'=>array('fields'=>array('location')),
		'lab'=>array('fields'=>array('lab')),
		'ctime'=>array('fields'=>array('ctime')),
		'mtime'=>array('fields'=>array('mtime')),
	),

);
*/
