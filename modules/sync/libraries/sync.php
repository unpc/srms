<?php
/**
 * sync应用层, 决定各个ORM 处理具体业务逻辑
 * 如有新添加的需要同步的ORM, 在 libraries/sync 下实现此抽象类
 */
abstract class Sync_Handler
{
    public $object;
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * 根据业务逻辑返回当前object 的uuid
     *
     * @return string uuid
     */
    abstract public function uuid();

    /**
     * 根据数据更改情况, 决定是否生成uuid
     *
     * @param array $old_data
     * @param array $new_data
     * @return bool
     */
    abstract public function should_save_uuid($old_data, $new_data);

    /**
     * 根据数据更改情况, 决定是否发布此次更新
     *
     * @param array $old_data
     * @param array $new_data
     * @return bool
     */
    abstract public function should_save_publish($old_data, $new_data);

    /**
     * object 的数据封装
     *
     * @return array
     */
    abstract public function format();

    /**
     * 订阅后对数据的处理
     *
     * @param array $params
     * @return void
     */
    abstract public function handle($params);
}

/**
 * sync会话层, 对应用层调用的接口封装
 */
class Sync
{
    public $object;
    public $handler;
    public function __construct($object)
    {
        $this->object = $object;
        // 各站点按需扩展 参考 LAB_ID->nankai_pest && nankai
        $className = LAB_ID . '_' . 'Sync_' . ucwords($object->name());
        if (!class_exists($className)) {
            $className = 'Sync_' . ucwords($object->name());
            if (!class_exists($className)) {
                return;
            }
        }

        $this->handler = new $className($object);
    }

    public function uuid()
    {
        return $this->handler->uuid();
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return $this->handler->should_save_uuid($old_data, $new_data);
    }

    public function should_save_publish($old_data, $new_data)
    {
        return in_array("{$this->object->name()}.save", Config::get('sync.topics'))
        && $this->object->platform == LAB_ID
        && $this->handler->should_save_publish($old_data, $new_data);
    }

    public function should_delete_publish()
    {
        return in_array("{$this->object->name()}.delete", Config::get('sync.topics'));
    }

    public function publish_save()
    {
        $exchangeName = Config::get('sync.exchange.name', 'sync');
        $routing_key  = "{$this->object->name()}.save";
        $mq           = new MQ('rabbitmq', Config::get('rabbitmq.opts'));
        $channel      = $mq->get_channel();
        $channel->exchange_declare($exchangeName, 'topic', false, false, false);
        $mq->set_publish_ack();

        $data = $this->_package_msg('save');
        $mq->basic_publish($data, $exchangeName, $routing_key);
    }

    public function publish_delete()
    {
        $exchangeName = Config::get('sync.exchange.name', 'sync');
        $routing_key  = "{$this->object->name()}.delete";
        $mq           = new MQ('rabbitmq', Config::get('rabbitmq.opts'));
        $channel      = $mq->get_channel();
        $channel->exchange_declare($exchangeName, 'topic', false, false, false);
        $mq->set_publish_ack();

        $data = $this->_package_msg('delete');
        $mq->basic_publish($data, $exchangeName, $routing_key);
    }

    private function _package_msg($mode)
    {
        return [
            'version'  => time(),
            'platform' => LAB_ID,
            'object'   => $this->object->name(),
            'uuid'     => $this->object->uuid,
            'method'   => $mode,
            'payload'  => $this->handler->format(),
        ];
    }

    public function handle($data)
    {
        return $this->handler->handle($data);
    }
}
