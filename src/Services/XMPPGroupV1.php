<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class XMPPGroupV1
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;

    public function __construct(LoggerInterface $groupapi1Logger, DefaultValue $defVal, EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $groupapi1Logger;
    }

    public function createDefaultGroup($roomName, $title, $description)
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-CREATION');
                $form_params = ['roomname' => $roomName, 'title' => $title, 'description' => $description];
                $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
                $body = $response->getBody();
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (ClientException $ce) {
                $status = 'false';
                $message = $ce->getMessage();
                $data = [];
            } catch (RequestException $re) {
                $status = 'false';
                $message = $re->getMessage();
                $data = [];
            } catch (Exception $e) {
                $this->status = 'false';
                $this->message = $e->getMessage();
                $data = [];
            }

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-MEMBER');
                $form_params = ['room' => $roomName, 'username' => 'lbot-nic.in'];
                $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
                $body = $response->getBody();
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (ClientException $ce) {
                $status = 'false';
                $message = $ce->getMessage();
                $data = [];
            } catch (RequestException $re) {
                $status = 'false';
                $message = $re->getMessage();
                $data = [];
            } catch (Exception $e) {
                $this->status = 'false';
                $this->message = $e->getMessage();
                $data = [];
            }

            if ($data and 'error' === $data->status) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function addMemberToGroup($room, $username)
    {
        if ('OFFLINE' !== $this->appENV) {
            // JabberName arun.kv-nic.in
            // Username === Jabbername
            // Group
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-MEMBER');
                $form_params = ['room' => $room, 'username' => $username];
                $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
                $body = $response->getBody();
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (ClientException $ce) {
                $status = 'false';
                $message = $ce->getMessage();
                $data = [];
            } catch (RequestException $re) {
                $status = 'false';
                $message = $re->getMessage();
                $data = [];
            } catch (Exception $e) {
                $this->status = 'false';
                $this->message = $e->getMessage();
                $data = [];
            }
            if ($data and 'error' === $data->status) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function removeMemberFromGroup($room, $username)
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-MEMBER');
                $form_params = ['room' => $room, 'username' => $username];
                $response = $client->request('DELETE', $url, ['body' => json_encode($form_params)]);
                $body = $response->getBody();
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (ClientException $ce) {
                $status = 'false';
                $message = $ce->getMessage();
                $data = [];
            } catch (RequestException $re) {
                $status = 'false';
                $message = $re->getMessage();
                $data = [];
            } catch (Exception $e) {
                $this->status = 'false';
                $this->message = $e->getMessage();
                $data = [];
            }
            if ($data and 'error' === $data->status) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function createGroup($roomName, $title, $description)
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-CREATION');
                $form_params = ['roomname' => $roomName, 'title' => $title, 'description' => $description];
                $paramJson = json_encode($form_params);
                $response = $client->request('POST', $url, ['body' => $paramJson]);
                $this->logger->info('XMPP V1GC  Group Creation Started ');
                $this->logger->info('XMPP V1GC API URL '.$url);
                $this->logger->info('XMPP V1GC API Values Passed '.$paramJson);
                $body = $response->getBody();
                $this->logger->info('XMPP V1GC API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (ClientException $ce) {
                $status = 'false';
                $message = $ce->getMessage();
                $data = [];
            } catch (RequestException $re) {
                $status = 'false';
                $message = $re->getMessage();
                $data = [];
            } catch (Exception $e) {
                $this->status = 'false';
                $this->message = $e->getMessage();
                $data = [];
            }
            // Majority of the team members supported this..
            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-GROUP-MEMBER');
                $form_params = ['room' => $roomName, 'username' => 'lbot-nic.in'];
                $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
                $body = $response->getBody();
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (ClientException $ce) {
                $status = 'false';
                $message = $ce->getMessage();
                $data = [];
            } catch (RequestException $re) {
                $status = 'false';
                $message = $re->getMessage();
                $data = [];
            } catch (Exception $e) {
                $this->status = 'false';
                $this->message = $e->getMessage();
                $data = [];
            }
        } else {
            return false;
        }
    }
}
