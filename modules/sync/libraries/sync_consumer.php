<?php

class Sync_Consumer
{
    public static function dispatch($data)
    {
        if ($data['platform'] == LAB_ID) {
            return true;
        }

        if ($data['object'] == 'relationship') {
            $r1 = O($data['r1'], ['uuid' => $data['uuid1']]);
            $r2 = O($data['r2'], ['uuid' => $data['uuid2']]);

            if ($r1->id && $r2->id) {
                $syncObj = new Sync_Relationship($r1, $r2);
                $syncObj->handle($data);
            }
            return true;
        }

        $object = O($data['object'], ['uuid' => $data['uuid']]);
        if ($object->id && $object->version > $data['version']) {
            return true;
        }

        if ($object->id && $data['method'] == 'delete') {
            $object->delete();
        } elseif ($data['method'] == 'save') {
            if (!$object->id) {
                $object->uuid = $data['uuid'];
            }
            $object->version  = $data['version'];
            $object->platform = $data['platform'];
            $syncObj          = new Sync($object);
            $syncObj->handle($data['payload']);
        }
        return true;
    }
}
