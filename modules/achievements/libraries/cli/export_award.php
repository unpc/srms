<?php

class CLI_Export_Award {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);

        $awards = Q($selector);

        $excel = new Excel($params[1]);

        $excel->write(array_values($valid_columns));

        if ($awards->total_count()) {
            foreach ($awards as $award) {
                $data = [];
                if (array_key_exists('name', $valid_columns)) {
                    $data[] = H($award->name) ? : '-';
                }
                if (array_key_exists('level', $valid_columns)) {
                    $level = Q("$award tag_achievements_award")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $level)) ? : '-';
                }
                if (array_key_exists('date', $valid_columns)) {
                    $data[] = date('Y/m/d', H($award->date)) ? : '-';
                }
                if (array_key_exists('people', $valid_columns)) {
                    $people = Q("ac_author[achievement=$award]")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $people)) ? : '-';
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $labs = Q("$award lab")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $labs)) ? : '-';
                }
                if (array_key_exists('project', $valid_columns)) {
                    $projects = Q("$award lab_project")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $projects)) ? : '-';
                }
                if (array_key_exists('equipment', $valid_columns)) {
                    $equipments = Q("$award equipment")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $equipments)) ? : '-';
                }
                $excel->write($data);
            }
        }
        $excel->save();
    }
}
