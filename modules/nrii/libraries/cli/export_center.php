<?php

class CLI_Export_Center {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $columns = json_decode($params[2], true);

        $excel = new Excel($params[1]);
        $excel->write(array_values($columns));
        $centers = Q($selector);
        $columns = Config::get('columns.export_columns.center');

        foreach($centers as $center) {
            $data = [];
            foreach ($columns as $key => $val) {
                switch ($key) {
                    case 'location':
                        $address = $center->address;
                        $province = O('address', ['level' => 'province', 'adcode' => substr($address, 0, 2).'0000'])->name;
                        $city = O('address', ['level' => 'city', 'adcode' => substr($address, 0, 4).'00'])->name;
                        $area = O('address', ['level' => 'area', 'adcode' => $address])->name;
                        $a = [];
                        $province && $a[] = $province;
                        $city && $a[] = $city;
                        $area && $a[] = $area;
                        $data[] = H(join(', ', $a).$center->contact_street) ? : '--';
                        break;
                    case 'realm':
                        $data[] = H($center->realm ? join(',', json_decode($center->realm, TRUE)) : '--');
                        break;
                    case 'begin_date':
                        $data[] = H($center->begin_date ? date('Y-m-d H:i:s', $center->begin_date) : '--');
                        break;
                    case 'accept':
                        $data[] = H(Nrii_Center_Model::$accept_status[$center->accept] ? : '--');
                        break;
                    default:
                        $data[] = $center->$key;
                }
            }
            $excel->write($data);
        }

        $excel->save();
    }
}
