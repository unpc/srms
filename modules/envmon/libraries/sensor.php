<?php

class Sensor {

	static $colors = ['#4bb2c5', '#c5b47f', '#EAA228', '#66cc66', '#cccc66', '#958c12', '#953579', '#4b5de4', '#d8b83f', '#ff5800', '#0085cc'];

    static function sensor_ACL($e, $user, $perm, $sensor, $params) {

        if ($user->access('管理所有环境监控对象')) {
            $e->return_value = TRUE;
            return FALSE;
        }

        if (Node::user_is_node_incharge($user, $sensor->node)) {
            $e->return_value = TRUE;
            return FALSE;
        }
    }

    static function update_sensor_value($id, $container_id) {
        $sensor = O('env_sensor', $id);
        Output::$AJAX['#'.$container_id] = (string) V('envmon:sensor/single_sensor', ['sensor'=>$sensor]);
    }

    static function envmon_newsletter_content($e, $user) {
        
        $templates = Config::get('newsletter.template');

        $dtstart = strtotime(date('Y-m-d')) - 86400;
        $dtend = strtotime(date('Y-m-d'));
        $db = Database::factory();
        $template = $templates['security']['alarm_times'];
        $sql = "SELECT sensor_id,COUNT(*) as count FROM env_sensor_alarm WHERE dtstart>%d AND dtstart<%d group by sensor_id";
        $query = $db->query($sql, $dtstart, $dtend);
        if ($query) {
            $results = $query->rows();
            foreach ($results as $result) {
                $sensor = O("env_sensor", $result->sensor_id);
                $count = $result->count;
                if ($count > 0) {
                    $str .= V('envmon:newsletter/alarm_times', [
                        'sensor' => $sensor,
                        'count' => $result->count,
                        'template' => $template,
                    ]);
                }
            }
        }

        $template = $templates['security']['need_fix'];
        $sql = "SELECT DISTINCT sensor_id FROM env_sensor_alarm WHERE dtstart>%d AND dtstart<%d AND dtend=0";
        $query = $db->query($sql, $dtstart, $dtend);
        if ($query) {
            $results = $query->rows();
            $str .= V('envmon:newsletter/need_fix', [
                    'results'=>$results,
                    'template'=>$template,
                ]);
        }

        if (strlen($str) > 0) {
            $view = V('envmon:newsletter/view', [
                    'str' => $str,
            ]);
            $e->return_value .= $view;   
        }
    }
}
