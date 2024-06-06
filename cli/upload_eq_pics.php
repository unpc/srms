<?php
require './base.php';

$dir = $argv[1] ?? die("usage: SITE_ID=xx LAB_ID=xxx php upload_eq_pics.php /path/to/pics\n");

File::traverse($dir, "upload_one_pic");

/**
 * 上传单张仪器图片
 *
 * @param [string] path/to/eq_pic.jpg
 * @return void
 */
function upload_one_pic($path)
{
    if (is_dir($path)) {
        return;
    }
    $name = File::basename($path);
    list($ref, $ext) = explode('.', $name, 2);
    $ref = str_replace('P', '', $ref);
    $equipment = O('equipment', ['ref_no' => $ref]);
    if (!$equipment->id) {
        echo "未找到{$ref}编号的图片\n";
        return;
    }

    if (!preg_match('/^data/', $equipment->icon_url('16'))) {
        echo "{$equipment->name}[{$equipment->id}] 已存在图片 {$equipment->icon_url('16')}\n";
        return;
    }

    $image = @Image::load($path, $ext);
    if (!$image) {
        echo "{$ref}编号的图片格式有误 请尝试手动上传\n";
        return;
    }
    $equipment->save_real_icon($image);
    $equipment->save_icon($image);

    echo "{$equipment->name}[{$equipment->id}] 已上传 {$path}\n";
        
    
}
