<?php
/**
 * 进门概况
 *：/
*/
class API_GPUI_Dc_Record extends API_Common
{
    /**
     * 人员进门人数
     *  @param string $build 楼宇名称
     * @param string $floor 楼层名称
     * @return void
     */
    function doorIndoorUser($params = [])
    {
        if (!Module::is_installed('entrance')) return [];

        $this->_ready('gpui');

        $db = Database::factory();

        $build = isset($params["build"]) ? $params["build"] : '';
        $floor = isset($params["floor"]) ? $params["floor"] : '';

        $SQL = "SELECT `t2`.`location1`, `t2`.`location2`, count(*) as `number`" .
            " FROM `dc_record` as `t1`" .
            " LEFT OUTER JOIN `door` as `t2` ON (`t1`.`door_id` = `t2`.`id`) ".
            " WHERE t1.direction=".DC_Record_Model::IN_DOOR;

        if ($build != '') {
            $SQL .= " and `t2`.`location1`='{$build}'";
        }
        if ($floor != '') {
            $SQL .= " and `t2`.`location2`='{$floor}'";
        }

        $SQL .= " group by `t2`.`location1`, `t2`.`location2`";

        $rows = $db->query($SQL)->rows();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'build' => $row->location1,
                'floor' => $row->location2,
                'num' => $row->number
            ];
        }
    $start = Q("dc_record:sort(time A)")->current();
        return [
            'data' => $data,
            'time' => date("Y-m-d",$start->time) . '--' . date("Y-m-d",Date::time())
        ];
        //return $data;
    }


    /**
     * 最近一段时间进出记录
     *  @param string $build 楼宇名称
     * @param string $floor 楼层名称\
     *  @param integer $num 跨度
     * @param string $format 单位
     * @return void
     */
    function doorIndoorAvgUser($params = [])
    {
        if (!Module::is_installed('entrance')) return [];

        $this->_ready('gpui');

        $db = Database::factory();

        $build = isset($params["build"]) ? $params["build"] : '';
        $floor = isset($params["floor"]) ? $params["floor"] : '';
        $num = isset($params["num"]) ? $params["num"] : 30;
        $format = isset($params["format"]) ? $params["format"] : 'd';


        switch ($format) {
            case 'd':
                $dtstart = Date::get_day_start();
                $dtend = Date::get_day_end();
                $date_format = 'd';
                break;
            default:
                return[];
        }

        $dtstart = Date::prev_time($dtstart, $num, $format);
        $dtend = Date::prev_time($dtend, 1, $format);

        $SQL = "SELECT `t2`.`location1`, `t2`.`location2`, count(*) as `number`" .
            " FROM `dc_record` as `t1`" .
            " LEFT OUTER JOIN `door` as `t2` ON (`t1`.`door_id` = `t2`.`id`) ".
            " WHERE t1.direction=".DC_Record_Model::IN_DOOR;

        if ($build != '') {
            $SQL .= " and `t2`.`location1`='{$build}'";
        }
        if ($floor != '') {
            $SQL .= " and `t2`.`location2`='{$floor}'";
        }

        $SQL .= " and `t1`.`time`>={$dtstart} and `t1`.`time`<={$dtend}";
        $SQL .= " group by `t2`.`location1`, `t2`.`location2`";

        $rows = $db->query($SQL)->rows();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'build' => $row->location1,
                'floor' => $row->location2,
                'num' => (float) sprintf('%.2f', ($row->number / $num))
            ];
        }
return [
            'data' => $data,
            'time' => date("Y-m-d",$dtstart) . '--' . date("Y-m-d",$dtend)
        ];
      //        return $data;
    }


    /**
     * 实验室进门记录
     */
   /**
     * 实验室进门记录
     */
    function doorIndoorList($params = [], $start = 0, $num = 5){
        if (!Module::is_installed('entrance')) return [];

        $this->_ready('gpui');

        if (!$params["doorAddr"]) return [];

        $door = Q("door[in_addr={$params["doorAddr"]}|out_addr={$params["doorAddr"]}]")->current();
        if (!$door->id) return [];

        $selector = "{$door} dc_record:sort(time D)";

        $data = [];

        foreach (Q("$selector")->limit($start, $num) as $item){
            $data[]  = [
        "name" => $item->door->name,
                "user_name" => $item->user->name,
                "time" => date('Y/m/d H:i:s',$item->time),
                "direction" => DC_Record_Model::$direction[$item->direction]
            ];
        }

        return $data;
    }



}

