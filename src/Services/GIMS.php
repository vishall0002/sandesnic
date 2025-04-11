<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use App\Entity\Portal\ExternalApps;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class GIMS
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;
    private $gw_clientid;
    private $gw_clientsecret;
    private $gw_hmackey;

    public function __construct(LoggerInterface $broadcastLogger, DefaultValue $defVal, EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $broadcastLogger;
        $this->gw_clientid = $this->defaultValue->getDefaultValue('GIMS-GW-CLIENTID');
        $this->gw_clientsecret = $this->defaultValue->getDefaultValue('GIMS-GW-CLIENTSECRET');
        $this->gw_hmackey = $this->defaultValue->getDefaultValue('GIMS-GW-HMACKEY');
    }
    public function getFileSlotThenUpload($file_name_with_path){
        // if ('OFFLINE' !== $this->appENV) {
            
            // curl -X GET \
            //   'http://dwar1.gims.gov.in/v1/api/upload/slot?name=myphoto.jpg&amp;content_type=image/jpeg&amp;size=1024&#39; \
            //   -H 'clientid: 90fbd095-633f-4e12-aa61-017529ea467d' \
            //   -H 'clientsecret: yoNbOCv4Fl7Ux3F4dV4Khh22YSk7CL9BAVmYhjcwUjRSmgWRCbktJnvrFgxS27dC'
            //  {"notice": "Unauthorized use of this API is illegal & punishable under IT Act & International laws","status": "success",
            // "data": {
            //  "upload_url": "https://upload.gim.gov.in/v1/api/upload/ad50d47fb6cff0f6f3cec44cb8d657329d124dec313af6b3a7d81a920f5f6163/myphoto.jpg",
            //  "download_url": "https://upload.gim.gov.in/v1/api/download/ad50d47fb6cff0f6f3cec44cb8d657329d124dec313af6b3a7d81a920f5f6163/myphoto.jpg"}
           // }
            $this->logger->info('File slot init');
            $download_url = null;
            $status = 'false';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $this->logger->info('File slot CLIENTID-'.$this->gw_clientid);
                $this->logger->info('File slot CLIENTSECRET-'.$this->gw_clientsecret);
                // $url = $this->defaultValue->getDefaultValue('FILE-SLOT-V1-URL');
                $url = "http://dwar1.gims.gov.in/v1/api/upload/slot";
                $this->logger->info('File slot URL - '. $url);
                $file_name = basename($file_name_with_path);
                $file_type = mime_content_type($file_name_with_path);
                $file_size = filesize($file_name_with_path);
                $encoded_params = '{"name":"'.$file_name.'","content_type":"'.$file_type.'","size":"'.$file_size.'"}';
                $this->logger->info('File slot params - '. $encoded_params);
                $hmac = base64_encode(\hash_hmac('sha256', $encoded_params, $this->gw_hmackey, true));
                $headers = ['clientid' => $this->gw_clientid, 'clientsecret' => $this->gw_clientsecret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
                $this->logger->info('File slot params  ' . $hmac);
                $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('File slot response ' . $body);
                // Performing upload of the file
                $return_object = \json_decode($body);
                $upload_url = $return_object->data->upload_url;
                $upload_content = fopen($file_name_with_path, 'r');
                $download_url = $return_object->data->download_url;
                $putclient = new Client(['verify' => false]);
                $response = $putclient->request('PUT', $upload_url, ['body' => $upload_content]);
                return $download_url;
            } catch (Exception $e) {
                $this->logger->info('File slot response ' . $e->getMessage());
            }
            return false;
    }
    public function sendMulticast($sender_app, $message, $receivers, $file_name_with_path = null)
    {
        if ('OFFLINE' !== $this->appENV) {
            $external_app = $this->emr->getRepository(ExternalApps::Class)->findOneById($sender_app);
            if (!$external_app){
                $external_app = $this->emr->getRepository(ExternalApps::Class)->findOneByAppName('gim-portal');
            }
            $this->logger->info('MULTICAST SENDER ID - '.$external_app->getAppTitle());
            $sender_clientid = $external_app->getClientId();
            
            if ($file_name_with_path){
                $file_content_url = $this->getFileSlotThenUpload($file_name_with_path);
                $file_content_type = mime_content_type($file_name_with_path);
            }
            $message = str_replace('"','',$message);
            $status = 'false';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $currentTime = new \DateTimeImmutable('now');
                $createTime = $currentTime->format('U');
                $expiryTime = $currentTime->add(new \DateInterval('P10D'))->format('U');
                $this->logger->info('MULTICAST CLIENTID-'.$sender_clientid);
                $this->logger->info('MULTICAST CLIENTSECRET-'.$this->gw_clientsecret);
                $this->logger->info('MULTICAST GIMS-GW-HMACKEY-'.$this->gw_hmackey);
                $url = $this->defaultValue->getDefaultValue('GIMS-GW-URL-MULTICAST');
                $this->logger->info('MULTICAST GW URL'.$url);
                if ($file_content_url){
                    $encoded_params = '{"message":"'.$message.'","type":"chat","title":"GIMSIMTest", "file_url":"'.$file_content_url.'", "file_content_type":"'.$file_content_type.'", "category":"info","created_on":'.$createTime.',"expire_on":'.$expiryTime.',"receivers":['.$receivers.']}';
                } else {
                    $encoded_params = '{"message":"'.$message.'","type":"chat","title":"GIMSIMTest","category":"info","created_on":'.$createTime.',"expire_on":'.$expiryTime.',"receivers":['.$receivers.']}';
                }
                $this->logger->info('MULTICAST PARAMS '.$encoded_params);
                $hmac = base64_encode(\hash_hmac('sha256', $encoded_params, 'bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3', true));
                $this->logger->info('MULTICAST HMAC-KEY ' . $hmac);
                $headers = ['pim' => 'Y', 'clientid' => $sender_clientid, 'clientsecret' => $this->gw_clientsecret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
                $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('MULTICAST RESPONSE ' . $body);
                $response = json_decode($body);
                $status = $response->status;
                if($status == "success"){
                    $message = 'Message scheduled for delivery';
                } else {
                    $message = $response->message;
                }
            } catch (Exception $e) {
                $status = 'danger';
                $message = 'General Exception->'.$e->getMessage();
            }
            $data = ['status' => $status, 'message' => $message];
        } else {
            $data = ['status' => 'danger', 'message' => 'You are in offline mode, no messages sent'];
        }
        $this->logger->info('MULTICAST RETURN MESSAGE '.\json_encode($data));
        return $data;
    }
    public function sendOrganizationBroadCast($sender_app, $message, $organizationGuId, $file_name_with_path = null)
    {
        if ('OFFLINE' !== $this->appENV) {
            $external_app = $this->emr->getRepository(ExternalApps::Class)->findOneById($sender_app);
            if (!$external_app){
                $external_app = $this->emr->getRepository(ExternalApps::Class)->findOneByAppName('gim-portal');
            }
            $this->logger->info('MULTICAST SENDER ID - '.$external_app->getAppTitle());
            $sender_clientid = $external_app->getClientId();
            $file_content_url = null;

            if ($file_name_with_path){
                $file_content_url = $this->getFileSlotThenUpload($file_name_with_path);
                $file_content_type = mime_content_type($file_name_with_path);
            }
            $message = str_replace('"','',$message);
            $status = 'false';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $currentTime = new \DateTimeImmutable('now');
                $createTime = $currentTime->format('U');
                $expiryTime = $currentTime->add(new \DateInterval('P10D'))->format('U');
                $this->logger->info('BROADCAST-ORG CLIENTID-'.$sender_clientid);
                $this->logger->info('BROADCAST-ORG CLIENTSECRET-'.$this->gw_clientsecret);
                $this->logger->info('BROADCAST-ORG GIMS-GW-HMACKEY-'.$this->gw_hmackey);
                $url = $this->defaultValue->getDefaultValue('GIMS-GW-URL-ORGANIZATION-BROADCAST');
                if ($file_content_url){
                    $encoded_params = '{"org_id":"'.$organizationGuId.'", "message":{"message":"'.$message.'","type":"chat","title":"General Broadcast Message", "file_url":"'.$file_content_url.'", "file_content_type":"'.$file_content_type.'","category":"info","created_on":'.$createTime.',"expire_on":'.$expiryTime.'}}';
                } else {
                    $encoded_params = '{"org_id":"'.$organizationGuId.'", "message":{"message":"'.$message.'","type":"chat","title":"General Broadcast Message","category":"info","created_on":'.$createTime.',"expire_on":'.$expiryTime.'}}';
                }
                $this->logger->info('BROADCAST-ORG PARAMS '.$encoded_params);
                $hmac = base64_encode(\hash_hmac('sha256', $encoded_params, $this->gw_hmackey, true));
                $this->logger->info('BROADCAST-ORG HMAC-KEY' . $hmac);
                $headers = ['pim' => 'Y', 'clientid' => $sender_clientid, 'clientsecret' => $this->gw_clientsecret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
                $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
                $body = $response->getBody();
                $this->logger->info('BROADCAST-ORG RESPONSE ' . $body);
                $response = json_decode($body);
                $status = $response->status;
                if($status == "success"){
                    $message = 'Message scheduled for delivery';
                } else {
                    $message = $response->message;
                }
            } catch (Exception $e) {
                $status = 'danger';
                $message = 'General Exception->'.$e->getMessage();
            }
            $data = ['status' => $status, 'message' => $message];
        } else {
            $data = ['status' => 'danger', 'message' => 'You are in offline mode, no messages sent'];
        }
        $this->logger->info('BROADCAST-ORG RETURN MESSAGE '.\json_encode($data));
        return $data;
    }
    public function sendGroupBroadCast($sender_app, $message, $groupGuId, $file_name_with_path = null)
    {
        if ('OFFLINE' !== $this->appENV) {
            $external_app = $this->emr->getRepository(ExternalApps::Class)->findOneById($sender_app);
            if (!$external_app){
                $external_app = $this->emr->getRepository(ExternalApps::Class)->findOneByAppName('gim-portal');
            }
            $this->logger->info('MULTICAST SENDER ID - '.$external_app->getAppTitle());
            $sender_clientid = $external_app->getClientId();

            if ($file_name_with_path){
                $file_content_url = $this->getFileSlotThenUpload($file_name_with_path);
                $file_content_type = mime_content_type($file_name_with_path);
            }
            $message = str_replace('"','',$message);
            $status = 'false';
            $data = json_decode('init');
            try {
                $client = new Client(['verify' => false]);
                $currentTime = new \DateTimeImmutable('now');
                $createTime = $currentTime->format('U');
                $expiryTime = $currentTime->add(new \DateInterval('P10D'))->format('U');
                $this->logger->info('BROADCAST-GROUP CLIENTID-'.$sender_clientid);
                $this->logger->info('BROADCAST-GROUP CLIENTSECRET-'.$this->gw_clientsecret);
                $this->logger->info('BROADCAST-GROUP GIMS-GW-HMACKEY-'.$this->gw_hmackey);
                $url = $this->defaultValue->getDefaultValue('GIMS-GW-URL-GROUP-BROADCAST');
                if ($file_content_url){
                    $encoded_params = '{"list_id":"'.$groupGuId.'", "message":{"message":"'.$message.'","type":"chat","title":"General Broadcast Message", "file_url":"'.$file_content_url.'", "file_content_type":"'.$file_content_type.'","category":"info","created_on":'.$createTime.',"expire_on":'.$expiryTime.'}}';
                } else {
                    $encoded_params = '{"list_id":"'.$groupGuId.'", "message":{"message":"'.$message.'","type":"chat","title":"General Broadcast Message","category":"info","created_on":'.$createTime.',"expire_on":'.$expiryTime.'}}';
                }
                $this->logger->info('BROADCAST-GROUP PARAMS '.$encoded_params);
                $hmac = base64_encode(\hash_hmac('sha256', $encoded_params, $this->gw_hmackey, true));
                $this->logger->info('BROADCAST-GROUP HMAC-KEY' . $hmac);
                $headers = ['pim' => 'Y', 'clientid' => $sender_clientid, 'clientsecret' => $this->gw_clientsecret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
                $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
                $body = $response->getBody();
                $this->logger->info('BROADCAST-GROUP RESPONSE ' . $body);
                $response = json_decode($body);
                $status = $response->status;
                if($status == "success"){
                    $message = 'Message scheduled for delivery';
                } else {
                    $message = $response->message;
                }
            } catch (Exception $e) {
                $status = 'danger';
                $message = 'General Exception->'.$e->getMessage();
            }
            $data = ['status' => $status, 'message' => $message];
        } else {
            $data = ['status' => 'danger', 'message' => 'You are in offline mode, no messages sent'];
        }
        $this->logger->info('BROADCAST-GROUP RETURN MESSAGE '.\json_encode($data));
        return $data;
    }
}
