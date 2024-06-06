<?php
/*
user_signup 用户注册表单
初次注册和事后编辑所用视图有所不同，但均根据该配置参数做相应处理
	token 登录账号
	passwd 密码
	confirm_passed 确认密码
	name 姓名
	gender 性别
	member_type 人员类型
	group_id 组织机构
	lab_id 实验室
	ref_no 学号/工号
	mentor_name  导师姓名
	personal_phone 个人手机
	major 专业
	organization 单位名称
	email 电子邮箱
	address 地址
    time 所在时间
    lab_signup 实验室注册表单
	name 实验室名称
	lab_contact 联系方式
	group_id 组织机构
	pi_name 姓名
	pi_token 账号
	pi_email 邮箱
	pi_phone 实验室项目信息
*/
$config['user_signup']['requires'] = [
    'token'=>TRUE,
    'passwd'=>TRUE,
    'confirm_passwd'=>TRUE,
    'name'=>TRUE,
    'gender'=>FALSE,
    'member_type'=>TRUE,
    'group_id'=>FALSE,
    'lab_id'=>TRUE,
    'ref_no'=>FALSE,
    'major'=>FALSE,
    'organization'=>FALSE,
    'lab'=> TRUE, //默认为TRUE
    'time'=>FALSE,
    'email'=>TRUE,
    'phone'=>TRUE,
    'address'=>FALSE,
];


$config['lab_signup']['requires'] = [
    'name'=>TRUE,
    'lab_contact'=>TRUE,
    'group_id'=>FALSE,
    'pi_name'=>TRUE,
    'pi_token'=>TRUE,
    'passwd'=>FALSE,
    'pi_email'=>TRUE,
    'pi_phone'=>TRUE,
    'project'=>TRUE,
];

//修改页面
$config['user_signup_edit'] = $config['user_signup'];

$config['validate.card_no.start'] = 6;
$config['validate.card_no.end'] = 10;
