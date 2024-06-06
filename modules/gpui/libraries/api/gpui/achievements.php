<?php
class API_GPUI_Achievements extends API_Common
{
    /**
     * 设备成果产出排行
     *
     * @return array
     */
    public function equipmentRank($num = 10, $params = [])
    {
        if (!Module::is_installed('achievements')) {
            return [];
        }
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $db = Database::factory();
        $data = [];

        if ($db->query("SHOW TABLES LIKE '_r_publication_equipment'")) {
            $SQL = "SELECT `r`.`id2`, COUNT(`p`.`id`) as `cnt`" .
                " FROM `publication` as `p`" .
                " LEFT OUTER JOIN `_r_publication_equipment` AS `r` ON (`p`.`id` = `r`.`id1`)" .
                " WHERE `p`.`date` BETWEEN %start AND %end" .
                " GROUP BY `r`.`id2`" .
                " HAVING `cnt` > 0";
            $publication_stat = $db->query(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();

            foreach ($publication_stat as $row) {
                if (!$row->id2) {
                    continue;
                }
                if (!isset($data[$row->id2])) {
                    $data[$row->id2] = 0;
                }
                $data[$row->id2] += $row->cnt;
            }
        }

        if ($db->query("SHOW TABLES LIKE '_r_patent_equipment'")) {
            $SQL = "SELECT `r`.`id2`, COUNT(`p`.`id`) as `cnt`" .
                " FROM `patent` as `p`" .
                " LEFT OUTER JOIN `_r_patent_equipment` AS `r` ON (`p`.`id` = `r`.`id1`)" .
                " WHERE `p`.`date` BETWEEN %start AND %end" .
                " GROUP BY `r`.`id2`" .
                " HAVING `cnt` > 0";
            $patent_stat = $db->query(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();
            foreach ($patent_stat as $row) {
                if (!$row->id2) {
                    continue;
                }
                if (!isset($data[$row->id2])) {
                    $data[$row->id2] = 0;
                }
                $data[$row->id2] += $row->cnt;
            }
        }

        if ($db->query("SHOW TABLES LIKE '_r_equipment_award'")) {
            $SQL = "SELECT `r`.`id1`, COUNT(`a`.`id`) as `cnt`" .
                " FROM `award` as `a`" .
                " LEFT OUTER JOIN `_r_equipment_award` AS `r` ON (`a`.`id` = `r`.`id2`)" .
                " WHERE `a`.`date` BETWEEN %start AND %end" .
                " GROUP BY `r`.`id1`" .
                " HAVING `cnt` > 0";
            $award_stat = $db->query(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();
            foreach ($award_stat as $row) {
                if (!$row->id1) {
                    continue;
                }
                if (!isset($data[$row->id1])) {
                    $data[$row->id1] = 0;
                }
                $data[$row->id1] += $row->cnt;
            }
        }

        arsort($data);

        $ret = [];
        foreach (array_slice($data, 0, $num, true) as $k => $v) {
            $ret[] = [
                'total' => $v,
                'name' => H(O('equipment', $k)->name)
            ];
        }

        if (count($ret) < $num) {
            $ret = array_pad($ret, $num, ['total' => 0, 'name' => '--']);
        }
        return $ret;
    }

    /**
     * 成果产出分布
     *
     * @param array $params
     * @return void
     */
    public function tagStat($params = [])
    {
        if (!Module::is_installed('achievements')) {
            return [];
        }
        $this->_ready('gpui');

        $db = Database::factory();
        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        if ($db->query("SHOW TABLES LIKE '_r_tag_publication'")) {
            $publication_root = Tag_Model::root('achievements_publication');
            $publication_tag_ids = Q("tag[root={$publication_root}][parent={$publication_root}]")->to_assoc('id', 'id');
            $SQL = "SELECT `r`.`id1`, COUNT(`p`.`id`) as `cnt`" .
                " FROM `publication` as `p`" .
                " LEFT OUTER JOIN `_r_tag_publication` AS `r` ON (`p`.`id` = `r`.`id2`)" .
                " WHERE `p`.`date` BETWEEN %start AND %end" .
                " AND `r`.`id1` IN (%tag_ids)" .
                " GROUP BY `r`.`id1`" .
                " HAVING `cnt` > 0";
            $rows = $db->query(strtr($SQL, [
                '%tag_ids' => join(',', $publication_tag_ids),
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();
            $publication_tags = [];
            foreach ($rows as $row) {
                if (!$row->id1) {
                    continue;
                }
                $publication_tags[$row->id1] = [
                    'name' => H(O('tag', $row->id1)->name),
                    'total' => (int) $row->cnt
                ];
            }
            foreach ($publication_tag_ids as $id) {
                if (!isset($publication_tags[$id])) {
                    $publication_tags[$id] = [
                        'name' => H(O('tag', $id)->name),
                        'total' => 0
                    ];
                }
            }
        }

        if ($db->query("SHOW TABLES LIKE '_r_tag_patent'")) {
            $patent_root = Tag_Model::root('achievements_patent');
            $patent_tag_ids = Q("tag[root={$patent_root}][parent={$patent_root}]")->to_assoc('id', 'id');
            $SQL = "SELECT `r`.`id1`, COUNT(`p`.`id`) as `cnt`" .
                " FROM `patent` as `p`" .
                " LEFT OUTER JOIN `_r_tag_patent` AS `r` ON (`p`.`id` = `r`.`id2`)" .
                " WHERE `p`.`date` BETWEEN %start AND %end" .
                " AND `r`.`id1` IN (%tag_ids)" .
                " GROUP BY `r`.`id1`" .
                " HAVING `cnt` > 0";
            $rows = $db->query(strtr($SQL, [
                '%tag_ids' => join(',', $patent_tag_ids),
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();
            $patent_tags = [];
            foreach ($rows as $row) {
                if (!$row->id1) {
                    continue;
                }
                $patent_tags[$row->id1] = [
                    'name' => H(O('tag', $row->id1)->name),
                    'total' => (int) $row->cnt
                ];
            }
            foreach ($patent_tag_ids as $id) {
                if (!isset($patent_tags[$id])) {
                    $patent_tags[$id] = [
                        'name' => H(O('tag', $id)->name),
                        'total' => 0
                    ];
                }
            }
        }

        if ($db->query("SHOW TABLES LIKE '_r_tag_award'")) {
            $award_root = Tag_Model::root('achievements_award');
            $award_tag_ids = Q("tag[root={$award_root}][parent={$award_root}]")->to_assoc('id', 'id');
            $SQL = "SELECT `r`.`id1`, COUNT(`p`.`id`) as `cnt`" .
                " FROM `award` as `p`" .
                " LEFT OUTER JOIN `_r_tag_award` AS `r` ON (`p`.`id` = `r`.`id2`)" .
                " WHERE `p`.`date` BETWEEN %start AND %end" .
                " AND `r`.`id1` IN (%tag_ids)" .
                " GROUP BY `r`.`id1`" .
                " HAVING `cnt` > 0";
            $rows = $db->query(strtr($SQL, [
                '%tag_ids' => join(',', $award_tag_ids),
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();
            $award_tags = [];
            foreach ($rows as $row) {
                if (!$row->id1) {
                    continue;
                }
                $award_tags[$row->id1] = [
                    'name' => H(O('tag', $row->id1)->name),
                    'total' => (int) $row->cnt
                ];
            }
            foreach ($award_tag_ids as $id) {
                if (!isset($award_tags[$id])) {
                    $award_tags[$id] = [
                        'name' => H(O('tag', $id)->name),
                        'total' => 0
                    ];
                }
            }
        }

        $data = [
            'publication_tags' => $publication_tags,
            'patent_tags' => $patent_tags,
            'award_tags' => $award_tags
        ];
        return $data;
    }


    /**
     * 用户成果产出排名
     */
    public function userRank($num = 10, $params = [])
    {
        if (!Module::is_installed('achievements')) {
            return [];
        }
        $this->_ready('gpui');

        $db = Database::factory();
        $data = [];
        $data["publication"] = [];
        if ($db->query("SHOW TABLES LIKE 'ac_author'")) {
            $sql = "SELECT user.name, ac_author.user_id,count(ac_author.user_id) as number,tag.name as tag  FROM ac_author
                        inner join user on (user.id=ac_author.user_id)
                        inner join tag  on (user.group_id=tag.id)
            where achievement_name='publication'
                         GROUP BY ac_author.user_id
                        ORDER by count(ac_author.user_id) desc
                      limit 0,{$num}
                    ";
            $rows = $db->query($sql)->rows();
            foreach ($rows as $value) {
                $data["publication"][] = [
                    "user_id" => $value->user_id,
                    "number" => $value->number,
                    "name" => $value->name,
            "tag" =>$value->tag
                ];
            }
        }

        $data["award"] = [];
        if ($db->query("SHOW TABLES LIKE 'ac_author'")) {
            $sql = "SELECT user.name, ac_author.user_id,count(ac_author.user_id) as number,tag.name as tag  FROM ac_author
                        inner join user on (user.id=ac_author.user_id)
            inner join tag  on (user.group_id=tag.id)
                        where achievement_name='award'
                         GROUP BY ac_author.user_id
                        ORDER by count(ac_author.user_id) desc
                        limit 0,{$num}
                    ";
            $rows = $db->query($sql)->rows();
            foreach ($rows as $value) {
                $data["award"][] = [
                    "user_id" => $value->user_id,
                    "number" => $value->number,
                    "name" => $value->name,
            "tag" =>$value->tag
                ];
            }
        }

        $data["patent"] = [];
        if ($db->query("SHOW TABLES LIKE 'ac_author'")) {
            $sql = "SELECT user.name, ac_author.user_id,count(ac_author.user_id) as number,tag.name as tag  FROM ac_author
                        inner join user on (user.id=ac_author.user_id)
            inner join tag  on (user.group_id=tag.id)
                        where achievement_name='patent'
                         GROUP BY ac_author.user_id
                        ORDER by count(ac_author.user_id) desc
                      limit 0,{$num}
                    ";
            $rows = $db->query($sql)->rows();
            foreach ($rows as $value) {
                $data["patent"][] = [
                    "user_id" => $value->user_id,
                    "number" => $value->number,
                    "name" => $value->name,
            "tag" =>$value->tag
                ];
            }
        }

        return $data;
    }
}
