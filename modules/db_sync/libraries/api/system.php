<?php

class API_System extends API_Common
{

    public function update_system_config($master, $select, $form)
    {
        if ($master != Config::get('site.master')['name']) {
            throw new API_Exception(self::$errors[400], 400);
        }

        $configs = Config::get('support.' . $select)['items'];
        foreach ($configs as $prekey => $subconfigs) {
            if ($subconfigs['require_module'] && !Module::is_installed($subconfigs['require_module'])) {
                continue;
            }

            foreach ($subconfigs['subitems'] as $key => $item) {
                Event::trigger('admin.support.submit', $prekey, $key, $form[$prekey . '_' . $key], $form);
                Log::add(strtr('[support] %user[%id]修改了系统设置：%name1-%name2-%name3为%value', [
                    '%user'  => L('ME')->name,
                    '%id'    => L('ME')->id,
                    '%name1' => Config::get('support.' . $select)['name'],
                    '%name2' => $subconfigs['subname'],
                    '%name3' => $item['name'],
                    '%value' => $form[$prekey . '_' . $key],
                ]), 'support');
            }
        }

        return true;
    }
}
