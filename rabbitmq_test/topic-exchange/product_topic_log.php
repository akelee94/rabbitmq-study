<?php
require_once __DIR__ .'/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @desc rabbitmq 生成消息
 * @author 82年的烂香蕉(Jonny) <lw1772363381@163.com>   topic交换机
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
     * @desc 虚拟机
     */
    public $amqp_vhost = '/'; 

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
        $this->amqp_connection = new AMQPStreamConnection($this->amqp_host,$this->amqp_port,$this->amqp_username,$this->amqp_password,$this->amqp_vhost);
    }

    /**
     * @desc 声明创建一个交换机
     *
     * @param $exchange_name 交换机名称名称
     * @param $type 类型 此类型有多种 例如：direct，topic，headers， fanout这几种类型 后面一一解析
     * @param $passive 是否被动触发
     * @param $durable 交换机将在服务器重启后生存
     * @param $auto_delete 通道关闭的时候，是否自动删除交换机
     * @return void
     */
    public function exchangeDeclare($exchange_name = '',$type = 'topic',$passive = false,$durable = false,$auto_delete = false)
    {
        $this->amqp_channel = $this->amqp_connection->channel();
        //创建一个交换机
        $this->amqp_channel->exchange_declare($exchange_name,$type,$passive,$durable,$auto_delete);
        return $this;
    }
    
    /**
     * @desc 发布消息
     *
     * @param string $exchange_name 交换机名称
     * @param string $routing_key 路由key
     * @return void
     */
    public function sendMessage($exchange_name = '',$routing_key = '')
    {
        if(!$exchange_name || !$routing_key) return false;
        // $msessage = implode(' ', array_slice($argv, 2)); //此处可省略不要 么有什么意义
        if (empty($msessage)) {
            $msessage = "message: Hello phpassn!'\n  蜗牛巢高质量分享原创文章";
        }
        for($i=0;$i <=10; $i++) {
            $amqp_message = new AMQPMessage($msessage);
            $this->amqp_channel->basic_publish($amqp_message,$exchange_name,$routing_key);
            echo $routing_key . " -- Send 'Hello phpassn!'\n 蜗牛巢高质量分享原创文章 -- " . $i;
        }
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
$exchange_name = "topic_phpassn";
$exchange_type = "topic";
// $routing_key = "user.name.one";
// $routing_key = "list1.user.two";
// $routing_key = "test.test1.log";
$routing_key = "user.name.one";
$amqp_connection->exchangeDeclare($exchange_name,$exchange_type);
$amqp_connection->sendMessage($exchange_name,$routing_key);
$amqp_connection->closeChannel();
