>上一章我们讲解了[最适合入门的RabbitMQ+PHP教程（五）交换机类型](https://www.phpassn.com/article/102.html)我们通过direct exchange（直连交换机）可以根据路由键进行路由，但是还是不够灵活，它只能进行完全匹配。这节我们引入Topic exchange(主题交换机)，支持对路由键的模糊匹配 
上篇文章实现生产者发送一个消息，这个消息同时被传送给所有队列。但是有时我们不希望所有的消息都被所有队列接收，我们希望可以指定类型为a的消息只能被队列A接收，类型为b的消息只能被队列B,C接收。扇型交换机只能无脑地广播消息给所有的消费者，实质是广播给所有关联的队列。 
为了实现这个功能，一种是建立多个交换机，这种方式简单暴力但是不灵活。本节我们介绍使用单个直连交换机+路由实现以上功能

### Topic Exchange(主题交换)的路由键条件
1.消息发送到主题交换机不能具有任意的`routing_key`，它必须是由`点（.）`分割开的单词列表。单词可以是任意的内容，但是通常单词的定义都是与当前的消息主题类型有关系。一些有效的路由键示例：`stock.usd.nyse`，`nyse.vmw`，`quick.orange.rabbit`。路由密钥中可以包含任意数量的单词，最多可达255个字节。
2.绑定密钥也必须采用`相同`的形式。主题交换背后的逻辑类似于`直接交换`- 使用特定`路由密钥`发送的消息将被传递到与`匹配绑定密钥绑定的所有队列`。但是绑定键有两个重要的特殊情况：
(1)`*`(star)可以替代一个单词
(2)`#`(hash)可以替换零个或多个单词

### Topic Exchange(主题交换)的原理
Direct Exchange（直接交换机）的路由规则是必须完全匹配`routing key`(路由key)与`binding key`(绑定key)，但是这中匹配方式并不能满足我们有一些特定的业务逻辑。所以我们可以使用Topic Exchange（主题交换机），与直连交换机不同的是匹配规则发生了变化：
1.routing key(路由key)是以一个.作为字符串的分隔符，每一块是一个单词
2.binding key(绑定key)跟routing key一样都是以.分割字符串。匹配规则是，使用*与#做模糊匹配，例如：'*.user.*','*.*.log','user.#'

```
routing_key=list1.user.two
routing_key=list2.user.three
routing_key=user.name.one
routing_key=user.name.two
routing_key=test.test1.log
routing_key=test2.test3.log
```
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/68/u8/2019-03-29_d86c0f7e6b255787e3bb7ba7c07cc7e4)

从上面图可以看出：路由密钥设置为"list1.user.two"与"list2.user.three"的消息将被传递到queue_topic_1队列,
路由key为"user.name.one","user.name.two","test.test1.log","test2.test3.log"则会被发送到queue_topic_2队列中。
如果现在有一个路由key都不符合上面规则的话，则意味着跟任何绑定都不匹配，因此它讲被丢弃掉。

### Topic Exchange(主题交换)的小结

>主题交换机功能比较全面比较大，当队列绑定"＃"匹配绑定密钥时 - 它将接收所有消息，而不管路由密钥 - 如扇出交换机;当特殊字符"*"和"＃"未在绑定中使用时，主题交换的行为就像直接交换一样。下面我们就实际操作一下topic交换机使用！

### Topic Exchange(主题交换)的代码案例
1.匹配规则不正确演示：
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/37/f3/2019-03-29_8f5ccf25f5240a747655eb9e0bc4b955)

![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/19/f2/2019-03-29_3878a3c1b5f9e0ac3a6ad69387f4e80c)

2.*匹配规则演示
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/90/l1/2019-03-29_fd0d583284bd5342791a4a80732aaaed)
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/91/x4/2019-03-29_26c2f69e54fa2a5ee161608b612b0047)

3.*号匹配规则2演示，匹配最后一个.的内容
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/13/i2/2019-03-29_380d8b08604422fb7d7b88f449090b43)
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/63/d4/2019-03-29_853df01a83f153ad5e5364e81ff3b930)
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/15/v0/2019-03-29_2c9d60d3c578e7712cac51132222926a)

4.#号匹配规则演示
![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/25/v9/2019-03-29_cfd2a6a6e15bdd722fb358ef286a5b6f)

![最适合入门的RabbitMQ+PHP教程（六）Topic exchange（主题交换机）使用！](image.phpassn.com/upload/Assn02/61/l8/2019-03-29_fd7591d7ca60c3cfd9b79144075f0046)

(1)接收消息设置队列名称
```
public function setQueueName($queue_name = "",$passive = false,$durable = false,$exclusive = true,$auto_delete = false)
{
    if(!$queue_name) return false;

    $this->amqp_channel->queue_declare($queue_name, $passive, $durable, $exclusive, $auto_delete);

    $this->queue_name = $queue_name;
}
```
(2)将队列绑定到交换机（注意可以绑定多个）
```
public function bindQueue($queue_name = '',$exchange_name = '',$binding_routing_key = '')
{
    if(!$exchange_name || !$binding_routing_key) return false;

    if(!$queue_name) $queue_name = $this->queue_name;

    //多个绑定key 循环绑定
    if(is_array($binding_routing_key)) {
        foreach($binding_routing_key as $key => $binding_key) {
            $this->amqp_channel->queue_bind($queue_name, $exchange_name, $binding_key);
        }
        return true;
    }
    $this->amqp_channel->queue_bind($queue_name, $exchange_name, $binding_routing_key);
}
```
(3)接收消息设置队列名称
```
public function sendMessage($exchange_name = '',$routing_key = '')
{
    if(!$exchange_name || !$routing_key) return false;
    $msessage = "message: Hello phpassn!'\n  蜗牛巢高质量分享原创文章";
    for($i=0;$i <=10; $i++) {
        $amqp_message = new AMQPMessage($msessage);
        $this->amqp_channel->basic_publish($amqp_message,$exchange_name,$routing_key);
        echo $routing_key . " -- Send 'Hello phpassn!'\n 蜗牛巢高质量分享原创文章 -- " . $i;
    }
} 
```

总结：`Topic Exchange(主题交换机)`有的时候可以充当直接交换机，但是更多的时候可以多种使用，也是我们比较常用的，比如小编的公司项目等等都会使用主题交换，需要补充知识的童鞋们捉紧了，下一章我们会讲解`RabbitMQ之实现RPC模式`。童鞋们捉紧关注一波了！

原文链接：[https://www.phpassn.com/article/108.html](https://www.phpassn.com/article/108.html)