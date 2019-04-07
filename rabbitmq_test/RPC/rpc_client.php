<?php
require_once __DIR__ .'/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @desc rabbitmq 生成消息
 * @author 82年的烂香蕉(Jonny) <lw1772363381@163.com>
 */
class FibonacciRpcClient {
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
     * @desc 回调队列名称
     *
     * @var [type]
     */
    private $callback_queue;

    /**
     * @desc响应消息内容
     *
     * @var [type]
     */
    private $response;

    /**
     * @desc
     *
     * @var [type]
     */
    private $corr_id;

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
        //系统随机声明队列
        list($this->callback_queue, ,) = $this->amqp_channel->queue_declare("",false,false,true,false);
        // callback_queue 系统随机分配的队列名称  onResponse回调函数名称
        $this->amqp_channel->basic_consume($this->callback_queue,'',false,true,false,false,array($this,'onResponse'));
    }

    /**
     * @desc 回调队列处理消息
     *
     * @param [type] $response
     * @return void
     */
    public function onResponse($response)
    {
        // 判断消息体携带的correlationId与当前的是否一样 一样的就处理消息内容
        if ($response->get('correlation_id') == $this->corr_id) {
            $this->response = $response->body;
        }
    }

    /**
     * @desc rpc 发送延迟消息
     *
     * @param [type] $body
     * @return void
     */
    public function call($body = null,$name = null)
    {
        if (!$body || !$name) return false;
        $this->response = null;
        // 随机一个cid码
        $this->corr_id = uniqid();
        //replyTo指定队列callback_queue并携带correlationId校验码（姑且这么说）
        $params = ['correlation_id' => $this->corr_id,'reply_to' => $this->callback_queue];
        $message = new AMQPMessage((string) $body,$params);
        $this->amqp_channel->basic_publish($message, '', $name);
        while (!$this->response) {
            //等待处理
            $this->amqp_channel->wait();
        }
        return intval($this->response);
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
$amqp_connection = new FibonacciRpcClient($config);
$name = 'phpassn3';
$body = 10;
$response = $amqp_connection->call($body,$name);
echo 'Requesting', $response, "\n";
$amqp_connection->closeChannel();
