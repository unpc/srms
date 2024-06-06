<?php

class API_Equipment_Announce extends API_Common
{
    public function getLists($params = [])
    {
        $this->_ready();
        extract($params);
        if (!$uuid) return [];
        $uuid = trim($uuid);
        if (is_numeric($uuid)) {
            $equipment = O('equipment', $uuid);
        }

        if (!$equipment->id) {
            $equipment = O('equipment', ['ref_no' => $uuid]);
        }

        if (!$equipment->id) return [];

        list($start, $end) = $limit;
        $start = $start ? (int)$start : 0;
        $end = $end ? (int)$end : 1;

        $selector = "eq_announce[equipment={$equipment}]:sort(is_sticky D, mtime D)";
        $total = Q($selector)->total_count();
        $announces = Q($selector)->limit($start, $end);

        $datas = [];
        foreach ($announces as $announce) {
            $tmp = [];
            $tmp['title'] = $announce->title;
            $tmp['content'] = $announce->content;
            $tmp['author'] = $announce->author->name;
            $tmp['equipment'] = $announce->equipment->name;
            $tmp['is_sticky'] = $announce->is_sticky;
            $datas[] = $tmp;
        }

        return ['total' => $total, 'list' => $datas];
    }
}