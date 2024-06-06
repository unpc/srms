<?php

class CLI_Export_Training_Group {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $objects = Q($selector);
        $excel = new Excel($params[1]);

        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($objects as $object) {
            $data = [];
            foreach($valid_columns as $key=> $value) {
                switch($key) {
                    case 'user' :
                        $data[] = $object->user->name;
                        break;
                    case 'login_token' :
                        list($t, $u) = Auth::parse_token($object->user->token);
                        $data[] = $t;
                        break;
                    case 'ntotal' :
                        $data[] = $object->ntotal;
                        break;
                    case 'napproved' :
                        $data[] = $object->napproved;
                        break;
                    case 'date' :
                        $data[] = Date::format($object->date);
                        break;
                    case 'description' :
                        $data[] = $object->description;
                        break;
                }
            }
            $excel->write($data);
        }
        $excel->save();
    }
}
