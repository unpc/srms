<?php
class Address_AJAX_Controller extends AJAX_Controller {
    function index_address_change($id = 0){
        $form = Input::form();
        if (!$form['adcode']) return;

        $mode = $form['mode'];
        if ($mode == 'city') {
            $prefix = substr($form['adcode'], 0, 2);
        }
        elseif ($mode = 'area') {
            $prefix = substr($form['adcode'], 0, 4);
        }
        else {
            return;
        }

        $address = Q("address[level={$mode}][adcode^={$prefix}]")->to_assoc('adcode', 'name');

        Output::$AJAX[$mode] = $address;
    }
}