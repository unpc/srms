<?php


class EQ_Preheat_Cooling_Model extends Presentable_Model
{
    public function save($overwrite = false)
    {
        $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}
