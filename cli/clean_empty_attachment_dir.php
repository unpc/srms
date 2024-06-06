#!/usr/bin/env php


<?php

/*
 * @file       clean_empty_attachment_dir.php
 * @author     Rui Ma<rui.ma@geneegroup.com> 
 * @date       2011.11.1
 *
 * usage: sudo -u www-data SITE_ID=cf LAB_ID=test ./clean_empty_attachment_dir.php
 *
 */

require 'base.php';

$to_clean_object_array = [
                    'award',
                    'publication',
                    'patent',
                    'equipment',
                    'eq_record',
                    'eq_sample',
                    'cal_component',
                    'stock',
                    'tn_note'

                ];

foreach($to_clean_object_array as $oname) {

    $onames = Q($oname);
    
    foreach($onames as $o){
        $a_path = NFS::get_path($o, '', 'attachments', TRUE);
        if(clean_empty_dir($a_path)) {
            echo T("清除空目录 %path \n", ['%path'=>$a_path]);
        } 
    }

}


//清空目录
function clean_empty_dir($dir) {

    
    //假设当前目录下都是隐藏文件
    $all_hidden_file = TRUE;
    $file_count = 0;
    if($handle = @opendir($dir)) {
        while(false != ($file = readdir($handle))) {
            $file_count ++;
            if($file != '.' && $file != '..' && !preg_match('/^\.\w+/', $file)) {
                $all_hidden_file = FALSE;
                return FALSE;
            }
        }
        
        //如果文件数量和为2，也就说当前目录下只有.和..两个目录，即该目录为空
        if ($file_count == 2 || $all_hidden_file) {
            File::rmdir($dir);
            return TRUE;
        }
    }
    else {
        if(is_dir($dir)) {
            File::rmdir($dir);
            return TRUE;
        } 
    }
    return FALSE;
}

