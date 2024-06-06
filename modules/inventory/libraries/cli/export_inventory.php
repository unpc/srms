<?php

class CLI_Export_Inventory {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);

        $inventories = Q($selector);

        $excel = new Excel($params[1]);
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($inventories as $inventory) {
            $data = [];
            if (array_key_exists('product_name', $valid_columns)) {
                $data[] = H($inventory->product_name)?:'-';
            }
            if (array_key_exists('ref_no', $valid_columns)) {
                $data[]= H($inventory->ref_no) ?: '-';
            }
            if (array_key_exists('catalog_no', $valid_columns)) {
                $data[] = H($inventory->catalog_no)?:'-';
            }
            if (array_key_exists('vendor', $valid_columns)) {
                $data[] = H($inventory->vendor)?:'-';
            }
            if (array_key_exists('manufacturer', $valid_columns)) {
                $data[] = H($inventory->manufacturer)?:'-';
            }
            if (array_key_exists('barcode', $valid_columns)) {
                $data[] = H($inventory->barcode)?:'-';
            }
            if (array_key_exists('model', $valid_columns)) {
                $data[] = H($inventory->model)?:'-';
            }
            if (array_key_exists('spec', $valid_columns)) {
                $data[] = H($inventory->spec)?:'-';
            }
            if (array_key_exists('unit_price', $valid_columns)) {
                $data[] = H($inventory->unit_price)?:'-';
            }
            if (array_key_exists('type', $valid_columns)) {
                $data[] = H($inventory->type) ? : '-';
            }
            if (array_key_exists('quantity', $valid_columns)) {
                $data[] = H($inventory->quantity)?:'-';
            }
            if (array_key_exists('location', $valid_columns)) {
                $data[] = H($inventory->location)?:'-';
            }
            if (array_key_exists('status', $valid_columns)) {
                $status = [
                    '1' => '不详',
                    '2' => '充足',
                    '3' => '紧张',
                    '4' => '用罄',
                ];
                $data[] = I18N::HT('inventory', $status[$inventory->status]) ? : '-';
            }
            if (array_key_exists('tags', $valid_columns)) {
                $root = Tag_Model::root('inventory');
                $tags = (array) Q("$inventory tag[root=$root]")->to_assoc('name','name');
                $data[] = H(implode(',',$tags))?:'-';
             }						
            if (array_key_exists('note', $valid_columns)) {
                $data[] = H($inventory->note)?:'-';
            }
            $excel->write($data);
        }
        $excel->save();
    }
}
