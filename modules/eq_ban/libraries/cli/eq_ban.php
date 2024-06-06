<?php
class CLI_EQ_Ban
{
    public static function delete_expire_banned()
    {
        $now = Date::get_day_start(time());
        $banneds = Q("eq_banned[atime][atime<={$now}]");
        $banneds->delete_all();
    }

    public static function clear_all()
    {
        $banneds = Q('eq_banned[equipment]');
        foreach ($banneds as $banned) {
            $user = $banned->user;
            $equipment = $banned->equipment;
            if ($banned->delete()) {
                echo T("删除仪器%equipment[%equipment_id] 黑名单用户%user[%user_id]\n", [
                    '%equipment' => $equipment->name,
                    '%equipment_id' => $equipment->id,
                    '%user' => $user->name,
                    '%user_id' => $user->id,
                ]);
            }
        }
    }

    public static function export_ban()
    {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $title = json_decode($params[3], true);
        $eq_bans = Q($selector);
        $excel = new Excel($params[1]);
        $excel->write($title);
        foreach ($eq_bans as $eq_ban) {
            $data = [];
            foreach ($valid_columns as $key => $value) {
                switch ($key) {
                    case 'name':
                        $data[] = $eq_ban->user->name;
                        break;
                    case 'ctime':
                        $data[] = Date::format($eq_ban->ctime, 'Y/m/d');
                        break;
                    case 'atime':
                        $data[] = Date::format($eq_ban->atime, 'Y/m/d');
                        break;
                    default:
                        $data[] = trim($eq_ban->$key);
                        break;
                }
            }
            $excel->write($data);
        }
        $excel->save();
    }
    public static function export_ban_unseal()
    {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $title = json_decode($params[3], true);
        $eq_bans = Q($selector);
        $excel = new Excel($params[1]);
        $excel->write($title);
        foreach ($eq_bans as $eq_ban) {
            $data = [];
            foreach ($valid_columns as $key => $value) {
                switch ($key) {
                    case 'name':
                        $data[] = $eq_ban->user->name;
                        break;
                    case 'unsealing_user':
                        $data[] = $eq_ban->unsealing_user->name;
                        break;
                    case 'unsealing_ctime':
                        $data[] = Date::format($eq_ban->unsealing_ctime, 'Y/m/d');
                        break;
                    case 'ctime':
                        $data[] = Date::format($eq_ban->ctime, 'Y/m/d');
                        break;
                    case 'atime':
                        $data[] = Date::format($eq_ban->atime, 'Y/m/d');
                        break;
                    case 'lab':
                        $data[] = $eq_ban->lab->name;
                        break;
                    default:
                        $data[] = trim($eq_ban->$key);
                        break;
                }
            }
            $excel->write($data);
        }
        $excel->save();
    }
    public static function export_violation()
    {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
        $title = json_decode($params[3], true);
        $violations = Q($selector);
        $excel = new Excel($params[1]);
        $excel->write($title);

        foreach ($violations as $violation) {
            $data = [];
            foreach ($valid_columns as $key => $value) {
                switch ($key) {
                    case 'name':
                        $data[] = $violation->user->name;
                        break;
                    case 'total':
                        $data[] = $violation->total_count;
                        break;
                    case 'late':
                        $data[] = $violation->eq_late_count;
                        break;
                    case 'leave_early':
                        $data[] = $violation->eq_leave_early_count;
                        break;
                    case 'overtime':
                        $data[] = $violation->eq_overtime_count;
                        break;
                    case 'miss':
                        $data[] = $violation->eq_miss_count;
                        break;
                    default:
                        $data[] = trim($eq_ban->$key);
                        break;
                }
            }
            $excel->write($data);
        }
        $excel->save();
    }
}
