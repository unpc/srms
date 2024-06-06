<?php
class Sync_Tag extends Sync_Handler
{
    public static $publish_keys = [
        'name',
        'parent',
        'ctime',
        'weight',
        'code'
    ];

    public function uuid() {
		$conf_id_name = 'tag.group_id';
        $tag = $this->object;

        $id = Lab::get($conf_id_name);
        if ($id == $tag->id) {
            return $conf_id_name;
        } elseif ($tag->root->id == $id) {
            return uniqid(LAB_ID, true);
        }
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return true;
    }

    public function should_save_publish($old_data, $new_data) {
        $root = Tag_Model::root('group');
        if ($this->object->root->id != $root->id) {
            return false;
        }
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
        return [
            'name' => $this->object->name,
            'name_abbr' => $this->object->name_abbr,
            'parent_id' => $this->object->parent->uuid,
            'root_id' => $this->object->root->uuid,
            'readonly' => $this->object->readonly,
            'ctime' => $this->object->ctime,
            'mtime' => $this->object->mtime,
            'weight' => $this->object->weight,
            'code' => $this->object->code
        ];
    }

    public function handle($params) {
        $tag = $this->object;
        $tag->name = $params['name'] ? : '';
        $tag->name_abbr = $params['name_abbr'];
        $parent = O('tag', ['uuid' => $params['parent_id']]);
        $tag->parent = $parent;
        $root = O('tag', ['uuid' => $params['root_id']]);
        $tag->root = $root;
        $tag->readonly = $params['readonly'] ? : 0;
        $tag->ctime = $params['ctime'] ? : 0;
        $tag->mtime = $params['mtime'] ? : 0;
        $tag->weight = $params['weight'] ? : 0;
        $tag->code = $params['code'] ? : '';
        $tag->save();
    }
}
