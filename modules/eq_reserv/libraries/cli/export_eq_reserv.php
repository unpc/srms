<?php

class CLI_Export_Eq_Reserv {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $columns = json_decode($params[2], true);

        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[1]);
        $excel->write(array_values($columns));

        $eq_reservs = Q($selector);

        foreach($eq_reservs as $eq_reserv) {
            $component = $eq_reserv->component;
            $data = [];
            $calendar = $component->calendar;
            $equipment = NULL;
            if ($calendar->parent->name() == 'equipment') {
                $equipment = $calendar->parent;
            }

            if (array_key_exists('equipment', $columns)) {
                $data[] = $equipment->name.Event::trigger('extra.equipment.name', $equipment);
            }
            if (array_key_exists('eq_ref_no', $columns)) {
                $data[] = $equipment->ref_no;
            }
            if (array_key_exists('eq_cf_id', $columns)) {
                $data[] = $equipment->id;
            }
            if (array_key_exists('eq_group', $columns)) {
                $data[] = $equipment->group->name;
            }
            if (array_key_exists('organizer', $columns)) {
                $data[] = $component->organizer->name;
            }
            if (array_key_exists('login_token', $columns)) {
                list($t, $b) = Auth::parse_token($component->organizer->token);
                $data[] = H($t);
            }
            if (array_key_exists('email', $columns)) {
                $data[] = $component->organizer->email;
            }
            if (array_key_exists('phone', $columns)) {
                $data[] = $component->organizer->phone;
            }
            if (array_key_exists('group', $columns)) {
                $data[] = $component->organizer->group->name;
            }
            if (array_key_exists('lab', $columns)) {
                $lab = $eq_reserv->project->lab->id ?
                        $eq_reserv->project->lab :
                        Q("{$component->organizer} lab")->current();
                $data[] = $lab->name;
            }
            if (array_key_exists('time', $columns)) {
                $data[] = Date::format($component->dtstart, 'Y/m/d H:i:s'). ' - '. Date::format($component->dtend, 'Y/m/d H:i:s');
            }
            if (array_key_exists('duration', $columns)) {
                $data[] = I18N::T('eq_reserv', '%duration小时', [
                        '%duration'=> round(($component->dtend - $component->dtstart) / 3600, 2)]);
            }
            if (array_key_exists('name', $columns)) {
                $data[] = $component->name;
            }
            if (array_key_exists('reserv_type', $columns)) {
                $ctype = $component->type;
                if ($ctype == Cal_Component_Model::TYPE_VEVENT) {
                    $data[] = I18N::T('eq_reserv', '预约');
                }
                elseif ($ctype == Cal_Component_Model::TYPE_VFREEBUSY) {
                    $data[] = I18N::T('eq_reserv', '非预约时段');
                }
            }
            if (array_key_exists('count', $columns)) {
                $data[] = $eq_reserv->count;
            }
            if (array_key_exists('description', $columns)) {
                $data[] = $component->description;
            }
            if (array_key_exists('status', $columns)) {
                $data[] = EQ_Reserv_Model::$reserv_status[$eq_reserv->get_status()];
            }
            if(array_key_exists('university', $columns)) {
                $data[] = Config::get('university.list')[$eq_reserv->source_name];
            }

            if (array_key_exists('site', $columns)) {
                $data[] = Config::get('site.map')[$eq_reserv->equipment->site];
            }
            
            $data_extra = Event::trigger('export_eq_reserv.export_list_csv', $component, $data, $columns);
            if(is_array($data_extra)) $data = $data_extra;
            $excel->write($data);
        }

        $excel->save();
    }
}
