>上一章我们讲解了`rabbitmq`的其中一个功能案例发布与订阅，其实使用到了交换机的其中一个类型就是`Fanout exchange（扇型交换机）`，那么今天能就来说一说其他几种交换机类型。

## 主要章节
1.交换机用来做什么的？有什么作用？
2.交换机的四种类型以及图形解析！

###1.交换机是用来干嘛的？
先来看下`rabbitmq`实现一个消息的生产与发送的整个流程：
(1).生产者：负责生产消息
(2).交换机：将收到的消息根据路由规则路由到特定队列
(3).队列：消息存储器
(4).消费者：接收消息消费掉

注解：rabbitmq消息队列处理中，生产者并不是直接将消息发送给消费者，甚至生成者根本不晓得信息要传到哪一个队列里面处理，只是将消息发送到随机(不指定)的一个交换机内，然后交换机根据匹配规则进行发放消息到哪一个队列里面。但是这种，会出现将消息发送到多个队列中，这样就导致我本身不想要的信息却丢给我了，这样不就是做的无用功嘛；又或者将发送的消息弄丢掉。所以我们在正常使用rabbitmq的时候都需要指定一个交换机发送到指定的队列里面，至于消息丢失，后面我们会讲到rabbitmq的持久化操作。

###2.交换机的四种类型

Rabbitmq根据路由过程的不同划分四种类型：
1.`Direct exchange（直连交换机）`
2.`Topic exchange（主题交换机）`
3.`Headers exchange（头交换机）`
4.`Fanout exchange（扇型交换机）`
补充说明一下：虽然说官方本义说是四种交换机类型，实际上还有自己默认定义的交换机类型，比如：`默认交换机`，`amq.* exchanges(amq.开头交换机)`,还有一种`Dead Letter Exchange（死信交换机）`，死信交换机后面会讲述一下。

#### Direct exchange（直连交换机）
直连型交换机（direct exchange）是根据消息携带的路由键（`routing key`）将消息投递给对应队列的。举例说明：
1，有一个路由键名为：`phpassn_routing`
2，有一个消息队列为：`phpassn_queue`
3，有一个交换机为：`phpassn_exchange`
他们之间的关系是：队列会bind绑定到交换机上的时候同时会赋予给这个绑定队列一个路由键（phpassn_routing），当一个携带路由值为phpassn_routing的消息发送到交换机上时，交换机就会找到绑定它本身值为phpassn_routing这个路由键，然后把消息发送给他。这种模式看起来就比较像我们一对一的处理。下面是分析图，红色叉部分是不通过的。
![photo.png](https://image.phpassn.com/upload/Assn02/13/g0/2019-03-22_222e6192baf9375d2188974cabde324a)

### Topic exchange（主题交换机）

主题交换机是类似redis的发布于订阅模式，它是消息队列通过路由键绑定到交换机上后，交换机再根据消息里面的路由值，将消息路由给一个或多个绑定队列。跟扇形交换机很相似，可以这么说，主题交换机就像是数学中的绝对匹配，类似PHP的正则匹配，只有满足路由规则才能将消息发送到对应的队列里。如下图：

![photo.png](https://image.phpassn.com/upload/Assn02/30/f4/2019-03-22_7db74e460fb3b70bda8406d076d9d967)

大家能很清楚地看到最后一个队列的路由键对应不上路由规则，交换机就不会将消息发送到该队列中。

### Headers exchange（头交换机）

目前公司项目中还没有用到头交换机这种类型，由于是集群模式，这里就不细说了，就说说自己本地搭建的情况。首先头交换机跟主题交换机非常相似，都是通过路由匹配规则来验证是否符合条件，然后分配消息。但是两者不同之处是，上面我们讲了主题交换机是通过路由键作为匹配规则的，而头交换机则是通过消息体本身的属性进行消息的分发，是通过判断消息头的值是否与指定的绑定相匹配来确立路由规则。头交换机中有一个特别的参数`”x-match”`，这个参数是用来匹配消息属性值：

1.当”x-match”为“any”时，消息头的任意一个值被匹配就可以满足条件
2.当”x-match”设置为“all”的时候，就需要消息头的所有值都匹配成功（那么这个相对来说就是比较严格了）

举例说明一下：
队列A：绑定交换机参数是：logs1='phpassn',log2='www.phpassn.com'
队列B: 绑定交换机参数是：logs1='phpassn1',log2='www.phpassn.com2'
队列C：绑定交换机参数是：logs1='phpassn2',log2='www.phpassn.com3'
队列D：绑定交换机参数是：logs1='phpassn',log2='www.phpassn.com1

Message1发送交换机的头参数是：logs1='phpassn',log2='www.phpassn.com'  匹配规则x-match=all
Message2发送交换机的头参数是：logs1='phpassn' 匹配规则x-match=any
Message3发送交换机的头参数是：logs1='phpassn5' log2='www.phpassn.com5 匹配规则x-match=any 

![photo.png](https://image.phpassn.com/upload/Assn02/75/w1/2019-03-22_f0019022e08d80146ec316d7054467ae)

可以很清楚的看到最后一个Message3的消息没有通过交换机匹配到队列，因此消息会被丢弃掉处理。

### Fanout Exchange（扇型交换机）

这个应该是我们用的最多的一种类型了。扇型交换机（funout exchange）将消息路由给绑定到它身上的所有消息队列，其中它的路由键是不起作用的，所以路由键是可以直接设置为空的。那么既然可以直接将消息发布到绑定在交换机上的匹配的消息队列，那么它就是相当于一个广播站，直接将订阅这个交换机的信息分布到所有匹配的队列上，所以比较适用于发布订阅这一类的。

![photo.png](https://image.phpassn.com/upload/Assn02/20/n1/2019-03-22_ed1be7c1ce42e4bcd9d50189baeb769c)

总结：上面画的图都是自己手动画板上画的 并不是怎么好看！上面几种方式则是常用的四个交换机类型。有需要的童鞋们可以自行阅读！

下面是后台默认的几种交换机类型：
![photo.png](https://image.phpassn.com/upload/Assn02/19/c8/2019-03-22_9ad9685239b340a45175a7cb63f96308)

原文链接：[https://www.phpassn.com/article/102.html](https://www.phpassn.com/article/102.html)