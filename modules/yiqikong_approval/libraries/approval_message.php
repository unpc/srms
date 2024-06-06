<?php

class Approval_Message {

    public static function create($approval) {
        $equipment = $approval->equipment;
        $incharges = Q("$equipment<incharge user");
        if ($incharges->total_count()) {
            foreach ($incharges as $incharge) {
                Notification::send('approval_create', $incharge, [
                    '%incharge' => $incharge->name,
                    '%equipment'=> Markup::encode_Q($approval->equipment),
                    '%ctime'=> Date::format($approval->ctime, 'Y/m/d H:i:s'),
                    '%user' => $approval->user->name,
                    '%dtstart' => Date::format($approval->source->dtstart, 'Y/m/d H:i:s'),
                ]);
            }
        }
        return;
    }
    public static function result($result, $approval) {
        $me = L('ME');
        Notification::send('approval_result', $approval->user, [
            '%ctime'=> Date::format($approval->ctime, 'Y/m/d H:i:s'),
            '%equipment'=> Markup::encode_Q($approval->equipment),
            '%auditor' => $me->name,
            '%result' => $result
        ]);
    }

    public static function expired($approval) {
        $equipment = $approval->equipment;
        $incharges = Q("$equipment<incharge user");
        if ($incharges->total_count()) {
            foreach ($incharges as $incharge) {
                Notification::send('approval_expired', $incharge, [
                    '%ctime'=> Date::format($approval->ctime, 'Y/m/d H:i:s'),
                    '%equipment'=> Markup::encode_Q($approval->equipment)
                ]);
            }
        }
        Notification::send('approval_expired', $approval->user, [
            '%ctime'=> Date::format($approval->ctime, 'Y/m/d H:i:s'),
            '%equipment'=> Markup::encode_Q($approval->equipment)
        ]);

        return;
    }
}
