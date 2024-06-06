<?php

class Autocomplete_Controller extends AJAX_Controller
{
    public function equipment()
    {
        $form = Input::form();
        $s = trim($form['s']);
        $st = (int)trim($form['st']);
        $labs = trim($form['labs']);
        $pre_selector = "lab[id={$labs}] eq_charge[source_name=eq_record|source_name=eq_sample|source_name=eq_reserv]";
        $start = 0;
        if ($st) {
            $start = $st;
        }
        $n = 5;
        if ($start == 0) {
            $n = 10;
        }
        if ($start >= 100) {
            return;
        }

        if (!$labs || !count($labs)) {
            Output::$AJAX[] = [
                'html' => '<div class="empty">' . T('请先选择课题组!') . '</div>',
                'special' => true
            ];
            return;
        } else {
            $db = Database::factory();
            $sql = "SELECT DISTINCT `t15`.`id` FROM `equipment` `t15` 
            LEFT JOIN (`eq_sample` `t3`, `user` `t1`, `_r_user_lab` r2, `lab` `t0`) 
            ON (`t0`.`id` IN (%s) AND `r2`.`type`='' AND `r2`.`id1`=`t1`.`id` AND `r2`.`id2`=`t0`.`id` AND `t3`.`sender_id`=`t1`.`id` AND `t3`.`equipment_id`=`t15`.`id`) 
            LEFT JOIN (`eq_record` `t8`, `user` `t6`, `_r_user_lab` r7, `lab` `t5`) 
            ON (`t5`.`id` IN (%s) AND `r7`.`type`='' AND `r7`.`id1`=`t6`.`id` AND `r7`.`id2`=`t5`.`id` AND `t8`.`user_id`=`t6`.`id` AND `t8`.`equipment_id`=`t15`.`id`) 
            LEFT JOIN (`eq_reserv` `t13`, `user` `t11`, `_r_user_lab` r12, `lab` `t10`) 
            ON (`t10`.`id` IN (%s) AND `r12`.`type`='' AND `r12`.`id1`=`t11`.`id` AND `r12`.`id2`=`t10`.`id` AND `t13`.`user_id`=`t11`.`id` AND `t13`.`equipment_id`=`t15`.`id`) 
            WHERE (`t13`.`id` IS NOT NULL OR `t8`.`id` IS NOT NULL OR `t3`.`id` IS NOT NULL) ";
            if ($s) {
                $sql .= "AND (`t15`.`name` LIKE '%%{$s}%%' OR `t15`.`name_abbr` LIKE '%%{$s}%%') ";
            }
            $sql .= " LIMIT {$start},{$n}";
        }

        $equipments = $db->query($sql, $labs, $labs, $labs)->rows();

        if ($start == 0 && !count($equipments)) {
            if ($s) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/empty'),
                    'special' => true
                ];
                return;
            } else {
                Output::$AJAX[] = [
                    'html' => '<table class="mini_form"><tr><td class="middle"><h4><span class="name">--</span></h4></td></tr></table>',
                    'alt' => '0',
                    'text' => '--',
                    'special' => true
                ];
                return;
            }
        } else {
            foreach ($equipments as $equipment) {
                $equipment = O('equipment', $equipment->id);
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/equipment', ['equipment'=>$equipment]),
                    'alt' => $equipment->id,
                    'text' => H($equipment->name),
                    'data' => [
                        'incharges' => $incharges,
                    ]
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => true
                ];
            }
        }
    }
}
