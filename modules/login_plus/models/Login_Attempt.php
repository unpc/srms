<?php

class Login_Attempt_Model extends Presentable_Model {

    function save($overwrite = FALSE) {
        $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}