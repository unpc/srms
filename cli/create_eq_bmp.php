#!/usr/bin/env php
<?php
    /*
     * file create_eq_bmp.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2015-04-20
     *
     * useage SITE_ID=cf LAB_ID=may php create_eq_bmp.php xxx.csv
     * brief 用来通过 csv 文件创建文件内对应的 bmp 图片
     */

require 'base.php';

$file = $argv[1];

if (!file_exists($file)) {
    die("Usage: \n\n\t php create_eq_bmp.php *.csv \n\n");
}

$basename = basename($file, '.csv');

if (!file_exists($basename)) { 
    mkdir($basename);
}

Core::load(THIRD_BASE, 'qrcode', '*');

$csv = new CSV($file, 'r+');

while($line = $csv->read()) {

    list($id, $name, $ref_no, $yiqikong_id) = $line;

    $pic_png = strtr('%basename/[%id]%name.png', [
        '%basename'=> $basename,
        '%id'=> $id,
        '%name'=> str_replace('/', '_', $name),
    ]);

    $pic_bmp = strtr('%basename/[%id]%name.bmp', [
        '%basename'=> $basename, '/', '_',
        '%id'=> $id,
        '%name'=> str_replace('/', '_', $name),
    ]);

    $data = Config::get('wechat.wechat_equipment_url'). $yiqikong_id;

    Core::load(THIRD_BASE, 'qrcode', '*');

    QRcode::png($data, $pic_png, QR_ECLEVEL_M, 10,0);

    $image = Image::load($pic_png);

    $image->resize(660, 660);

    $image->crop(-10, -10, 680, 990);

    $font = Core::file_exists(PRIVATE_BASE.'fonts/SourceHanSansK-Normal.ttf', 'wechat');

    $arr = [];
    $j = 0;
    $l = 0;

    for($i = 0; $i < mb_strlen($name); $i += 1) {
        $item = mb_substr($name, $i, 1);
        switch(strlen($item)) {
            case 3 : //中文
                $dl = 2;
            break;
            case 1 : //英文
                $dl = 1;
            break;
        }

        $l += $dl;
        $arr[$j] .= $item;

        if ($l >= 24) {
            $j ++;
            $l = 0;
        }
    }

    $posy = 690; 

    foreach($arr as $a) {
        $image->text($a, 10, $posy, $font, 40);
        $posy += 58;
    }

    $image->text($ref_no, 10, $posy + 8, $font, 35);

    $image->background_color('#FFFFFF');

    switch($basename) {
        case 'tju' :
            $lab_name = '天津大学大型仪器管理平台';
        break;
        case 'nankai' :
            $lab_name = '南开大学大型仪器管理系统';
        break;
        default;
    }

    if ($lab_name) {
        $image->text($lab_name, 10, $posy + 60, $font, 35);
    }

    $image->save('png', $pic_png);

    $im = new Imagick($pic_png);
    $im->setImageFormat('bmp');
    ob_start();
    echo $im;
    $content = ob_get_contents();
    ob_end_clean();
    file_put_contents($pic_bmp, $content);
    File::delete($pic_png);

    ///

    /*
    $image = Image::load($pic_png);
    $image->resize(180, 180);

    $image->crop(-10, -10, 200, 285);

    $font = Core::file_exists(PRIVATE_BASE.'fonts/simsun.ttf', 'wechat');

    $arr = [];
    $j = 0;
    for($i = 0; $i < mb_strlen($name); $i += 1) {
        $item = mb_substr($name, $i, 1);
        switch(strlen($item)) {
            case 3 : //中文
                $dl = 2;
                break;
            case 1 : //英文
                $dl = 1;
                break;
        }

        $l += $dl;
        $arr[$j] .= $item;

        if ($l >= 22) {
            $j ++;
            $l = 0;
        }
    }

    $posy = 200;

    foreach($arr as $a) {
        $image->text($a, 10, $posy, $font, 11);
        $posy += 20;
    }

    $image->text($ref_no, 10, $posy + 4, $font, 10);

    $image->background_color('#FFFFFF');

    $image->save('png', $pic_png);

    $image = Image::load($pic_png);
    $image->resize(170, 280);
    $image->save();
    */
    echo '.';
}

$csv->close();
