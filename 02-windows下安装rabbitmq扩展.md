>上一篇文章我们介绍快速安装rabbitmq的使用环境，这一篇文章则是安装rabbitmq的扩展amqp，这样我们才可以 使用PHP来处理消息队列。

扩展地址：[http://pecl.php.net/package/amqp](http://pecl.php.net/package/amqp)

![photo.png](https://image.phpassn.com/upload/Assn02/58/y6/2019-03-14_b81cb8d3a8a5f10a6373eb3ef50a0fc1)

我这里选择了最新版，大家最好自己根据电脑配置，根据PHP版本选择对应的。我本地PHP版本7.1,然后是x86（NTS）的。

![photo.png](https://image.phpassn.com/upload/Assn02/84/u5/2019-03-14_ff6cf129eed606ebb62447324ef3f9c7)

1.将php_amqp.dll放在php的ext目录里，然后修改php.ini文件，在文件的最后面添加两行

```
[amqp]
extension=php_amqp.dll
```

2.将rabbitmq.4.dll文件放在php7.1的根目录里，然后再到apache目录下修改httpd.con文件，文件尾部添加一行
```
#rabbitmq
LoadFile  "D:\phpstudy2018\PHPTutorial\php\php-7.1.13-nts\rabbitmq.4.dll"
```
![photo.png](https://image.phpassn.com/upload/Assn02/54/e3/2019-03-14_a292028fc408c45c7d35532ed802ddf8)

3.刷新本地的 `localhost/phpinfo.php`

![photo.png](https://image.phpassn.com/upload/Assn02/97/i4/2019-03-14_9a1b32a2f0b7a68e3650a5d1263ee6eb)

接下来我们抽时间开始windows下使用rabbitmq做消息队列处理demo。
原文：[https://www.phpassn.com/article/98.html](https://www.phpassn.com/article/98.html)