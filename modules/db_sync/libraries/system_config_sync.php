<?php

class System_Config_Sync
{
    /**
     * 技术支持更新 系统配置时 rpc更新所有从站点
     * @param select 选中要更新的key 当前只更新i18n
     * @param form 
     */
    public static function sync_slave_site($e, $select, $form)
    {
        $sync_system_config = ['i18n'];
	if (DB_SYNC::is_master() 
	    && DB_SYNC::is_module_unify_manage('i18n')
	    && in_array($select, $sync_system_config)) {
            foreach (Config::get('site.slave') as $site) {
                $rpc = new RPC($site['host'] . '/api');
                $rpc->System->Update_system_config(LAB_ID, $select, $form);
            }
        }

        return;
    }

}
