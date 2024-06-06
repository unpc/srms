<?php
class Eq_Charge_API
{
    public static function equipment_charges_get($e, $params, $data, $query)
    {
        $selector = "eq_charge[amount]";

        $user = L('gapperUser');
        
        if (isset($query['equipmentId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['equipmentId$']));
            if (!count($ids)) {
                throw new Exception('equipmentId Cannot be empty', 404);
            }

            // 查询固定仪器的记录，任意一台没有权限,就应该让他只能看到自己的
            foreach (Q("equipment[id=" . join(",", $ids) . "]") as $equipment) {
                if (!$user->is_allowed_to('查看收费情况', $equipment)) {
                    $selector = "{$user} eq_charge[amount]";
                    break;
                }
            }

            $selector .= "[equipment_id=" . join(",", $ids) . "]";
        } else {
            if (!$user->is_allowed_to('查看收费情况', 'equipment')) {
                if (!isset($query['userId$']) && !isset($query['labId$'])) {
                    $selector = "{$user} eq_charge[amount]";
                }
            }
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
            $selector .= "[lab_id=" . join(",", $ids) . "]";
        }

        if (isset($query['startTime']) && $query['endTime']) {
            $dtstart = intval($query['startTime']);
            $selector .= "[ctime>={$dtstart}]";
        }
        if (isset($query['endTime']) && $query['endTime']) {
            $dtend = intval($query['endTime']);
            $selector .= "[ctime>0][ctime<={$dtend}]";
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(ctime D)";
        $charges = [];
        error_log($selector);
        foreach (Q("$selector") as $charge) {
            $charges[] = self::charge_format($charge);
        }
        $e->return_value = ["total" => $total, "items" => $charges];
    }

    public static function charge_format($charge)
    {
        $icon_url = Config::get('uno.icon_url') ?: Config::get('system.base_url');
        $ret = [
            'id' => Number::fill($charge->transaction_id ?: $charge->id, 6),
            'user' => [
                'id' => $charge->user->id,
                'name' => $charge->user->name,
            ],
            'equipment' => [
                'id' => $charge->equipment->id,
                'name' => $charge->equipment->name,
                'icon' => [
                    'original' => $charge->equipment->icon_url($charge->equipment->icon_file('real') ? 'real' : 128),
                    '32×32' => $charge->equipment->icon_url('32'),
                ],
            ],
            'amount' => $charge->amount,
            'description' => strip_tags($charge->description),
            'ctime' => $charge->ctime,
            'context' => []
        ];
        switch ($charge->source->name()) {
            case 'eq_record':
                $ret['charge_type'] = '使用收费';
                $ret['context']['log'] = [
                    'id' => $charge->source->id,
                ];
                break;
            case 'eq_reserv':
                $ret['charge_type'] = '预约收费';
                $ret['context']['booking'] = [
                    'id' => $charge->source->id,
                ];
                break;
            case 'eq_sample':
                $ret['charge_type'] = '送样收费';
                $ret['context']['sample'] = [
                    'id' => $charge->source->id,
                ];
                break;
        }

        if ($chargeDuration = explode(" - ", $charge->charge_duration_blocks)) {
            $ret['context']['chargeDuration'] = $chargeDuration;
        }
        return $ret;
    }
}
