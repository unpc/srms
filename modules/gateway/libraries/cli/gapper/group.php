<?php

class CLI_Gapper_Group
{
    public static function sync_lab()
    {
        $groupRoot = Gateway::getRemoteGroupRoot();

        $total = -1;
        $page = 1;
        $perPage = 100;
        while ($total === -1 || $total >= ($page - 1) * $perPage) {
            $labs = Gateway::getRemoteGroupDescendants([
                'group_id' => $groupRoot['id'],
                'pp' => $perPage,
                'pg' => $page,
                'type' => 'lab'
            ]);
            $total = $labs['total'];

            foreach ($labs['items'] as $remote_lab) {
                $lab = O('lab', ['gapper_id' => $remote_lab['id']]);
                $lab->gapper_id = $remote_lab['id'];
                $lab->name = $remote_lab['name'];
                $lab->atime = $lab->id ? $lab->atime : time();
                $lab->description = $remote_lab['description'];
                $creator = O('user', ['gapper_id' => $remote_lab['creator_id']]);
                if ($creator->id) $lab->creator = $creator;
                $group = O('tag_group',['gapper_id'=>$remote_lab['parent_id']]);
                if ($group->id){
                    $lab->group = $group;
                }
                if ($lab->save()) {
                    $group->connect($lab);
                    Upgrader::echo_title("[{$lab->id}]{$lab->name}");
                }
            }
            $page++;
        }
        Upgrader::echo_success("Done.");
    }

    public static function sync_group()
    {
        $groupRoot = Gateway::getRemoteGroupRoot();

        $total = -1;
        $page = 1;
        $perPage = 100;
        while ($total === -1 || $total >= ($page - 1) * $perPage) {
            $groups = Gateway::getRemoteGroupDescendants([
                'group_id' => $groupRoot['id'],
                'pp' => $perPage,
                'pg' => $page,
                'type' => 'organization',
            ]);
            $total = $groups['total'];

            foreach ($groups['items'] as $remote_group_info) {
                $parent = self::group_save_recursion($remote_group_info);
            }

            $page++;
        }
        $conf_id_name = 'gapper_system_group_id';
        $id = Lab::get($conf_id_name);
        $root = O('tag_group', ['id'=>$id]);
        if ($root->id) {
            $groupRoot = Gateway::getRemoteGroupRoot();
            Lab::set($conf_id_name, (int)$groupRoot['id']);
        }

        Upgrader::echo_success("Done.");
    }

    private static function group_save_recursion($info)
    {
        if (!$info['id']) return;
        $root = Tag_Model::root('group');

        $group = O('tag_group', ['gapper_id' => $info['id']]);
        $group->gapper_id = $info['id'];
        $group->name = $info['name'];
        $group->type = $info['type'];
        $group->root = $root;
        if ($info['code']) $group->code = $info['code'];
        if ($info['description']) $group->description = $info['description'];

        $parent_info = Gateway::getRemoteGroupDetail(['group_id' => $info['parent_id']]);
        $parent =  O('tag_group', ['gapper_id' => $info['parent_id']]);
        if (!$parent->id) {
            $parent = self::group_save_recursion($parent_info);
        }
        $group->parent = $parent;
        if ($group->save()) {
            $parent_arr = [$group->name];
            while ($parent->id != $root->id) {
                array_unshift($parent_arr, $parent->name);
                $parent = $parent->parent;
            }
            $str = join(" >> ", $parent_arr);
            Upgrader::echo_title("{$str}: [{$group->id}]{$group->name}");
        }
        return $group;
    }

    public function test()
    {
        // $groupTypes = Gateway::getRemoteGroupTypes();
        // $groupChildren = Gateway::getRemoteGroupChildren([
        //     'group_id' => $groupRoot['id'],
        //     'pp' => 100,
        //     'pg' => 1,
        //     // 'type' =>
        // ]);
        $groupDetail = Gateway::getRemoteGroupDetail(['group_id' => 10001587]);
    }

    public static function push_group()
    {
        try {
            $remote_group = Gateway::getRemoteGroupRoot();
            $group_root = Tag_Model::root('group');
            self::push_group_node($group_root, $remote_group['id']);
            Upgrader::echo_success("Done.");
        } catch (Exception $e) {

        }
    }

    public static function push_group_node($node, $parent_id)
    {
        $childrens = Q("tag_group[!gapper_id][parent={$node}]");
        foreach ($childrens as $children) {
            $push_data = [
                'name' => $children->name,
                'type' => 'organization',
            ];
            if ($parent_id) $push_data['parent_id'] = $parent_id;
            $children->gapper_id ? $push_data['id'] = $children->gapper_id : '';
            $result = Gateway::pushRemoteGroup($push_data);
            $children->gapper_id = $result['id'];
            $children->save();
            self::push_group_node($children, $result['id']);
        }
    }

    public static function push_lab()
    {
        try {
            $remote_group = Gateway::getRemoteGroupRoot();
            $labs = Q("lab[atime][!gapper_id]");
            foreach ($labs as $lab) {
                $group_gapper_id = $lab->group->gapper_id ?: 0;
                $push_data = [
                    'name' => $lab->name,
                    'type' => 'lab',
                    'parent_id' => $remote_group['id'],
                ];
                $group_gapper_id ? $push_data['parent_id'] = $group_gapper_id : '';
                $lab->gapper_id ? $push_data['id'] = $lab->gapper_id : '';
                $result = Gateway::pushRemoteLab($push_data);
                $lab->gapper_id = $result['id'];
                $lab->save();
            }
        } catch (Exception $e) {

        }
        Upgrader::echo_success("Done.");
    }
    
}
