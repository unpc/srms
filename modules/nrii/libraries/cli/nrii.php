<?php

class CLI_Nrii {

    static function sync_service() {
        Nrii::sync_service();
    }

    static function sync_device($user_id) {
        Cache::L('ME', o('user', $user_id));
        Nrii::sync('device');
    }

    static function sync_center($user_id) {
        Cache::L('ME', o('user', $user_id));
        Nrii::sync('center');
    }

    static function sync_unit() {
        Nrii::sync('unit');
    }

    static function sync_equipment($eid = 0, $user_id) {
        Cache::L('ME', o('user', $user_id));
        Nrii::sync('equipment',$eid);
    }

    static function sync_record($user_id) {
        Cache::L('ME', o('user', $user_id));
        Nrii::sync('record');
    }

    static function import_record() {
        Nrii_Record::importRecords();
    }

    static function upgrade_address () {
        Nrii_Address::sync_address();
    }

    //已经上传的直接通过审核
    static function equipment_shen_status(){
        foreach(Q('nrii_equipment[nrii_status=100]') as $eq){
            $eq->shen_status = 1;
            $eq->save();
        }
    }
}

