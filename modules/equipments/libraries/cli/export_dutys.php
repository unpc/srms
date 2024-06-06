<?php

class CLI_Export_Dutys
{

    static function export()
    {
        $params = func_get_args();

        $form = json_decode($params[0],true);
        $sql = EQ_Record::mksql($form['form'] ?? []);
        $valid_columns = json_decode($params[2], true);
        $dutys = Database::factory()->query($sql)->rows();
        $excel = new Excel($params[1]);

        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($dutys as $duty) {
            $data = new ArrayIterator;
            if (array_key_exists('duty_teacher_id', $valid_columns)) {
                $data['duty_teacher_id'] = $duty->duty_teacher_id ? O('user', $duty->duty_teacher_id)->name : '';
            }
            if (array_key_exists('used_dur', $valid_columns)) {
                $data['used_dur'] = $duty->record_used_dur ? round($duty->record_used_dur / 3600, 2) : 0;
            }
            if (array_key_exists('sample_dur', $valid_columns)) {
                $data['sample_dur'] = $duty->sample_dur ? round($duty->sample_dur / 3600, 2) : 0;
            }
            if (array_key_exists('record_counts', $valid_columns)) {
                $data['record_counts'] = $duty->record_sample_counts ?? 0;
            }
            if (array_key_exists('sample_counts', $valid_columns)) {
                $data['sample_counts'] = $duty->sample_counts ?? 0;
            }
            if (array_key_exists('amount', $valid_columns)) {
                $record_amount = $duty->record_amount ?: 0;
                $sample_amount = $duty->sample_amount ?: 0;
                $data['amount'] = $record_amount + $sample_amount;
            }
            if (array_key_exists('service_users', $valid_columns)) {
                $uids = $duty->user_id ? explode(',', $duty->user_id) : [];
                $uids = array_unique($uids);
                $data['service_users'] = count($uids);
            }
            if (array_key_exists('service_labs', $valid_columns)) {
                $uids = $duty->user_id ? explode(',', $duty->user_id) : [];
                $uids = implode(',', array_unique($uids));
                $data['service_labs'] = Q("user[id={$uids}] lab")->total_count() ?: 0;
            }

            $data = array_replace($valid_columns, iterator_to_array($data));
            $data = array_values($data);
            $excel->write($data);
        }
        $excel->save();
    }
}
