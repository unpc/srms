<?php

class Autocomplete_Controller extends AJAX_Controller
{
    public function doors()
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        $n = 5;
        if ($start == 0) {
            $n = 10;
        }
        if ($start >= 100) {
            return;
        }
        //从接口中获取 门牌信息
        $selector = [];
        if ($s) {
            $selector["name"] = ["ct", $s];
        }
        $selector["st"] = $start;
        $selector["pp"] = $n;

        $response = Remote_Door::getDoors($selector);
        $doors_count = $response['total'];
        $doors = $response['items'];

        if ($start == 0 && !$doors_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => true
            ];
        } else {
            foreach ($doors as $door) {
                Output::$AJAX[] = [
                    'html'=>(string) V('iot_gdoor:autocomplete/door', ['door'=>$door]),
                    'alt'=>$door['id'],
                    'text'=>I18N::T('iot_gdoor', '%door', ['%door'=>$door['name']]),
                ];
            }
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => true
                ];
            }
        }
    }
}
