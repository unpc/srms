<?php

class CLI_Uno_Entries
{
    public static function init()
    {
        $entries = Config::get('uno_entries');
        print_r($entries);
    }
}