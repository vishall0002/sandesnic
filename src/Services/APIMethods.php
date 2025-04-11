<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class APIMethods
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;

    public function __construct(LoggerInterface $generalapiLogger, DefaultValue $defVal, EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $generalapiLogger;
        
    }
    // ---------------------------- Delete Account API Call
    public function sendSMS($user_id, $mobile_no, $sms_message, $template_id)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('SMS APIV5 Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-SMS-URL-V5');
                $this->logger->info('SMS APIV5 URL '.$url);
                $this->logger->info('SMS APIV5 Headers '.json_encode($headers));
                // $payload = '{"mobile_no":"'.$mobile_no.'","message":"'.$sms_message.'","template_id":"'.$template_id.'"}';
                // dump($payload);
                // $this->logger->info('SMS APIV5 Values Passed '.$payload);
                $payload = \json_encode(['mobile_no' => $mobile_no, 'message' => $sms_message, 'template_id' => $template_id]);
                $this->logger->info('SMS APIV5 Values Passed '.$payload);
                $response = $client->request("POST", $url, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('SMS APIV5 Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return $data;
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('SMS APIV5 '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
    // CURL Version for testing
    // curl --location --request POST 'http://10.247.252.57:8081/v5/api/sms/send' --header 'Content-Type: application/json' --header 'user_id: 65189' --data-raw '{"mobile_no":"919562735438","message":"You are officially onboarded to Sandes, the Government instant messaging system. You may install Sandes app from https://www.sandes.gov.in/get and register using your mobile number +919562735438.Sandes-NICSI","template_id":"1107162383170334979"}'
    


}
