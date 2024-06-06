<?php

class CLI_Gapper_Role
{
    public static function sync_role()
    {

        $total = -1;
        $page = 1;
        $perPage = 100;
        while ($total === -1 || $total >= ($page - 1) * $perPage) {
            $roles = Gateway::getRemoteRoles([
                'pp' => $perPage,
                'pg' => $page,
                // 'type' => ''
            ]);
            $total = $roles['total'];

            foreach ($roles['items'] as $remote_role) {
                if ($remote_role['key'] == 'lab_master') {
                    $role = O('role', ['weight' => ROLE_LAB_PI]);
                } else if ($remote_role['key'] == 'equipment_master') {
                    $role = O('role', ['weight' => ROLE_EQUIPMENT_CHARGE]);
                } else {
                    $role = O('role', ['gapper_id' => $remote_role['id']]);
                }
                $role->gapper_id = $remote_role['id'];
                $role->name = $remote_role['name'];
                $role->type = $remote_role['type'];
                if ($remote_role['description']) $role->description = $remote_role['description'];
                if ($remote_role['group_types']) $role->group_types = $remote_role['group_types'];
                if ($role->save()) {
                    Upgrader::echo_title("[{$role->id}]{$role->name}");
                }
            }
            $page++;
        }
        Upgrader::echo_success("Done.");
    }

    public static function sync_permission()
    {
        $total = -1;
        $page = 1;
        $perPage = 100;
        while ($total === -1 || $total >= ($page - 1) * $perPage) {
            $permissions = Gateway::getRemotePermissions([
                'pp' => $perPage,
                'pg' => $page,
                // 'type' => ''
            ]);
            $total = $permissions['total'];

            foreach ($permissions['items'] as $remote_permission) {
                if (!preg_match('/^lims/', $remote_permission['key'])) {
                    continue;
                }
                $permission = O('perm', ['gapper_key' => $remote_permission['key']]);
                $permission->gapper_key = $remote_permission['key'];
                $permission->name = $remote_permission['name'];
                if ($remote_permission['path']) $permission->path = $remote_permission['path'];
                if ($remote_permission['group_type']) $permission->group_type = $remote_permission['group_type'];
                if ($permission->save()) {
                    Upgrader::echo_title("[{$permission->id}]{$permission->name}");
                }
            }
            $page++;
        }
        Upgrader::echo_success("Done.");
    }

    public static function sync_role_permission()
    {
        foreach (Q("role[gapper_id>0]") as $role) {
            $remote = Gateway::getRemoteRolePermissions([
                'role_id' => $role->gapper_id,
            ]);

            $local_keys = array_values(Q("{$role} perm")->to_assoc('id', 'gapper_key'));
            $remote_keys = array_column($remote['permissions'], 'key');
            foreach (array_diff($local_keys, $remote_keys) as $key_to_delete) {
                $perm = O('perm', ['gapper_key' => $key_to_delete]);
                if (!$perm->id) {
                    continue;
                }
                $role->disconnect($perm);
                Upgrader::echo_title("[{$role->id}]{$role->name} disconnect [{$perm->id}]{$perm->name}");
            }
            foreach (array_diff($remote_keys, $local_keys) as $key_to_add) {
                $perm = O('perm', ['gapper_key' => $key_to_add]);
                if (!$perm->id) {
                    continue;
                }
                $role->connect($perm);
                Upgrader::echo_title("[{$role->id}]{$role->name} connect [{$perm->id}]{$perm->name}");
            }
        }
        Upgrader::echo_success("Done.");
    }

    public static function sync_user_role()
    {
        $groupRoot = Gateway::getRemoteGroupRoot();
        foreach (Q("user[gapper_id>0]") as $user) {
            $remote = Gateway::getRemoteUserRoles([
                'user_id' => $user->gapper_id,
                'group_id' => $groupRoot['id']
            ]);

            $local_ids = array_values(Q("{$user} role")->to_assoc('id', 'gapper_id'));
            $remote_ids = array_column($remote['roles'], 'role_id');
            foreach (array_diff($local_ids, $remote_ids) as $id_to_delete) {
                $role = O('role', ['gapper_id' => $id_to_delete]);
                if (!$role->id) {
                    continue;
                }
                $user->disconnect($role);
                Upgrader::echo_title("[{$user->id}]{$user->name} disconnect [{$role->id}]{$role->name}");
            }
            foreach (array_diff($remote_ids, $local_ids) as $id_to_add) {
                $role = O('role', ['gapper_id' => $id_to_add]);
                if (!$role->id) {
                    continue;
                }
                $user->connect($role);
                Upgrader::echo_title("[{$user->id}]{$user->name} connect [{$role->id}]{$role->name}");
            }
        }
        Upgrader::echo_success("Done.");
    }

    public static function test()
    {
        $remote = Gateway::postRemotePermission([
            'key' => 'tj-test',
            'name' => '天津测试权限',
            'path' => '权限/目录/是/什么',
            // 'group_type' => [system,organization,lab,area,building,room]
        ]);
        var_dump($remote);
    }
    public static function test2()
    {
        $remote = Gateway::deleteRemotePermission([
            'key' => 'tj-test',
        ]);
        var_dump($remote);
    }

    public static function push_role()
    {
        $roles = Q("role[weight>0]");
        $remote_group = Gateway::getRemoteGroupRoot();
        foreach ($roles as $role) {
            $perms = Q("{$role} perm")->to_assoc('id', 'gapper_key');
            $push_data = [
                'name' => $role->name,
                'group' => $remote_group['id'],
                'permissions' => $perms,
                'key' => PinYin::code($role->name) . $role->id,
            ];
            $role->gapper_id ? $push_data['id'] = $role->gapper_id : '';
            $result = Gateway::pushRemoteRole($push_data);
            $role->gapper_id = $result['id'];
            $role->save();
        }
        Upgrader::echo_success("Done.");
    }
    
}
