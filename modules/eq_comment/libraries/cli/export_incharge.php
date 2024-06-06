<?php

class CLI_Export_Incharge {

    static function export() {
        $tip = Config::get('comment.rate')['tip'];
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $comments = Q($selector);

        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[1], 'xls');

        $excel->write(array_values($valid_columns));

        while (1) {
            $comments = $comments->limit($start, $per_page);
            if ($comments->length() == 0) {
                break;
            }
            foreach ($comments as $comment) {

                $data = [];

                if (array_key_exists('equipment', $valid_columns)) {
                    $data[] = $comment->equipment->name;
                }
                if (array_key_exists('user', $valid_columns)) {
                    $data[] = $comment->user->name;
                }
                if (array_key_exists('service_attitude', $valid_columns)) {
                    $data[] = $tip[$comment->service_attitude - 1];
                }
                if (array_key_exists('service_quality', $valid_columns)) {
                    $data[] = $tip[$comment->service_quality - 1];
                }
                if (array_key_exists('technical_ability', $valid_columns)) {
                    $data[] = $tip[$comment->technical_ability - 1];
                }
                if (array_key_exists('emergency_capability', $valid_columns)) {
                    $data[] = $tip[$comment->emergency_capability - 1];
                }
                if (array_key_exists('detection_performance', $valid_columns)) {
                    $data[] = $tip[$comment->detection_performance - 1];
                }
                if (array_key_exists('accuracy', $valid_columns)) {
                    $data[] = $tip[$comment->accuracy - 1];
                }
                if (array_key_exists('compliance', $valid_columns)) {
                    $data[] = $tip[$comment->compliance - 1];
                }
                if (array_key_exists('timeliness', $valid_columns)) {
                    $data[] = $tip[$comment->timeliness - 1];
                }
                if (array_key_exists('sample_processing', $valid_columns)) {
                    $data[] = $tip[$comment->sample_processing - 1];
                }
                if (array_key_exists('comment_suggestion', $valid_columns)) {
                    $data[] = $comment->comment_suggestion;
                }

                $excel->write($data, 100, count($data) - 1);
            }
            $start += $per_page;
        }

        $excel->save();
    }
}