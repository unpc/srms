<?php
/**
 * 学院概况
 */
class API_GPUI_Eq_Group extends API_Common
{
    /**
     * 取该站点二级组织机构列表
     *
     * @return void
     */
    private function _get_groups()
    {
        $root = Tag_Model::root('group');
        $groups = [];
        foreach (Q("tag[root={$root}][parent={$root}]") as $group1) {
            foreach (Q("tag[root={$root}][parent={$group1}]") as $group2) {
                $groups[] = $group2;
            }
        }
        return $groups;
    }


    /**
     * 学院资产价值排行
     *
     * @return array
     */
    public function priceRank($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = new ArrayIterator([]);
        foreach ($this->_get_groups() as $g) {
            $data[] = [
                'name' => H($g->name),
                'total' => (float)Q("{$g}<group equipment[ctime={$dtstart}~{$dtend}]")->sum('price') ? : 0
            ];
        }

        return (array)$data;
    }

    /**
     * 学院资产数量分布
     *
     * @return array
     */
    public function cntRank($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = new ArrayIterator([]);
        foreach ($this->_get_groups() as $g) {
            $data[] = [
                'name' => H($g->name),
                'total' => (int)Q("{$g}<group equipment[ctime={$dtstart}~{$dtend}]")->total_count() ? : 0
            ];
        }

        return (array)$data;
    }

    /**
     * 学院仪器使用排行
     *
     * @return array
     */
    public function timeRank($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];
        $db = Database::factory();

        $data = new ArrayIterator([]);
        foreach ($this->_get_groups() as $g) {
            $eq_ids = Q("{$g}<group equipment")->to_assoc('id', 'id');
            if (count($eq_ids)) {
                $SQL = "SELECT SUM(`r`.`dtend` - `r`.`dtstart`) as `sum`" .
                    " FROM `eq_record` as `r`" .
                    " WHERE `r`.`dtend` > 0 AND `r`.`equipment_id` IN (%eq_ids) " .
                    " AND `r`.`dtend` BETWEEN %start AND %end ";
                $value = (int)$db->value(strtr($SQL, [
                    '%eq_ids' => join(',', $eq_ids),
                    '%start' => $dtstart,
                    '%end' => $dtend,
                ])) ? : 0;
                $data[] = [
                    'name' => H($g->name),
                    'total' => (float)sprintf('%.2f', ($value / 3600))
                ];
            } else {
                $data[] = [
                    'name' => H($g->name),
                    'total' => 0
                ];
            }
        }

        return (array)$data;
    }

    /**
     * 学院仪器成果排行
     *
     * @return array
     */
    public function eqAchiveRank($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = new ArrayIterator([]);
        foreach ($this->_get_groups() as $g) {
            $data[] = [
                'name' => H($g->name),
                'total' => (int)(Q("({$g}<group, publication[date={$dtstart}~{$dtend}]) equipment")->total_count()
                    + Q("({$g}<group, patent[date={$dtstart}~{$dtend}]) equipment")->total_count()
                    + Q("({$g}<group, award[date={$dtstart}~{$dtend}]) equipment")->total_count())
                    ? : 0
            ];
        }

        return (array)$data;
    }

    /**
     * 学院人员成果排行
     *
     * @return array
     */
    public function userAchiveRank($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = new ArrayIterator([]);
        foreach ($this->_get_groups() as $g) {
            $data[] = [
                'name' => H($g->name),
                'total' => (int)(Q("{$g}<group user ac_author<achievement publicatoin[date={$dtstart}~{$dtend}]")->total_count()
                    + Q("{$g}<group user ac_author<achievement patent[date={$dtstart}~{$dtend}]")->total_count()
                    + Q("{$g}<group user ac_author<achievement award[date={$dtstart}~{$dtend}]")->total_count())
                    ? : 0
            ];
        }

        return (array)$data;
    }

    /**
     * 学院测试收入对比
     *
     * @return array
     */
    public function chargeRank($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = new ArrayIterator([]);
        foreach ($this->_get_groups() as $g) {
            $data[] = [
                'name' => H($g->name),
                'total' => (float)Q("{$g}<group equipment eq_charge[ctime={$dtstart}~{$dtend}]")->sum('amount') ? : 0
            ];
        }

        return (array)$data;
    }
}
