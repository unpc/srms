<?php
$config['domain_url'] = 'http://uno.test.gapper.in/';
$config['domain'] = $config['domain_url'] . 'gapper/';
$config['api']    = [
    'device_auth' => 'gateway/api/v1/auth/owner',
    'verify' => 'gateway/api'
];
$config['server'] = [
    'url'         => $config['domain'] . 'gateway/api/v1/',
    'refresh_url' => $config['domain'] . 'gateway/oauth/server/token',
    'params'      => [
        'client_id'     => 'lims',
        'client_secret' => 'c088c68c66bf6b83ed48fa768d737438',
    ],
];

$config['application'] = [
    'client_id'     => 'lims',
    'client_secret' => 'c088c68c66bf6b83ed48fa768d737438',
    'name' => '大型仪器管理系统',
    'shortName' => '大仪平台',
    'url' => 'http://127.0.0.1/lims/',
];

//cli绑定用户key，最先匹配原则，匹配到则停止
$config['bind_gapper_keys'] = [
    'ref_no',
    'email',
];

/**
 * @param path 请求路径
 * @param method 请求方式 GET | POST
 * @param expires_in 缓存时间 单位:minutes | true => 单次内存变量缓存
 */

// 楼宇、房间
$config['getBuildingList'] = ['path' => 'buildings', 'method' => 'get', 'expires_in' => 5];
$config['getBuildingRooms'] = ['path' => 'building/{BUILDING_ID}/rooms', 'method' => 'get', 'expires_in' => 5];
$config['getAppToken'] = ['path' => 'auth/app-token', 'method' => 'post'];

// 取房间资源类型列表
$config['getRoomresourcetypeList'] = ['path' => 'room-resource-type', 'method' => 'get'];
// 推送房间资源类型
$config['getRoomresourcetype'] = ['path' => 'room-resource-type', 'method' => 'post'];
// 房间资源数据推送
$config['postRoomResources'] = ['path' => 'room-resources', 'method' => 'post'];
$config['getRoomResources'] = ['path' => 'room/{ROOM_ID}/resources', 'method' => 'get', 'expires_in' => 5];
$config['pushRoomresource'] = ['path' => 'room-resources', 'method' => 'post'];
$config['deleteRoomresource'] = ['path' => 'room/{ROOM_ID}/resource/{RESOURCE_TYPE}/{RESOURCE_ID}', 'method' => 'delete'];

// 人员信息
$config['verifyAuth'] = ['path' => 'auth/user-token', 'method' => 'post'];
$config['getRemoteUser'] = ['path' => 'users', 'method' => 'get'];
$config['getRemoteUserDetail'] = ['path' => 'user/{USER_ID}', 'method' => 'get'];

// 分组信息
$config['getRemoteGroupRoot'] = ['path' => 'group/root', 'method' => 'get'];
$config['getRemoteGroupDetail'] = ['path' => 'group/{GROUP_ID}', 'method' => 'get'];
$config['getRemoteGroupChildren'] = ['path' => 'group/{GROUP_ID}/children', 'method' => 'get'];
$config['getRemoteGroupDescendants'] = ['path' => 'group/{GROUP_ID}/descendants', 'method' => 'get'];
$config['getRemoteGroupTypes'] = ['path' => 'group-types', 'method' => 'get'];
// $config['getRemoteGroupRoles'] = ['path' => 'group/{GROUP_ID}/roles', 'method' => 'get'];
$config['getRemoteRoles'] = ['path' => 'roles', 'method' => 'get'];
$config['getRemotePermissions'] = ['path' => 'permissions', 'method' => 'get'];
$config['getRemoteRolePermissions'] = ['path' => 'role/{ROLE_ID}/permissions', 'method' => 'get'];
$config['getRemoteUserRoles'] = ['path' => 'user/{USER_ID}/roles', 'method' => 'get'];
$config['getRemoteUserPermissions'] = ['path' => 'user/{USER_ID}/permissions', 'method' => 'get'];
$config['getRemoteUserGroups'] = ['path' => 'user/{USER_ID}/groups', 'method' => 'get'];
$config['postRemotePermission'] = ['path' => 'permission', 'method' => 'post'];
$config['deleteRemotePermission'] = ['path' => 'permission/{KEY}', 'method' => 'delete'];
$config['postRemoteRole'] = ['path' => 'role', 'method' => 'post'];
$config['postRemoteUserGroupRoles'] = ['path' => 'user/{USER_ID}/group-roles', 'method' => 'post'];
$config['deleteRemoteUserGroupRoles'] = ['path' => 'user/{USER_ID}/group-roles', 'method' => 'delete'];
$config['postCustomField'] = ['path' => 'custom-field', 'method' => 'post'];
//push
$config['pushRemoteGroup'] = ['path' => 'group', 'method' => 'post'];
$config['pushRemoteLab'] = ['path' => 'group', 'method' => 'post'];
$config['pushRemoteRole'] = ['path' => 'role', 'method' => 'post'];
$config['pushRemoteUser'] = ['path' => 'user', 'method' => 'post'];
$config['pushRemoteUserGroup'] = ['path' => 'user/{USER_ID}/groups', 'method' => 'post'];
$config['pushRemoteUserRole'] = ['path' => 'user/{USER_ID}/group-roles', 'method' => 'post'];

