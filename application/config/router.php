<?php
$config['api'] = [
    // 'regxp' => ['path' => 'api/v1', 'file' => 'v1', 'params' => ['baz']]
    '/^api\/v1\/binding\/(?P<deviceIdentifier>[^\/]+)/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['deviceIdentifier']
    ],
    '/^api\/v1\/current-log\/(?P<equipmentId>[^\/]+)/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/equipment\/(?P<equipmentId>[^\/]+)/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/device-state\/(?P<deviceIdentifier>[^\/]+)/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['deviceIdentifier']
    ],
    // '/^api\/v1\/device-state\/(?P<deviceIdentifier>[^\/]+)/i' => [
    //     'path' => 'api/v1',
    //     'file' => 'v1',
    //     'params' => ['deviceIdentifier']
    // ],
    '/^api\/v1\/equipment\/(?P<equipmentId>[^\/]+)\/sample-schema/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/equipment\-sample\/(?P<sampleId>[^\/]+)$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['sampleId']
    ],
    '/^api\/v1\/equipment\/(?P<equipmentId>[^\/]+)\/booking-schema/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/equipment\-booking\/(?P<bookingId>[^\/]+)$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['bookingId']
    ],
    '/^api\/v1\/equipment\/(?P<equipmentId>[^\/]+)\/log-schema/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/equipment-announcement\/(?P<announcementId>[^\/]+)/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['announcementId']
    ],
    '/^api\/v1\/equipment-state\/(?P<equipmentId>[^\/]+)/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/equipment\/(?P<equipmentId>[^\/]+)$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^api\/v1\/equipment/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
    ],
    '/^api\/v1\/log\/(?P<logId>[^\/]+)$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['logId']
    ],
    '/^api\/v1\/task\/(?P<taskId>[^\/]+)\/complete$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['taskId']
    ],
    '/^api\/v1\/user\/(?P<userId>[^\/]+)\/permissions$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['userId']
    ],
    '/^api\/v1\/role/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
    ],
    '/^api\/v1\/billing-equipments/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
    ],
    '/^api\/v1\/group\/(?P<groupId>[^\/]+)\/children$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['groupId']
    ],
    '/^api\/v1\/group\/(?P<groupId>[^\/]+)$/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['groupId']
    ],
    '/^api\/v1\/group/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
    ]
];

$config['equipment'] = [
    '/^equipment\/api\/v1\/announcement-permission?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/announcement\/(?P<announcementId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['announcementId']
    ],
    '/^equipment\/api\/v1\/announcement(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/binding\/(?P<deviceIdentifier>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['deviceIdentifier']
    ],
    '/^equipment\/api\/v1\/binding(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/booking\/(?P<bookingId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['bookingId']
    ],
    '/^equipment\/api\/v1\/booking(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/reserv-permission\/(?P<reservId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['reservId']
    ],
    '/^equipment\/api\/v1\/charge(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/current-log\/(?P<equipmentId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['equipmentId']
    ],
    '/^equipment\/api\/v1\/(?P<equipmentId>[^\/]+)\/(feedback|log|sample|booking)-schema/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['equipmentId']
    ],
    '/^equipment\/api\/v1\/(?P<equipmentId>[^\/]+)\/log-user/i' => [
        'path' => 'api/v1',
        'file' => 'v1',
        'params' => ['equipmentId']
    ],
    '/^equipment\/api\/v1\/(following|filters|list)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/log\/(?P<logId>[^\/]+)\/feedback/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['logId']
    ],
    '/^equipment\/api\/v1\/log\/(?P<logId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['logId']
    ],
    '/^equipment\/api\/v1\/log-permission\/(?P<logId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['logId']
    ],
    '/^equipment\/api\/v1\/log(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/(sample|sample-permission)\/(?P<sampleId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['sampleId']
    ],
    '/^equipment\/api\/v1\/sample(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/(accept|permission|state|stat)\/(?P<equipmentId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['equipmentId']
    ],
    '/^equipment\/api\/v1\/task\/(?P<taskId>[^\/]+)\/complete/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['taskId']
    ],
    '/^equipment\/api\/v1\/task(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/training\/(?P<trainingId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['trainingId']
    ],
    '/^equipment\/api\/v1\/training(s)?/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
    ],
    '/^equipment\/api\/v1\/(?P<equipmentId>[^\/]+)/i' => [
        'path' => 'equipment/api',
        'file' => 'api',
        'params' => ['equipmentId']
    ],
];

$config['billing'] = [
    '/^billing\/api\/v1\/stat?/i' => [
        'path' => 'billing/api',
        'file' => 'api',
    ],
    '/^billing\/api\/v1\/account(s)?/i' => [
        'path' => 'billing/api',
        'file' => 'api',
    ],
    '/^billing\/api\/v1\/department(s)?/i' => [
        'path' => 'billing/api',
        'file' => 'api',
    ],
    '/^billing\/api\/v1\/transaction(s)?/i' => [
        'path' => 'billing/api',
        'file' => 'api',
    ],
];

$config['user'] = [
    '/^user\/api\/v1\/list/i' => [
        'path' => 'user/api',
        'file' => 'api',
    ],
];

$config['message'] = [
    '/^message\/api\/v1\/list/i' => [
        'path' => 'message/api',
        'file' => 'api',
    ],
    '/^message\/api\/v1\/(?P<messageId>[^\/]+)/i' => [
        'path' => 'message/api',
        'file' => 'api',
        'params' => ['messageId']
    ],
];

$config['nfs'] = [
    '/^nfs\/api\/v1\/lite\/(?P<objectName>[^\/]+)\/(?P<objectId>[^\/]+)\/(?P<pathType>[^\/]+)/i' => [
        'path' => 'nfs/api',
        'file' => 'api',
        'params' => ['objectName', 'objectId', 'pathType']
    ],
];


$config['authx-card'] = [
    '/^authx-card\/api\/v1/i' => [
        'path' => 'authx/card',
        'file' => 'card',
    ]
];

$config['authx-face'] = [
    '/^authx-face\/api\/v1/i' => [
        'path' => 'authx/face',
        'file' => 'face',
    ]
];
