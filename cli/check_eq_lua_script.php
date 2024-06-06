#!/usr/bin/env php
<?php
    /*
     * file check_eq_lua_script.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-12-05 (今天发工资)
     *
     * useage SITE_ID=cf LAB_ID=nankai php check_eq_lua_script.php
     * brief 用于对系统中所有的仪器进行遍历，检测仪器的送样、预约、计费脚本是否有语法错误
     */

require dirname(__FILE__). '/base.php';

//语法错误
$syntax_errors = [];
/*
   syntax_errors结构
   array(
       仪器id=> 属性名称
   )
 */

//ob_start();

echo "开始进行检测:\n";

//开始进行检测
foreach(Q('equipment') as $equipment) {

    $id = $equipment->id;

    //reserv_script
    $reserv_script = $equipment->reserv_script;

    if (!lua::compile($reserv_script)) {
        $syntax_errors[$id][] = 'reserv_script';
    }

    //charge_script
    $charge_script = $equipment->charge_script;

    if ($charge_script = $equipment->charge_script) {
        $charge_template = $equipment->charge_template;
        foreach((array) $charge_script as $type => $script) {
            //compile编译不通过,或者包含duration，则为语法错误
            if (!lua::compile($script) || ($charge_template[$type] == 'custom_'. $type &&  strpos($script, 'duration'))) {
                $syntax_errors[$id][] = 'charge_script:'. $type;
            }
        }
    }
}

if (count($syntax_errors)) {
    //发现错误
    echo "发现错误:\n";
    foreach($syntax_errors as $id => $es) {
        echo "仪器[$id]如下脚本有错误:\n";
        foreach($es as $e) {
            echo "\t$e\n";
        }
    }
}
else {
    echo "没有发现仪器lua脚本语法错误\n";
}

/*
$output = ob_get_contents();

ob_end_clean();

$email = new Email;

$receiver = ['rui.ma@geneegroup.com', 'cheng.liu@geneegroup.com', 'xiaopei.li@geneegroup.com'];

$email->to($receiver);

$subject = Config::get('page.title_default') . '系统仪器lua脚本检测结果';

$email->subject($subject);
$email->body($output);
$email->send();
*/
