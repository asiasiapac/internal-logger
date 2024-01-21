<?php 

namespace AsiAsiapac\InternalLogger;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class SysLogger{
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
            "remark"    => $remark,
            "level"             => $level,
            "endpoint_rules"    => $task,
            "note"      => $note,
            "endpoint_type"     => $_ENV['PROJECT_ENDPOINT_TYPE'],
            "source"    => $_ENV['PROJECT_CODE'] ,
            "time"      => date("Y-m-d\TH:i:s"),
            "uuid"      => SysLogger::uuid_v4()
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
    
    public static function uuid_v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    
          // 32 bits for "time_low"
          mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    
          // 16 bits for "time_mid"
          mt_rand(0, 0xffff),
    
          // 16 bits for "time_hi_and_version",
          // four most significant bits holds version number 4
          mt_rand(0, 0x0fff) | 0x4000,
    
          // 16 bits, 8 bits for "clk_seq_hi_res",
          // 8 bits for "clk_seq_low",
          // two most significant bits holds zero and one for variant DCE1.1
          mt_rand(0, 0x3fff) | 0x8000,
    
          // 48 bits for "node"
          mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
      }

}