<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class XMPPGeneral
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
    public function getOnlineUsers()
    {
        $this->logger->info('Method online users hit ENV->'.$this->appENV);
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-ONLINEUSERS');
                $response = $client->request('GET', $url);
                $body = $response->getBody();
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
                $OnlineUserCount = $data->data->stat;

                return $OnlineUserCount;
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
                return '*';
            } else {
                return '*';
            }
        } else {
            return false;
        }
    }

    public function getRegisteredUsers()
    {
        $this->logger->info('Method registered users hit ENV->'.$this->appENV);

        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-REGISTEREDUSERS');
                $response = $client->request('GET', $url);
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

    public function getUptime()
    {
        $this->logger->info('Method uptime hit ENV->'.$this->appENV);

        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-UPTIME');
                $response = $client->request('GET', $url);
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

    public function refreshProfile($jabberID)
    {
        $this->logger->info('Method refresh profile hit ENV->'.$this->appENV);

        if ('OFFLINE' !== $this->appENV) {
            // JabberID arun.kv-nic.in@gimkerala.nic.in
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client();
                $url = $this->defaultValue->getDefaultValue('XMPP-REFRESH-PROFILE');
                $this->logger->info('XMPP RP API Call Refresh Profile Initiated ');
                $this->logger->info('XMPP RP API URL '.$url);
                $form_params = ['jid' => $jabberID];
                $this->logger->info('XMPP RP API Values Passed '. json_encode($form_params));
                $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
                $body = $response->getBody();
                $this->logger->info('XMPP RP API Response Received '.$body);
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
   
    public function refreshProfileV5($employeeGUID)
    {
        if ('OFFLINE' !== $this->appENV) {
            // JabberID arun.kv-nic.in@gimkerala.nic.in
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');

            try {
                $client = new Client(['verify' => false]);
                $url = $this->defaultValue->getDefaultValue('XMPP-REFRESH-PROFILE');
                $this->logger->info('XMPP RP API URL '.$url. ' for '.$employeeGUID);
                $params = ['gu_id' => $employeeGUID];
                $response = $client->request('POST', $url, ['body' => json_encode($params)]);
                $body = $response->getBody();
                $this->logger->info('XMPP RP API Response Received '.$body);
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

    public function refreshORGProfile($organizationID)
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $url = $this->defaultValue->getDefaultValue('XMPP-REFRESH-ORG-PROFILE');
                $url = \str_replace('__orgid__', $organizationID, $url);
                $this->logger->info('XMPP ORG RP API URL '.$url. ' for '.$organizationID);
                $response = $client->request('POST', $url);
                $body = $response->getBody();
                $this->logger->info('XMPP RP API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP RP API Exception GENERAL '.$message);
                $data = '{"status":"error","message":"General exception admin should check logs"}';
            }
            return $data;
        } else {
            return false;
        }
    }

    public function verifyLiteUser($userid, $objid, $remarks)
    {
        $this->logger->info('Method Verify Lite Users hit ENV->'.$this->appENV);

        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = '';
            try {
                $client = new Client();
                $URL = $this->defaultValue->getDefaultValue('LITE-EMPLOYEE-VERIFY-V5');
                $headers = ['user_id' => $userid];
                $payload = ['gu_id' => $objid, 'remarks' => $remarks];
                $this->logger->info('LITE V5 employee verification started ');
                $this->logger->info('LITE V5 API URL '.$URL);
                $this->logger->info('LITE V5 API Headers '.json_encode($headers));
                $this->logger->info('LITE V5 API values passed '.json_encode($payload));
                $response = $client->request("POST", $URL, ['body' => json_encode($payload), 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('LITE V5 API response received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = $body;
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('LITE V5 API Exception GENERAL '.$message);
                $data = '{"status":"error","message":"General exception admin should check logs"}';
            }
             return $data;
        } else {
            return false;
        }
    }

    // ---------------------------- LITE Rejection API
    public function rejectLiteUser($userid, $objid, $remarks)
    {

        $this->logger->info('Method reject users hit ENV->'.$this->appENV);

        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = '';
            try {
                $client = new Client();
                $URL = $this->defaultValue->getDefaultValue('LITE-EMPLOYEE-REJECT-V5');
                $headers = ['user_id' => $userid];
                $payload = ['gu_id' => $objid, 'remarks' => $remarks];
                $this->logger->info('LITE V5 employee rejection started ');
                $this->logger->info('LITE V5 API rejection URL '.$URL);
                $this->logger->info('LITE V5 API rejection Headers '.json_encode($headers));
                $this->logger->info('LITE V5 API rejection values passed '.json_encode($payload));
                $response = $client->request("POST", $URL, ['body' => json_encode($payload), 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('LITE V5 API rejection response received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = $body;
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('LITE V5 API rejection Exception GENERAL '.$message);
                $data = '{"status":"error","message":"General exception admin should check logs"}';
            }
             return $data;
        } else {
            return false;
        }
    }

    // ---------------------------- LITE Rejection API
    public function migrateUser($userid, $objid, $vhost)
    {
        $this->logger->info('Method migrate users hit ENV->'.$this->appENV);

        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = '';
            try {
                $client = new Client();
                $URL = $this->defaultValue->getDefaultValue('EMPLOYEE-MIGRATION-V5');
                $headers = ['user_id' => $userid];
                $payload = ['gu_id' => $objid, 'target_vhost' => $vhost];
                $this->logger->info('MIGRATION V5 API hit ');
                $this->logger->info('MIGRATION V5 API service URL '.$URL);
                $this->logger->info('MIGRATION V5 API service headers '.json_encode($headers));
                $this->logger->info('MIGRATION V5 API service values passed '.json_encode($payload));
                $response = $client->request("POST", $URL, ['body' => json_encode($payload), 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('MIGRATION V5 API service response received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = $body;
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('MIGRATION V5 API service exception '.$message);
                $data = '{"status":"error","message":"General exception admin should check logs"}';
            }
             return $data;
        } else {
            return false;
        }
    }

    public function updateCache($entityname,$id)
    {      
        if ('OFFLINE' !== $this->appENV) {  
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $url = $this->defaultValue->getDefaultValue('XMPP-CACHE-INSERT-UPDATE-REFRESH-V5');
                $url = \str_replace('__entityname__', $entityname, $url);
                $url = \str_replace('__id__', $id, $url);

                $this->logger->info('XMPP-CACHE-INSERT-UPDATE-REFRESH-V5 '.$url. ' for '.$entityname. ' ID '.$id);
                $response = $client->request('POST', $url);
                $body = $response->getBody();
                $this->logger->info('XMPP-CACHE-INSERT-UPDATE-REFRESH-V5 API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP-CACHE-INSERT-UPDATE-REFRESH-V5 API Exception GENERAL '.$message);
                $data = '{"status":"error","message":"General exception admin should check logs"}';
            }
            return $data;
        } else {
            return false;
        }
    }

    public function removeCache($entityname,$id)
    {
        if ('OFFLINE' !== $this->appENV) {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $url = $this->defaultValue->getDefaultValue('XMPP-CACHE-DELETE-REFRESH-V5');
                $url = \str_replace('__entityname__', $entityname, $url);
                $url = \str_replace('__id__', $id, $url);

                $this->logger->info('XMPP-CACHE-DELETE-REFRESH-V5 '.$url. ' for '.$entityname. ' ID '.$id);
                $response = $client->request('POST', $url);
                $body = $response->getBody();
                $this->logger->info('XMPP-CACHE-DELETE-REFRESH-V5 API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP-CACHE-DELETE-REFRESH-V5 API Exception GENERAL '.$message);
                $data = '{"status":"error","message":"General exception admin should check logs"}';
            }
            return $data;
        } else {
            return false;
        }
    }
    // ---------------------------- Delete Account API Call
    public function deleteAccount($user_id, $employee_guid, $delete_reason)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('INTERNAL API - DELETE ACCOUNT Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-INTERNAL-DELETE-ACCOUNT');
                $this->logger->info('INTERNAL API - DELETE ACCOUNT URL '.$url);
                $this->logger->info('INTERNAL API - DELETE ACCOUNT Headers '.json_encode($headers));
                $payload = \json_encode(['gu_id' => $employee_guid, 'reason_code' => (int)$delete_reason, 'other_reason' => '']);
                $this->logger->info('INTERNAL API - DELETE ACCOUNT Values Passed '.$payload);
                $response = $client->request("DELETE", $url, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('INTERNAL API - DELETE ACCOUNT Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return ['status' => 'success', 'message' => 'Deletion successful'];
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('INTERNAL API - DELETE ACCOUNT '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
    // ---------------------------- Delete Account API Call
    public function remoteWipeout($user_id, $employee_guid, $wipeout_reason)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('INTERNAL API - REMOTE WIPEOUT Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-INTERNAL-REMOTE-WIPEOUT');
                $this->logger->info('INTERNAL API - REMOTE WIPEOUT URL '.$url);
                $this->logger->info('INTERNAL API - REMOTE WIPEOUT Headers '.json_encode($headers));
                $payload = \json_encode(['gu_id' => $employee_guid, 'reason_code' => (int)$wipeout_reason, 'other_reason' => '']);
                $this->logger->info('INTERNAL API - REMOTE WIPEOUT Values Passed '.$payload);
                $response = $client->request("POST", $url, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('INTERNAL API - REMOTE WIPEOUT Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return ['status' => 'success', 'message' => 'Deletion successful'];
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('INTERNAL API - REMOTE WIPEOUT '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
    // ---------------------------- Delete Account API Call
    public function employeeOffboard($user_id, $employee_guid, $offboard_reason)
    {
        if ('OFFLINE' !== $this->appENV) {
            try {
                $client = new Client();
                $headers = ['user_id' => $user_id, 'Content-Type' => 'application/json'];
                $this->logger->info('OFFBOARD Service Call Initiated ');
                $url = $this->defaultValue->getDefaultValue('API-INTERNAL-EMPLOYEE-OFFBOARD');
                $this->logger->info('OFFBOARD URL '.$url);
                $this->logger->info('OFFBOARD Headers '.json_encode($headers));
                $payload = \json_encode(['gu_id' => $employee_guid, 'reason_code' => (int)$offboard_reason]);
                $this->logger->info('OFFBOARD Values Passed '.$payload);
                $response = $client->request("POST", $url, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('OFFBOARD Response Received '.$body);
                $data = json_decode($body);
                if ($data->status !== "error") {
                    return ['status' => 'success', 'message' => 'Offboarding successful'];
                } else {
                    return ['status' => 'danger', 'message' => 'CODE '. $data->code . ' - '. $data->message];
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->logger->info('OFFBOARD EXCEPTION '.$message);
                return ['status' => 'danger', 'message' =>  "401EX - Unsuccessful ;)"];
            }
        } else {
            return ['status' => 'danger', 'message' =>  'IN OFFLINE MODE'];
        }
    }
}
