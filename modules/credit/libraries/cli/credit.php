<?php

class CLI_Credit
{
    public static function update_user_credit()
    {
        foreach (Q("user") as $user) {
            $user->init_credit = true;
            $user->save();
        }
    }

    public static function update_user_levels()
    {
        $db      = Database::factory();
        $credits = Q("credit");
        $arr     = [];

        foreach ($credits as $credit) {
            $user  = $credit->user;
            $score = $credit->total;

            if (!$user->id) {
                continue;
            }

            if (array_key_exists($score, $arr)) {
                $credit->percent      = $arr[$score]['percent'];
                $credit->credit_level = $arr[$score]['level'];
                $credit->line         = $arr[$score]['line'];
                $credit->utime        = Date::time();
                $credit->save();
                continue;
            }

            // 系统总行数
            // 不要放到foreach之上，会出现$total_line小于$line的情况
            $sql        = "select count(*) from credit";
            $total_line = $db->value($sql);

            // 当前用户所在credit中的行数
            $sql = "select d.rownum from (
                    SELECT * FROM (SELECT @rownum:=0) c,
                    (SELECT a.*, @rownum:=IFNULL(@rownum, 0) + 1 rownum FROM credit a ORDER BY a.total) b ) d where d.user_id = {$user->id}";
            $line = $db->value($sql);

            $percent = (int) (100 - ($line / $total_line) * 100);
            $line    = $total_line - $line;
            $line    = $line == 0 ? $line + 1 : $line;
            // echo $user->id . ' - ' . $credit->total . ' - ' . $line . ' - ' . $percent . '%' . PHP_EOL;
            $credit->utime        = Date::time();
            $credit_level         = Q("credit_level[rank_start<={$percent}][rank_end>{$percent}]")->current();
            $credit->credit_level = $credit_level;
            $credit->line         = $line;
            $percent = 100 - $percent;
            $percent = $percent == 0 ? $percent + 1 : $percent;
            $percent = $percent == 100 ? $percent - 1 : $percent;
            $credit->percent      = $percent;
            $credit->save();
            $arr[$score] = ['percent' => $percent, 'level' => $credit_level, 'line' => $line];
        }
    }

    public static function update_user_feedback()
    {
        // 5天内的使用记录
        $dtend   = Date::get_day_start();
        $dtstart = $dtend - 86400 * 5;
        $records = Q("eq_record[!feedback][dtend<={$dtend}][dtstart>={$dtstart}]");
        foreach ($records as $r) {
            if ($r->credit_count) {
                continue;
            }

            Event::trigger('trigger_scoring_rule', $r->user, 'feedback', $r->equipment);
            $r->credit_count = 1;
            $r->save();
        }
    }
}
