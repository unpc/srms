<?php

// Key 为默认的 orm 对象名称
// need_workflow 为 orm 需要做工作流的开关限制
    // -- 可以通过 callback_func 直接方法调用来判断，也可以通过 hooks 钩子来实现

// steps 为 orm 工作流中的详细行进步骤
    // done rejected 为工作流中默认的两个流程
    // done 为通过，通过后流程结束
    // rejected 为最终驳回，驳回后流程也结束，相应 source 会进行删除清理, 仅备份保留快照信息

    // 流程中 action 方案，且对于的必带 pass 或 reject 两个动作, 并制定对应的 next 的路由步骤
        // pass / reject 中对应的 check 配置用于检查当前对象是否允许pass或者reject，可以通过 callback_func 直接方法调用来判断，也可以通过 hooks 钩子来实现
    // 流程中 title 为该流程标题
    // 流程中 styles 为该流程样式表链, 其中 class 为标签样式，style 为行内样式直接重写

$config['me_reserv'] = [
    'need_workflow' => [
        'callback_func' => 'ME_Reserv_Flow::need_workflow',
        // 'hooks' => 'me_reserv_model.need_workflow',
    ],
    'steps' => [
        'approve_incharge' => [
            'title' => '待负责人审核',
            'styles' => [
                'class' => 'label lable-warning',
                'style' => [
                    'color' => '#FFFFFF'
                ]
            ],
            'action' => [
                'pass' => [
                    'check' => [
                        'callback_func' => 'ME_Reserv_Flow::incharge_pass',
                        // 'hooks' => 'me_reserv_model.incharge_pass',
                    ],
                    'title' => '通过',
                    'next' => 'done'
                ],
                'reject' => [
                    'title' => '驳回',
                    'next' => 'rejected'
                ],
            ]
        ],
        'done' => [
            'title' => '已通过'
        ],
        'rejected' => [
            'title' => '已驳回'
        ]
    ]
];