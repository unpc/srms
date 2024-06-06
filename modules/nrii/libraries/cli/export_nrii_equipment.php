<?php

class CLI_Export_Nrii_Equipment {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $columns = json_decode($params[2], true);

        $excel = new Excel($params[1]);
        $excel->write(array_values($columns));
        $nrii_equipments = Q($selector);
        
        $columns = Config::get('columns.export_columns.equipment');
        
        foreach($nrii_equipments as $nrii_equipment) {
            $data = [];
            foreach ($columns as $key => $val) {
                switch ($key) {
                    case 'eq_id':
                        $equipment = O('equipment', $nrii_equipment->eq_id);
                        $data[] = $equipment->name ? : '--';
                        break;
                    case 'affiliate':
                        $data[] = H(Nrii_Equipment_Model::$affiliate_type[$nrii_equipment->affiliate] ? : '--');
                        break;
                    case 'class':
                        $class = $nrii_equipment->class;
                        $lg = Config::get('class._root_')[substr($class, 0, 2).'0000'];
                        $md = Config::get('class.'.substr($class, 0, 2).'0000')[substr($class, 0, 4).'00'];
                        $sm = Config::get('class.'.substr($class, 0, 4).'00')[$class];
                        $c = [$lg, $md, $sm];
                        $data[] = H(join(', ', $c)) ? : '--';
                        break;
                    case 'location':
                        $address = $nrii_equipment->address;
                        $province = O('address', ['level' => 'province', 'adcode' => substr($address, 0, 2).'0000'])->name;
                        $city = O('address', ['level' => 'city', 'adcode' => substr($address, 0, 4).'00'])->name;
                        $area = O('address', ['level' => 'area', 'adcode' => $address])->name;
                        $province ? $a[] = $province : '';
                        $city ? $a[] = $city : '';
                        $area ? $a[] = $area : '';
                        $data[] = H(join(', ', $a).$nrii_equipment->street) ? : '--';
                        break;
                    case 'eq_source':
                        $data[] = H(Nrii_Equipment_Model::$eq_source[$nrii_equipment->eq_source] ? : '--');
                        break;
                    case 'type_status':
                        $data[] = H(Nrii_Equipment_Model::$type_status[$nrii_equipment->type_status] ? : '--');
                        break;
                    case 'realm':
                        $data[] = H($nrii_equipment->realm ? join(',', json_decode($nrii_equipment->realm, TRUE)) : '--');
                        break;
                    case 'beginDate':
                        $data[] = H($nrii_equipment->begin_date ? date('Y-m-d H:i:s', $nrii_equipment->begin_date) : '--');
                        break;
                    case 'funds':
                        $data[] = H($nrii_equipment->funds ? join(',', json_decode($nrii_equipment->funds, TRUE)) : '--');
                        break;
                    case 'cus_import_date':
                        $data[] = $nrii_equipment->customs_id ?  date('Y-m-d H:i:s', O('nrii_customs', $nrii_equipment->customs_id)->import_date) : '--';
                        break;
                    case 'cus_inner_id':
                    case 'cus_ins_code':
                    case 'cus_declaration_number':
                    case 'cus_item_number':
                    case 'cus_form_name':
                        $cus_key = substr($key,4);
                        $data[] = $nrii_equipment->customs_id ? O('nrii_customs', $nrii_equipment->customs_id)->$cus_key : '--';
                        break;
                    default:
                        $data[] = $nrii_equipment->$key ? : '--';
                }
            }
            $excel->write($data);
        }

        $excel->save();
    }
}
