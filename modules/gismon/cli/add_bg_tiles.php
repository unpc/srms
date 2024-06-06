#!/usr/bin/env php
<?php

// 使用方法：SITE_ID=XXX LAB_ID=XXX php add_bg_tiles.php file
// file为图片在lims下的路径，调整$zoom可以缩放大小

$base = dirname(dirname(dirname(dirname(__FILE__)))) . '/cli/base.php';
require $base;

try {

	$args = $argv;
	array_shift($args);
	
	foreach ($args as $arg) {
		// 搜索对象目录
		$path = ROOT_PATH.$arg;
		add_bg_to_tile($path);
		
	}

		
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

function add_bg_to_tile($path) {
	$zoom = 2;
	
	$TILE_WIDTH = 256;
	$TILE_HEIGHT = 256;
	
	if (!file_exists($path)) {
		echo "$path 不存在!\n";
		return;
	}
	
	$im = @ImageCreateFromPng($path);
	
	if (!$im) {
		echo "$path图片打开失败!\n";
		return;
	}
	
	echo "打开$path\n";
	list($w, $h) = GetImageSize($path);
	echo "宽度:{$w}px 高度{$h}px\n";

	$new_im = ImageCreateTrueColor($w * $zoom, $h * $zoom);
	ImageAlphaBlending($new_im,false);
	
	$colorToPaint = ImageColorAllocate($new_im,0,0x22,0x66);
	ImageFill($new_im, 0, 0, $colorToPaint);

	ImageAlphaBlending($new_im,true);
	// ImageCopy($new_im, $im, 0, 0, 0, 0, $w, $h);
	ImageCopyResampled($new_im, $im, 0, 0, 0, 0, $w * $zoom, $h * $zoom, $w, $h);

	ImageSaveAlpha($new_im,false);
	ImageDestroy($im);

	ImagePng($new_im, $path);
	ImageDestroy($new_im);
	
	
}
