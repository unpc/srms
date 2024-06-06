<?php
use \Pheanstalk\Pheanstalk;

class Control_Baratheon
{
    private static function get_user_labs($user) {
        if ($GLOBALS['preload']['people.multi_lab']) {
            $lab_ids = [];
            foreach (Q("$user lab") as $lab) {
                $lab_ids[$lab->id] = $lab->name;
            }
            return $lab_ids;
        }
        else {
            $lab = Q("$user lab")->current();
            return [$lab->id => $lab->name];
        }
    }

    static function on_user_saved($e, $user, $old_data, $new_data) {
        if (count($new_data) == 1 && isset($new_data['input_user_select_role_id'])) return;
        
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        //获取到用户信息
        $tag = $user->group;
        $group = $tag->id ? [$tag->name] : NULL ;
        while($tag->parent->id && $tag->parent->root->id){
            array_unshift($group, $tag->parent->name);
            $tag = $tag->parent;
        }
        $roles = L('ROLES');
        $role_names = [];
        foreach ((array)$user->roles() as $rid) {
            $role = $roles[$rid];
            if ($role) {
                $role_names[$rid] = $role->name;
            }
        }
        $equipment_user_tags = [];

        $root = Tag_Model::root('equipment_user_tags');
        foreach (Q("{$user} {$root->name()}[root=$root]") as $item) {
            $equipment_user_tags[] = [
                "id" => $item->id,
                "source" => LAB_ID
            ];
        }
        $data = [
            'id' => $user->id,
            'token' => $user->token,
            'avatar' => $user->icon_url('128'),
            'name' => $user->name,
            'gender' => $user->gender,
            'member_type' => $user->member_type,
            'member_type_label' => $user->get_member_label($user->member_type),
            'group' => $group,
            'group_id' => $user->group->id ? : 0,
            'ref_no' => $user->ref_no,
            'major' => $user->major,
            'organization' => $user->organization,
            'dto' => $user->dto,
            'email' => $user->email,
            'phone' => $user->phone,
            'binding_phone'=>$user->binding_phone,
            'address' => $user->address,
            'card_no' => $user->card_no,
            'lab_id' => self::get_user_labs($user),
            'privacy' => $user->privacy,
            'lab_pi' => Q("$user<pi lab")->to_assoc('id', 'id'),
            'roles' => $role_names,
            'is_admin' => $user->access('管理所有内容') ? TRUE : FALSE,
            'creator' => $user->creator->name,
            'auditor' => $user->auditor->name,
            'atime' => $user->atime,
            'ctime' => $user->ctime,
            'source' => LAB_ID,
            'icon16_url' => $user->icon_url(16),
            'icon32_url' => $user->icon_url(32),
            'icon48_url' => $user->icon_url(48),
            'icon64_url' => $user->icon_url(64),
            'icon128_url' => $user->icon_url(128),
            'eq_user_tag' => $equipment_user_tags,
            'yiqikong_id' => $user->yiqikong_id
        ];
        $user_perms = $user->all_perms();
        if (in_array('查看门禁模块',$user_perms) && in_array('远程控制负责的门禁',$user_perms)){
            $data['is_door_admin'] = TRUE;
        }
        if (in_array('查看视频监控模块',$user_perms) || in_array('管理所有内容',$user_perms)){
            $data['is_vidcam_admin'] = TRUE;
        }
        if (in_array('查看负责仪器的视频监控',$user_perms)){
            $data['is_incharge_vidcam_admin'] = TRUE;
        }
        Event::trigger('user.extra.keys', $user, $data);
        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "v2/cuser",
            'body' => $data
        ];
        $mq
            ->useTube('control_user')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_user_deleted($e, $user){
        if (!Config::get('lab.modules')['app']) return TRUE;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "v2/cuser",
            'body' => [
                'id' => $user->id,
                'source' => LAB_ID
            ]
        ];
        $mq
            ->useTube('control_user')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

}