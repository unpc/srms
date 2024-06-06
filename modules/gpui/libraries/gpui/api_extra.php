<?php
class GPUI_API_Extra
{
    public static function equipment_detail_basic($e, $equipment, $params, $data)
    {
        if ($params["basic"]) {
            $data["id"] = $equipment->id;
            $data["picture"] =  $equipment->icon_file('real')
                ? Config::get('system.base_url') . Cache::cache_file($equipment->icon_file('real')) . '?_=' . $equipment->mtime
                : $equipment->icon_url('128');
            $data["name"] = $equipment->name;
            $charge = [];
            foreach (Q("{$equipment} user.incharge") as $user) {
                $charge[] = $user->name;
            }
            $data["charge"] =  $charge;
            $data["accept_reserv"] = $equipment->accept_reserv;
            $data["accept_sample"] = $equipment->accept_sample;
            $data["phone"] = $equipment->phone;
            $data["status"] = $equipment->status;
            if ($equipment->yiqikong_id) {
                $data["qrcode"] = Config::get('wechat.wechat_equipment_url').$equipment->yiqikong_id;
            }
        }
        if ($params["info"]) {
            $data["tech_specs"] = $equipment->tech_specs;
            $data["features"] = $equipment->features;
            $data["configs"] = $equipment->configs;
        }

        if ($params["using"]) {
            $now = Date::time();
            $data['using'] = [];
            //获取当前使用者
            $record = Q("eq_record[equipment={$equipment}][dtstart<{$now}][dtend=0]:sort(dtstart D):limit(1)")->current();
            $member_type = in_array($record->user->member_type,[0,1,2,3]) ? '学生' : in_array($record->user->member_type,[10,11,12,13]) ? '教师' : '其他';
            if ($record->id) {
                // 当前使用者使用者信息包括：使用者姓名、所属课题组、预约时段；
                $data['using'] = [
                    "record_id" => (int)$record->id,
                    'user' => $record->user->token,
                    'user_id' => $record->user->id,
                    "avatar" => $record->user->icon_url('128'),
                    "card_no" => $record->user->card_no,
                    "name" => $record->user->name,
                    "lab" => H(join(' ', Q("{$record->user} lab")->to_assoc('id', 'name'))),
                    "dtstart" => (int) $record->dtstart,
                    "dur" => Date::format_duration($record->dtstart, $now, 'i'),
                    // 以下2个字段供仪器平板上机时, 是否显示仪器设置判断
                    'is_admin' => $record->user->access('管理所有内容') ? TRUE : FALSE,
                    'equipments' => Q("$record->user<incharge equipment")->to_assoc('id', 'id'),
                    'member_type' => $member_type
                ];

                if (!Module::is_installed('eq_reserv')) {
                    return;
                }
                $reserv = $record->reserv;
                if ($reserv->id) {
                    $data["using"]["reserv_start"] = (int)$reserv->dtstart;
                    $data["using"]["reserv_end"] = (int)$reserv->dtend;
                }
            }
        }
    }

    public static function equipment_detail_stat($e, $equipment, $params, $data)
    {
        $db = Database::factory();
        if ($params["stat"]) {
            $ids = " and `eq_record`.`equipment_id` in (".$equipment->id.")";
            //运行机时 使用总机时、使用次数（使用总机时=该仪器使用记录中使用时长总和；使用次数=该仪器使用记录总条数）
            $selector = " FROM `eq_record` WHERE `dtend` > `dtstart` {$ids}" ;
            $data["useNum"] = $db->value("SELECT COUNT(`id`) {$selector}");
            $data["useDur"] = $db->value("SELECT sum(`dtend`-`dtstart`) {$selector}");
        }
    }

    public static function equipment_detail_reserv($e, $equipment, $params, $data)
    {
        if (!Module::is_installed('eq_reserv')) {
            return;
        }

        if ($params["reservList"]) {
            //预约记录
            $data["reservList"] = [];
            $dtstart = Date::get_week_start();
            $dtend= Date::get_week_end();
            $reservs = Q("{$equipment} eq_reserv[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart A)");
            foreach ($reservs as $reserv) {
                $data["reservList"][] = [
                    "img" => $reserv->user->icon_url('128'),
                    "name" => H($reserv->user->name),
                    'labs' => $reserv->project->lab->id ? H($reserv->project->lab->name) : H(Q("{$reserv->user} lab")->current()->name),
                    'start' => (int)$reserv->dtstart,
                    'end' => (int)$reserv->dtend,
                ];
            }
        }
        if ($params["reservNext"]) {
            $data["reservNext"] = [];
            $reserv = Q("{$equipment} eq_reserv[dtstart>{$now}]:sort(dtstart A):limit(1)")->current();
            if ($reserv->id) {
                $data["reservNext"] = [
                    "img" => $reserv->user->icon_url('128'),
                    "name" => H($reserv->user->name),
                    'labs' => $reserv->project->lab->id ? H($reserv->project->lab->name) : H(Q("{$reserv->user} lab")->current()->name),
                    'start' => (int)$reserv->dtstart,
                    'end' => (int)$reserv->dtend,
                ];
            }
        }
    }

    public static function equipment_detail_jarvis($e, $equipment, $params, $data)
    {
        if ($params["jarvis"]) {
            $data["code"] = $equipment->watcher_code;
            $data["control_address"] = $equipment->control_address;

            $now = Date::time();
            $current_record = Q("eq_record[equipment={$equipment}][dtstart<={$now}][dtend=0]")->current();

            if (!$current_record->id) {
                return;
            }
            if (Module::is_installed('eq_reserv')) {
                $now = Date::time();
                $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart<={$now}][dtend>={$now}]")->current();
                if ($reserv->id) {
                    $data['reserv_dtstart'] = $reserv->dtstart;
                    $data['reserv_dtend'] = $reserv->dtend;
                }
            }
        }
    }
}
