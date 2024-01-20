<?php 

namespace InternalLogger;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class LoggerConnection{
    // (new RabbitMQHelper)->publish('impact_email_invitation', 'test coba belajar');
	public const INFO = "INFO";
	public const WARNING = "WARNING";
	public const ERROR = "ERROR";
	public const TRACE = "TRACE";
	public const DEBUG = "DEBUG";

    private $connection, $channel = null;
	 
	public static function save( $level, $task, $remark, $note = "" ){
        $connectMQ = $this->AmqpConnect( );

        $queue_name = 'logger-sys'; 
        $message = [
            "time"      =>date("Y-m-d\TH:i:s"),
            "remark"    =>$remark,
            "level"     =>$level,
            "endpoint_rules" => $task,
            "endpoint_type" => 'AMQP',
            "source" => 'WEBLMS',
            "uuid" => md5(mt_rand(0,1000).time()),
            "note" => $note
        ];
        $message_json = json_encode( $message );
        $connectMQ->channel->queue_declare($queue_name, true, false, false, false);

        $msg = new AMQPMessage($message_json);

        $connectMQ->channel->basic_publish($msg, '', $queue_name);
        $connectMQ->channel->close();
        $connectMQ->connection->close();
        return true;
    }  

    public function AmqpConnect(){
        $this->connection =  new AMQPStreamConnection(
            env('RABBITMQ_HOST')  , 
            env('RABBITMQ_PORT') , 
            env('RABBITMQ_USERNAME'), 
            env('RABBITMQ_PASSWORD'),
            env('RABBITMQ_VHOST') 
        ); 
        $this->channel = $this->connection->channel( );
    }
         
}