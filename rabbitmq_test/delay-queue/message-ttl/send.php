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
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * @desc 针对消息message 延迟发送
 * Class JdelayMessage
 */
class JdelaySendMessage
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
     * 队列延迟时间 | 毫秒时间
     * @var bool
     */
    private static $ttl_time = 15000;

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
     * @param string $ttl_time
     */
    public function createOutQueue($exchange_name = '', $queue_name = '', $ttl_time = '')
    {
        $this->delay_exchange_name = $exchange_name;
        $this->delay_queue_name = $queue_name;
        self::$ttl_time = $ttl_time ? $ttl_time : 15000;
        try {
            $this->setExchangeDeclare($this->delay_exchange_name, self::$type, false, false, false);
            $this->getQueueDeclare($this->delay_queue_name, false, true, false, false, false);
            $this->setQueueBind($this->delay_queue_name, $this->delay_exchange_name, $this->delay_exchange_name);
        } catch (\Exception $e) {
            // 返回异常报错地方
            return 'error info:' . $e->getMessage();
        }
    }

    /**
     * @desc 创建队列与交换机
     * @param string $exchange_name
     * @param string $queue_name
     * @return string
     */
    public function createDefaultQueue($exchange_name = '', $queue_name = '')
    {
        $this->exchange_name = $exchange_name;
        $this->queue_name = $queue_name;
        try {
            $tale = new AMQPTable();
            $tale->set('x-dead-letter-exchange', $this->delay_exchange_name); // 表示过期后由哪个exchange处理
            $tale->set('x-dead-letter-routing-key', $this->delay_exchange_name);  //死信路由key
            $tale->set('x-message-ttl', self::$ttl_time); //存活时长   下面的过期时间不能超过
            $this->setExchangeDeclare($this->exchange_name, self::$type, false, false, false);
            $this->getQueueDeclare($this->queue_name, false, true, false, false, false, $tale);
            $this->setQueueBind($this->queue_name, $this->exchange_name, $this->exchange_name);
        } catch (\Exception $e) {
            return 'Info:' . $e->getMessage();
        }
    }

    /**
     * @desc 设置延迟交换机值
     * @return AMQPTable
     */
    private function setDelayData()
    {
        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->delay_exchange_name); // 表示过期后由哪个exchange处理
        $tale->set('x-dead-letter-routing-key', $this->delay_exchange_name);
        $tale->set('x-message-ttl', self::$ttl_time);
        return $tale;
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
     * @desc 发送消息
     * @param string $message
     */
    public function sendMessage($message = '')
    {
        for ($i = 1; $i <= 10; $i++) {
            $messages = new AMQPMessage($message, [
                'expiration' => intval(self::$ttl_time),
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);
            $this->amqp_channel->basic_publish($messages, $this->exchange_name, $this->exchange_name);
            sleep(2);
            echo date('Y-m-d H:i:s') . " Sent " . $message . PHP_EOL;
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
    }
}

$config = ['host' => 'localhost', 'port' => 5672, 'username' => 'guest', 'password' => 'guest'];

$amqp_connect = new JdelaySendMessage($config);

$amqp_connect->createOutQueue('delay_exchange2', 'delay_queue2');

$amqp_connect->createDefaultQueue('cache_exchange2', 'cache_queue2');

$amqp_connect->sendMessage('hello world 蜗牛巢 致力于分享原创高质量文章');

$amqp_connect->closeConnectionAndChannel();