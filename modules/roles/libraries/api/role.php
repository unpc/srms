<?php

class API_Role extends API_Common
{
    public function get($id = null, $module = null, $skip = true)
    {
        $this->_ready();

        $perms = Config::get("perms.{$module}", []);
        $u = O('user', $id);
        if (!$u->id || !$module) {
            return false;
        }
        unset($perms['#name']);
        unset($perms['#icon']);

        if (!is_array($module)) $module = [$module];
        $data = [];
        foreach ($module as $name) {
            $perms = Config::get("perms.{$name}");
            unset($perms['#name']);
            unset($perms['#icon']);
    
            foreach ((array) $perms as $key => $value) {
                if ($key[0] == '-') continue;
                $data[$key] = $u->access($key, $skip);
            }
        }

        return array_filter($data);
    }

    public function get_perms($id = null, $module = null, $skip = true)
    {
        $this->_ready();

        $perms = Config::get("perms.{$module}", []);
        $unoperms = Config::get('uno_perm');

        $u = O('user', $id);
        if (!$u->id || !$module) {
            return false;
        }
        unset($perms['#name']);
        unset($perms['#icon']);

        if (!is_array($module)) $module = [$module];
        $data = [];
        foreach ($module as $name) {
            $perms = Config::get("perms.{$name}");
            unset($perms['#name']);
            unset($perms['#icon']);
    
            foreach ($perms as $key => $value) {
                if ($key[0] == '-') continue;
                $uniq_key = isset($unoperms[$key]['key']) ? $unoperms[$key]['key'] : $key;
                if($u->access($key, $skip)){
                    $data[] = $uniq_key;
                }
            }
        }

        return array_filter($data);
    }

    public function get_roles($start = 0, $step = 100)
    {
        $roles = Q('role')->limit($start, $step);
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
        return $info;
    }
}
