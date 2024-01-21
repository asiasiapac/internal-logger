<?php 

namespace AsiAsiapac\InternalLogger;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class LoggerConnection{
    // (new RabbitMQHelper)->publish('impact_email_invitation', 'test coba belajar');
	public const INFO = "INFO";
	public const WARNING = "WARNING";
	public const ERROR = "ERROR";
	public const TRACE = "TRACE";
	public const DEBUG = "DEBUG";

    private $connection, 
                $channel = null;
	 
	public static function save( $level, $task, $remark, $note = "" ){
         
        $connectMQ = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST']  , 
            $_ENV['RABBITMQ_PORT'] , 
            $_ENV['RABBITMQ_USERNAME'], 
            $_ENV['RABBITMQ_PASSWORD'],
            $_ENV['RABBITMQ_VHOST'] 
        ); 
        $channelMQ = $connectMQ->channel(); 
         
        $queue_name = 'logger-sys'; 
        $message = [
            "time"      => date("Y-m-d\TH:i:s"),
            "remark"    => $remark,
            "level"             => $level,
            "endpoint_rules"    => $task,
            "endpoint_type"     => $_ENV['PROJECT_ENDPOINT_TYPE'],
            "source"    => $_ENV['PROJECT_CODE'] ,
            "uuid"      => md5(mt_rand(0,1000).time()),
            "note"      => $note
        ];
        $message_json = json_encode( $message );
        $channelMQ->queue_declare($queue_name, true, false, false, false);

        $msg = new AMQPMessage($message_json);

        $channelMQ->basic_publish($msg, '', $queue_name);
        $channelMQ->close();

        $connectMQ->close();
        return true;
    }  

    public static function AmqpConnect(){
        return new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST']  , 
            $_ENV['RABBITMQ_PORT'] , 
            $_ENV['RABBITMQ_USERNAME'], 
            $_ENV['RABBITMQ_PASSWORD'],
            $_ENV['RABBITMQ_VHOST'] 
        ); 
        
    }
         
}