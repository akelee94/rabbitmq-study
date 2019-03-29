<?php
require_once __DIR__ .'/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * @desc rabbitmq 生成消息
 * @author 82年的烂香蕉(Jonny) <lw1772363381@163.com>
 */
class ReceiveMessage {
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

    public $queue_name = null;

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
     * @param $durable 是否持久换
     * @param $auto_delete 是否自动删除
     * @return void
     */
    public function exchangeDeclare($exchange_name = '',$type = 'topic',$passive = false,$durable = false,$auto_delete = false)
    {
        $this->amqp_channel = $this->amqp_connection->channel();

        $this->amqp_channel->exchange_declare($exchange_name,$type,$passive,$durable,$auto_delete);
    }

    /**
      * @desc 设置队列名称 
      * $queue_name: 队列名称
      * $passive: false
      * $durable: false  队列是否持久化 
      * $exclusive: false  当前连接不在时，队列是否自动删除
      * $auto_delete: false  没有consumer时，队列是否自动删除 
      * @return
      */
    public function setQueueName($queue_name = "",$passive = false,$durable = false,$exclusive = true,$auto_delete = false)
    {
        if(!$queue_name) return false;

        $this->amqp_channel->queue_declare($queue_name, $passive, $durable, $exclusive, $auto_delete);

        $this->queue_name = $queue_name;
    }

    /**
     * @desc 将队列绑定到交换机
     * @param $queue_name
     * @param $exchange_name
     * @param $binding_routing_key
     * @return
     */
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
    
   /**
     * 开启队列的消费
     *
     * @param string $queue_name
     * @param bool $no_local
     * @param bool $no_ack
     * @param bool $exclusive
     * @param bool $nowait
     * @param callback|null $callback
     * @return mixed|string
     */
    public function receiveMessage($queue_name = '')
    {
        if(!$queue_name) $queue_name = $this->queue_name;
        //信息输出 传一个闭包
        $callback = function ($message) {
            echo $message->delivery_info['routing_key'] . 'Received Message:', $message->body, "\n";
        };
        $this->amqp_channel->basic_consume($queue_name, '', false, true, false, false, $callback);
        //while判断消息是否已经消费完
        while (count($this->amqp_channel->callbacks)) {
            $this->amqp_channel->wait();
            //为了方便演示 延迟两秒消费
            sleep(2);
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
$amqp_connection = new ReceiveMessage($config);
//交换机名称
$exchange_name = "topic_phpassn";
$amqp_connection->exchangeDeclare($exchange_name,'topic');
//队列名称
$queue_name = 'queue_topic_clevercode1';
// $queue_name = 'queue_topic_clevercode2';
$amqp_connection->setQueueName($queue_name);
//绑定交换机
// $binding_routing_key = '*.user.*';
// $binding_routing_key = '*.*.log';
$binding_routing_key = 'user.#';
// $binding_routing_key = 'user.#';
$amqp_connection->bindQueue($queue_name,$exchange_name,$binding_routing_key);

$amqp_connection->receiveMessage($queue_name);

$amqp_connection->close();
