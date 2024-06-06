<?php

class Sync_Utils
{
    public static function orm_model_call_url($e, $object, $url, $query, $fragment)
    {
        switch ($object->name()) {
            case 'equipment':
            case 'user':
            case 'lab':
                // if ($object->source_name && $object->source_name != LAB_ID && strpos($url, 'edit') !== false) {
                if ($object->source_name && $object->source_name != LAB_ID) {
                    //$query['oauth-sso'] = 'sync.' . $object->source_name;
                    $url             = Config::get('source')[$object->source_name]['url'] . str_replace($object->id, $object->source_id, $url);
                    $e->return_value = URI::url($url, $query, $fragment);
                }
                break;
        }
    }
}
