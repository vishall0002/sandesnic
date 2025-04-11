<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class FMTApi
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;

    public function __construct(LoggerInterface $fmtLogger, DefaultValue $defVal, EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $fmtLogger;
        
    }
    public function traceOriginator($user_id, $traceID)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('TRACE ORIGINATOR Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-INTERNAL-TRACE-ORIGINATOR');
                $this->logger->info('TRACE ORIGINATOR URL '.$url);
                $url = \str_replace('__traceid__', $traceID, $url);
                $this->logger->info('TRACE ORIGINATOR Headers '.json_encode($headers));
                $this->logger->info('TRACE ORIGINATOR Values Passed '.$traceID);
                $response = $client->request("GET", $url, ['body' => '', 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('TRACE ORIGINATOR Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return $data;
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('TRACE ORIGINATOR '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
    public function traceRecipient($user_id, $message_report_gu_id)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('TRACE RECIPIENT Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-INTERNAL-TRACE-RECIPIENT');
                $this->logger->info('TRACE RECIPIENT URL '.$url);
                $url = \str_replace('__objid__', $message_report_gu_id, $url);
                $this->logger->info('TRACE RECIPIENT Headers '.json_encode($headers));
                $this->logger->info('TRACE RECIPIENT Values Passed '.$message_report_gu_id);
                $response = $client->request("POST", $url, ['body' => '', 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('TRACE RECIPIENT Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return $data;
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('TRACE RECIPIENT '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
    public function retract($user_id, $recipient_trace_request_guid)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('RETRACT MESSAGE Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-INTERNAL-RETRACT-MESSAGE');
                $this->logger->info('RETRACT MESSAGE URL '.$url);
                $url = \str_replace('__objid__', $recipient_trace_request_guid, $url);
                $this->logger->info('RETRACT MESSAGE Headers '.json_encode($headers));
                $this->logger->info('RETRACT MESSAGE Values Passed '.$recipient_trace_request_guid);
                $response = $client->request("POST", $url, ['body' => '', 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('RETRACT MESSAGE Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return $data;
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('RETRACT MESSAGE '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
}
