<?php
class Multi_Stage {
    static function equipment_get_object_page ($e, $equipment, $url) {
        if (!in_array(LAB_ID, Config::get('site.main_stage', []))) return;

        $site = Q("$equipment site");
        if ($site->total_count()) {
            $base_url = $site->current()->base_url;
            $e->return_value = $base_url . $url;
            return FALSE;
        }
    }

    static function gateway_login ($e) {
        $e->return_value .= V('multi_stage:gateway_login');
    }

    static function user_extra_keys($e, $user, $info) {
        $group = $user->group;
        $info['group_info'] = [
            'id' => $group->id,
            'name' => $group->name,
            'parent_id' => $group->parent->id,
            'root_id' => $group->root->id,
            'source_id' => $group->source_id,
            'source_name' => $group->source_name
        ];
    }
}

