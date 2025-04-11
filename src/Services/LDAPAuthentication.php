<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class LDAPAuthentication
{
    private $emr;
    private $security;
    private $defaultValue;
    private $logger;

    public function __construct(LoggerInterface $ldapLogger, EntityManagerInterface $em, DefaultValue $defVal, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->logger = $ldapLogger;
    }

    public function isPasswordValid($clientEmail, $clientPassword)
    {
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');

        try {
            $client = new Client();
            $url = $this->defaultValue->getDefaultValue('LDAP_SERVER_URL');
            $form_params = ['email' => $clientEmail, 'password' => $clientPassword];
            $this->logger->info('LDAP URL '.$url);
            $this->logger->info('LDAP Username '.$clientEmail);
            $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
            $body = $response->getBody();
            $this->logger->info('LDAP Response Received '.$body);
            $status = 'true';
            $message = 'Data found!';
            $data = json_decode($body);
        } catch (Exception $e) {
            $status = 'false';
            $message = $e->getMessage();
            $this->logger->info('LDAP Exception '.$message);
            $data = [];
        }
        if ($data ? $data->data->result : null) {
            return true;
        } else {
            return false;
        }
    }
    public function triggerGIMSOTP($clientEmail, $OTPMessage)
    {
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');

        try {
            $client = new Client();
            // @TODO
            $url = "http://localhost:8021/send?receiverid=".$clientEmail."&msg=". urlencode($OTPMessage)."&confidential=Y'";
            $this->logger->info('SANDES OTP '.$url);
            $response = $client->request('POST', $url);
            $body = $response->getBody();
            $this->logger->info('SANDES OTP Response Received '.$body);
            $status = 'true';
            $message = 'Data found!';
            $data = json_decode($body);
        } catch (Exception $e) {
            $status = 'false';
            $message = $e->getMessage();
            $this->logger->info('LDAP Exception '.$message);
            $data = [];
        }
        return true;
        // @TODO to be handled to return proper message
    }

    public function getGIMSWebSession($clientEmail, $clientPassword)
    {
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');

        try {
            $client = new Client([
                'verify' => false,
            ]);
            $url = $this->defaultValue->getDefaultValue('GIMS-WEB-SESSION-URL');
            $this->logger->info('WEB Session API URL '.$url);

            $form_params = json_encode(['email' => $clientEmail, 'password' => $clientPassword]);
            $this->logger->info('WEB Session API Return data '.$form_params);
            $headers = ['content-type' => 'application/json', 'Accept' => 'application/json'];
            $response = $client->request('POST', $url, ['body' => $form_params, 'headers' => $headers, 'http_errors' => true]);
            $body = $response->getBody();
            $this->logger->info('WEB Session API Return data '.$body);
            $status = 'true';
            $message = 'Data found!';
            $data = json_decode($body);
        } catch (Exception $e) {
            $this->status = 'false';
            $message = $e->getMessage();
            $this->logger->info('WEB Session API Exception '.$message);
            $data = [$message];
        }
        if ($data ? $data : null) {
            return $data;
        } else {
            return false;
        }
    }
    public function notifyForUnreadStatus($clientEmail)
    {
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');

        try {
            $client = new Client([
                'verify' => false,
            ]);
            $url =  'https://10.247.138.153/v1/api/bot/subscribe/unread';
            $gw_clientid = $this->defaultValue->getDefaultValue('GIMS-GW-CLIENTID');
            $gw_clientsecret = $this->defaultValue->getDefaultValue('GIMS-GW-CLIENTSECRET');
            $gw_hmackey = $this->defaultValue->getDefaultValue('GIMS-GW-HMACKEY');
            $encoded_params = '{"email_id":"'.$clientEmail.'"}';
            $hmac = base64_encode(\hash_hmac('sha256', $encoded_params, $gw_hmackey, true));
            $headers = ['clientid' => $gw_clientid, 'clientsecret' => $gw_clientsecret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
            $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
            $body = $response->getBody();
            $status = 'true';
            $message = 'Data found!';
            $data = json_decode($body);
        } catch (Exception $e) {
            $this->status = 'false';
            $this->message = $e->getMessage();
            $data = [$message];
        }
        if ($data ? $data : null) {
            return $data;
        } else {
            return false;
        }
    }
}
