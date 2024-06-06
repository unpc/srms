<?php

/**
 * 模块或功能是否启用
 */

class API_Enable extends API_Common
{
    public function get($module, $config = null)
    {
        $this->_ready();
        if (!$module) {
            return false;
        }

        if ($config) {
            return Config::get($module . '.enable_' . $config); // eg. equipment.enable_use_type
        } else {
            return Module::is_installed($module);
        }

    }

}
