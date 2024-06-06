<?php
class Sync_Lab extends Sync_Handler
{
    public static $publish_keys = [
        'creator',
        'auditor',
        'owner',
        'ref_no',
        'name',
        'description',
        'rank',
        'ctime',
        'mtime',
        'atime',
        'name_abbr',
        'contact',
        'group',
        'hidden',
        'location',
        'location2',
        'type',
        'util_area',
        'subject',
    ];

    public function uuid() {
        $lab = $this->object;
        if ($lab->id == Lab_Model::default_lab()->id) {
            return 'Lab_Model::default_lab()';
        }
        elseif ($lab->id == Equipments::default_lab()->id) {
            return 'Equipments::default_lab()';
        } else {
            return uniqid(LAB_ID, true);
        }
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
        $lab = $this->object;
        $params = [
            'creator_id' => $lab->creator->uuid,
            'auditor_id' => $lab->auditor->uuid,
            'owner_id' => $lab->owner->uuid,
            'ref_no' => $lab->ref_no,
            'name' => $lab->name,
            'description' => $lab->description,
            'rank' => $lab->rank,
            'ctime' => $lab->ctime,
            'mtime' => $lab->mtime,
            'atime' => $lab->atime,
            'name_abbr' => $lab->name_abbr,
            'contact' => $lab->contact,
            'group_id' => $lab->group->uuid,
            'hidden' => $lab->hidden,
            'location' => $lab->location,
            'location2' => $lab->location2,
            'type' => $lab->type,
            'util_area' => $lab->util_area,
            'subject' => $lab->subject,
        ];
        return $params;
    }

    public function handle($params) {
        $lab = $this->object;
        $creator = O('user', ['uuid' => $params['creator_id']]);
        $lab->creator = $creator;
        $auditor = O('user', ['uuid' => $params['auditor_id']]);
        $lab->auditor = $auditor;
        $owner = O('user', ['uuid' => $params['owner_id']]);
        $lab->owner = $owner;
        $lab->ref_no = $params['ref_no'];
        $lab->name = $params['name'] ? : '';
        $lab->description = $params['description'];
        $lab->rank = $params['rank'] ? : 0;
        $lab->ctime = $params['ctime'] ? : 0;
        $lab->mtime = $params['mtime'] ? : 0;
        $lab->atime = $params['atime'] ? : 0;
        $lab->name_abbr = $params['name_abbr'];
        $lab->contact = $params['contact'] ? : '';
        $group = O('tag_group', ['uuid' => $params['group_id']]);
        if ($group->id) $lab->group = $group;
        $lab->hidden = $params['hidden'] ? : 0;
        $lab->location = $params['location'];
        $lab->location2 = $params['location2'];
        $lab->type = $params['type'];
        $lab->util_area = $params['util_area'];
        $lab->subject = $params['subject'];

        if ($lab->save()) {
            if ($group->id) {
                $group->connect($lab);
            }
        }
    }
}
