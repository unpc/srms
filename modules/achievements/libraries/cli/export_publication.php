<?php

class CLI_Export_Publication {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);

        $publications = Q($selector);

        $excel = new Excel($params[1]);

        $excel->write(array_values($valid_columns));

        if ($publications->total_count()) {
            foreach ($publications as $publication) {
                $data = [];
                if (array_key_exists('title', $valid_columns)) {
                    $data[] = H($publication->title) ? : '-';
                }
                if (array_key_exists('author', $valid_columns)) {
                    $authors = Q("ac_author[achievement=$publication]")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $authors)) ? : '-';
                }
                if (array_key_exists('journal', $valid_columns)) {
                    $data[] = H($publication->journal) ? : '-';
                }
                if (array_key_exists('date', $valid_columns)) {
                    $data[] = date('Y/m/d', H($publication->date)) ? : '-';
                }
                if (array_key_exists('volume', $valid_columns)) {
                    $data[] = H($publication->volume) ? : '-';
                }
                if (array_key_exists('issue', $valid_columns)) {
                    $data[] = H($publication->issue) ? : '-';
                }
                if (array_key_exists('tags', $valid_columns)) {
                    $tags = Q("$publication tag_achievements_publication")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $tags)) ? : '-';
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $labs = Q("$publication lab")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $labs)) ? : '-';
                }
                if (array_key_exists('project', $valid_columns)) {
                    $projects = Q("$publication lab_project")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $projects)) ? : '-';
                }
                if (array_key_exists('equipment', $valid_columns)) {
                    $equipments = Q("$publication equipment")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $equipments)) ? : '-';
                }
                $excel->write($data);
            }
        }
        $excel->save();
    }
}
