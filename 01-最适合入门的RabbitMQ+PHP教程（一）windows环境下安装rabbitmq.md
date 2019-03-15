>最近本来想继续研究`rabbitmq`，但是虚拟机出现了点点问题，所以就快速的再windows上安装一下rabbitmq的环境，方便直接写PHP代码来测试一些项目功能。那么下面就直接记录下整个安装流程了。

## 1、Erlang的安装
首先，您需要安装支持的 Windows 版Erlang。下载并运行`Erlang for Windows` 安装程序。为什么要安装erlang？因为RabbitMQ服务端代码是使用并发式语言Erlang编写的，安装Rabbit MQ的前提是必须安装Erlang。

1. 下载地址[http://www.erlang.org/downloads](http://www.erlang.org/downloads)，我本机电脑是64位的所以下载的64位版本。
![photo.png](image.phpassn.com/upload/Assn02/30/p0/2019-03-14_e6f5f793d50db28e7e388fec90264f37)


1. 配置环境变量：`ERLANG_HOME=D:\erl10.3`（刚刚安装路径）

1. 然后path加参数：`%ERLANG_HOME%\bin;`

1. 下面验证Erlang配置是否正确是否正常启动，快捷键windows+R，输入cmd打开命令窗口，输入erl，显示如下版本号了，证明我们已经配置成功！

## 2，下载rabbitmq与安装流程

（1），下载地址：[http://www.rabbitmq.com/install-windows.html](http://www.rabbitmq.com/install-windows.html)

（2），rabbitmq环境变量配置 

1.`RABBITMQ_SERVER=D:\rabbitmq\rabbitmq_server-3.7.13`

2.在Path中加入：`%RABBITMQ_SERVER%\sbin;`

（3），接下来安装`RabbitMQ-Plugins`，激活rabbitmq_management。快捷键windows+R，输入cmd打开命令窗口cd命令，进入RabbitMQ安装的sbin目录。
执行：`rabbitmq-plugins enable rabbitmq_management`


（4），我们一起来启动rabbitmq服务

第一种方式：找到安装目录下的sbin目录，我本地是：`D:\rabbitmq\rabbitmq_server-3.7.13\sbin`，找到`rabbitmq-server.bat`这个脚本双击即可启动。可以打开这个文件大致看一下，是一个rabbitmq启动服务命令。

双击之后会自动弹窗一个命令框如下：

然后我们浏览器输入http://localhost:15672（15672端口是rabbitmq默认的端口哦，不明白的看看rabbitmq官方文档就知道为什么是这个）。

注意：强调一下rabbitmq默认的账户和密码都是guest，不明白的查看官方文档。

登录以后界面如下图：

第二种启动方式：把上面打开的命令框关闭。
	`net stop RabbitMQ && net start RabbitMQ`
可能会出现一下情况：

这个告诉我们服务名无效，则说明RabbitMQ不是windows的服务，所以我们需要注册一下windows服务：切换目录到sbin下 执行`rabbitmq-service.bat install` 安装一下

然后我们来检测一下rabbitmq是否已经注册到windows服务里面，windows+R 输入`services.msc`查看windows服务：

发现已经注册上了，则可以使用启动命令了。输入net start RabbitMQ出现一下界面，已经成功。


然后显示界面就跟上面一个样子了。到这里，整个windows下配置rabbitmq已经全部搞定！

下面就会开始rabbitmq的使用，每一个章节都会上代码，请时刻关注一下，会继续跟新rabbitmq的使用，明白当前最流行的消息队列服务。

Linux版本的安装方式请看另一篇章：[https://www.phpassn.com/article/62.html](https://www.phpassn.com/article/62.html)

