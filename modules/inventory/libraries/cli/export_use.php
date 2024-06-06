<?php

class CLI_Export_Use {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);

        $uses = Q($selector);

        $excel = new Excel($params[1]);
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($uses as $use) {
            $data = [];
            if (array_key_exists('ctime', $valid_columns)) {
                $data[] = Date::format($use->ctime, 'Y/m/d H:i');
            }
            if (array_key_exists('stock', $valid_columns)) {
                $data[] = $use->stock->product_name;
            }

            if (array_key_exists('user', $valid_columns)) {
                $data[] = $use->user->name;
            }

            if (array_key_exists('use_quantity', $valid_columns)) {
                $data[] = $use->quantity > 0 ?  $use->quantity : null;
            }

            if (array_key_exists('return_quantity', $valid_columns)) {
                $data[] = $use->quantity < 0 ?  (-1)*$use->quantity : null;
            }

            if (array_key_exists('unit_price', $valid_columns)) {
                $data[] = $use->stock->unit_price;
            }

            if (array_key_exists('total_price', $valid_columns)) {
                $data[] = $use->stock->unit_price * ($use->quantity > 0 ?  $use->quantity : (-1) * $use->quantity);
            }

            if (array_key_exists('status', $valid_columns) && $use->status) {
                $data[] = Stock_Use_Model::$status[$use->status];
            }

            if (array_key_exists('note', $valid_columns)) {
                $data[] = $use->note;
            }
            $excel->write($data);
        }
        $excel->save();
    }
}
