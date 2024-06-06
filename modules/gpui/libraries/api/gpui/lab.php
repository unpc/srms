<?php
class API_GPUI_Lab extends API_Common
{
    /**
     * 科研项目占比
     *
     * @return void
     */
    public function projectTypeStat()
    {
        $this->_ready('gpui');

        $db = Database::factory();
        $SQL = "SELECT `p`.`type`, COUNT(`p`.`id`) AS `cnt`" .
            " FROM `lab_project` as `p`" .
            " GROUP BY `p`.`type`";

        $rows = $db->query($SQL)->rows();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'name' => Lab_Project_Model::$types[$row->type],
                'time' => (int) $row->cnt
            ];
        }
        return $data;
    }
}
