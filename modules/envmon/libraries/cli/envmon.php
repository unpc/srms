<?php

class CLI_Envmon
{
    public static function abnormal_nodata_notification()
    {
        $process = popen("ps -ef | grep abnormal_nodata_notification", 'r');
        $output = fread($process, 1024);
        $infos = array_filter(explode("\n", $output));
        array_pop($infos);
        array_pop($infos);

        if (count($infos) > 2) {
            return;
        }

        $now = time();
        $page = 50;
        $num = 0;
        $start = $num * $page;

        $sensors = Q("env_sensor:limit({$start},{$page})");

        while ($sensors->length() > 0) {
            //所有在监控的传感器
            foreach ($sensors as $sensor) {
                if ($sensor->status == Env_Sensor_Model::IN_SERVICE && $sensor->data_alarm) {
                    $node = $sensor->node;

                    $limit_nodata_times = $sensor->nodata_check_status ? $sensor->limit_nodata_times : Config::get('envmon.limit_nodata_times', 3);
                    //检测间隔
                    $check_nodata_time = ($sensor->nodata_check_status ? $sensor->check_nodata_time : Config::get('envmon.check_nodata_time', 5)) * 60;
                    $nodata_alert_time = ($sensor->nodata_check_status ? $sensor->nodata_alert_time : Config::get('envmon.nodata_alert_time', 5)) * 60;

                    $db = ORM_Model::db('env_datapoint');
                    //count用来判断是否有数据
                    $count = $db->value("SELECT COUNT(value) FROM env_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $now - $nodata_alert_time, $now, $sensor->id);
                    $count = round($count, 1);
                    //报警的次数
                    $alert_times = (int)$sensor->_alert_time_nodata;
                    $warning_time = (int)$sensor->_warning_nodata_time;

                    if ($count == 0) {
                        if (($now > $check_nodata_time + $sensor->ctime) && ($limit_nodata_times == 0 || $alert_times < $limit_nodata_times) && $now - $warning_time >= $check_nodata_time) {
                            $nodata_dtstart = $now - $nodata_alert_time;
                            $content = [
                                    '%node'=> $sensor->node->name,
                                    '%sensor' => $sensor->name,
                                    '%dtstart' => Date::format($nodata_dtstart),
                                    '%dtend' => Date::relative($now, $nodata_dtstart),
                                ];
                            self::send_notification('envmon.sensor.nodata', $sensor, $content);

                            $sensor->_alert_time_nodata ++;
                            $sensor->_warning_nodata_time = $now;
                            if ($sensor->save()) {
                                $node->alarm = Env_Node_Model::ALARM_NODATA_ABNORMAL;
                                $node->save();
                            }

                            if ($sensor->_alert_time_nodata == 1) {
                                $env_sensor_alarm = O('env_sensor_alarm');
                                $env_sensor_alarm->sensor = $sensor;
                                $env_sensor_alarm->dtstart = $nodata_dtstart;
                                $env_sensor_alarm->save();
                            }
                        }
                    } else {
                        $env_sensor_alarm = Q("env_sensor_alarm[dtend=0][sensor={$sensor}]:sort(ctime D):limit(1)")->current();
                        if ($env_sensor_alarm->id) {
                            $env_sensor_alarm->dtend = $now;
                            $env_sensor_alarm->save();
                        }
                        $sensor->_alert_time_nodata = 0;
                        if ($sensor->save() && $node->alarm == Env_Node_Model::ALARM_NODATA_ABNORMAL) {
                            $node->alarm = Env_Node_Model::ALARM_NORMAL;
                            $node->save();
                        }
                    }


                    //数据异常的处理
                    $limit_abnormal_times = $sensor->abnormal_check_status ? $sensor->limit_abnormal_times : Config::get('envmon.limit_abnormal_times', 3);
                    //检测间隔
                    $check_abnormal_time = ($sensor->abnormal_check_status ? $sensor->check_abnormal_time : Config::get('envmon.check_abnormal_time', 5)) * 60;
                    //检测时段
                    $alert_time = ($sensor->abnormal_check_status ? $sensor->alert_time : Config::get('envmon.alert_time', 5)) * 60;

                    //报警次数
                    $alert_times = (int)$sensor->_alert_times_abnormal;
                    //上次报警时间
                    $warning_time = (int)$sensor->_warning_time;
                    //正常后第一次报警时间
                    $first_warning_time = (int)$sensor->_first_warning_time;

                    $db = ORM_Model::db('env_datapoint');
                    $unusual = $db->value("SELECT `value` FROM `env_datapoint` WHERE `sensor_id` = %d ORDER BY `id` DESC limit 1", $sensor->id);
                    if (($unusual > $sensor->vto || $unusual < $sensor->vfrom) && $unusual !== null) {
                        //在检测间隔后，次数小于设置时产生报警
                        if (($now > $check_abnormal_time + $sensor->ctime) && ($limit_abnormal_times == 0 || $alert_times < $limit_abnormal_times) && $now - $warning_time >= $check_abnormal_time) {
                            if ($first_warning_time == 0) {
                                $sensor->_first_warning_time = $now;
                                $sensor->save();
                            } elseif (($now - $first_warning_time) >= ($alert_time - 5)) {
                                //这次警报的时间
                                $abnormal_dtstart = $now - $alert_time;
                                $unusual = round($unusual, 1);
                                $content = array(
                                    '%node'=> $sensor->node->name,
                                    '%sensor' => $sensor->name,
                                    '%dtstart' => Date::format($abnormal_dtstart),
                                    '%dtend' => Date::relative($now, $abnormal_dtstart),
                                    '%data' => $unusual.$sensor->unit(),
                                    '%alert_data'=> $unusual.$sensor->unit(),
                                    '%standard_start'=> $sensor->vfrom.$sensor->unit(),
                                    '%standard_end'=> $sensor->vto.$sensor->unit(),
                                    );

                                self::send_notification('envmon.sensor.warning', $sensor, $content);

                                $sensor->_warning_time = $now;
                                $sensor->_alert_times_abnormal++;
                                if ($sensor->save()) {
                                    $node->alarm = Env_Node_Model::ALARM_UNUSUAL_ABNORMAL;
                                    $node->save();
                                }

                                if ($sensor->_alert_times_abnormal == 1) {
                                    $env_sensor_alarm = O('env_sensor_alarm');
                                    $env_sensor_alarm->sensor = $sensor;
                                    $env_sensor_alarm->dtstart = $abnormal_dtstart;
                                    $env_sensor_alarm->save();
                                }
                            }
                        }
                    } else {
                        //如果数据正常了设置为0
                        $env_sensor_alarm = Q("env_sensor_alarm[dtend=0][sensor={$sensor}]:sort(ctime D):limit(1)")->current();
                        if ($env_sensor_alarm->id) {
                            $env_sensor_alarm->dtend = $now;
                            $env_sensor_alarm->save();
                        }
                        $sensor->_first_warning_time = 0;
                        $sensor->_alert_times_abnormal = 0;
                        if ($sensor->save()) {
                            if ($node->alarm == Env_Node_Model::ALARM_UNUSUAL_ABNORMAL) {
                                $node->alarm = Env_Node_Model::ALARM_NORMAL;
                                $node->save();
                            }
                        }
                    }
                }
            }

            unset($sensors);
            $num++;
            $start = $num * $page;
            $sensors = Q("env_sensor:limit({$start},{$page})");
        }
    }

    //发送消息
    private static function send_notification($notify_key, $sensor, $content)
    {
        $node = $sensor->node;

        //监控对象负责人
        $send_users = Q("{$node} user.incharge")->to_assoc('id', 'id');

        //系统设置中需要发送的人
        $tokens = Config::get('envmon.admin');
        $tokens = is_array($tokens) ? $tokens : [$tokens];
        foreach ($tokens as $token) {
            $user = O('user', ['token' => Auth::normalize($token)]);
            if ($user->id) {
                $token_users[] = $user->id;
            }
        }

        $user_array = $send_users + $token_users;

        $u = implode(',', $user_array);
        $users = Q("user[id={$u}]");

        foreach ($users as $user) {
            $content['%user'] = Markup::encode_Q($user);
            Notification::send($notify_key, $user, $content);
        }
    }

    public static function check_871()
    {
        /*
          在串口服务器能连到服务器, 但所有传感器都不正常时,
          可用此脚本检测基站(871)是否活着及其频道;

          步骤如下:
          0. 现场短接 871 左1 和 左2 线 !!!!
          1. 修改 xinetd 配置, 让 tszz 转至本脚本, 即
             server = /home/xiaopei.li/lims2/cli/cli.php
             参数填写：
             server_args = envmon check_871
          2. 确保此脚本 xinetd 配置所述 user 或 group 可执行
          3. 重启 xinetd
          4. kill 相应的 php, 使 tszz 重连
          5. tail /var/log/php5/cli.log

          命令 ： AA 40 00 00 BB <发送命令之前需要短接z-871机站设备的L1、L2线>
          返回 ： AA 4F [发射功率] [发射频道] [信号强度] BB

          (xiaopei.li@2013-02-05)
        */

        $check_871_cmd = chr(0xAA) . chr(0x40) . chr(0x00) . chr(0x00) . chr(0xBB);

        while (1) {
            self::log_command($check_871_cmd);
            $ret = @fwrite(STDOUT, $check_871_cmd);
            if ($ret === false) {
            }

            sleep(1); // wait for recv

            $raw = @fread(STDIN, 1048576);
            self::log_command($raw, true);

            sleep(10); // wait for next check
        }
    }

    private static function log_command($data, $recv = false)
    {
        if ($recv) {
            $log_mode = "RECV <== ";
        } else {
            $log_mode = "SEND ==> ";
        }

        $hex = '';
        for ($i=0; $i<strlen($data); $i++) {
            $hex .= sprintf("%02X ", ord($data[$i]));
        }
    }

    public static function flush()
    {
        $sensors = Q("env_sensor");

        $now = time();

        foreach ($sensors as $sensor) {
            $node = $sensor->node;

            if ($sensor->status == Env_Sensor_Model::IN_SERVICE && $sensor->data_alarm) {
                $node = $sensor->node;

                $nodata_alert_time = ($sensor->nodata_check_status ? $sensor->nodata_alert_time : Config::get('envmon.nodata_alert_time', 5)) * 60;

                $db = ORM_Model::db('env_datapoint');

                $count = $db->value("SELECT COUNT(value) FROM env_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $now - $nodata_alert_time, $now, $sensor->id);
                $count = round($count, 1);
                //报警的次数
                $alert_times = (int)$sensor->_alert_time_nodata;
                $warning_time = (int)$sensor->_warning_nodata_time;

                if ($count == 0) {
                    $node->alarm = Env_Node_Model::ALARM_NODATA_ABNORMAL;
                    $node->save();
                }

                $db = ORM_Model::db('env_datapoint');
                $unusual = $db->value("SELECT `value` FROM `env_datapoint` WHERE `sensor_id` = %d ORDER BY `id` DESC limit 1", $sensor->id);
                if (($unusual > $sensor->vto || $unusual < $sensor->vfrom) && $unusual !== null) {
                    $node->alarm = Env_Node_Model::ALARM_UNUSUAL_ABNORMAL;
                    $node->save();
                }
            }
        }
    }

    /**
     * 删除一段时间之前的env_datapoint, 防止数据量过多, 影响数据库IO, 从而引发API超时
     *
     * @return void
     */
    public static function delete_env_datapoint()
    {
        $db = ORM_Model::db('env_datapoint');
        $query = sprintf("DELETE FROM env_datapoint WHERE ctime < %d", time() - Config::get('envmon.env_datapoint.exp_time', 86400 * 90));
        $db->query($query);
    }
}
