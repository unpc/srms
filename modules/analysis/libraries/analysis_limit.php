<?php

class Analysis_Limit {
    // TODO 这边的应用限制是否应该让godiva-store那边去做？ 防止恶意拦截

    static function equipment ($e, $user, $role, $config) {
        // 需要和PO确认 是否全部放行?
        // $key = $config['key'];
        return [];
    }

    static function polymerize ($e, $user, $role, $config, $origin) {
        $key = $config['key'];
        $result = [];

        if ($role == Analysis::ROLE_ADMIN) {
            $e->return_value = [];
            return;
        }

        if ($role == Analysis::ROLE_PLATFORM) {
            $e->return_value = [
                'key' => "{$key}.{$origin}.equipment>{$key}.equipment.group",
                'op' => ['='],
                'value' => [$user->group->id]
            ];
            return;
        }

        if ($role == Analysis::ROLE_PI) {
            $lab = Q("{$user} lab")->current();
            $e->return_value = [
                'key' => "{$key}.{$origin}.lab",
                'op' => ['='],
                'value' => [$lab->id]
            ];
            return;
        }

        if ($role == Analysis::ROLE_INCHARGE) {
            $e->return_value = [
                'key' => "{$key}.{$origin}.equipment",
                'op' => ['in'],
                'value' => [Q("$user<incharge equipment")->to_assoc('id', 'id')]
            ];
            return;
        }

        // 当用户没有任何特殊角色时
        $e->return_value = [
            'key' => "{$key}.{$origin}.equipment",
            'op' => ['='],
            'value' => [0]
        ];
        return;
    }

}
