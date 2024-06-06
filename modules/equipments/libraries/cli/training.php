<?php
class CLI_Training {

    static function training_overdue () {
        $now = Date::time();
        $status = UE_Training_Model::STATUS_APPROVED;
        $trainings = Q("ue_training[atime=1~{$now}][status={$status}]");
        foreach ($trainings as $training) {
            $training->status = UE_Training_Model::STATUS_OVERDUE;
            $training->save();

            //初始化 incharges 避免出现 incharges 累加问题
            $user = $training->user;
            $incharges = [];
            foreach(Q("{$training->equipment} user.incharge") as $i) {
                $incharges[] = Markup::encode_Q($i);
            }
            Notification::send('equipments.training_deleted', $user, [
                '%incharge' => join(', ', $incharges),
                '%user'=>Markup::encode_Q($user),
                '%equipment'=>Markup::encode_Q($training->equipment),
            ]);

        }
    }

    static function delete_expire_training () {
        if (Config::get('training.training_period')) {
            $now = Date::time();
            //遍历需要培训授权的仪器
            foreach(Q('equipment[require_training]') as $e) {
                //过期删除时间
                $expire_time = $e->training_expire_time;
                //不能取所有的培训，应该只取已通过的培训
                $status = UE_Training_Model::STATUS_APPROVED;
                if ($expire_time) {
                    //所有培训
                    foreach(Q("ue_training[equipment={$e}][status={$status}]") as $ue) {
                        $user = $ue->user;

                        $records = Q("eq_record[user={$user}][equipment={$e}][dtend>0]:sort(dtend D):limit(1)");

                        //使用记录结束时间
                        //或者ue的创建时间
                        $dtend = max($records->current()->dtend, $ue->ctime);
                        if (
                            ($now - $dtend > $expire_time) //长时间没使用
                            ||
                            ($ue->atime && $ue->atime < $now) //过期时间设定了过期了
                            )  $ue->delete();
                        }
                }
                else {
                    //未设定时间
                    Q("ue_training[equipment={$e}][status={$status}][atime=1~{$now}]")->delete_all();
                }
            }
        }
    }

    //在删除前notif
    static function training_before_delete_notif () {
        if (Config::get('training.training_period')) {
            $now = Date::get_day_start();
            //遍历需要培训授权的仪器
            foreach(Q('equipment[require_training]') as $e) {
                //过期删除时间
                $expire_time = $e->training_expire_time;
                $status = UE_Training_Model::STATUS_APPROVED;
                if ($expire_time) {
                    //所有培训
                    foreach(Q("ue_training[equipment={$e}][status={$status}]") as $ue) {
                        $user = $ue->user;

                        $records = Q("eq_record[user={$user}][equipment={$e}][dtend>0]:sort(dtend D):limit(1)");

                        //使用记录结束时间
                        //或者ue的创建时间
                        $dtend = max($records->current()->dtend, $ue->ctime);

                        if (
                            $dtend + $expire_time - 6 * 86400 > $now //过期时间前7天
                            &&
                            $dtend + $expire_time - 7 * 86400 < $now //过期时间前8天
                            ) {

                            //初始化 incharges 避免出现 incharges 累加问题
                            $incharges = [];

                            foreach(Q("{$e} user.incharge") as $i) {
                                $incharges[] = Markup::encode_Q($i);
                            }

                            Notification::send('equipments.training_before_delete', $user, [
                                '%user'=> Markup::encode_Q($user),
                                '%equipment'=> Markup::encode_Q($e),
                                '%incharge'=> join(', ', $incharges),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
