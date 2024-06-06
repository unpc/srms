<?php

class Sync_Relationship
{
    public $r1;
    public $r2;
    public function __construct($r1, $r2)
    {
        $this->r1 = $r1;
        $this->r2 = $r2;
    }

    public function publish_save($type)
    {
        $exchangeName = Config::get('sync.exchange.name', 'sync');
        $routing_key = "{$this->r1->name()}.save";
        $mq = new MQ('rabbitmq', Config::get('rabbitmq.opts'));
        $channel = $mq->get_channel();
        $channel->exchange_declare($exchangeName, 'topic', false, false, false);
        $mq->set_publish_ack();

        $data = $this->_package_msg('save', $type);
        $mq->basic_publish($data, $exchangeName, $routing_key);
    }

    public function publish_delete($type)
    {
        $exchangeName = Config::get('sync.exchange.name', 'sync');
        $routing_key = "{$this->r1->name()}.delete";
        $mq = new MQ('rabbitmq', Config::get('rabbitmq.opts'));
        $channel = $mq->get_channel();
        $channel->exchange_declare($exchangeName, 'topic', false, false, false);
        $mq->set_publish_ack();

        $data = $this->_package_msg('delete', $type);
        $mq->basic_publish($data, $exchangeName, $routing_key);
    }

    private function _package_msg($mode, $type)
    {
        return [
            'version' => time(),
            'platform' => LAB_ID,
            'method' => $mode,
            'object' => 'relationship',
            'r1' => $this->r1->name(),
            'uuid1' => $this->r1->uuid,
            'r2' => $this->r2->name(),
            'uuid2' => $this->r2->uuid,
            'type' => $type
        ];
    }

    public function handle($data)
    {
        $_SESSION['sync_relationship'] = true;
        if ($data['method'] == 'save') {
            $this->r1->connect($this->r2, $data['type']);
        }
        if ($data['method'] == 'delete') {
            $this->r1->disconnect($this->r2, $data['type']);
        }
        $_SESSION['sync_relationship'] = false;
    }
}
