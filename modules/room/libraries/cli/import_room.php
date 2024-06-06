<?php

class CLI_Import_Room
{
    public static function main($file)
    {

/* 读取输入文件 */
        $csv = new CSV($file, 'r');

        $escape_n_rows = 1;
        for (;$escape_n_rows--;) {
            $csv->read(',');
        }

        while ($row = $csv->read(',')) {
            $name = trim($row[0]);
            $room = O('room', ['name' => $name]);
            if (!$room->id) {
                $room->name = $name;
            }
            $room->save();
        }
    }
}
