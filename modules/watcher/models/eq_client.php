<?php

class EQ_Client extends Presentable_Model {

    public function save($overwrite = FALSE) {
        if (!$this->ctime) $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}