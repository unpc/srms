<?php

class FILE extends _FILE
{

    // 返回此路径的去除点，空格等后的合法路径$path
    public static function fix_path($path)
    {
        // 由于不同浏览器解析问题，需要rawurldecode()统一进行处理为未转义情况
        $path = rawurldecode($path);
        $path = preg_replace('/[\/\s]+|[\/\s]+$/', '', $path);
        $path = preg_replace('/\.{1,2}\//', '', $path);
        $path = preg_replace('/\/\.{1,2}\//', '/', $path);
        return $path;
    }

}
