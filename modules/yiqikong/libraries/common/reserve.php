<?php

class Common_Reserve extends Common_Base
{

    public static function delete($data)
    {
        $params = json_decode($data, TRUE);
        try {
            $reserv = O('eq_reserv', $params['reserve_id']);
            $component = O('cal_component', $reserv->component->id);
            if (!$component->id) throw new Error_Exception(parent::$errors[1001]);
            $reserv = O('eq_reserv', ['component' => $component]);
            if (!$reserv->id) throw new Error_Exception(parent::$errors[1001]);

            if (isset($params['user_info']['user_local'])) {
                $user = parent::_MAKEUSER($params['user_info']['yiqikong_id'], $params['user_info']['user_local']);
            } else {
                $user = parent::_MAKEUSER($params['user_info']['yiqikong_id']);
            }

            if (!$user->id) throw new Error_Exception(parent::$errors[1010]);
            Cache::L('ME', $user);
            //有权限, 并且成功
            $operate = $params['operate_user_local'] ? O('user',$params['operate_user_local']) : $user;
            if (!$operate->is_allowed_to('删除', $component)) {
                throw new Error_Exception(parent::$errors[1002]);
            }

            Cache::L('YiQiKongReservAction', TRUE);
            return $component->delete() ? [
                'success' => 1,
                'uuid' => $params[uuid],
                'params' => $params
            ] : [
                'success' => 0,
                'uuid' => $params[uuid],
                'error_msg' => I18N::T('yiqikong', '删除预约失败!')
            ];
        } catch (Error_Exception $e) {
            return ['uuid' => $params[uuid], 'error_msg' => $e->getMessage()];
        }
    }
}