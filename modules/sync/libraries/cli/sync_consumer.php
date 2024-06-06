<?php
require dirname(dirname(dirname(dirname(__DIR__)))).'/cli/base.php';

$topics = Config::get('sync.receive_topics');
$pool = new Swoole\Process\Pool(1);

$pool->on("WorkerStart", function ($pool, $workerId) {
    global $topics;
    $exchangeName = Config::get('sync.exchange.name', 'sync');
    echo "Worker#{$workerId} is started\n";
    $mq = new MQ('rabbitmq', Config::get('rabbitmq.receive_opts'));
    $channel = $mq->get_channel();
    $channel->exchange_declare($exchangeName, 'topic', false, false, false);

    $queue_name = LAB_ID;
    foreach ($topics as $binding_key) {

        // 3rd params: true
        // make sure that RabbitMQ will never lose our queue.
        // In order to do so, we need to declare it as durable.
        // 说人话就是rabbitMQ重启队列不会丢, 消息数据持久化
        list($queue_name, , ) = $channel->queue_declare($queue_name, false, true, false, false);

        $channel->queue_bind($queue_name, $exchangeName, $binding_key);

        // echo " [*] Waiting for {$binding_key}. To exit press CTRL+C\n";
    }

    $callback = function ($msg) use ($workerId) {
        echo "Worker#{$workerId} received\n";
        $body = @json_decode($msg->body, true);
        if (Sync_Consumer::dispatch($body)) {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
    };

    // 4th params: false
    // need send basic_ack, instead of auto-ack
    // 说人话就是callback要手动发ack给队列, 标记完成, 而不是让amqplib自动发
    $channel->basic_consume($queue_name, '', false, false, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }
});

$pool->on("WorkerStop", function ($pool, $workerId) {
    echo "Worker#{$workerId} is stopped\n";
});

$pool->start();
