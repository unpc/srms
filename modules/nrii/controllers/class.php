<?php
class Class_AJAX_Controller extends AJAX_Controller {
    function index_class_change($id = 0){
        $id = (string)$_POST['id'];
        $class_select = (string)$_POST['class_select'];
       
        if (!$id || $id %100 != 0) return;
        if ($id % 10000 == 0){
            $mode = 'class_md';
        }elseif ($id % 100 == 0){
            $mode = 'class_sm';
        }

        if($id == '990000' && $class_select == '#class_md select'){
            $mode = 'class_sm';
        }

        $ret = Config::get('class.'.$id);
        Output::$AJAX[$mode] = $ret;
    }
}