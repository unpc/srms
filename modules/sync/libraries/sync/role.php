<?php
class Sync_Role extends Sync_Handler
{
    public static $publish_keys = [
        'name',
        'weight',
    ];

    public function uuid() {
        $default_roles = Config::get('roles.default_roles');
        $role = $this->object;
        foreach ($default_roles as $default_role) {
            if ($role->name == $default_role['name']) {
                return $default_role['name'];
            }
        }
        return uniqid(LAB_ID, true);
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return true;
    }

    public function should_save_publish($old_data, $new_data) {
        foreach (self::$publish_keys as $key) {
            if (isset($new_data[$key])) {
                if (is_object($new_data[$key]) && $new_data[$key]->id != $old_data[$key]->id) {
                    return true;
                } elseif (is_scalar($new_data[$key]) && $new_data[$key] != $old_data[$key]) {
                    return true;
                }
            }
        }
        return false;
    }

    public function format() {
        $role = $this->object;
        $params = [
            'name' => $role->name,
            'weight' => $role->weight,
        ];
        return $params;
    }

    public function handle($params) {
        $role = $this->object;
        $role->name = $params['name'];
        $role->weight = $params['weight'];
        $role->save();
    }
}
