<?php

abstract class LIMS_Accredit {

    static function factory($server) {

        $client_class = 'LIMS_Accredit_' . $server;

        if (!class_exists($client_class)) {
            // 无此 client class
            return FALSE;
        }

        $client = new $client_class($accreditor_conf);
        return $client;
    }

    abstract function get_user_info($form);
    abstract function find_user_by_info ($info);
}