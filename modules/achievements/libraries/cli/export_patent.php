<?php

class CLI_Export_Patent {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);

        $patents = Q($selector);

        $excel = new Excel($params[1]);

        $excel->write(array_values($valid_columns));

        if ($patents->total_count()) {
            foreach ($patents as $patent) {
                $data = [];
                if (array_key_exists('name', $valid_columns)) {
                    $data[] = H($patent->name) ? : '-';
                }
                if (array_key_exists('ref_no', $valid_columns)) {
                    $data[] = H($patent->ref_no) ? : '-';
                }
                if (array_key_exists('date', $valid_columns)) {
                    $data[] = date('Y/m/d', H($patent->date)) ? : '-';
                }
                if (array_key_exists('type', $valid_columns)) {
                    $level = Q("$patent tag_achievements_patent")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $level)) ? : '-';
                }
                if (array_key_exists('people', $valid_columns)) {
                    $people = Q("ac_author[achievement=$patent]")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $people)) ? : '-';
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $labs = Q("$patent lab")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $labs)) ? : '-';
                }
                if (array_key_exists('project', $valid_columns)) {
                    $projects = Q("$patent lab_project")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $projects)) ? : '-';
                }
                if (array_key_exists('equipment', $valid_columns)) {
                    $equipments = Q("$patent equipment")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $equipments)) ? : '-';
                }
                $excel->write($data);
            }
        }
        $excel->save();
    }
}
