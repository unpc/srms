#!/usr/bin/env php
<?php
    /*
     * file check_bin_extra_data.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2013-11-21
     *
     * useage SITE_ID=cf LAB_ID=test php check_extra_data.php
     * brief 用于检测现有 _p_ 的extra属性是否有无法json_encode() json_decode() 的数据
     */

require 'base.php';

$db = Database::factory();

ob_start();

echo '系统ORM对象虚属性转移测试结果:';

//获取_p_开头的表
$tables_query = $db->query("SHOW TABLES LIKE '\_p\_%'");

//无法写入数据，结构: $errors[$object][$id] = $data;
$errors = [];

//遍历
while(($table = current($tables_query->row('num'))) != NULL) {

    $start_id = 0;
    $perpage = 10;

    //使用 id 进行，效率相对LIMIT OFFSET 比较高
    while($_data = $db->query('SELECT `id`, `data` FROM `%s` WHERE `id` > %d ORDER BY `id` LIMIT %d', $table, $start_id, $perpage)->rows('assoc')) {

        foreach($_data as $d) {

            $id = $d['id'];

            $data = (array) (@unserialize($d['data']) ?: @unserialize(base64_decode($d['data'])));

            //虚属性的错误数据过滤
            if (isset($data[0]) && $data[0] === FALSE) unset($data[0]);

            if (!count($data)) continue;

            //进行写入

            try {
                $tmp_data = @json_encode($data);

                if (json_last_error()) throw new Error_Exception;

                @json_decode($tmp_data);

                if (json_last_error()) throw new Error_Exception;

                //echo '.';
            }
            catch(Error_Exception $e) {
                $errors[$table][$id] = $data;
            }
        }

        $start_id = $id;
        $start += $perpage;
    }
}


if (count($errors)) {

    $perrors = [];

    foreach($errors as $object=> $id_data) {
        echo "\n有无法转移数据! \n";

        foreach($id_data as $id=> $data) {
            foreach($data as $property=> $d) {

                try {
                    $tmp_data = @json_encode([$property=>$d]);

                    if (json_last_error()) throw new Error_Exception;

                    @json_decode($tmp_data);

                    if (json_last_error()) throw new Error_Exception;
                }
                catch(Error_Exception $e) {
                    $perrors[$object][] = $property;
                }
            }
        }
    }

    foreach($perrors as $object => $properties) {
        echo "\n$object 数据错误:";
        foreach(array_unique($properties) as $p) {
            echo "\n\t $p";
        }
    }

}
else {
    echo "\n 数据均可正常转移!";
}

echo "\n";

$output = ob_get_contents();

ob_end_clean();

$email = new Email;

$receiver = ['xiaopei.li@geneegroup.com', 'rui.ma@geneegroup.com', 'cheng.liu@geneegroup.com'];

$email->to($receiver);

$subject = Config::get('page.title_default') . '系统虚属性转移测试';

$email->subject($subject);
$email->body($output);
$email->send();
