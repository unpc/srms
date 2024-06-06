<?php

class API_Cron {

    private function log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', [
                        '%name' => '[CRON]',
                        '%str' => $str,
            ]), 'cron');
        }
    }

    private function check() {
        $cron = Config::get('rpc.servers')['cron'];
        if (!isset($_SESSION['cron.client_id']) ||
            $cron['client_id'] != $_SESSION['cron.client_id']) {
            throw new API_Exception('Access denied.', 401);
        }
    }

    function auth($clientId, $clientSecret)
    {
        $cron = Config::get('rpc.servers')['cron'];
        if ($cron['client_id'] == $clientId &&
            $cron['client_secret'] == $clientSecret) {
            $_SESSION['cron.client_id'] = $clientId;
            return session_id();
        }

        return false;
    }

    public function get() {
        $cron_jobs = Config::get('cron');
        return $cron_jobs;
    }

    public function exec($command) {
        $this->check();
        exec("SITE_ID=" . SITE_ID . " LAB_ID=" . LAB_ID . ' ' . $command . " >/dev/null 2>&1 &", $res);
        $this->log("执行了cron {$command}");
    }

}
