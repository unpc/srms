<?php
class Sync_Utils
{
    public static function orm_model_call_url($e, $object, $url, $query, $fragment)
    {
        if ($object->name() == 'equipment' && $object->platform && $object->platform != LAB_ID) {
            $url = Config::get('sync.sites')[$object->platform]['url'].'/'
                . str_replace($object->id, $object->source_id, $url);
            $e->return_value = URI::url($url, $query, $fragment);
        }
    }

    static function equipment_api_extra ($e, $equipment, $data) {
        $data['uuid'] = $equipment->uuid;
        $data['group_uuid'] = $equipment->group->uuid ? $equipment->group->uuid : '';

        $tag = $equipment->group;
        $group_uuid_info = $tag->id ? [$tag->uuid => $tag->name] : [] ;
        while($tag->parent->id && $tag->parent->root->id){
            $group_uuid_info += [$tag->parent->uuid => $tag->parent->name];
            $tag = $tag->parent;
        }
        $data['group_uuid_info'] = $group_uuid_info;

        $root = Tag_Model::root('equipment');
        $tags = Q("{$equipment} tag[root=$root][uuid]")->to_assoc('uuid', 'name');
        $data['tag_uuid_info'] = $tags;

        $e->return_value = $data;
    }

    static function user_extra_keys($e, $user, $info)
    {
        $info['uuid'] = $user->uuid;
    }

    static function lab_extra_keys($e, $lab, $data) {
        $data['uuid'] = $lab->uuid;
        $data['group_uuid'] = $data['group']->uuid ? $data['group']->uuid : '';
        $data['creator_uuid'] = $data['creator_id'] ? O('user',$data['creator_id'])->uuid : '';
        $data['auditor_uuid'] = $data['auditor_id'] ? O('user',$data['auditor_id'])->uuid : '';
        $data['owner_uuid'] = $data['owner_id'] ? O('user',$data['owner_id'])->uuid : '';
    }
}
