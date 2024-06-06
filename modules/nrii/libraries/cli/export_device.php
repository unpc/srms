<?php

class CLI_Export_Device {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $columns = json_decode($params[2], true);

        $excel = new Excel($params[1]);
        $excel->write(array_values($columns));
        $devices = Q($selector);
        $columns = Config::get('columns.export_columns.device');

        foreach($devices as $device) {
            $data = [];
            foreach ($columns as $key => $val) {
                switch ($key) {
                    case 'location':
                        $address = $device->address;
                        $province = O('address', ['level' => 'province', 'adcode' => substr($address, 0, 2).'0000'])->name;
                        $city = O('address', ['level' => 'city', 'adcode' => substr($address, 0, 4).'00'])->name;
                        $area = O('address', ['level' => 'area', 'adcode' => $address])->name;
                        $province ? $a[] = $province : '';
                        $city ? $a[] = $city : '';
                        $area ? $a[] = $area : '';
                        $data[] = H(join(', ', $a).$device->contact_street) ? : '--';
                        break;
                    case 'realm':
                        $data[] = H($device->realm ? join(',', json_decode($device->realm, TRUE)) : '--');
                        break;
                    case 'begin_date':
                        $data[] = H($device->begin_date ? date('Y-m-d H:i:s', $device->begin_date) : '--');
                        break;
                    case 'device_category':
                        $data[] = H(Nrii_Device_Model::$device_category[$device->device_category] ? : '--');
                        break;
                    case 'construction':
                        $data[] = H(Nrii_Device_Model::$construction[$device->construction] ? : '--');
                        break;
                    default:
                        $data[] = $device->$key;
                }
            }
            $excel->write($data);
        }

        $excel->save();
    }
}
