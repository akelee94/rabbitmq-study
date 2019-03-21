>上一章我们举例说明了`rabbitmq`的简单使用，即是生产者发一条消息，消费者则消费掉这个消息，一对一的去处理。那么这章节我们看下rabbitmq的发布与订阅，童鞋们可能最直接想到的是redis的发布与订阅消息。看官方案例提到的，我们使用的是一个日志系统，即一个生产者发布一条消息，传给更多地消费者使用。为了说明这种模式，我们将构建一个简单的日志记录系统。它将包含两个程序 - 第一个将发出日志消息，第二个将接收和打印它们。

###1.注意事项
(1)生产者是用来生产消息发送出消息的应用程序
(2)消息队列是存储消息的缓冲器
(3)消费者是接受发送的应用程序
(4)RabbitMQ中消息传递模型的核心思想是生产者永远不会将任何消息直接发送到队列。有时候生产者通常甚至不知道消息是否会被传递到任何队列。

###2.使用交换机发送消费消息（使用fanout类型）
它可以将自己接收到的所有消息广播到它知道的所有队列中。
```
$this->amqp_channel = $this->amqp_connection->channel();
//创建一个交换机
$this->amqp_channel->exchange_declare($exchange_name,$type,$passive,$durable,$auto_delete);
return $this;
```

###3.交换机与队列之绑定
上一章我们是直接生成者直接生产消息丢出去，并不知道丢到了哪里，而消费者则是直接读取消息，并不知道消息来源于哪里。有的时候我们需要指定从某一个队列里面拿到消息并要在生产者和消费者之间共享队列时，我们就需要指定一下消息发出到接受地。进而出现了交换机来帮助处理，我们创建一个`fanout`类型的交换机和一个队列，然后就需要告诉交换机将消息发送到我们指定的队列里，这就形成了一个绑定关系（也可以称为指定）。

```
$this->amqp_channel = $this->amqp_connection->channel();
$this->amqp_channel->exchange_declare($exchange_name,$type,$passive,$durable,$auto_delete);
list($queue_name, ,) = $this->amqp_channel->queue_declare("", false, false, true, false);
$this->queue_name = $queue_name;
//将队列绑定到交换机
$routing_key = ''; //绑定秘钥 默认传空  不需要秘钥
$this->amqp_channel->queue_bind($queue_name,$exchange_name,$routing_key);
return $this;
```
这样的话，交换机就会吧消息附件到我们的队列里。

总结：虽然订阅与发布跟上面两章接的几乎一样，但是我们将消息从一个无名交换机发布到指定的交换机上，同时也可以使用同一个交换机发布到不同的队列上让消费者消费。若果队列没有绑定交换机上，消息会出现丢失问题。