<?php

/**
 * 设置填报者相关权限等功能
 */
class API_Filler extends Base
{
    public function setFiller($new_filler_id, $old_filler_id = 0)
    {
        $this->_ready();

        if (!$new_filler_id) {
            throw new API_Exception(self::$errors[402], 402);
        }

        if ($old_filler_id) {
            $old_filler            = O('user', $old_filler_id);
            $old_filler->is_filler = false;
            $old_filler->save();
        }

        $new_filler            = O('user', $new_filler_id);
        $new_filler->is_filler = true;
        $new_filler->save();

        $res['status'] = 'success';
        return $res;
    }
}
