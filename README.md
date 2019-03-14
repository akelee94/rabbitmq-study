>最近本来想继续研究`rabbitmq`，但是虚拟机出现了点点问题，所以就快速的再windows上安装一下rabbitmq的环境，方便直接写PHP代码来测试一些项目功能。那么下面就直接记录下整个安装流程了。

## 1、Erlang的安装
首先，您需要安装支持的 Windows 版Erlang。下载并运行`Erlang for Windows` 安装程序。为什么要安装erlang？因为RabbitMQ服务端代码是使用并发式语言Erlang编写的，安装Rabbit MQ的前提是必须安装Erlang。

1. 下载地址[http://www.erlang.org/downloads](http://www.erlang.org/downloads)，我本机电脑是64位的所以下载的64位版本。
![photo.png](image.phpassn.com/upload/Assn02/30/p0/2019-03-14_e6f5f793d50db28e7e388fec90264f37)

1. 下载完成以后安装就是常规走法，一直next下一步下一步就完事了，下面是我的傻瓜式安装流程（不需要的 可以略过此步操作）
![photo.png](image.phpassn.com/upload/Assn02/85/y1/2019-03-14_46e18103c80c645b79ccdb7cba7aad3e)
![photo.png](image.phpassn.com/upload/Assn02/98/b3/2019-03-14_46a80e2ccbc63716951e926030c59225)
![photo.png](image.phpassn.com/upload/Assn02/23/e0/2019-03-14_f2c20e12d4a6be4830d38b104e31aed5)
![photo.png](image.phpassn.com/upload/Assn02/17/r6/2019-03-14_c39f2dd843e00e57808dda01cd6cdcea)

1. 配置环境变量：`ERLANG_HOME=D:\erl10.3`（刚刚安装路径）

![photo.png](image.phpassn.com/upload/Assn02/99/b6/2019-03-14_f6c573e8739c6d0ef8ce8c71320762e4)

1. 然后path加参数：`%ERLANG_HOME%\bin;`

![photo.png](image.phpassn.com/upload/Assn02/18/d9/2019-03-14_47cb3b81deb2e97cbd2a741e92310d6c)

1. 下面验证Erlang配置是否正确是否正常启动，快捷键windows+R，输入cmd打开命令窗口，输入erl，显示如下版本号了，证明我们已经配置成功！

![photo.png](image.phpassn.com/upload/Assn02/97/i6/2019-03-14_2cde9f6df37eabc803a552311552bc6f)

## 2，下载rabbitmq与安装流程

（1），下载地址：[http://www.rabbitmq.com/install-windows.html](http://www.rabbitmq.com/install-windows.html)

![photo.png](image.phpassn.com/upload/Assn02/41/c6/2019-03-14_8945c392b5ebbc7be784efd1d129417b)

我这边的安装流程如下（傻瓜式的next，会的直接略过）：
![photo.png](image.phpassn.com/upload/Assn02/87/i3/2019-03-14_635e36c733779758d6675388b8640ea9)
![photo.png](image.phpassn.com/upload/Assn02/12/e3/2019-03-14_897236f752fd9e00491d84826a451ccf)
![photo.png](image.phpassn.com/upload/Assn02/75/b9/2019-03-14_ae82771392d84610828b3f524613948a)

（2），rabbitmq环境变量配置 

1.`RABBITMQ_SERVER=D:\rabbitmq\rabbitmq_server-3.7.13`

![photo.png](image.phpassn.com/upload/Assn02/18/t2/2019-03-14_97267b49b10438bab7d160accf7d2052)

2.在Path中加入：`%RABBITMQ_SERVER%\sbin;`
![photo.png](image.phpassn.com/upload/Assn02/58/y4/2019-03-14_e209223329acfe8ab84ea836aa9cc7bf)

（3），接下来安装`RabbitMQ-Plugins`，激活rabbitmq_management。快捷键windows+R，输入cmd打开命令窗口cd命令，进入RabbitMQ安装的sbin目录。
执行：`rabbitmq-plugins enable rabbitmq_management`

![photo.png](image.phpassn.com/upload/Assn02/33/v8/2019-03-14_44c624913f9e3b6c689fb1078478ab85)


（4），我们一起来启动rabbitmq服务

第一种方式：找到安装目录下的sbin目录，我本地是：`D:\rabbitmq\rabbitmq_server-3.7.13\sbin`，找到`rabbitmq-server.bat`这个脚本双击即可启动。可以打开这个文件大致看一下，是一个rabbitmq启动服务命令。

![photo.png](image.phpassn.com/upload/Assn02/59/y6/2019-03-14_24f3faa31bec128707d73f7426c247b7)

双击之后会自动弹窗一个命令框如下：
![photo.png](image.phpassn.com/upload/Assn02/34/v2/2019-03-14_a073ffb98c39a24a5de4b915691a79d3)

然后我们浏览器输入http://localhost:15672（15672端口是rabbitmq默认的端口哦，不明白的看看rabbitmq官方文档就知道为什么是这个）。

![photo.png](image.phpassn.com/upload/Assn02/66/m0/2019-03-14_c6f70946d3d9fb5fd1ad23b08ab4d07c)

注意：强调一下rabbitmq默认的账户和密码都是guest，不明白的查看官方文档。

登录以后界面如下图：

![photo.png](image.phpassn.com/upload/Assn02/29/y1/2019-03-14_4862bf3d4fb601b9a6b50395cff802f8)

第二种启动方式：把上面打开的命令框关闭。
	`net stop RabbitMQ && net start RabbitMQ`
可能会出现一下情况：

![photo.png](image.phpassn.com/upload/Assn02/84/v1/2019-03-14_9915f5d6a5ad6ba6e9232247bf72e6a6)

这个告诉我们服务名无效，则说明RabbitMQ不是windows的服务，所以我们需要注册一下windows服务：切换目录到sbin下 执行`rabbitmq-service.bat install` 安装一下

![photo.png](image.phpassn.com/upload/Assn02/71/g7/2019-03-14_c8fcabbb3843ba325b79906de9b10195)

然后我们来检测一下rabbitmq是否已经注册到windows服务里面，windows+R 输入`services.msc`查看windows服务：

![photo.png](image.phpassn.com/upload/Assn02/24/s1/2019-03-14_457f9624a2351fd464e295743df56d85)

发现已经注册上了，则可以使用启动命令了。输入net start RabbitMQ出现一下界面，已经成功。

![photo.png](image.phpassn.com/upload/Assn02/92/o3/2019-03-14_4afa909dc71bb09f57cb9783eb7f0759)

然后显示界面就跟上面一个样子了。到这里，整个windows下配置rabbitmq已经全部搞定！

下面就会开始rabbitmq的使用，每一个章节都会上代码，请时刻关注一下，会继续跟新rabbitmq的使用，明白当前最流行的消息队列服务。

Linux版本的安装方式请看另一篇章：[https://www.phpassn.com/article/62.html](https://www.phpassn.com/article/62.html)

