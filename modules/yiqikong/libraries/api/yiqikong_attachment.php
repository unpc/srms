<?php

class API_YiQiKong_Attachment extends API_Common
{

    public function get_attachments($source_name, $source_id)
    {
        if (!$source_id || !$source_name) return [];

        $object = O($source_name, $source_id);
        if (!$object->id) return [];

        $full_path = NFS::get_path($object);
        $files = NFS::file_list($full_path);
        if (empty($files)) return [];

        $response = [];

        foreach ($files as $file) {
            $tmp = [];
            $tmp['name'] = $file['name'];
            $tmp['type'] = $file['type'];
            $tmp['url'] = H(URI::url('!nfs/nfs_lite/index.' . $object->name() . '.' . $object->id . '.attachments', ['path' => $file['path']]));
            $response[] = $tmp;
        }

        return $response;
    }

    public function delete_attachment($source_name, $source_id, $name)
    {
        if (!$source_id || !$source_name || !$name) return false;

        $object = O($source_name, $source_id);
        if (!$object->id) return false;

        $full_path = NFS::get_path($object);
        $files = NFS::file_list($full_path);
        if (empty($files)) return false;

        $response = false;

        foreach ($files as $file) {
            if ($file['name'] == $name) {
                @File::delete($full_path . $file['name']);
                $response = true;
            }
        }

        return $response;
    }

    public function update_index($object_name, $object_id, $path)
    {
        $object = O($object_name, $object_id);
        if (!$object->id) return true;

        $path_name = NFS::fix_path($path);

        Search_NFS::update_nfs_indexes($object, $path_name, 'attachments');
        return true;
    }

    public function delete_index($object_name, $object_id, $path)
    {
        $object = O($object_name, $object_id);
        if (!$object->id) return true;

        $path_name = NFS::fix_path($path);

        Search_NFS::delete_nfs_indexes($object, $path_name, 'attachments');
        return true;
    }
}

