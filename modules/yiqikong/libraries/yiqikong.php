<?php

class YiQiKong
{

    /**
     * 获取当前站点gateway和mq等配置，
     * 兼容云部署及本地部署
     * @return array
     */
    static function getYiqikongConfig($siteId = '', $labId = '')
    {
        $yiqikongConfig = Config::get('yiqikong');
        return $yiqikongConfig;
    }
}
