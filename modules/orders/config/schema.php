<?php

$config['stock']['fields']['order'] = ['type'=>'object', 'oname'=>'order']; // 存货对应订单

/* xiaopei.li@2011.02.15 新的订单*/
$config['order'] = [
	'fields' => [
		'product_name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], /* 产品名称 */
		'manufacturer'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], /* 生产商 */
		'catalog_no'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''], /* 目录号 */
		'package'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], /* 包装 */
		'model' => ['type'=>'varchar(80)', 'null' => FALSE, 'default' => ''], //型号
		'spec'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''], /* 规格 */
		'quantity'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 数量 */
		'vendor'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''], /* 供应商 */
		'unit_price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0], /* 单价 */
		'price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0], /* 总价 */
		'fare'=>['type'=>'double', 'null'=>TRUE, 'default'=>0], /* 运费 */
		'order_no'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''], /* 订单编号 */
		'requester'=>['type'=>'object', 'oname'=>'user'], /* 申购人 */
		'request_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 申购日期 */
		'request_note'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''], /* 申购备注 */
		'approver'=>['type'=>'object', 'oname'=>'user'], /* 确认人 */
		'approve_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 确认日期 */
		'approve_note'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''], /* 确认备注 */
		'purchaser'=>['type'=>'object', 'oname'=>'user'], /* 订购人 */
		'purchase_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 订购日期 */
		'purchase_note'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''], /* 订购备注 */
		'receiver'=>['type'=>'object', 'oname'=>'user'], /* 收货人 */
		'receive_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 收货日期 */
		'receive_note'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''], /* 收货备注 */
		/* xiaopei.li@2011.02.22 是否记录撤销log? */
		'canceler'=>['type'=>'object', 'oname'=>'user'],
		'cancel_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'cancel_note'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''],
		'stock'=>['type'=>'object', 'oname'=>'stock'],
		'expense'=>['type'=>'object', 'oname'=>'grant_expense'],
		'source'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''],
		'status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0], /* 订单状态 */
		'deliver_status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0], /* 订单状态 */
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 创建时间 */
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], /* 修改时间 */
		],
	'indexes' => [
		'product_name'=>['fields'=>['product_name']],
		'manufacturer'=>['fields'=>['manufacturer']],
		'catalog_no'=>['fields'=>['catalog_no']],
		'package'=>['fields'=>['package']],
		'vendor'=>['fields'=>['vendor']],
		'source_order_no'=>['fields'=>['order_no', 'source']],
		'requester'=>['fields'=>['requester']],
		'request_date'=>['fields'=>['request_date']],
		'purchaser'=>['fields'=>['purchaser']],
		'purchase_date'=>['fields'=>['purchase_date']],
		'receiver'=>['fields'=>['receiver']],
		'receive_date'=>['fields'=>['receive_date']],
		'status'=>['fields'=>['status']],
		'deliver_status'=>['fields'=>['deliver_status']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
		],
	];

$config['mall_order'] = [
    'fields'=>[
        'order'=>['type'=>'object', 'oname'=>'order'],
        'item_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'product_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'remote_order_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'status'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
        'source'=> ['type'=> 'varchar(50)', 'null'=> FALSE, 'default'=> ''],
        'link'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''],
    ],
    'indexes'=>[
        'source_item_id'=>['fields'=>['source', 'item_id','product_id'], 'type'=>'unique'],
        'status'=> ['fields'=> ['status']],
    ],
];

$config['mall_user'] = [
    'fields'=>[
        'source'=>['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''],
        'user'=>['type'=>'object', 'oname'=>'user'],
        'source_uid'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes'=>[
        'source_uid'=>['fields'=>['source_uid']],
        'source'=>['fields'=>['source']],
        'unique'=> ['fields'=> ['source_uid', 'source'], 'type'=> 'unique']
    ],
];
