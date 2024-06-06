<?php

class Approved_Model extends Presentable_Model {

    public function save($overwrite = FALSE) {
        $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}
