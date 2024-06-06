<?php

class CLI_Export_Credit
{
    public static function export()
    {
        $params        = func_get_args();
        $selector      = $params[0];
        $current_user  = $params[1];
        $valid_columns = json_decode($params[3], true);
        $credits       = Q($selector);

        $start    = 0;
        $per_page = 100;

        $excel = new Excel($params[2], 'xls');
        $excel->write(array_values($valid_columns));

        while (1) {
            $pp_credits = $credits->limit($start, $per_page);
            if ($pp_credits->length() == 0) {
                break;
            }
            foreach ($pp_credits as $credit) {
                $data = [];
                if (array_key_exists('name', $valid_columns)) {
                    $data[] = H($credit->user->name);
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $lab    = Q("{$credit->user} lab")->current();
                    $data[] = H($lab->id ? $lab->name : '');
                }
                if (array_key_exists('group', $valid_columns)) {
                    $data[] = H($credit->user->group_id ? $credit->user->group->name : '');
                }
                if (array_key_exists('level', $valid_columns)) {
                    $data[] = str_repeat('A', $credit->credit_level->level);
                }
                if (array_key_exists('credit_score', $valid_columns)) {
                    $data[] = H($credit->total);
                }
                $excel->write($data, 100, count($data) - 1);
            }
            $start += $per_page;
        }
        $excel->save();
    }

    public static function credit_record_export()
    {
        $params        = func_get_args();
        $selector      = $params[0];
        $current_user  = $params[1];
        $valid_columns = json_decode($params[3], true);
        $credits       = Q($selector);

        $start    = 0;
        $per_page = 100;

        $excel = new Excel($params[2], 'xls');
        $excel->write(array_values($valid_columns));

        while (1) {
            $pp_credits = $credits->limit($start, $per_page);
            if ($pp_credits->length() == 0) {
                break;
            }
            foreach ($pp_credits as $credit) {
                $data = [];
                if (array_key_exists('id', $valid_columns)) {
                    $data[] = Number::fill(H($credit->id), 6);
                }
                if (array_key_exists('ctime', $valid_columns)) {
                    $data[] = H(Date('Y-m-d', $credit->ctime));
                }
                if (array_key_exists('name', $valid_columns)) {
                    $data[] = H($credit->user->name);
                }
                if (array_key_exists('event', $valid_columns)) {
                    $data[] = H(in_array($credit->credit_rule->ref_no, [Credit_Rule_Model::CUSTOM_ADD, Credit_Rule_Model::CUSTOM_CUT]) ? $credit->description : $credit->credit_rule->name);
                }
                if (array_key_exists('equipment', $valid_columns)) {
                    $data[] = H($credit->equipment->id ? $credit->equipment->name : '');
                }
                if (array_key_exists('score', $valid_columns)) {
                    $data[] = H($credit->score);
                }
                if (array_key_exists('total', $valid_columns)) {
                    $data[] = H($credit->total);
                }
                if (array_key_exists('operator', $valid_columns)) {
                    $data[] = H($credit->operator->id ? $credit->operator->name : I18N::T('credit', '系统'));
                }
                if (array_key_exists('operation_time', $valid_columns)) {
                    $data[] = H($credit->operation_time ? Date('Y-m-d', $credit->operation_time) : I18N::T('credit','无'));
                }
                $excel->write($data, 100, count($data) - 1);
            }
            $start += $per_page;
        }
        $excel->save();
    }
}
