<?php
/**
 * 地理位置
 */
class API_GPUI_Gismon extends API_Common
{
    public function gisList()
    {
        $this->_ready('gpui');

        $selector = 'gis_building';

        $data = [];
        foreach (Q($selector) as $site) {
            $data[] = [
                "name" => $site->name,
                "longitude" => $site->longitude,
                "latitude" => $site->latitude,
            ];
        }
        return $data;
    }
}
