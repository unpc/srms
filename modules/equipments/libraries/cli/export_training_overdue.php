<?php

class CLI_Export_Training_Overdue {

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
                    case 'lab' :
                        $labs = Q("{$object->user} lab")->to_assoc('id', 'name');
                        $data[] = H(join(', ', $labs));
                        break;
                    case 'group' :
                        $data[] = $object->user->group->name;
                        break;
                    case 'phone' :
                        $data[] = $object->user->phone;
                        break;
                    case 'email' :
                        $data[] = $object->user->email;
                        break;
                    case 'address' :
                        $data[] = $object->user->address;
                        break;
                    case 'ctime' :
                        $data[] = $object->ctime ? Date::format($object->ctime) : '--';
                        break;
                    case 'atime' :
                        $data[] = $object->atime ? Date::format($object->atime) : I18N::T('equipments', '不过期');
                        break;
                }
            }
            $excel->write($data);
        }
        $excel->save();
    }
}
