<?php 

namespace AsiAsiapac\InternalLogger;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Adrianorosa\GeoLocation\GeoLocation;
use Jenssegers\Agent\Agent;

class UserActivity{
    private $connection, 
                $channel = null;
     
    public static function save( $username, $remark, $note = "" ){

        $ip         = $_SERVER['REMOTE_ADDR'];
        
        $agent      = new Agent();
        $details    = GeoLocation::lookup($ip);

        $browser    = $agent->browser();
        $browserVersion = $agent->version($browser);

        $platform = $agent->platform();
        $platformVersion = $agent->version($platform);

        if (!empty($browser)){
            $agent_name = $browser.' '.$browserVersion;
        }elseif ($agent->isRobot()){
            $agent_name = $agent->robot();
        }elseif ($agent->isPhone()){
            $agent_name = $agent->device();
        }else{
            $agent_name = 'Unidentified User Agent';
        }

        $MQ_config = [
            'HOST' => (!empty($_ENV['RABBITMQ_HOST'])) ? $_ENV['RABBITMQ_HOST'] : $_ENV['MQ_HOST'],
            'PORT' => (!empty($_ENV['RABBITMQ_PORT'])) ? $_ENV['RABBITMQ_PORT'] : $_ENV['MQ_PORT'],
            'USERNAME' => (!empty($_ENV['RABBITMQ_USERNAME'])) ? $_ENV['RABBITMQ_USERNAME'] : $_ENV['MQ_USER'],
            'PASSWORD' => (!empty($_ENV['RABBITMQ_PASSWORD'])) ? $_ENV['RABBITMQ_PASSWORD'] : $_ENV['MQ_PASS'],
            'VHOST' => (!empty($_ENV['RABBITMQ_VHOST'])) ? $_ENV['RABBITMQ_VHOST'] : $_ENV['MQ_VHOST'],
        ];

        $connectMQ = new AMQPStreamConnection(
            $MQ_config['HOST']  , 
            $MQ_config['PORT'] , 
            $MQ_config['USERNAME'], 
            $MQ_config['PASSWORD'],
            $MQ_config['VHOST'] 
        ); 

        $channelMQ = $connectMQ->channel(); 
        $queue_name = 'log_user_activity'; 
        $message = [
            "task" => 'save-user-activity',
            'collection' => [
                "host"              => $_SERVER['HTTP_HOST'],
                "remark"            => $remark,
                "note"              => $note,
                "username"          => $username,
                "endpoint_rules"    => $_SERVER['REQUEST_URI'],
                "projectcode"       => (!empty($_ENV['PROJECT_CODE'])) ? $_ENV['PROJECT_CODE'] : 'not yet defined',
                "loggedtime"        => date("Y-m-d\TH:i:s"),
                'ip'                => $details->getIp(),
                'city'              => $details->getCity(),
                'region'            => $details->getRegion(),
                'country'           => $details->getCountry(),
                'loc'               => $details->getLatitude().','.$details->getLongitude(),

                'user_agent'        => $agent_name,
                'browser'           => $browser,
                'browserVersion'    => $browserVersion,
                'platform'          => $platform,
                'platform_version'  => $platformVersion,
                'is_mobile'         => ($agent->isPhone()) ? '1' : '0',
                'is_robot'          => ($agent->isRobot()) ? '1' : '0',
                'is_browser'        => ($agent->isDesktop()) ? '1' : '0',
            ]
        ];

        $message_json = json_encode( $message );
        $channelMQ->queue_declare($queue_name, true, false, false, false);

        $msg = new AMQPMessage($message_json);

        $channelMQ->basic_publish($msg, '', $queue_name);
        $channelMQ->close();

        $connectMQ->close();

        return true;
    }  
}