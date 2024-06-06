<?php

/*
配置类型如下
product_name        产品名称
manufacturer        生产商
catalog_no          目录号
package             包装
model               型号
spec                规格
quantity            数量
vendor              供应商
unit_price          单价
price               总价
fare                运费
order_no            订单编号
requester           申购人
request_date        申购日期
request_note        申购备注
approver            确认人
approve_date        确认日期
approve_note        确认备注
purchaser           订购人
purchase_date       订购日期 
purchase_note       订购备注
receiver            收货人
receive_date        收货日期
receive_note        收货备注
canceler            取消人
cancel_date         取消日期
cancel_note         取消备注
stock               存货
expense             关联经费
source              对应源
status              订单状态
deliver_status      订单收货状态 
receive_address     收货配送地址  
receive_postcode    收货邮政编码
receive_email       收货电子邮箱
receive_phone       收货联系电话
*/

//申购 request
$config['orders']['request']['requires'] = [
    'product_name'=> true,
    'quantity'=> true,
];

//确认 confirm
$config['orders']['confirm']['requires'] = [
    'product_name'=> true,
    'quantity'=> true,
    'price'=> true,
];

//到货 receive
$confirm['orders']['receive']['requires'] = [
];

//修改 edit
$confirm['orders']['edit']['requires'] = [
    'product_name'=> true,
    'quantity'=> true,
    'price'=> true,
];

//补增 add
$confirm['orders']['add']['requires'] = [
    'product_name'=> true,
    'quantity'=> true,
    'price'=> true,
    'vendor'=> true,
    'receive_address'=> true,
    'receive_postcode'=> true,
    'receive_email'=> true,
    'receive_phone'=> true,
];
