<?php
class Eq_Reserv_API
{
    public static function equipment_bookings_get($e, $params, $data, $query)
    {
        $selector = "eq_reserv";
        if (isset($query['equipmentId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['equipmentId$']));
            if (!count($ids)) {
                throw new Exception('equipmentId Cannot be empty', 404);
            }
            $selector .= "[equipment_id=" . join(",", $ids) . "]";
        }

        if (isset($query['equipmentId'])) {
            $selector .= "[equipment_id={$query['equipmentId']}]";
        }

        if (isset($query['userId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['userId$']));
            if (!count($ids)) {
                throw new Exception('userId Cannot be empty', 404);
            }
            $selector .= "[user_id=" . join(",", $ids) . "]";
        }
        if (isset($query['labId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['labId$']));
            if (!count($ids)) {
                throw new Exception('labId Cannot be empty', 404);
            }
            $selector = "lab#" . join(",", $ids) . " user " . $selector;
        }

        $selector_times = [];
        if (isset($query['startTime']) && intval($query['startTime'])) {
            $dtstart = intval($query['startTime']);
            $selector_times[] = "[dtstart~dtend={$dtstart}";
        }
        if (isset($query['endTime']) && intval($query['endTime'])) {
            $dtend = intval($query['endTime']);
            $selector_times[] = "dtstart~dtend={$dtend}";
        }
        if ($dtstart && $dtend) {
            $selector_times[] = "dtstart={$dtstart}~{$dtend}";
        }
        if (count($selector_times)) $selector .= "[" . implode("|", $selector_times) . "]";

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(dtstart D)";
        $reservs = [];
        foreach (Q("$selector") as $reserv) {
            $reservs[] = self::reserv_format($reserv);
        }
        $e->return_value = ["total" => $total, "items" => $reservs];
    }
    public static function equipment_booking_get($e, $params, $data, $query)
    {
        $reserv = O('eq_reserv', $params[0]);
        if (!$reserv->id) {
            throw new Exception('reserv not found', 404);
        }
        $e->return_value = self::reserv_format($reserv);
    }


    public static function equipment_booking_delete($e, $params, $data, $query)
    {
        $me = L("gapperUser");
        $reserv = O('eq_reserv', ['id' => $params[0]]);
        $equipment = $reserv->equipment;
        $component = $reserv->component;
        $user = $reserv->user;
        $now = Date::time();

        if (!$me->is_allowed_to('删除', $component)) {
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
        }

        $ret = self::reserv_format($reserv);
        if ($component->delete()) {
            Log::add(strtr('[eq_reserv_api] %user_name[%user_id]删除了%equipment_name[%equipment_id]的预约[%reserv_id]', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_name' => $equipment->name,
                '%equipment_id' => $equipment->id,
                '%reserv_id' => $reserv->id
            ]), 'journal');
            $e->return_value = $ret;
        }
    }

    public static function reserv_permission_post($e, $params, $data, $query)
    {
        $me = L('gapperUser');
        $reserv = O('eq_reserv', ['id' => $params[0]]);
        if (!$reserv->id) {
            throw new Exception('reserv not found', 404);
        }

        $object = O('cal_component', $reserv->component->id);

        if (!$object->id) {
            throw new Exception('reserv component not found', 404);
        }

        $links = [
            'actions' => []
        ];

        if ($me->is_allowed_to('修改', $object)) {
            $links['actions'][] = [
                'title' => I18N::T('equipments', '编辑'),
                'action' => 'edit',
            ];
        }
        if ($me->is_allowed_to('删除', $object)) {
            $links['actions'][] = [
                'title' => I18N::T('equipments', '删除'),
                'action' => 'delete',
            ];
        }

        $links['total'] = count($links['actions']);
        $e->return_value = $links;
    }

    public static function reserv_format($reserv)
    {
        $status = 'requested';
        if (Module::is_installed('approval_flow')) {
            $approval = Q("{$reserv}<source approval")->current();
            switch ($approval->flag) {
                case 'done':
                    $status = 'approved';
                    break;
                case 'rejected':
                    $status = 'rejected';
                    break;
                default:
                    $status = 'requested';
                    break;
            }
        }
        $extra_value = O('extra_value', ['object' => $reserv]);
        return [
            'id' => $reserv->id,
            'user' => [
                'id' => $reserv->user->id,
                'name' => $reserv->user->name,
            ],
            'equipment' => [
                'id' => $reserv->equipment->id,
                'name' => $reserv->equipment->name,
                'icon' => [
                    'original' => $reserv->equipment->icon_url($reserv->equipment->icon_file('real') ? 'real' : 128),
                    '32×32' => $reserv->equipment->icon_url('32')
                ],
            ],
            // bioisland 兼容
            'startTime' => $reserv->dtstart ?: 0,
            'endTime' => $reserv->dtend ?: 0,
            // bioisland ends
            'dtstart' => $reserv->dtstart ?: 0,
            'dtend' => $reserv->dtend ?: 0,
            'description' => $reserv->component->description,
            'status' => $status,
            'extra' => Extra_API::extra_value_format($extra_value)
        ];
    }
}
