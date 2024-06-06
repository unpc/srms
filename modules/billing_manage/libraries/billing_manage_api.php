<?php

class Billing_Manage_Api {

	public static function equipment_format($equipment)
    {
		$db = Database::factory();

        $data = [
            'id' => (int) $equipment->id,
            'name' => (string) $equipment->name
        ];

        return $data;
    }

	public static function billing_equipments_get($e, $params, $data, $query)
    {
        $selector = "equipment";
        $pre_selectors = [];

        if ($query['id']) {
            $id = intval($query['id']);
            $selector .= "[id={$id}]";
        }

        if ($query['equipment_name']) {
            $selector .= "[name*={$query['equipment_name']}]";
        }
        
        if ($query['group_id']) {
            $group = O('tag_group', intval($query['group_id']));
            if ($group->id) {
                $pre_selectors['group'] = "{$group}";
            } else {
                $selector .= "[group_id={$query['group_id']}]";
            }
        }

        $queryTime = "";
        if ($query['start_time']) {
            $query['start_time'] = strtotime($query['start_time']);
            $queryTime .= "[ctime>={$query['start_time']}]";
        }
        if ($query['end_time']) {
            $query['end_time'] = strtotime($query['end_time']);
            $queryTime .= "[ctime<{$query['end_time']}]";
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        if ($query['type'] && $query['type'] == 'amount') {
            $amount = Q("$selector eq_charge$queryTime")->SUM('amount');
            $e->return_value = $amount;
            return;
        }
        $total = Q($selector)->total_count();

        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page})";
        $equipments = Q($selector);
        $items = [];
        foreach ($equipments as $equipment) {
            $item = self::equipment_format($equipment);
            $item['eq_amount'] = Q("$equipment eq_charge$queryTime")->SUM('amount');
            $items[] = $item;
        }

        $ret = [
            'total' => $total,
            'st' => $start,
            'pp' => $per_page,
            'items' => $items,
        ];
        $e->return_value = $ret;
        return;
    }

}

