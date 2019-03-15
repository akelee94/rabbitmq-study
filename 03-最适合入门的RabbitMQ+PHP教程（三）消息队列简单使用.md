>RabbitMQ是一个消息代理的服务站，它可以支持消息的接收与转发。我们可以使用它作为消息队列来处理大量的消息发送。比如购物，厂家代表生产者（rabbitmq），我们则是消费者取消费商品。下面小试牛刀，我们开始输入一个 hellow world phpassn 的消息体来演示

（1）作为生产者，我们需要将消息生产到队列中，记住虽然消息经过了MQ和我们的应用程序，但是消息只能存储在队列中。队列存储受限于主机的储存器与磁盘限制，其实本质只是一个非常的消息缓冲器。多个生产者可以把消息发送到一个队列里面，而且多个消费者也可以从一个队列里面接受消息并消费掉。

（2） 作为消费者，无非就是等待一个接受的信号，当消息传递到队列中时，消费者会收到一个进来的消息，进行消费。

（3）生成者和消费者不一定再同一主机上使用。

（4）下面是我画的一张生成者与消费者之间的一张示意图：


1.确保我们本地已经安装了conposer，执行下面命令

`composer require php-amqplib/php-amqplib`

2.下面我们将调用我们的消息生产者`send.php` 和我们的消息消费者`receive.php`。生产者将连接到RabbitMQ，发送单个消息，然后退出。

  （1）创建连接对象
    ```
    $this->amqp_host = $config['host']?:$this->amqp_host;
    $this->amqp_port = $config['port']?:$this->amqp_port;
    $this->amqp_username = $config['username']?:$this->amqp_username;
    $this->amqp_password = $config['password']?:$this->amqp_password;
    $this->amqp_connection = new AMQPStreamConnection($this->amqp_host,$this->amqp_port,$this->amqp_username,$this->amqp_password);
    ```
解析：我们构建参数，创建服务器的连接对象，为我们负责协议版本协商和身份验证，防止被串改。这里是本地连接，默认使用的是localhost，如果是再其他机器上或者以后配置成立MQ集群，可以更改为其他指定的名称或者IP地址。

（2）必须申明一个消息队列，可以让我们把消息发送到队列中。
    ```
    //获取由数字通道标识的通道对象，或者如果该对象不存在，则创建该对象
    $channel_id = null;
    $this->amqp_channel = $this->amqp_connection->channel($channel_id);
    //声明一个队列
    $this->amqp_channel->queue_declare($queue_name,$passive,$durable,$exclusive,$auto_delete,$nowait);
    return $this;
    ```
解析：消息队列的是幂等的，只有在不存在的情况下才会取创建它，而消息内容则是字符串，可以随意的编辑消息内容。当然还有其他的参数，比如消息的持久化等等。。。

（3）消费者与生产者一样需要创建一个连接与通道，并且声明将要消耗的队列。（代码同上）

（4）接受服务器发送的消息进行消费，记住消息是从服务器异步发送到客户端的。
    ```
     $callback = function ($message) {
        //输出 消息体内容
        echo 'Received ', $message->body, "\n";
    };
    $this->amqp_channel->basic_consume($queue_name, '', false, true, false, false, $callback);
    //while判断消息是否已经消费完
    while (count($this->amqp_channel->callbacks)) {
        $this->amqp_channel->wait();
        //为了方便演示 延迟两秒消费
        sleep(2);
    }
    ```

3.终端运行两个文件，切换到所在文件目录。
先启动消费者:`php receive.php`
再启动生产者:`php send.php`

4.查看RabbitMQ有哪些队列以及它们中有多少消息，使用命令行:
    `rabbitmqctl.bat list_queues`

代码目录：同级目录下面的rabbitmq_test/send.php receive.php

原文链接：[https://www.phpassn.com/article/99.html](https://www.phpassn.com/article/99.html)