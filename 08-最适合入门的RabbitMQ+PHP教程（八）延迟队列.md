### 场景
开发中经常需要用到定时任务，对于商城来说，定时任务尤其多，比如优惠券定时过期、订单定时关闭、微信支付2小时未支付关闭订单等等，都需要用到定时任务，但是定时任务本身有一个问题，一般来说我们都是通过定时轮询查询数据库来判断是否有任务需要执行，也就是说不管怎么样，我们需要先查询数据库，而且有些任务对时间准确要求比较高的，需要每秒查询一次，对于系统小倒是无所谓，如果系统本身就大而且数据也多的情况下，这就不大现实了，所以需要其他方式的，当然实现的方式有多种多样的，比如Redis实现定时队列、基于优先级队列的JDK延迟队列、时间轮等。

### Rabbitmq延迟队列
Rabbitmq本身是没有延迟队列的，只能通过Rabbitmq本身队列的特性来实现，想要Rabbitmq实现延迟队列，需要使用Rabbitmq的死信交换机（Exchange）和消息的存活时间TTL（Time To Live）

### 死信交换机
一个消息在满足如下条件下，会进死信交换机，记住这里是交换机而不是队列，一个交换机可以对应很多队列。
一个消息被Consumer拒收了，并且reject方法的参数里requeue是false。也就是说不会被再次放在队列里，被其他消费者使用。
上面的消息的TTL到了，消息过期了。队列的长度限制满了。排在前面的消息会被丢弃或者扔到死信路由上。

### 消息TTL（消息存活时间）
消息的TTL就是消息的存活时间。RabbitMQ可以对队列和消息分别设置TTL。对队列设置就是队列没有消费者连着的保留时间，也可以对每一个单独的消息做单独的设置。超过了这个时间，我们认为这个消息就死了，称之为死信。如果队列设置了，消息也设置了，那么会取小的。所以一个消息如果被路由到不同的队列中，这个消息死亡的时间有可能不一样（不同的队列设置）。

### 发送消息代码（send）
```
/**
    * @desc 创建超时队列和交换机
    * @param string $exchange_name
    * @param string $queue_name
    * @param string $ttl_time
    */
public function createOutQueue($exchange_name = '', $queue_name = '', $ttl_time = '')
{
    $this->delay_exchange_name = $exchange_name;
    $this->delay_queue_name = $queue_name;
    self::$ttl_time = $ttl_time ? $ttl_time : 15000;
    try {
        $this->setExchangeDeclare($this->delay_exchange_name, self::$type, false, false, false);
        $this->getQueueDeclare($this->delay_queue_name, false, true, false, false, false);
        $this->setQueueBind($this->delay_queue_name, $this->delay_exchange_name, $this->delay_exchange_name);
    } catch (\Exception $e) {
        return 'Info:' . $e->getMessage();
    }
}
```

```
/**
    * @desc 创建队列与交换机
    * @param string $exchange_name
    * @param string $queue_name
    * @return string
    */
public function createDefaultQueue($exchange_name = '', $queue_name = '')
{
    $this->exchange_name = $exchange_name;
    $this->queue_name = $queue_name;
    try {
        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->delay_exchange_name); // 表示过期后由哪个exchange处理
        $tale->set('x-dead-letter-routing-key', $this->delay_exchange_name);  //死信路由key
        $tale->set('x-message-ttl', self::$ttl_time); //存活时长   下面的过期时间不能超过
        $this->setExchangeDeclare($this->exchange_name, self::$type, false, false, false);
        $this->getQueueDeclare($this->queue_name, false, true, false, false, false, $tale);
        $this->setQueueBind($this->queue_name, $this->exchange_name, $this->exchange_name);
    } catch (\Exception $e) {
        return 'Info:' . $e->getMessage();
    }
}
```
```
/**
    * @desc 发送消息
    * @param string $message
    */
public function sendMessage($message = '')
{
    // 试验效果  多发送几条
    for ($i = 1; $i <= 10; $i++) {
        $messages = new AMQPMessage($message, [
            'expiration' => intval(self::$ttl_time),
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        $this->amqp_channel->basic_publish($messages, $this->exchange_name, $this->exchange_name);
        //为了方便看到消息发送  延迟2s
        sleep(2);
        echo date('Y-m-d H:i:s') . " Sent " . $message . PHP_EOL;
    }
}
```
源文件：[send.php](https://gitee.com/jonny-li/rabbitmq-study/blob/master/rabbitmq_test/delay-queue/message-ttl/send.php)

### 接收消息代码（receive）
```
 /**
    * @desc 接收消息 并消费掉
    */
public function receiveMessage()
{
    echo 'Waiting for message. To exit press CTRL+C ' . PHP_EOL;

    $callback = function ($message) {

        echo date('Y-m-d H:i:s') . " Received: ", $message->body, PHP_EOL;

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']); 
    };
    $this->amqp_channel->basic_qos(null, 1, null);

    $this->amqp_channel->basic_consume($this->delay_queue_name, '', false, false, false, false, $callback);

    while (count($this->amqp_channel->callbacks)) {

        $this->amqp_channel->wait();
    }
}
```
源文件：[receive.php](https://gitee.com/jonny-li/rabbitmq-study/blob/master/rabbitmq_test/delay-queue/message-ttl/receive.php)

总结：基于Rabbitmq实现定时任务，就是将消息设置一个过期时间，放入一个没有读取的队列中，让消息过期后自动转入另外一个队列中，监控这个队列消息的监听处来处理定时任务具体的操作.

原文链接：[最适合入门的RabbitMQ+PHP教程（八）延迟队列](https://www.phpassn.com/article/118.html)