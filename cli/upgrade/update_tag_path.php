#!/usr/bin/env php
<?php

require "base.php";


try {

	//更新tag数据
	$tags = Q('tag');
	foreach ($tags as $tag) {
		$tag->save();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
