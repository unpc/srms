<?php

class Scope_Api {
    static function current_server() {
        return $_SESSION['rpc.server'];
    }

    static function is_authenticated($scope){
        $server = self::current_server();
        if(!$server) return;

        $servers = Config::get('rpc.servers');
        $server_scope = $servers[$server]['scope'];

        if($server_scope && ($server_scope == '*' || in_array($scope, $server_scope))) return TRUE;

        return FALSE;
    }
}
