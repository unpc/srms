<?php

// 用户信用等级
class Credit_Level_Model extends ORM_Model
{
    public function save($overwrite = false)
    {
        if (!$this->ctime) {
            $this->ctime = Date::time();
        }
        return parent::save($overwrite);
    }
}
