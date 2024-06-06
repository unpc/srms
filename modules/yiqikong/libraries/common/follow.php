<?php

class Common_Follow
{
    public static function create($data)
    {
        $me = O('user', $data['user_local']);
        if (!$me->id) throw new API_Exception;
        $object = O($data['source_name'], $data['source_id']);
        if (!$object->id) throw new API_Exception;
        $follow = O('follow',['user'=>$me,'object'=>$object]);
        $follow->user = $me;
        $follow->object = $object;
        return $follow->save();
    }

    public static function delete($data)
    {
        $me = O('user', $data['user_local']);
        if (!$me->id) throw new API_Exception;
        $object = O($data['source_name'], $data['source_id']);
        if (!$object->id) throw new API_Exception;
        $follow = O('follow',['user'=>$me,'object'=>$object]);
        $follow->delete();
    }

}
