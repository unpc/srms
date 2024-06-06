<?php

class Autocomplete_Controller extends AJAX_Controller {

    function building() {
        $query = trim(Input::form('s'));
        $st = trim(Input::form('st'));

        $start = 0;
        if ($st) {
            $start = $st;
        }
        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;

        if ($query) {
            $query = Q::quote($query);
            $selector = "gis_building[name*={$query}]:limit({$start},{$n})";
        }       
        else {
            $selector = "gis_building:limit({$start},{$n})";
        }
        $buildings = Q($selector);
        $count = $buildings->length();

        if ($start == 0 && !$count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($buildings as $building) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/building', ['building' => $building]),
                    'alt' => $building->name,
                    'text' => I18N::T('gismon', '%building', ['%building'=>H($building->name)]),
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

}
