<?php

class API_Six extends Base {

    public function get($lab_id, $dtstart, $dtend) {
        $this->_ready();

        if (!$lab_id || !$dtstart || !$dtend) {
            throw new API_Exception(self::$errors[402], 402);
        }

        $res = [];

        $lab = O('lab', $lab_id);
        if ($lab->id) {

            $fields = Config::get('six.fields');

            foreach ($fields as $field => $name) {
                $res[$field] = Event::trigger('basic_info.six.'.$field, $lab, strtotime($dtstart), strtotime($dtend)) ? : 0;
            }
        }

        return $res;
    }

}

