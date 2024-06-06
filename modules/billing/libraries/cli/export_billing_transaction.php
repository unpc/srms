<?php

class CLI_Export_Billing_Transaction {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[3], true);
        $visible_columns = json_decode($params[4], true);
        $valid_columns_header = json_decode($params[5], true);
        $user = O('user', $params[6]);
        $object_name = $params[2];
        $transactions = Q($selector);

        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[1]);
        $excel->write(array_values($valid_columns_header));

        while (1) {
            $pp_trans = $transactions->limit($start, $per_page);
            if ($pp_trans->length() == 0) break;

            foreach ($pp_trans as $t) {
			    if ($user->is_allowed_to('查看', $t)) {
                    $data = [];
                    foreach($valid_columns as $key => $tmp) {
                        $data[] = (string) V("billing:transactions_output/export/$key", ['transaction'=> $t]);
                    }
                    $excel->write($data);
                }
            }
            $start += $per_page;
        }

        $excel->save();
    }
}
