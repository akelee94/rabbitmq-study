<?php
require_once __DIR__ .'./vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @desc rabbitmq 生成消息
 * @author 82年的烂香蕉(Jonny) <lw1772363381@163.com>
 */
class SendMessage {
    /**
     * @desc 默认主机地址
     *
     * @var string
     */
    public $amqp_host = "localhost";
    
    /**
     * @desc 主机连接端口
     *
     * @var integer
     */
    public $amqp_port = 5672;

    /**
     * @desc 主机登录用户名
     *
     * @var string
     */
    public $amqp_username = "guest";

    /**
     * @desc 主机登录密码
     *
     * @var string
     */
    public $amqp_password = "guest";

    /**
     * @desc 连接对象实例 
     *
     * @var [type]
     */
    public $amqp_connection = null;

    /**
     * @desc 频道
     *
     * @var [type]
     */
    public $amqp_channel = null;

    /**
     * @desc 初始化构造数据
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->amqp_host = $config['host']?:$this->amqp_host;
        $this->amqp_port = $config['port']?:$this->amqp_port;
        $this->amqp_username = $config['username']?:$this->amqp_username;
        $this->amqp_password = $config['password']?:$this->amqp_password;
        $this->amqp_connection = new AMQPStreamConnection($this->amqp_host,$this->amqp_port,$this->amqp_username,$this->amqp_password);
    }

    /**
     * @desc 声明创建一个队列
     *
     * @param $queue_name 队列名称
     * @param $passive 是否被动处理
     * @param $durable 是否持久化
     * @param $exclusive 当前连接不在时，是否自动删除队列
     * @param $auto_delete 没有consumer时，是否自动删除队列 
     * @param $nowait 是否不需要等待
     * @return void
     */
    public function queueDeclare($queue_name = '',$passive = false,$durable = false,$exclusive = false,$auto_delete = false,$nowait = false)
    {
        //获取由数字通道标识的通道对象，或者如果该对象不存在，则创建该对象
        $channel_id = null;
        $this->amqp_channel = $this->amqp_connection->channel($channel_id);
        //声明一个队列
        $this->amqp_channel->queue_declare($queue_name,$passive,$durable,$exclusive,$auto_delete,$nowait);
        return $this;
    }
    
    /**
     * @desc 发布消息
     *
     * @param string $queue_name 队列名称
     * @return void
     */
    public function sendMessage($queue_name = '')
    {
        $msessage = "Hellow world";
        for($i=0;$i <=30; $i++) {
            $amqp_message = new AMQPMessage($msessage);
            $this->amqp_channel->basic_publish($amqp_message,'',$queue_name);
        }
        echo "Send 'Hello World phpassn!'\n";
    } 

    /**
     * @desc 关闭服务
     *
     * @return void
     */
    public function closeChannel()
    {
        $this->amqp_channel->close();
        $this->amqp_connection->close();
    }
}
$config = ['host'=>'localhost','port'=>5672,'username'=>'guest','password'=>'guest'];
//实例化发送类
$amqp_connection = new SendMessage($config);
$routing_key = "phpassn";
$amqp_connection->queueDeclare($routing_key);
$amqp_connection->sendMessage($routing_key);
$amqp_connection->closeChannel();
