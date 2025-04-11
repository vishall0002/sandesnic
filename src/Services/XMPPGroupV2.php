<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class XMPPGroupV2
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;

    public function __construct(LoggerInterface $groupapi2Logger, DefaultValue $defVal, EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $groupapi2Logger;
    }

    // ---------------------------- Group Management V2 API
    public function groupV2ServiceCall($URL, $employeeID, $payload, $requestType = 'POST')
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client();
                $headers = ['admin_emp_id' => $employeeID, 'Content-Type' => 'application/json'];
                $this->logger->info('XMPP V2 Group Management Service Call Initiated ');
                $this->logger->info('XMPP V2 API URL '.$URL);
                $this->logger->info('XMPP V2 API Headers '.json_encode($headers));
                $this->logger->info('XMPP V2 API Values Passed '.$payload);
                $response = $client->request($requestType, $URL, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('XMPP V2 API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP Exception GENERAL '.$message);
                $data = [];
            }
        } else {
            return false;
        }
    }

    public function createGroupV2($employeeID, $payload)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-CREATION-V2');

        return $this->groupV2ServiceCall($url, $employeeID, $payload);
    }

    public function subscribeMemberV2($employeeID, $payload, $group_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-SUBSCRIBE-V2');
        $url = \str_replace('__groupname__', $group_name, $url);

        return $this->groupV2ServiceCall($url, $employeeID, $payload);
    }

    public function subscribeMemberByEmailV2($employeeID, $payload, $group_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-SUBSCRIBE-BYEMAIL-V2');
        $url = \str_replace('__groupname__', $group_name, $url);

        return $this->groupV2ServiceCall($url, $employeeID, $payload);
    }

    public function unSubscribeMemberV2($employeeID, $group_name, $member_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-UNSUBSCRIBE-V2');
        $url = \str_replace('__groupname__', $group_name, $url);
        $url = \str_replace('__membername__', $member_name, $url);

        return $this->groupV2ServiceCall($url, $employeeID, '', 'DELETE');
    }

    public function updateGroupV2($employeeID, $payload, $group_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-UPDATE-V2');
        $url = \str_replace('__groupname__', $group_name, $url);

        return $this->groupV2ServiceCall($url, $employeeID, $payload, 'PUT');
    }

    public function disperseGroupV2($employeeID, $group_name)
    {
        $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-DISPERSE-V2');
        $url = \str_replace('__groupname__', $group_name, $url);

        return $this->groupV2ServiceCall($url, $employeeID, '', 'DELETE');
    }


}
