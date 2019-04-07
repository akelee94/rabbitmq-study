<?php
require_once __DIR__ .'/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @desc rabbitmq 生成消息
 * @author 82年的烂香蕉(Jonny) <lw1772363381@163.com>
 */
class FibonacciRpcServer {
    /**
     * @desc 默认主机地址
     *
     * @var string
     */
    private $amqp_host = "localhost";
    
    /**
     * @desc 主机连接端口
     *
     * @var integer
     */
    private $amqp_port = 5672;

    /**
     * @desc 主机登录用户名
     *
     * @var string
     */
    private $amqp_username = "guest";

    /**
     * @desc 主机登录密码
     *
     * @var string
     */
    private $amqp_password = "guest";

    /**
     * @desc 连接对象实例 
     *
     * @var [type]
     */
    private $amqp_connection = null;

    /**
     * @desc 频道
     *
     * @var [type]
     */
    private $amqp_channel = null;

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
        // 声明一个频道
        $this->amqp_channel = $this->amqp_connection->channel();
    }

    /**
     * @desc 申明一个队列
     *
     * @return void
     */
    public function queue_declare($queue_name = null)
    {
        if(!$queue_name) return false;

        $this->amqp_channel->queue_declare($queue_name,false,false,true,false); 

        return $this;
    }

    /**
     * @desc 
     *
     * @return void
     */
    public function fib($n)
    {
        if ($n == 0) {
            return 0;
        }
        if ($n == 1) {
            return 1;
        }
        return $this->fib($n-1) + $this->fib($n-2);
    }

    /**
     * @desc rpc 
     *
     * @return void
     */
    public function basic_consume($queue_name = null)
    {
        if(!$queue_name) return false;
        //回调函数
        echo "Awaiting RPC requests\n";
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
$amqp_connection = new FibonacciRpcServer($config);
$queue_name = 'phpassn3';
$amqp_connection->queue_declare($queue_name);
$amqp_connection->basic_consume($queue_name);
$amqp_connection->closeChannel();
