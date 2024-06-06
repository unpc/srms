#!/usr/bin/env php
<?php
// 对单个 php encode, 可用来检查 "打包后产生的 BUG"
require "includes/php_obfuscator.php";

$path = $argv[1];
//移除脚本中的注释和回车	
$source = @file_get_contents($path);
$total = strlen($source);

$source = file_get_contents($path);
$ob = new PHP_Obfuscator($source);
$ob->set_reserved_keywords(['$config', '$lang']);
$content = $ob->format();

echo $content."\n";