//地理位置
$config['getLocationArea'] = ['path' => 'areas', 'method' => 'get'];
$config['getLocationAreaBuildings'] = ['path' => 'area/{AREA_ID}/buildings', 'method' => 'get'];
$config['getLocationAreaBuildingRooms'] = ['path' => 'building/{BUILDING_ID}/rooms', 'method' => 'get'];

$lims_path = 'lims';
$lims_url = $config['domain_url'] . $lims_path;
$config['entries_url'] = $config['domain_url'] . 'uno-auth/?gpui=/uno/gpui.webview.js&login=' . $lims_url.'/api/v1/uno&state=';

/*
$config['entries'] = [
    'billing.list' => [
        'redirect' => $lims_url  . "/!billing?uno=1",
        'title' => '财务中心'
    ],
    'equipment.list' => [
        'redirect' => $lims_url . "/!equipments/index?uno=1",
        'title' => '仪器目录'
    ],
    'eq_ban.list' => [
        'redirect' => $lims_url . "/!eq_ban?uno=1",
        'title' => ' 黑名单'
    ],
    'people.list' => [
        'redirect' => $lims_url . "/!people/list/index?uno=1",
        'title' => '成员目录'
    ],
    'labs.list' => [
        'redirect' => $lims_url . "/!labs/index?uno=1",
        'title' => '课题组'
    ],
    'messages.list' => [
        'redirect' => $lims_url . "/!messages?uno=1",
        'title' => '消息中心'
    ],
    'nfs_share.list' => [
        'redirect' => $lims_url . "/!nfs_share?uno=1",
        'title' => '文件系统'
    ],
    'roles.list' => [
        'redirect' => $lims_url . "/!roles?uno=1",
        'title' => '权限管理'
    ]
];
*/
$config['entries'] = [];

$config['mobiHome'] = "http://uno.demo.genee.cn/logon-ui/mobi";
$config['mobiEntries'] = [
    "follow" => [
        "title" =>  "我的关注",
        "uri" => $config['mobiHome'] . "/#/follow",
        "permissions" => [
            "lsi-v1-charge-room-safe"
        ]
    ],
    "equipments" => [
        "title" =>  "仪器列表",
        "uri" => $config['mobiHome'] . "/#/equipments",
    ],
    "my-reservation" => [
        "title" =>  "我的预约",
        "uri" => $config['mobiHome'] . "/#/my-reservation",
    ],
    "my-record" => [
        "title" =>  "个人记录",
        "uri" => $config['mobiHome'] . "/#/my-record",
        "permissions" => [
            "lsi-v1-charge-room-safe"
        ]
    ],
    "approve" => [
        "title" => "仪器审批",
        "uri" => $config['mobiHome'] . "/#/approve"
    ],
    "group-accounts" => [
        "title" => "财务账号",
        "uri" => $config['mobiHome'] . "/#/group-accounts"
    ],
    "group-record" => [
        "title" => "组内记录",
        "uri" => $config['mobiHome'] . "/#/group-record"
    ]
];