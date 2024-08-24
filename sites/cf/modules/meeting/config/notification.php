<?php
$config['classification']['meeting_incharge']['#module']=  'meeting';
$config['classification']['meeting_incharge']['#enable_callback'] = 'Meetings::notif_classification_enable_callback';
$config['classification']['meeting_incharge']['#name'] = '会议室负责人';

$config['classification']['meeting_incharge']["meeting\004负责会议室被预约相关消息提醒"][] = 'meeting.meeting_room_be_reserved';
$config['classification']['meeting_incharge']["meeting\004负责会议室预约被修改相关消息提醒"][] = 'meeting.component_be_edited'; 
$config['classification']['meeting_incharge']["meeting\004负责会议室预约被删除相关消息提醒"][] = 'meeting.meeting_component_delete';
$config['classification']['meeting_incharge']["meeting\004负责会议室授权相关消息提醒"][] = 'meeting.apply_user_auth';
$config['classification']['meeting_incharge']["meeting\004负责会议室授权相关消息提醒"][] = 'meeting.user_apply_approved';
$config['classification']['meeting_incharge']["meeting\004负责会议室授权相关消息提醒"][] = 'meeting.delete_user_auth';
$config['classification']['meeting_incharge']["meeting\004负责会议室授权相关消息提醒"][] = 'meeting.apply_rejected';

//申请授权通知负责人
$config['meeting.apply_user_auth'] = [
    'description'=>'设置用户申请授权使用会议室的提醒消息',
    'title'=>'提醒: 有人申请您负责会议室的授权',
    'body'=>'%incharge,您好: \n\n%user申请授权使用您负责的会议室%meeting.  \n如需查看, 详细地址链接如下: \n%link\n',
    'i18n_module'=>'meeting',
    'strtr'=>[
        '%incharge'=>'管理员姓名',
        '%user'=>'用户姓名',
        '%meeting'=>'会议室名称',
        '%link'=>'链接地址',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],        
    ],
];
//通过申请授权通知用户
$config['meeting.user_apply_approved'] = [
    'description'=>'设置用户通过会议室授权的提醒消息',
    'title'=>'提醒: 您通过了会议室%meeting的授权',
    'body'=>'%user,您好: \n\n您通过了会议室%meeting的授权申请',
    'i18n_module'=>'meeting',
    'strtr'=>[
        '%user'=>'用户姓名',
        '%meeting'=>'会议室名称',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],        
    ],
];
//删除申请授权通知用户
$config['meeting.delete_user_auth'] = [
    'description'=>'设置负责人删除会议室授权的提醒消息',
    'title'=>'提醒: 您在会议室%meeting的授权已经被删除',
    'body'=>'%user,您好: \n\n您在会议室%meeting的授权申请已经被删除',
    'i18n_module'=>'meeting',
    'strtr'=>[
        '%user'=>'用户姓名',
        '%meeting'=>'会议室名称',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],        
    ],
];
//拒绝用户授权申请通知用户
$config['meeting.apply_rejected'] = [
    'description'=>'设置负责人拒绝会议室授权申请的消息提醒',
    'title'=>'提醒: 您的会议室培训申请被拒绝!',
    'body'=>'%user, 您好: \n\n您向会议室 %meeting 提交的授权申请被会议室管理员 %incharge 拒绝. ',
    'i18n_module' => 'meeting',
    'strtr'=>[
        '%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%meeting'=> '会议室名称',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];
//删除预约时, 
$config['meeting.disconnect_meeting_confirmed'] = [
    'description'=>'设置用户删除已关联日程的会议室预约后的提醒消息',
    'title'=>'提醒: 您日程中关联的会议室预约已删除! ',
    'body'=>'%user, 您好: \n\n您在日程%schedule中关联的会议室预约%meeting已经被删除! ',
    'i18n_module' =>'meeting',
    'strtr'=>[
        '%user'=> '用户姓名',
        '%schedule'=> '日程主题',
        '%meeting'=> '会议室名称'
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

//创建预约通知创建人
$config['meeting.meeting_room_be_reserved'] = [
    'description'=>'设置用户预约会议室成功发送通知信息',
    'title'=>'提醒: 有人申请使用会议室%meeting',
    'body'=>'您好: 用户 %user 预约了会议室 %meeting. \n预约主题 %title \n预约时间为 %dtstart - %dtend. \n如需查看, 详细地址链接如下:\n%link\n',
    'i18n_module' =>'meeting',
    'strtr'=>[
        '%incharge' => '管理员姓名',
        '%user' => '用户姓名',
        '%meeting' => '会议室名称',
        '%title' => '预约主题',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
        '%link' => '链接地址',
        '%user_phone' => '用户电话',
        '%user_email' => '用户电子信箱',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];


$config['meeting.component_be_edited'] = [
    'description'=>'设置用户修改会议室预约成功发送给联系人通知信息',
    'title'=>'提醒: %user修改会议室%meeting预约',
    'body'=>'您好: \n用户 %user 修改了会议室 %meeting的预约 . \n 预约标题为 %title \n预约时间为 %dtstart - %dtend. \n如需查看, 详细地址链接如下:\n%link\n',
    'i18n_module' =>'meeting',
    'strtr'=>[
        '%user' => '用户姓名',
        '%meeting' => '会议室名称',
        '%title' => '预约主题',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
        '%link' => '链接地址',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['meeting.lab_component_be_edited'] = [
    'description'=>'设置用户修改课题组日程关联预约会议室成功发送通知信息',
    'title'=>'提醒: 会议室 %meeting的预约已经修改了',
    'body'=>'您好: \n用户 %user 修改了会议室: %meeting的预约 . \n预约主题 %title\n预约时间为 %dtstart - %dtend. \n如需查看, 详细地址链接如下:\n%link\n',
    'i18n_module' =>'meeting',
    'strtr'=>[
        '%user' => '修改者姓名',
        '%meeting' => '会议室名称',
        '%title' => '预约主题',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['meeting.lab_component_be_reserved'] = [
    'description'=>'设置用户新建课题组日程关联预约会议室成功发送给用户通知信息',
    'title'=>'提醒: 您的会议室 %meeting的有新的预约申请',
    'body'=>'您好: \n用户 %user 申请了会议室: %meeting的预约 . \n预约主题 %title\n预约时间为 %dtstart - %dtend. \n如需查看, 详细地址链接如下:\n%link\n',
    'i18n_module' =>'meeting',
    'strtr'=>[
        '%user' => '用户姓名',
        '%meeting' => '会议室名称',
        '%title' => '预约主题',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];


$config['meeting.meeting_component_delete'] = [
    'description'=>'设置用户删除会议室预约后的提醒消息',
    'title'=>'提醒: 会议室 %meeting有预约被删除',
    'body'=>'您好: \n会议室%meeting有预约已经被删除! \n预约主题 %title\n预约时间为 %dtstart - %dtend.  \n如需查看, 详细地址链接如下:\n%link',
    'i18n_module' =>'meeting',
    'strtr'=>[
        '%user'=> '用户姓名',
        '%meeting'=> '会议室名称',
        '%title' => '预约主题',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];
