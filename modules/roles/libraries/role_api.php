<?php
class Role_API
{
    public static function Perms_get($e, $params, $data, $query)
    {
        $user_id = $params[0];
        $u = O('user', $user_id);
        $data = [];
        if ($u->id) {
            foreach ($query['modules'] as $module) {
                $perms = Config::get("perms.{$module}", []);
                unset($perms['#name']);
                unset($perms['#icon']);
                foreach ($perms as $key => $value) {
                    if ($key[0] == '-') continue;
                    if ($u->access($key, $skip)) {
                        $data[] = ['name' => $key];
                    }
                }
            }
        }
        $ret = [];
        $ret["permissions"] = $data;
        $e->return_value = $ret;
        return;
    }

    public static function role_list($e, $params, $data, $query)
    {
        $st = (int)$query['st'] ?: 1;
        if ($st < 1) {
            throw new Exception('st must greater than 1', 400);
        }
        $pp = (int)$query['pp'] ?: 10;
        if ($pp < 1) {
            throw new Exception('pp must greater than 1', 400);
        }
        $roles = Q('role')->limit($st, $pp);

        $info = [];

        if (count($roles)) {
            foreach ($roles as $role) {
                $info[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'extra' => $role->extra,
                    'weight' => $role->weight

                ];
            }
        }
        $e->return_value = new ArrayIterator($info);
        return false;
    }
}
