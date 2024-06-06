#!/usr/bin/env php
<?php
// 使用方法：SITE_ID=XXX LAB_ID=XXX php make_tiles.php dir
// dir 为需切图文件在lims的位置
// dir内第一级文件夹为楼宇id，第二级为文件名（楼层.png）楼层从0开始

$base = dirname(dirname(dirname(dirname(__FILE__)))) . '/cli/base.php';
require $base;

try {

	$args = $argv;
	array_shift($args);
	
	foreach ($args as $arg) {
		// 搜索对象目录
		$path = ROOT_PATH.$arg;
		$floor_pattern = $path.'/*/*.png';
		echo $floor_pattern."\n";
		$floor_paths = glob($floor_pattern);
		echo print_r($floor_paths)."\n";
		foreach ($floor_paths as $p) {
			// original
			resize_and_make_tiles($p, 0);
			resize_and_make_tiles($p, 1);
			resize_and_make_tiles($p, 2);
			resize_and_make_tiles($p, 3);
		}
		
	}

		
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

function resize_and_make_tiles($p, $zoom) {
	$dir = dirname($p);
	$file = basename($p);

	$path = $dir.'/'.$zoom.'/'.$file;
	File::check_path($path);
	switch ($zoom) {
	case 0:
	case 1:
		$img = Image::load($p);
		$img->resize($img->current_width >> (2 - $zoom) , $img->current_height >> (2 - $zoom), TRUE);
		$img->save('png', $path);
		break;
	default:
		@copy($p, $path);
	}

	make_tiles($path);
	@unlink($path);

}

function make_tiles($path) {
	
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
	
	$base = preg_replace('/\.[^\/]+?$/', '', $path);
	File::check_path($base.'/foo', 0775);		
	echo "打开$path\n";
	list($w, $h) = GetImageSize($path);
	echo "宽度:{$w}px 高度{$h}px\n";

	$new_im = ImageCreateTrueColor($w, $h);
	ImageAlphaBlending($new_im,false);
	ImageSaveAlpha($new_im,true);
	ImageCopy($new_im, $im, 0, 0, 0, 0, $w, $h);
	$im = $new_im;
	
	$ty = 0;
	for ($y = 0; $y < $h; $y += $TILE_HEIGHT) {
		$tx = 0;
		for ($x = 0; $x < $w; $x += $TILE_WIDTH) {
			$tile = ImageCreateTrueColor($TILE_WIDTH, $TILE_HEIGHT);
			
			ImageAlphaBlending($tile,false);
			ImageSaveAlpha($tile,true);

			//#026
			$colorToPaint = ImageColorAllocateAlpha($tile,0,0x22,0x66,0);
			ImageFill($tile, 0, 0, $colorToPaint);

			ImageAlphaBlending($tile, true);
			$tw = $TILE_WIDTH;
			$th = $TILE_HEIGHT;
			
			if ($x + $tw > $w) {
				$tw = $w - $x;
			}

			if ($y + $th > $h) {
				$th = $h - $y;
			}

			ImageCopyResampled($tile, $im, 0, 0, $x, $y, $tw, $th, $tw, $th);
			
			$tile_path = "$base/{$tx}_{$ty}.png";
			
			ImagePng($tile, $tile_path);
			ImageDestroy($tile);
			$tx ++;
		}
		$ty ++;
	}
	
	ImageDestroy($im);

}
