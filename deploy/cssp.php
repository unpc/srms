#!/usr/bin/env php
<?php

require "includes/cssp.php";

$path = $argv[1];
//移除脚本中的注释和回车	
if ($path && is_file($path)) {
	$source = @file_get_contents($path);
	$content = CSSP::fragment($source)->format();
}

echo $content."\n";
