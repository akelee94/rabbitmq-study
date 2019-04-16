<?php
// +----------------------------------------------------------------------
// | Rabbitmq [ study explame]
// +----------------------------------------------------------------------
// | webSite ( https://www.phpassn.com )
// +----------------------------------------------------------------------
// | Author: Jonny lee <lw1772363381@163.com>
// +----------------------------------------------------------------------

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class ReceiveMessage
 */
class JReceiveMessage
{
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
     * @desc 延迟交换机类型
     * @var string
     */
    private static $type = "direct";

    /**
     * @desc 延迟队列名称
     * @var string
     */
    private $delay_queue_name = '';

    /**
     * @desc 交换机名称
     * @var string
     */
    private $delay_exchange_name = '';

    /**
     * @desc 队列名称
     * @var string
     */
    private $queue_name = '';

    /**
     * @desc 交换机名称
     * @var string
     */
    private $exchange_name = '';

    /**
     * @desc 初始化构造数据
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->amqp_host = $config['host'] ?: $this->amqp_host;
        $this->amqp_port = $config['port'] ?: $this->amqp_port;
        $this->amqp_username = $config['username'] ?: $this->amqp_username;
        $this->amqp_password = $config['password'] ?: $this->amqp_password;
        $this->amqp_connection = new AMQPStreamConnection($this->amqp_host, $this->amqp_port, $this->amqp_username, $this->amqp_password);

        if (!(bool)$this->amqp_connection->isConnected()) {
            echo "Rabbitmq Connect error!";
            exit;
        }
        //设置频道
        $this->amqp_channel = $this->amqp_connection->channel();
    }

    /**
     * @desc 创建超时队列和交换机
     * @param string $exchange_name
     * @param string $queue_name
     */
    public function setOutQueue($exchange_name = '', $queue_name = '')
    {
        $this->delay_exchange_name = $exchange_name;
        $this->delay_queue_name = $queue_name;
        try {
            $this->setExchangeDeclare($this->delay_exchange_name, self::$type, false, false, false);
            $this->getQueueDeclare($this->delay_queue_name, false, true, false, false, false);
            $this->setQueueBind($this->delay_queue_name, $this->delay_exchange_name, $this->delay_exchange_name);
            return $this;
        } catch (\Exception $e) {
            return 'Info:' . $e->getMessage();
        }
    }

    /**
     * @desc 设置交换机
     * @param string $exchange_name
     * @return string
     */
    public function setDefaultQueue($exchange_name = '')
    {
        $this->exchange_name = $exchange_name;
        try {
            $this->setExchangeDeclare($this->exchange_name, self::$type, false, false, false);
            return $this;
        } catch (\Exception $e) {
            return 'Info:' . $e->getMessage();
        }
    }

    /**
     * @desc 设置延迟交换机
     * @return AMQPTable
     */
    public function setExchangeDeclare(
        $exchange,
        $type,
        $passive = false,
        $durable = false,
        $auto_delete = true,
        $internal = false,
        $nowait = false,
        $arguments = array(),
        $ticket = null
    )
    {
        $this->amqp_channel->exchange_declare(
            $exchange,
            $type,
            $passive,
            $durable,
            $auto_delete,
            $internal,
            $nowait,
            $arguments,
            $ticket
        );
    }

    /**
     * @desc 创建延迟队列
     * @param string $queue
     * @param bool $durable
     * @param null $arguments
     * @param bool $passive
     * @param bool $exclusive
     * @param bool $auto_delete
     * @param bool $nowait
     * @param null $ticket
     * @return mixed
     */
    public function getQueueDeclare(
        $queue = '',
        $passive = false,
        $durable = false,
        $exclusive = false,
        $auto_delete = true,
        $nowait = false,
        $arguments = array(),
        $ticket = null
    )
    {
        return $this->amqp_channel->queue_declare(
            $queue,
            $passive,
            $durable,
            $exclusive,
            $auto_delete,
            $nowait,
            $arguments,
            $ticket
        );
    }

    /**
     * @desc 绑定队列到交换机上
     * @param $queue
     * @param $exchange
     * @param string $routing_key
     * @param bool $nowait
     * @param null $arguments
     * @param null $ticket
     * @return mixed
     */
    public function setQueueBind(
        $queue,
        $exchange,
        $routing_key = '',
        $nowait = false,
        $arguments = array(),
        $ticket = null
    )
    {
        return $this->amqp_channel->queue_bind(
            $queue,
            $exchange,
            $routing_key,
            $nowait,
            $arguments,
            $ticket
        );

    }

    /**
     * @desc 接收消息 并消费掉
     */
    public function receiveMessage()
    {
        echo 'Waiting for message. To exit press CTRL+C ' . PHP_EOL;

        $callback = function ($message) {

            echo date('Y-m-d H:i:s') . " Received: ", $message->body, PHP_EOL;

            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        };
        $this->amqp_channel->basic_qos(null, 1, null);

        $this->amqp_channel->basic_consume($this->delay_queue_name, '', false, false, false, false, $callback);

        while (count($this->amqp_channel->callbacks)) {

            $this->amqp_channel->wait();
        }
    }

    /**
     * @desc 关闭连接与交换机连接
     * @return $this
     */
    public function closeConnectionAndChannel()
    {
        $this->amqp_channel->close();
        $this->amqp_connection->close();
        return $this;
    }
}
$config = ['host' => 'localhost', 'port' => 5672, 'username' => 'guest', 'password' => 'guest'];

$amqp_connect = new JReceiveMessage($config);

$amqp_connect->setOutQueue('delay_exchange2', 'delay_queue2')->setDefaultQueue('cache_exchange2')->receiveMessage()->closeConnectionAndChannel();



//$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
//$channel = $connection->channel();
//
//$channel->exchange_declare('delay_exchange2', 'direct', false, false, false);
//$channel->exchange_declare('cache_exchange2', 'direct', false, false, false);
//
//
//$channel->queue_declare('delay_queue2', false, true, false, false, false);
//$channel->queue_bind('delay_queue2', 'delay_exchange2', 'delay_exchange2');
//
//echo ' [*] Waiting for message. To exit press CTRL+C ' . PHP_EOL;
//
//$callback = function ($msg) {
//    echo date('Y-m-d H:i:s') . " [x] Received", $msg->body, PHP_EOL;
//
//    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
//
//};
//
////只有consumer已经处理并确认了上一条message时queue才分派新的message给它
//$channel->basic_qos(null, 1, null);
//$channel->basic_consume('delay_queue2', '', false, false, false, false, $callback);
//
//
//while (count($channel->callbacks)) {
//    $channel->wait();
//}
//$channel->close();
//$connection->close();