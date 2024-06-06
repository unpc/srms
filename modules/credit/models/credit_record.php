<?php

// 用户增减信用明细
class Credit_Record_Model extends ORM_Model
{
    public function save($overwrite = false)
    {
        // 拦截用户为空的情况
        if (!$this->user->id) {
            Log::add(strtr('[credit_record]拦截了一条用户为空的信用明细，信息如下%info', [
                '%user_name'        => json_encode($this)
            ]), 'credit');
            return false;
        }

        //计分时间
        if (!$this->ctime) {
            $this->ctime = Date::time();
        }
        //操作时间
        if (!$this->operation_time) {
            $this->operation_time = Date::time();
        }
        return parent::save($overwrite);
    }
}
