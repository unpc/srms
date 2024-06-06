<?php
class Autocomplete_Controller extends AJAX_Controller {
    
    function search($mode) {
        if (empty($mode)) return;

        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));

        $start = 0;
        if ($st) $start = $st;

        $n = 5;
        if($start == 0) $n = 10;
        if($start >= 100) return;

        if ($mode == 'funds') {
            $dictionary = Nrii_Equipment_Model::$funds;
        }
        else {
            $dictionary = Config::get($mode);
        }
        if ($s) {
            $objects = $this->limit($dictionary, $n, $s, $st);
        }else {
            $objects = $this->limit($dictionary, $n, '', $st);
        }
        $count = count($objects);

        if ($start == 0 && !$count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($objects as $key => $object) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/'.$mode, [$mode=>$object,$k=>$key]),
                    'alt' => $key,
                    'text' => $object,
                ];
            }

            if ($start== 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

    private function limit($arr ,$cnt, $input, $st){
        if (is_array($arr) && is_int($cnt)){
            $ret = [];
            if (count($ret) < $cnt && !empty($input)){
                $newcnt = 0;
                foreach ($arr as $key => $value) {
                    if (count($ret) >= $cnt) break;
                    if (strstr($value,$input)){
                        if ((!empty($st) && $newcnt >= $st)||empty($st)){
                            $ret[] = $value;
                        }else{
                            $newcnt++;
                        }
                    }
                }
            }else{
                $ret = $arr;
            }
            return $ret;
        }
    }
}