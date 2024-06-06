<?php

class Teach_Download_Controller extends Controller
{
    function index($id)
    {
        if (!Module::is_installed('nfs')) URI::redirect('error/404');
        $equipment = O('equipment', $id);
        if ($equipment->id) {
            //获取附件
            $path_type = 'attachments';
            $path = NFS::fix_path(Input::form('path')); /* 用户点击的路径，只应为文件不应为目录 */
            $full_path = NFS::get_path($equipment, $path, $path_type, TRUE);
            $files = NFS::file_list($full_path, $path);
            foreach ($files as $f) {
                $attachments[] = $f['name'];
            }

            echo V('equipments:equipment/teach_download', [
                'object' => $equipment,
                'path_type' => $path_type,
                'attachments' => $attachments,
            ]);
        }else{
            echo 'no file';
        }
    }

    function download(){
        $id = Input::form('id');
        $path = Input::form('path');
        $equipment = O('equipment', $id);
        $path_type = 'attachments';
        $full_path = NFS::get_path($equipment, $path, $path_type, TRUE);
        Downloader::download($full_path, TRUE);
    }
}
