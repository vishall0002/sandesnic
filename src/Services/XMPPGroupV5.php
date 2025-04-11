<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class XMPPGroupV5
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;

    public function __construct(LoggerInterface $groupapi5Logger, DefaultValue $defVal, EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $groupapi5Logger;
    }

    // ---------------------------- Group Management V5 API
    public function groupV5ServiceCall($URL, $userID, $payload, $requestType = 'POST', $in_ou_id = 0)
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client();
                $headers = ['ou_id' => $in_ou_id, 'user_id' => $userID, 'Content-Type' => 'application/json'];
                $this->logger->info('XMPP V5 Group Management Service Call Initiated ');
                $this->logger->info('XMPP V5 API URL '.$URL);
                $this->logger->info('XMPP V5 API Headers '.json_encode($headers));
                $this->logger->info('XMPP V5 API Values Passed '.$payload);
                $response = $client->request($requestType, $URL, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('XMPP V5 API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
                $return_status = $data->status ?? 'danger';
                if ($data->status === "success"){
                    $return_message = $data->message ?? 'Received success from service interface, Update shall be available soon in Sandes App';
                } else {
                    $return_message = $data->message ?? 'Unexpected response from service interface, Please check the status manually';
                }
                $this->logger->info('<-------- PAGE Returns -------'.$return_status . '-->>--'. $return_message);
                return json_encode(['status' => $return_status, 'message' => $return_message]);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP V5 API Exception GENERAL '.$message);
                $data = [];
                return json_encode(['status' => "danger", 'message' => "Unexpected error from service interface"]);
            }
        } else {
            return false;
        }
    }


    public function createGroupV5($userID, $payload,$ou_id)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-CREATION-V5');

        return $this->groupV5ServiceCall($url, $userID, $payload, 'POST', $ou_id);
    }

    public function subscribeMemberV5($userID, $payload, $group_name, $vhost_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-SUBSCRIBE-V5');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__vhostname__', $vhost_name, $url);

        return $this->groupV5ServiceCall($url, $userID, $payload);
    }

    public function subscribeMemberByEmailV5($userID, $payload, $group_name, $vhost_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-SUBSCRIBE-BYEMAIL-V5');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__vhostname__', $vhost_name, $url);
        
        return $this->groupV5ServiceCall($url, $userID, $payload);
    }

    public function subscribeMemberByMobileV5($userID, $payload, $group_name, $vhost_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-SUBSCRIBE-BYMOBILE-V5');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__vhostname__', $vhost_name, $url);
        
        return $this->groupV5ServiceCall($url, $userID, $payload);
    }
    
    public function unSubscribeMemberV5($userID, $member_name, $group_name, $vhost_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-UNSUBSCRIBE-V5');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__vhostname__', $vhost_name, $url);
        $url = \str_replace('__membername__', $member_name, $url);
        
        return $this->groupV5ServiceCall($url, $userID, '', 'DELETE');
    }
    
    public function updateGroupV5($userID, $payload, $group_name, $vhost_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-UPDATE-V5');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__vhostname__', $vhost_name, $url);
        
        return $this->groupV5ServiceCall($url, $userID, $payload, 'PUT');
    }
    
    public function disperseGroupV5($userID, $group_name, $vhost_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-DISPERSE-V5');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__vhostname__', $vhost_name, $url);

        return $this->groupV5ServiceCall($url, $userID, '', 'DELETE');
    }

}
