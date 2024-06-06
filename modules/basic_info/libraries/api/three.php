<?php

class API_Three extends Base {

    public function get($equipment_id, $dtstart, $dtend)
    {
        $this->_ready();

        if (!$equipment_id || !$dtstart || !$dtend) {
            throw new API_Exception(self::$errors[402], 402);
        }

        $equipment = O('equipment', $equipment_id);

        $res = [];

        if ($equipment->id) {

            $fields = Config::get('three.fields');

            foreach ($fields as $field => $name) {
                $res[$field] = Event::trigger('basic_info.three.'.$field, $equipment, strtotime($dtstart), strtotime($dtend));
            }
        }

        return $res;
    }
}