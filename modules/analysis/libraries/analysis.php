<?php

require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;

class Analysis {
    
    const ROLE_ADMIN = 1;
    const ROLE_PLATFORM = 2;
    const ROLE_INCHARGE = 3;
    const ROLE_PI = 4;
    
    static function people_extra_keys($e, $user, $info) {
        $info['permissions'] = [
            self::ROLE_ADMIN => (bool)$user->access('管理所有内容'), 
            self::ROLE_PLATFORM => (bool)$user->access('添加/修改下属机构的仪器'), 
            self::ROLE_INCHARGE => (bool)Q("{$user}<incharge equipment")->total_count(), 
            self::ROLE_PI => (bool)Q("lab[owner={$user}]")->total_count()
        ];

        return TRUE;
    }

    static function mark($e, $object, $old = [], $new = []) {
        if ($old && $new && !array_diff($old, $new)) return TRUE;
        return self::save($object);
    }
    
    static function mark_before($e, $source) {
        $object = O($source->name(), $source->id);
        return self::save($object);
    }

    private static function save($object) {
        // 取出各个记录中的键
        switch ($object->name()) {
            case 'eq_sample':
                $user = $object->sender;
                $equipment = $object->equipment;
                break;
            case 'eq_reserv':
            case 'eq_record':
                $user = $object->user;
                break;
        }

        $equipment = $object->equipment;
        $project = $object->project->type;

        // 对产生任何改动的数据做记录
        $time = Date::get_day_start();
        $date = Date::get_day_start($object->dtend);
        $mark = O('analysis_mark', [
            'user' => $user,
            'equipment' => $equipment,
            'project' => !is_null($project) ? $project : -1
        ]);
        
        if (!$mark->id || $mark->time != $time) {
            $mark->user = $user;
            $mark->equipment = $equipment;
            $mark->project = !is_null($project) ? $project : -1;
            $mark->date = $date;
            $mark->time = $time;
            $mark->save();
        }

        //保存数据到analysis_mark_desc
        $markDescObj = O('analysis_mark_desc',['source_id'=>$object->id,'source_name'=>$object->name()]);
        $markDescObj->source_id=$object->id;
        $markDescObj->source_name=$object->name();
        $markDescObj->ctime = time();
        $markDescObj->save();
        //end

        return TRUE;
    }

