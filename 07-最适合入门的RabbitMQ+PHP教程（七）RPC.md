>上一章我们讲解了[最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用](https://www.phpassn.com/article/108.html),我们通过实际的执行代码，可以很清晰的了解整体的匹配规则以及操作流程。那么这一章节讲解的是rabbitmq的RPC模式。

### 前提条件
RabbitMQ 已在标准端口（5672）上的localhost上安装并运行。如果您使用不同的主机，端口或凭据，则需要调整连接设置。如果您再本教程中遇到问题，可以随时通过邮件来联系我们。

### RPC 定义
要使用RPC那么我们就要搞清楚这个RPC到底是个什么东西，原理是什么？(RPC) Remote Procedure Call Protocol 远程过程调用协议,一般公稍微大一些的公司都是一个项目有多个系统构成，比如电商中的库存系统，商品系统，订单系统等等，不同的项目开发组维护不同的系统，每个系统有运行在不同的机器上。但是往往机器之间需要互相调用一些数据，由于不在同一台机器可以直接调用，所以需要通过网络来表达调用的语义和传达调用的数据。那么现在RPC的协议很多，Java RMI ， WebApi等等。

### 官方对于PRC的说明
尽管RPC在计算中是一种非常常见的模式，但它经常受到批评。当程序员不知道函数调用是本地的还是慢的RPC时，会出现问题。这样的混淆导致系统不可预测，并增加了调试的不必要的复杂性。错误使用RPC可以导致不可维护的意大利面条代码，而不是简化软件。
考虑到这一点，请考虑以下建议：
1.确保明显哪个函数调用是本地的，哪个是远程的。
2.记录您的系统。使组件之间的依赖关系变得清晰。
3.处理错误案例。当RPC服务器长时间停机时，客户端应该如何反应？
4.如有疑问，请避免使用RPC。如果可以，您应该使用异步管道 - 而不是类似RPC的阻塞，将结果异步推送到下一个计算阶段。

### 功能说明
本文使用RabbitMQ实现RPC的调用方式，我们需要使用回调队列(Callback queue)。
(1)Callback queue,通过Rabbitmq进行rpc很简单，客户端发送消息，服务端响应消息，但是为了接受响应，我们需要发送带有请求的回调队列地址。

![最适合入门的RabbitMQ+PHP教程（七）RPC -- 蜗牛巢](https://image.phpassn.com/upload/Assn02/72/p8/2019-04-07_72a02e2313cf387c8e5d54a1de16ee31)

(2) Message attribute
>AMQP 0-9-1协议预定义了一组带有消息的14个属性。大多数属性很少使用，但以下情况除外：
1.delivery_mode：标记消息传递模式，2-消息持久化，其他值-瞬态。
2.content_type：内容类型，用于描述编码的mime-type. 例如经常为该属性设置JSON编码。
3.reply_to：应答，通用的回调队列名称，
4.correlation_id：关联ID,方便RPC相应与请求关联。

(3) RPC处理流程
1. RPC客户端启动后，创建一个匿名、独占的、回调的队列
2. RPC客户端设置消息的2个属性：replyTo(回调队列名字)和correlationId(标记请求ID)，然后将消息发送到队列rpc_queue
3.请求被发送到rpc_queue队列中
4. RPC服务监听队列rpc_queue队列中的消息请求。rpc服务器端处理之后将结果封装成消息发送到replyTo指定的回调队列中，并且此消息带上correlationId
5. RPC客户端在队列replyTo上监听消息，当收到消息后，它会判断收到消息的correlationId。如果值和自己之前发送的一样，则这个值就是RPC的处理结果

(4) Correlation Id ??? 
若果按照正常的来说我们为每一个RPC创建一个回调队列的话，这个效率是非常低效的。那么我们可以选择为每一个客户端创建一个回调队列。但是如果队列收到一条回复消息，那么却不不清楚响应属于哪个请求来源，这是就需要使用correlationId属性了。我们要为每个请求设置唯一的值。然后，在回调队列中获取消息，查看这个属性，关联response和request就是基于这个属性值的。如果我们看到一个未知的correlationId属性值的消息，可以放心的无视它——它不是我们发送的请求。你可能问道，为什么要忽略回调队列中未知的信息，而不是当作一个失败？这是由于在服务器端竞争条件的导致的。虽然不太可能，但是如果RPC服务器在发送给我们结果后，发送请求反馈前就挂掉了，这有可能会发送未知correlationId属性值的消息。如果发生了这种情况，重启RPC服务器将会重新处理该请求。这就是为什么在客户端必须很好的处理重复响应，RPC应该是幂等的。

### RPC client code (客户端代码)
主要业务逻辑如下：
1. 配置连接工厂
2. 建立TCP连接
3. 在TCP连接的基础上创建通道
4. 定义临时队列replyQueueName，声明唯一标志本次请求corrId，并将replyQueueName和corrId配置要发送的消息队列中
5. 使用默认的交换机发送消息到队列rpc_queue中
6. 使用阻塞队列BlockingQueue阻塞当前进程
7. 收到请求后，将请求放入BlockingQueue中，主线程被唤醒，打印返回内容
```
 public function call($body = null,$name = null)
{
    if (!$body || !$name) return false;
    $this->response = null;
    // 随机一个correlation_id码
    $this->corr_id = uniqid();
    //replyTo指定队列callback_queue并携带correlationId校验码（姑且这么说）
    $params = ['correlation_id' => $this->corr_id,'reply_to' => $this->callback_queue];
    $message = new AMQPMessage((string) $body,$params);
    $this->amqp_channel->basic_publish($message, '', $name);
    while (!$this->response) {
        //等待处理
        $this->amqp_channel->wait();
    }
    return intval($this->response);
}
```
完整代码 
RPC客户端代码：[https://github.com/orchid-lyy/rabbitmq-study/blob/master/rabbitmq_test/RPC/rpc_client.php](https://github.com/orchid-lyy/rabbitmq-study/blob/master/rabbitmq_test/RPC/rpc_client.php)

### RPC Service code (服务端代码)
1. 配置连接工厂
2. 建立TCP连接
3. 在TCP连接的基础上创建通道
4. 声明一个rpc_queue队列
5. 设置同时最多只能获取一个消息
6. 在rpc_queue队列在等待消息
7. 收到消息后，调用回调对象对消息进行处理，向此消息的replyTo队列中发送处理并带上correlationId
8. 使用wait-notify实现主线程和消息处理回调对象进行同步
```
 public function basic_consume($queue_name = null)
{
    if(!$queue_name) return false;
    //回调函数
    echo " [x] Awaiting RPC requests\n";
    $callback = function ($response) {
        $n = intval($response->body);

        echo ' [.] 我是回调的数据哦:(', $response->body, ")\n";

        $params = ['correlation_id' => $response->get('correlation_id')];

        $msg = new AMQPMessage((string) $this->fib($n), $params);

        $response->delivery_info['channel']->basic_publish( $msg,'',$response->get('reply_to'));

        $response->delivery_info['channel']->basic_ack($response->delivery_info['delivery_tag']);
    };
    // $prefetch_size 所取大小  $prefetch_count 获取数量  $a_global全局
    $this->amqp_channel->basic_qos(null, 1, null);

    $this->amqp_channel->basic_consume($queue_name, '', false, false, false, false, $callback);
    while (count($this->amqp_channel->callbacks)) {
        $this->amqp_channel->wait();
    }
}
```
完整代码 
RPC服务端代码: [https://github.com/orchid-lyy/rabbitmq-study/blob/master/rabbitmq_test/RPC/rpc_server.php](https://github.com/orchid-lyy/rabbitmq-study/blob/master/rabbitmq_test/RPC/rpc_server.php)

### 测试结果
![最适合入门的RabbitMQ+PHP教程（七）RPC -- 蜗牛巢](https://image.phpassn.com/upload/Assn02/25/t4/2019-04-07_11815239d72d93badd2f7fb5aa2084f6)

![最适合入门的RabbitMQ+PHP教程（七）RPC -- 蜗牛巢](https://image.phpassn.com/upload/Assn02/90/j1/2019-04-07_d637f09c7d05d56324f09efee7445561)

```
[RpcServer] Awaiting RPC requests
[RpcClient] Requesting55
[RpcServer receive] [.] 我是回调的数据哦:(10)
```
总结：RPC的远程过程调用到这里已经结束，应该算是比较清晰一点了，说明白了其实就是客户端与服务端之间的异步交互，通过识别码回调给客户端时，创建一个匿名独占队列，通过这个队列把数据传给客户端。下一章节`rabbitmq延时队列实现`。

原文链接：[https://www.phpassn.com/article/112.html](https://www.phpassn.com/article/112.html)