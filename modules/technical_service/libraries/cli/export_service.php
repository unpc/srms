<?php

class CLI_Export_Service
{

    static function export()
    {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $services = Q($selector);
        $excel = new Excel($params[1]);

        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($services as $service) {
            $data = new ArrayIterator;

            if (array_key_exists('ref_no', $valid_columns)) {
                $data['ref_no'] = T($service->ref_no) ?: '';
            }
            if (array_key_exists('name', $valid_columns)) {
                $data['name'] = T($service->name) ?: '';
            }
            if (array_key_exists('service_type', $valid_columns)) {
                $data['service_type'] = $service->service_type->id ? $service->service_type->name : '';
            }
            if (array_key_exists('billing_department', $valid_columns)) {
                $data['billing_department'] = $service->billing_department->id ? $service->billing_department->name : '';
            }
            if (array_key_exists('incharges', $valid_columns)) {
                if ($service->id) {
                    $users = Q("$service<incharge user");
                    $incharges = [];
                    foreach ($users as $incharge) {
                        if ($GLOBALS['preload']['people.multi_lab']) {
                            $incharges[$incharge->id] = $incharge->name;
                        } else {
                            $incharges[$incharge->id] = $incharge->name . '(' . Q("{$incharge} lab")->current()->name . ')';
                        }
                    }
                }
                $data['incharges'] = implode(',', $incharges) ?: '';
            }
            if (array_key_exists('phones', $valid_columns)) {
                $data['phones'] = T($service->phones) ?: '';
            }
            if (array_key_exists('emails', $valid_columns)) {
                $data['emails'] = T($service->emails) ?: '';
            }

            $data_custom = Event::trigger('service.export_list_csv', $equipment, $data, $valid_columns);
            if (is_array($data_custom)) $data = $data_custom;

            $data = array_replace($valid_columns, iterator_to_array($data));
            $data = array_values($data);

            $excel->write($data);
        }
        $excel->save();
    }
}
