<?php
class Entrance_API
{
    public static function access_permission_get($e, $params, $data, $query)
    {
        $door = O('door', ['voucher' => $query['door_id']]);
        if (!$door->id) {
            throw new Exception('door not found', 404);
        }
        $user = O('user', $query['user_id']);
        if (!$user->id) {
            $user = O('user', ['gapper_id' => $query['user_id']]);
            if(!$user->id){
                throw new Exception('user not found', 404);
            }
        }

        $is_allowed = $user->is_allowed_to('刷卡控制', $door, ['direction' => 'in']);

        if (!$is_allowed && $door->cannot_access($user, Date::time(), 'in')) {
            Log::add("[entrance/iot-gdoor api] " . vsprintf(
                "门牌[%d] 开门验证失败: 禁止用户%s[%d] 开门",
                [
                    $door->id,
                    $user->name,
                    $user->id
                ]
            ), 'devices');
        } else {
            Log::add("[entrance/iot-gdoor api] " . vsprintf(
                "门牌[%d] 通过验证: %s[%d]",
                [
                    $door->id,
                    $user->name,
                    $user->id
                ]
            ), 'devices');
            $e->return_value = ['message' => 'yes'];
        }
    }
}
