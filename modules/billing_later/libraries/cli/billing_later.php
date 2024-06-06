<?php

class CLI_Billing_Later {

    static function updateLabGrant($lab) {
        if (!$lab->id) return;
        $opt = Config::get('rpc.servers')['billing.later'];
        $rpc = new RPC($opt['api']);

        $hideGrants = $showGrants = [];

        if ($rpc->Grant->Authorize($opt['client_id'], $opt['client_secret'])) {
            $result = $rpc->Grant->searchGrants(['labId' => $lab->id]);

            if (!$result['total']) return;

            $start = 0;
            $num = 20;

            while ($start <= $result['total']) {
                $grants = $rpc->Grant->getGrants($result['token'], $start, $num);
                foreach ($grants as $key => $grant) {
                    Event::trigger('billing_later.update_lab_grant', $grant, $lab);
                    // hideGrants, showGrants 是diguangzhao写的中南magic，重构前don't touch
                    if ($grant['hide']) {
                        $hideGrants[$key] = $grant['card'];
                    }
                    else {
                        $showGrants[$key] = $grant['card'];
                    }
                }
                $start += $num;
            }
        }

        $lab->showGrants = $showGrants;
        $lab->hideGrants = $hideGrants;
        $lab->save();

    }

    static function updateLabsGrant() {
        $labs = Q("lab");
        $totalCount = $labs->total_count();

        $start = 0;
        $step = 5;

        while ($start <= $totalCount) {

            foreach($labs->limit($start, $step) as $lab) {
                self::updateLabGrant($lab);
            }

            $start += $step;
        }
    }

    static function syncChargeLocked() {
        /* 设定时间点 */
        $times = Config::get('billinglater.lock.time', ['value'=>3, 'format'=>'m']);
        $interval = Date::convert_interval($times['value'], $times['format']);

        $now = getdate(time());
        $dtend = mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']);
        $time = $dtend - $interval;

        $charges = Q("eq_charge[is_locked=0][ctime<={$time}]");

        $db = Database::factory();

        foreach ($charges as $charge) {
            $object = $charge->source;
            if (!$object->id) continue;
            if ($object->name() == 'eq_record' && !$object->is_locked) continue;

            //定制化锁定charge需求
            if (Event::trigger('charge.locked_custom', $object)) continue;

            $chargeId = $charge->id;

            $db->query("UPDATE eq_charge SET is_locked = 1 WHERE id = %d", $chargeId);
            Log::add(strtr('[billing_later] 锁定eq_charge:is_locked[%id]', ['%id'=> $chargeId]), 'journal');
        }

    }

    static function syncBlStatus() {
        $opt = Config::get('rpc.servers')['billing.later'];
        $rpc = new RPC($opt['api']);
        $db = Database::factory();

        if ($rpc->Item->Authorize($opt['client_id'], $opt['client_secret'])) {
            $charges = Q("eq_charge[remoteLock=1][bl_status<2]");
            foreach ($charges as $charge) {
                $item = $rpc->Item->GetItem($charge->transaction->id);
                if ($item['status']) {
                    $res = $db->query("UPDATE eq_charge SET bl_status = %d WHERE id = %d", $item['status'], $charge->id);
                    Log::add(strtr('[billing_later] 同步eq_charge:bl_status = %bl_status[%id]', ['%bl_status' => $item['status'], '%id'=> $charge->id]), 'journal');
                }
            }
        }
    }
}
