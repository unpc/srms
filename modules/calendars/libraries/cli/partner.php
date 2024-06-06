<?php

class Partner {

    public function cooperate() {

        $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $client->on("connect", function(swoole_client $cli) {
            echo date('Y-m-d H:i:s').' Partner Connected: 172.17.42.1:9509'."\n";
        });

        $client->on("receive", function(swoole_client $cli, $data) {
            echo date('Y-m-d H:i:s')." Partner Received: $data\n";

            $data = json_decode($data, true);

            if (isset($data['site_id']) && $data['site_id'] 
                && isset($data['lab_id']) && $data['lab_id'] 
                && isset($data['tube']) && $data['tube']) {

                $res = exec('ps -ef | grep tube.php | grep '.$data['tube'], $output);

                if (!strpos($output[0], $data['site_id'].' '.$data['lab_id'].' '.$data['tube'])) {
                    $process = new swoole_process(function(swoole_process $w) use ($data) {
                        $command = __DIR__.'/tube.php';
                        $w->exec('/usr/bin/php', [$command, $data['site_id'], $data['lab_id'], $data['tube']]);
                    }, TRUE, TRUE);
                    // $process->setBlocking(true);
                    $pid = $process->start();

                    echo date('Y-m-d H:i:s').' Child Process('.$pid.') Started: '.$data['site_id'].' '.$data['lab_id'].' '.$data['tube']."\n";
                }

                swoole_process::wait(FALSE);

            } else {

            }

        });

        $client->on("error", function(swoole_client $cli) {
            echo date('Y-m-d H:i:s').' Partner Connection Error: 172.17.42.1:9509'."\n";
        });

        $client->on("close", function(swoole_client $cli) {
            echo date('Y-m-d H:i:s').' Partner Connection Closed: 172.17.42.1:9509'."\n";
        });

        $client->connect('172.17.42.1', 9509);
    }
}

$partner = new Partner();

$partner->cooperate();
