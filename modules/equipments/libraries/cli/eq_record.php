<?php
class CLI_EQ_Record {

    static function update_record_flag() {
        $records = Q('eq_record');
        foreach ($records as $record) {
            $reserv = $record->reserv;
            if ($reserv->id) {
                $status = $reserv->get_status(FALSE, $record);
                $record->flag = $flag;
                $record->save();
            }
        }
    }
}