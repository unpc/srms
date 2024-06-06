<?php

class API_Db_Sync extends API_Common
{

    public function follow($user_id, $object_id, $object_name)
    {
        $this->_ready();

        $user   = O('user', $user_id);
        $object = O($object_name, $object_id);
        $user->follow($object);
        return true;
    }

    public function unfollow($user_id, $object_id, $object_name)
    {
        $this->_ready();

        $user    = O('user', $user_id);
        $object  = O($object_name, $object_id);
        $follows = Q("follow[object={$object}][user={$user}]");
        $follows->delete_all();
        return true;
    }

    public function getRealIcon($object_name, $object_id)
    {
        $this->_ready();
        if (!$object_name || !$object_id) return false;
        $object  = O($object_name, $object_id);
        //128的图片不存在说明没有上传图片
        if (!$object->id || !$object->icon_file('128')) return false;

        $icon_info = [];
        if ($object->icon_file('real')) {
            $icon_info['iconreal_url'] = Config::get('system.base_url') . Cache::cache_file($object->icon_file('real'));
        }
        $icon_info['icon128_url'] = $object->icon_url('128');
        return $icon_info;
    }
}