    static function full_group($e, $rest) {
        $root = Tag_Model::root('group');
        $groups = Q("tag_group[root=$root]");
        $data = [
            [
                'id' => $root->id,
                'name' => $root->name,
                'parent' => $group->parent->id,
                'root' => $group->root->id
            ]
        ];

        foreach ($groups as $group) {
            $row = [];
            $row['id'] = $group->id;
            $row['name'] = $group->name;
            $row['parent'] = $group->parent->id;
            $row['root'] = $group->root->id;
            $data[] = $row;
        }

        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'group',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 组织机构[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }
    
    static function full_equipment($e, $rest) {
        $root = Tag_Model::root('equipment');
        if (Module::is_installed('sync')) {
            $lab = LAB_ID;
            $equipments = Q("equipment[!platform|platform={$lab}]");
        } else {
            $equipments = Q("equipment");
        }
        $data = [];

        $charge_standard = [
            'free' => '免费使用',
            'time_reserv_record' => '智能计费',
            'only_reserv_time' => '预约时间',
            'custom_reserv' => '自定义',
            'record_time' => '按使用时间',
            'record_times' => '按使用次数',
            'record_samples' => '按样品数',
            'custom_record' => '自定义',
            'advanced_custom' => '高级自定义',
        ];

        foreach ($equipments as $equipment) {
            $tags = Q("{$equipment} tag_equipment[root=$root]:limit(0,1)")->to_assoc('id', 'name');

            $row = [];
            $row['id'] = $equipment->id;
            $row['name'] = $equipment->name;
            $row['ref'] = $equipment->ref_no;
            $row['price'] = $equipment->price;
            $row['group'] = $equipment->group->id;
            $row['tag'] = implode(',', $tags);
            $row['model'] = $equipment->model_no;
            $row['cat'] = $equipment->cat_no;
            $row['manufacturer'] = $equipment->manufacturer;
            $row['status'] = $equipment->status;
            $row['charge_standard'] = $charge_standard[$equipment->charge_template['reserv']];
            $row['charge_type'] = $equipment->charge_setting['reserv']['*']['unit_price'];
            $row['owner'] = Q("{$equipment} user.incharge")->current()->id;
            $row['contact'] = Q("{$equipment} user.contact")->current()->id;
            $row['purchased_date'] = date('Y-m-d H:i:s', $equipment->purchased_date);
            $row['atime'] = date('Y-m-d H:i:s', $equipment->atime);
            $row['location'] = $equipment->location;
            $row['location2'] = $equipment->location2;
            $row['accept_sample'] = $equipment->accept_sample;
            $row['accept_reserv'] = $equipment->accept_reserv;
            $data[] = $row;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'equipment',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 仪器[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    static function full_equipment_group($e, $rest) {
        $data = [];
        $db = Database::factory();
        $root = Tag_Model::root('group');
        $sql = "SELECT `id1` AS `group`, `id2` AS `equipment` FROM `_r_tag_group_equipment` AS `r` ".
            " LEFT OUTER JOIN `tag_group` ON (`r`.`id1` = `tag_group`.`id`) WHERE `tag_group`.`root_id` = {$root->id} ";
        $rows = $db->query($sql)->rows();

        if (count($rows)) foreach($rows as $row) {
            $item = [];
            $item['id'] = $row->equipment . '_' . $row->group;
            $item['equipment'] = $row->equipment;
            $item['group'] = $row->group;
            $data[] = $item;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'equipment_group',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 仪器组织机构关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    static function full_user($e, $rest) {
        if (Module::is_installed('sync')) {
            $lab = LAB_ID;
            $users = Q("user[!platform|platform={$lab}]");
        } else {
            $users = Q("user");
        }
        $data = [];

        foreach ($users as $user) {
            $row = [];
            $row['id'] = $user->id;
            $row['name'] = $user->name;
            $row['sex'] = $user->sex;
            $row['ref_no'] = $user->ref_no;
            $row['phone'] = $user->phone;
            $row['email'] = $user->email;
            $row['type'] = $user->type;
            $row['group'] = $user->group->id;
            $row['lab'] = $user->lab->id ? : Q("$user lab")->current()->id;
            $data[] = $row;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'user',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 用户[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }
    
    static function full_user_equipment($e, $rest) {
        $data = [];
        $db = Database::factory();
        $sql = "SELECT `id1` AS `user`, `id2` AS `equipment`, `type` FROM `_r_user_equipment`";
        $rows = $db->query($sql)->rows();

        if (count($rows)) foreach($rows as $row) {
            $item = [];
            $item['id'] = $row->user . '_' . $row->equipment . '_' . $row->type;
            $item['user'] = $row->user;
            $item['equipment'] = $row->equipment;
            $item['type'] = $row->type;
            $data[] = $item;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'user_equipment',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 用户仪器关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }
    
    static function full_user_group($e, $rest) {
        $data = [];
        $db = Database::factory();
        $sql = "SELECT `id1` AS `user`, `id2` AS `group` FROM `_r_user_tag_group`";
        $rows = $db->query($sql)->rows();

        if (count($rows)) foreach($rows as $row) {
            $item = [];
            $item['id'] = $row->user . '_' . $row->group;
            $item['user'] = $row->user;
            $item['group'] = $row->group;
            $data[] = $item;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'user_group',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 用户组织机构关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }
    
    static function full_user_lab($e, $rest) {
        $data = [];
        $db = Database::factory();
        $sql = "SELECT `id1` AS `user`, `id2` AS `lab`, `type` FROM `_r_user_lab`";
        $rows = $db->query($sql)->rows();

        if (count($rows)) foreach($rows as $row) {
            $item = [];
            $item['id'] = $row->user . '_' . $row->lab;
            $item['user'] = $row->user;
            $item['lab'] = $row->lab;
            $item['type'] = $row->type;
            $data[] = $item;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'user_lab',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 用户课题组关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }
    
    static function full_lab($e, $rest) {
        if (Module::is_installed('sync')) {
            $lab = LAB_ID;
            $labs = Q("lab[!platform|platform={$lab}]");
        } else {
            $labs = Q("lab");
        }
        $data = [];

        foreach ($labs as $lab) {
            $row = [];
            $row['id'] = $lab->id;
            $row['name'] = $lab->name;
            $row['owner'] = $lab->owner->id;
            $data[] = $row;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'lab',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 课题组[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }
    
    static function full_lab_group($e, $rest) {
        $data = [];
        $db = Database::factory();
        $sql = "SELECT `id1` AS `group`, `id2` AS `lab` FROM `_r_tag_group_lab`";
        $rows = $db->query($sql)->rows();

        if (count($rows)) foreach($rows as $row) {
            $item = [];
            $item['id'] = $row->lab . '_' . $row->group;
            $item['group'] = $row->group;
            $item['lab'] = $row->lab;
            $data[] = $item;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'lab_group',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 课题组组织机构关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    static function full_project($e, $rest) {
        $projects = Lab_Project_Model::$types;
        $data = [];

        foreach ($projects as $key => $project) {
            $row = [];
            $row['id'] = $key;
            $row['name'] = $project;
            $data[] = $row;
        }
        
        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'project',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 项目[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    public static function delete($e, $source)
    {
        $table_name = $source->name();
        $client_id = Config::get('analysis.application')['client_id'];
        $rest = self::rest('godiva');
        $response = $rest->delete("v1/table/{$client_id}_{$table_name}/records?record_id={$source->id}", [
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $content = trim($body->getContents(), "\n");
        Log::add("[delete] {$name} delete {$record->id} done", 'analysis');
    }

    private static function rest ($type='app') {
        $rest = Config::get('rest.analysis')[$type];
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout']]);
        return $client;
    }

}
