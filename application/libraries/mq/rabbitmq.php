<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MQ_Rabbitmq implements MQ_Handler
{
    private $config;
    private $connection;
    private $publish_confirm = false;
    public $channel;

    public function __construct($config)
    {
        $this->config = $config;
        try {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['username'],
                $this->config['password']
            );
        } catch (\EXCEPTION $e) {
            if (Config::get('debug.rabbitmq', false)) {
                Log::add($e->getMessage(), 'mq');
            }
        }

        return $this;
    }

    public function get_channel()
    {
        $this->channel = $this->connection->channel();
        return $this->channel;
    }

    /*
     * bring the channel into publish confirm mode.
     * if you would call $ch->tx_select() befor or after you brought the channel into this mode
     * the next call to $ch->wait() would result in an exception as the publish confirm mode and transactions
     * are mutually exclusive
     */
    public function set_publish_ack()
    {
        $this->channel->set_ack_handler(function (AMQPMessage $message) {
            Event::trigger('rabbitmq.publish_confirm', $message);
            if (Config::get('debug.rabbitmq', false)) {
                Log::add(strtr('[rabbitmq] Message acked with content: %body', [
                    '%body' => $message->body
                ]), 'mq');
            }
        });

        $this->channel->set_nack_handler(function (AMQPMessage $message) {
            Event::trigger('rabbitmq.publish_unconfirm', $message);
            if (Config::get('debug.rabbitmq', false)) {
                Log::add(strtr('[rabbitmq] Message nacked with content: %body', [
                    '%body' => $message->body
                ]), 'mq');
            }
        });
        $this->channel->confirm_select();
        $this->publish_confirm = true;
        return $this;
    }

    private function _pack_msg($data)
    {
        if (is_scalar($data)) {
        } elseif (!$data) {
            $data = '';
        } elseif (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        } else {
            throw new MQ_Exception('Rabbit MQ 不支持此类型数据!');
        }

        if ($this->receive_confirm) {
            return new AMQPMessage(
                $data,
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );
        } else {
            return new AMQPMessage($data);
        }
    }

    public function basic_publish($params)
    {
        $message = $this->_pack_msg($params[0] ? : '');
        $this->channel->basic_publish(
            $message,
            $params[1] ? : '',
            $params[2] ? : '',
            $params[3] ? : false,
            $params[4] ? : [],
            $params[5] ? : null
        );

        if (Config::get('debug.rabbitmq', false)) {
            Log::add(strtr('[rabbitmq] Message Sent with content: %body, exchange: %exchange, routing_key: %routing_key', [
                '%body' => $message->body,
                '%exchange' => $params[1],
                '%routing_key' => $params[2],
            ]), 'mq');
        }

        if ($this->publish_confirm) {
            $this->channel->wait_for_pending_acks();
        }
    }

    public function __destruct()
    {
        if (!is_null($this->channel)) {
            $this->channel->close();
        }
        if (!is_null($this->connection)) {
            $this->connection->close();
        }
    }
}
