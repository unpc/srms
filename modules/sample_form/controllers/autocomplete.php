<?php
class Autocomplete_Controller extends AJAX_Controller
{
    public function element($id=0)
    {
        $s = Q::quote(Input::form('s'));
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if ($start >= 100) {
            return;
        }
        $n = 5;
        if ($start == 0) {
            $n = 10;
        }
        $me = L('ME');
        $equipment = O('equipment', $id);
        $status = join(',', Sample_Element_Model::$CONNECT_STATUS);
        $selector = "{$equipment} sample_element[status={$status}][!source][inspector={$me}]";

        if ($s) {
            $s = Q::quote($s);
            $selector .= "[user_name*={$s}]:sort(ctime D):limit({$start},{$n})";
        } else {
            $selector .= ":sort(ctime D):limit({$start},{$n})";
        }
		$elements = Q($selector);
        $elements_count = $elements->total_count();

        if (!$elements_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => true
            ];
        } else {
            foreach ($elements as $element) {
                Output::$AJAX[] = [
                    'html' => (string) V('sample_form:autocomplete/sample', ['element' => $element]),
                    'alt' => $element->id,
                    'text' => $element->id ? $element->toString() : '',
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
