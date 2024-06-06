<?php

class Equipment_Veronica {
    static function extra_login_view($e, $login, $equipment) {
        if (Config::get('equipment.enable_use_type')) {
            $login['#']['use_type'] = [
                'type' => 6,
                'title' => '使用用途',
                'required' => 1,
                'adopted' => null,
                'params' => EQ_Record_Model::$use_type,
                'default' => EQ_Record_Model::USE_TYPE_USING
            ];
            $login['#']['use_type_desc'] = [
                'type' => 5,
                'title' => '备注',
                'required' => 0,
                'adopted' => null,
                'params' => null,
                'default' => '',
            ];
        }
    }

    static function extra_login_validate($e, $data, $user, $equipment) {
        if (Config::get('equipment.enable_use_type')) {
			$extra = $data['extra']['use_type'];

            if ($user->member_type < 10
            && ! $user->is_allowed_to('管理使用', $equipment)
            && $use_type != EQ_Record_Model::USE_TYPE_USING) {
                throw new API_Exception(I18N::T('equipments', '您无权选择该使用类型'), 401);
            }
		}
    }

    static function extra_switch_on_before($e, $data, $record) {
        if (Config::get('equipment.enable_use_type')) {
            $record->use_type = $data['extra']['use_type'];
            $record->use_type_desc = $data['extra']['use_type_desc'];
        } 
        $extra_value = O('extra_value', ['object' => $record]);
        $extra_value->object = $record;
        $extra_value->values = $data['extra'];
        $extra_value->save();
    }
}
