<?php

class API_Entrance_Info extends API_Common
{
    public function get_doors($start = 0, $step = 100)
    {
        $this->_ready();
        $doors = Q('door')->limit($start, $step);
        $info = [];

        if (count($doors)) {
            foreach ($doors as $door) {
                $incharges = Q("{$door}<incharge user")->to_assoc('id', 'name');
                $data = new ArrayIterator([
                    'id' => $door->id,
                    'name' => $door->name,
                    'location' => $door->location,
                    'location2' => $door->location2,
                    'incharges' => $incharges,
                    'ctime' => $door->ctime
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }

    public function get_records($addr, $start = 0 ,$step = 10){
        $this->_ready();
        $dc_records = Q("door[in_addr={$addr}] dc_record[direction=1]:sort(time D)")->limit($start, $step);
        $info = [];

        if (count($dc_records)) {
            foreach ($dc_records as $dc_record) {
                $data = new ArrayIterator([
                    'name' => $dc_record->door->name,
                    'user_name' => $dc_record->user->name,
                    'time' => date('Y/m/d H:i:s',$dc_record->time)
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }
}
