<?php

class Analysis_Init {

    static function init_roles($e, $rest) {
        $rs = [
            Analysis::ROLE_ADMIN => '校级管理员', 
            Analysis::ROLE_PLATFORM => '平台负责人', 
            Analysis::ROLE_INCHARGE => '仪器负责人', 
            Analysis::ROLE_PI => '课题组负责人'
        ];
        
        $rest->post('permissions', [
            'form_params' => [
                'permissions' => $rs
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
    }

    static function init_group($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        $columns['name'] = ['name' => '名称', 'type' => 'string'];
        $columns['parent'] = ['name' => '父级', 'type' => 'int'];
        $columns['root'] = ['name' => '根级', 'type' => 'int'];
        // 前期不考虑对于多级别递归的增加

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'group',
                'name' => '组织机构情况表',
                'type' => 'info',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());

        $result = $result ? '成功' : '失败';
        echo "   \e[32m 组织机构情况表创建{$result} \e[0m\n";
    }

    static function init_equipment($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        $columns['name'] = ['name' => '名称', 'type' => 'string'];
        $columns['ref'] = ['name' => '编号', 'type' => 'string'];
        $columns['price'] = ['name' => '价格', 'type' => 'int'];
        $columns['group'] = ['name' => '组织机构', 'type' => 'string'];
        $columns['tag'] = ['name' => '仪器分类', 'type' => 'string'];
        $columns['model'] = ['name' => '仪器型号', 'type' => 'string'];
        $columns['cat'] = ['name' => '仪器分类号', 'type' => 'string'];
        $columns['manufacturer'] = ['name' => '生产厂家', 'type' => 'string'];
        $columns['status'] = ['name' => '仪器状态', 'type' => 'int'];
        $columns['charge_standard'] = ['name' => '收费标准', 'type' => 'string'];
        $columns['charge_type'] = ['name' => '收费类型', 'type' => 'string'];
        $columns['owner'] = ['name' => '机主', 'type' => 'int', 'associate' => 'user'];
        $columns['contact'] = ['name' => '联系人', 'type' => 'int', 'associate' => 'user'];
        $columns['purchased_date'] = ['name' => '购置时间', 'type' => 'datetime'];
        $columns['atime'] = ['name' => '入网时间', 'type' => 'datetime'];
        $columns['location'] = ['name' => '地理位置', 'type' => 'string'];
        $columns['location2'] = ['name' => '地理位置2', 'type' => 'string'];
        $columns['accept_sample'] = ['name' => '开放送样', 'type' => 'int'];
        $columns['accept_reserv'] = ['name' => '开放预约', 'type' => 'int'];

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'equipment',
                'name' => '仪器情况表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        
        $result = $result ? '成功' : '失败';
        echo "   \e[32m 仪器情况表创建{$result} \e[0m\n";
    }

    static function init_equipment_group($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'string'];
        $columns['equipment'] = ['name' => '仪器', 'type' => 'int'];
        $columns['group'] = ['name' => '组织机构', 'type' => 'int'];
        
        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'equipment_group',
                'name' => '仪器组织机构情况表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        
        $result = $result ? '成功' : '失败';
        echo "   \e[32m 仪器组织机构关系表创建{$result} \e[0m\n";
    }

    static function init_user($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        $columns['name'] = ['name' => '姓名', 'type' => 'string'];
        $columns['sex'] = ['name' => '性别', 'type' => 'string'];
        $columns['ref_no'] = ['name' => '学工号', 'type' => 'string'];
        $columns['phone'] = ['name' => '联系方式', 'type' => 'string'];
        $columns['email'] = ['name' => '邮箱', 'type' => 'string'];
        $columns['type'] = ['name' => '人员类型', 'type' => 'string'];
        $columns['group'] = ['name' => '组织机构', 'type' => 'int', 'associate' => 'group'];
        $columns['lab'] = ['name' => '课题组', 'type' => 'int', 'associate' => 'lab'];

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'user',
                'name' => '人员情况表',
                'type' => 'info',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());

        $result = $result ? '成功' : '失败';
        echo "   \e[32m 人员情况表创建{$result} \e[0m\n";
    }
    
    static function init_user_equipment($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'string'];
        $columns['user'] = ['name' => '用户', 'type' => 'int'];
        $columns['equipment'] = ['name' => '仪器', 'type' => 'int'];
        $columns['type'] = ['name' => '类型', 'type' => 'string'];
        
        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'user_equipment',
                'name' => '用户仪器情况表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        
        $result = $result ? '成功' : '失败';
        echo "   \e[32m 用户仪器关系表创建{$result} \e[0m\n";
    }
        
    static function init_user_group($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'string'];
        $columns['user'] = ['name' => '用户', 'type' => 'int'];
        $columns['group'] = ['name' => '组织机构', 'type' => 'int'];
        
        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'user_group',
                'name' => '用户组织机构情况表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        
        $result = $result ? '成功' : '失败';
        echo "   \e[32m 用户组织机构关系表创建{$result} \e[0m\n";
    }
    
    static function init_user_lab($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'string'];
        $columns['user'] = ['name' => '仪器', 'type' => 'int'];
        $columns['lab'] = ['name' => '课题组', 'type' => 'int'];
        $columns['type'] = ['name' => '类型', 'type' => 'string'];
        
        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'user_lab',
                'name' => '用户课题组关系表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        
        $result = $result ? '成功' : '失败';
        echo "   \e[32m 用户课题组关系表创建{$result} \e[0m\n";
    }

    static function init_lab($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        $columns['name'] = ['name' => '名称', 'type' => 'string'];
        $columns['owner'] = ['name' => '负责人', 'type' => 'int', 'associate' => 'user'];

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'lab',
                'name' => '课题组情况表',
                'type' => 'info',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());

        $result = $result ? '成功' : '失败';
        echo "   \e[32m 课题组情况表创建{$result} \e[0m\n";
    }
    
    static function init_lab_group($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'string'];
        $columns['lab'] = ['name' => '课题组', 'type' => 'int'];
        $columns['group'] = ['name' => '组织机构', 'type' => 'int'];

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'lab_group',
                'name' => '课题组组织机构关系表',
                'type' => 'info',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());

        $result = $result ? '成功' : '失败';
        echo "   \e[32m 课题组组织机构关系表创建{$result} \e[0m\n";
    }

    static function init_project($e, $rest) {
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        $columns['name'] = ['name' => '名称', 'type' => 'string'];
        // 前期不考虑对于多级别递归的增加

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'project',
                'name' => '项目情况表',
                'type' => 'info',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());

        $result = $result ? '成功' : '失败';
        echo "   \e[32m 项目情况表创建{$result} \e[0m\n";
    }
}
