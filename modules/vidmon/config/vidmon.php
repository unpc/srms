<?php
// 打成 phar 包后, bin 会失效, 故需放到指定的源码目录
// $config['snapshot_bin_path'] = '/usr/share/lims2/cli/vidmon/';
// deprecated(xiaopei.li@2012-11-16)

// 定时截图存储路径
// 若修改该路径, 需同时修改 debian_backup 中 big_backup()
$config['capture_path'] = LAB_PATH.PRIVATE_BASE.'vidcam/capture/';

// 另外应加有用户在系统中监控的 preview 的间隔 (xiaopei.li@2013-07-31)
// $config['preview_capture_duration'] = 2;

//定时截图时间间隔, 单位s
$config['capture_duration'] = 10;

//定时检测是否上传图片间隔, 单位s
$config['upload_duration'] = 30;

//报警后捕获/上传截图时间间隔，单位s
$config['alarmed_capture_duration'] = 5;

//报警后加速捕获/上传截图持续时间,单位s(报警后持续多久快速截图)
$config['alarmed_capture_timeout'] = 30;

$config['online_capture_duration'] = 2;

$config['online_capture_timeout'] = 30;

//alarm点保存capture单侧时间范围，单位秒(报警前多久的图片标为报警)
$config['alarm_capture_time'] = 30;

//alarm点缩略图尺寸
$config['thumbnail_width'] = 88;
$config['thumbnail_height'] = 64;

//capture最大存活时间， 默认先设定2个月
$config['capture_max_live_time'] = 86400;

//发送capture命令后，上传capture图片的路径
// $config['capture_upload_url'] = 'http://172.17.42.1/lims/!vidmon/snapshot/upload.%vidcam_id';

//实时监控摄像头的时候capture时间间隔，单位为毫秒
$config['preview_capture_timeout'] = 2000;

//用于配置多拍监控时发送ajax请求的url地址
$config['snapshot_get_url'] = 'http://cf.labscout.cn/test/get_snapshot.php';

$config['history_capture_url'] = 'http://172.17.42.1/lims/!vidmon/snapshot/history.%vidcam_id';

$config['vidmon_server'] = [
    'client_id' => 'c344742f-9b64-4010-8a73-2457b20ab953',
    'client_secret' => '6ee67faf-3586-4779-894b-b231948aabd3'
];
