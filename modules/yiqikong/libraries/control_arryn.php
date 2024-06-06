<?php
use \Pheanstalk\Pheanstalk;

class Control_Arryn
{
    static function update_user_info($user){
        $user->update_user_info_time = time();
        $user->save();
    }

    static function get_tag_table(){
        $tags_table = Config::get('tag');
        $tables = [];
        foreach ($tags_table as $key => $value) {
            $tables[] = $key;
        }
        return $tables;
    }

    static function on_tag_saved($e, $object, $old_data, $new_data) {
        $tag_type = str_replace("tag_", "", $object->name());
        if (!in_array($tag_type, self::get_tag_table())) return TRUE;
        if ("tag_$tag_type" != $object->name()) return TRUE;
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source' => LAB_ID,
            'id' => $object->id,
            'name' => $object->name,
            'parent_id' => $object->parent_id,
            'root' => $object->root_id,
            'weight' => $object->weight,
            'ctime' => $object->ctime,
            'path' => $object->path,
            'tag_type' => $tag_type
        ];
        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "tag",
            'body' => $data
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_tag_deleted($e, $object){
        $tag_type = str_replace("tag_", "", $object->name());
        if (!in_array($tag_type, self::get_tag_table())) return TRUE;
        if ("tag_$tag_type" != $object->name()) return TRUE;
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source' => LAB_ID,
            'id' => $object->id,
            'tag_type' => $tag_type
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "tag",
            'body' => $data
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_lab_saved($e, $lab, $old_data, $new_data) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $tag = $lab->group;
        $group = $tag->id ? [$tag->name] : null ;
        while($tag->parent->id && $tag->parent->root->id){
            array_unshift($group, $tag->parent->name);
            $tag = $tag->parent;
        }

        $data = new ArrayIterator([
            'creator_id' => $lab->creator_id,
            'auditor_id' => $lab->auditor_id,
            'name_abbr' => $lab->name_abbr,
            'icon16_url' => $lab->icon_url(16),
            'icon32_url' => $lab->icon_url(32),
            'icon48_url' => $lab->icon_url(48),
            'icon64_url' => $lab->icon_url(64),
            'icon128_url' => $lab->icon_url(128),
            'id' => $lab->id,
            'source' => LAB_ID,
            'name' => $lab->name,
            'group' => $group,
            'textbook' => $lab->group->id ? : 0,
            'group_id' => $lab->group->id,
            'contact' => $lab->contact,
            'ref_no' => $lab->ref_no,
            'type' => $lab->type,
            'subject' => $lab->subject,
            'util_area' => $lab->util_area,
            'location' => $lab->location,
            'location2' => $lab->location2,
            'owner'=> $lab->owner->name,
            'owner_id'=> $lab->owner->id,
            'description' => $lab->description,
            'atime' => $lab->atime,
            'ctime' => $lab->ctime
        ]);
        Event::trigger('lab.extra.keys', $lab, $data);

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "lab",
            'body' => $data
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_lab_deleted($e, $object){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source' => LAB_ID,
            'id' => $object->id
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "lab",
            'body' => $data
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));
        return TRUE;

    }

    static function on_user_tag_connect($e, $tag, $user, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'id' => $user->id,
            'source' => LAB_ID,
            "eq_user_tag" => [
                ["id" => $tag->id, "source" => LAB_ID]
            ]
        ];

        $data['tag_type'] = str_replace("tag_", "", $tag->name());

        $path = "";
        switch ($data['tag_type']) {
            case "equipment_user_tags":
                $path = "equsertag/user";break;
        }

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  $path,
            'body' => $data
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));

        self::update_user_info($user);
        return TRUE;
    }

    static function on_user_tag_disconnect($e, $tag, $user, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'id' => $user->id,
            'source' => LAB_ID,
            "tag" => $tag->id
        ];

        $data['tag_type'] = str_replace("tag_", "", $tag->name());


        $path = "";
        switch ($data['tag_type']) {
            case "equipment_user_tags":
                $path = "equsertag/user";break;
        }

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  $path,
            'body' => $data
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));
        self::update_user_info($user);
        return TRUE;
    }

    static function on_user_role_saved($e, $user, $add_roles, $substract_roles) {
        self::post_tag($user);
        self::update_user_info($user);
        return TRUE;
    }

    static function on_user_role_perm_saved($e, $role) {
        $users = Q("$role user");
        foreach ($users as $user) {
            self::post_tag($user);
            self::update_user_info($user);
        }
    }

    static function post_tag($user) {
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        list($token, $backend) = explode('|', $user->token);
        $tag_name = [];
        if ($token == 'genee') $tag_name[] = 'genee';
        if ($user->access('管理所有内容')) $tag_name[] = 'admin';
        if (count($tag_name) > 0 ) {
            $method = "post";
            $path = "user/tag";
            $body = [
                'id' => $user->id,
                'source' => LAB_ID,
                'tag_name' => $tag_name,
                'tag_type' => 'role',
                'user_id' => $user->yiqikong_id
            ];
        } else {
            $method = "delete";
            $path = "user/tag";
            $body = [
                'id' => $user->id,
                'source' => LAB_ID,
                'tag_type' => 'role',
            ];
        }

        $payload = [
            'method' => $method,
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => $path,
            'body' => $body,
        ];
        $mq
            ->useTube('tag')
            ->put(json_encode($payload, TRUE));
    }

}