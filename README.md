rabbitmq-PHP入门教程及文档
===================

![photo.png](https://image.phpassn.com/upload/Assn02/42/a8/2018-10-16_609e521312c6a793d2a50924d363d9cf)

>几个概念说明：
Broker：简单来说就是消息队列服务器实体。

Exchange：消息交换机，它指定消息按什么规则，路由到哪个队列。

Queue：消息队列载体，每个消息都会被投入到一个或多个队列。

Binding：绑定，它的作用就是把exchange和queue按照路由规则绑定起来。

Routing Key：路由关键字，exchange根据这个关键字进行消息投递。

vhost：虚拟主机，一个broker里可以开设多个vhost，用作不同用户的权限分离。

Producer：消息生产者，就是投递消息的程序。

Consumer：消息消费者，就是接受消息的程序。

Channel：消息通道，在客户端的每个连接里，可建立多个channel，每个channel代表一个会话任务。

消息队列的使用过程：

1、客户端连接到消息队列服务器，打开一个channel。

2、客户端声明一个exchange，并设置相关属性。

3、客户端声明一个queue，并设置相关属性。

4、客户端使用routing key，在exchange和queue之间建立好绑定关系。

5、客户端投递消息到exchange。

6、exchange接收到消息后，就根据消息的key和已经设由binding，进行消息路里，将消息投递到一个或多个队列里

ps:通过durable参数来进行exchang、queue、消息持久化

本教程永久github地址。
https://github.com/orchid-lyy/rabbitmq-study

##作者
Jonny（ID: 82年的烂香蕉） 

##联系方式
lw1772363381@163.com (Jonny)

##蜗牛巢社区
https://www.phpassn.com

最简单最易入门的Rabbitmq-PHP入门教程
===================
1. [01-最适合入门的RabbitMQ+PHP教程（一）windows环境下安装rabbitmq](https://www.phpassn.com/article/97.html) 
2. [02-最适合入门的RabbitMQ+PHP教程（二）windows安装PHP扩展amqp](https://www.phpassn.com/article/98.html) 
3. [03-最适合入门的RabbitMQ+PHP教程（三）消息队列简单使用](https://www.phpassn.com/article/99.html) 
4. [04-最适合入门的RabbitMQ+PHP教程（四）发布与订阅](https://www.phpassn.com/article/101.html) 



