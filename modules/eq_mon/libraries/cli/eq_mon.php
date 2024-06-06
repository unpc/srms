<?php

class CLI_EQ_Mon {
    static function empty_eq_chat(){
    	Q('eq_chat')->delete_all();
    }
}
