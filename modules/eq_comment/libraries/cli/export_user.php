<?php

class CLI_Export_User {

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
                if (array_key_exists('commentator', $valid_columns)) {
                    $data[] = $comment->commentator->name;
                }
                if (array_key_exists('user_attitude', $valid_columns)) {
                    $data[] = $tip[$comment->user_attitude - 1];
                }
                if (array_key_exists('user_proficiency', $valid_columns)) {
                    $data[] = $tip[$comment->user_proficiency - 1];
                }
                if (array_key_exists('test_understanding', $valid_columns)) {
                    $data[] = $tip[$comment->test_understanding - 1];
                }
                if (array_key_exists('user_cleanliness', $valid_columns)) {
                    $data[] = $tip[$comment->user_cleanliness - 1];
                }
                if (array_key_exists('test_importance', $valid_columns)) {
                    $data[] = $tip[$comment->test_importance - 1];
                }
                if (array_key_exists('test_purpose', $valid_columns)) {
                    $data[] = $comment->test_purpose;
                }
                if (array_key_exists('test_method', $valid_columns)) {
                    $data[] = $comment->test_method;
                }
                if (array_key_exists('test_result', $valid_columns)) {
                    $data[] = $comment->test_result;
                }
                if (array_key_exists('test_fit', $valid_columns)) {
                    $data[] = $comment->test_fit;
                }
                if (array_key_exists('test_remark', $valid_columns)) {
                    $data[] = $comment->test_remark;
                }

                $excel->write($data, 100, count($data) - 1);
            }
            $start += $per_page;
        }

        $excel->save();
    }
}