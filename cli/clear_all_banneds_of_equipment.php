#!/usr/bin/env php

<?php

require 'base.php';

try {
    $banneds = Q('eq_banned[equipment]');
    foreach ($banneds as $banned) {
        $user = $banned->user;
        $equipment = $banned->equipment;
        if ($banned->delete()) {
            echo T("删除仪器%equipment[%equipment_id] 黑名单用户%user[%user_id]\n", [
																'%equipment'=>$equipment->name,
																'%equipment_id'=>$equipment->id,
																'%user'=>$user->name,
																'%user_id'=>$user->id
				                                                ]);
        }
    }
}
catch (Error_Exception $e) {
    Log::add($e->getMessage(), 'error');
}
