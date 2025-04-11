<?php

namespace App\Controller;

use App\Services\DefaultValue;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class UMURSController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    private $userSession;

    public function __construct(DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, SessionInterface $userSession)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->userSession = $userSession;
    }

    /**
     * @Route("/umurs/pidicho", name="user_message_unread_status_pidicho")
     */
    public function umursPidicho(Request $request, LoggerInterface $generalapiLogger)
    {
        $host = $_ENV['REDIS_HOST'];
        $port = $_ENV['REDIS_PORT'];
        $redis = new \Predis\Client(['host' => $host, 'port' => $port]);
        $generalapiLogger->info('UMURS - RAW Request '.print_r($request->getContent(), true));
        $loggedUser = $this->getUser();
        if ('' === $request->getContent()) {
            $samplejson = <<<SAMPLE
                [
                         {"name": "Sunish Kumar", "designation": "Scientist-D","ou": "NIC, Kerala State Centre", "unread": 4},
                         {"name": "Syam Krishna", "designation": "Scientist-B","ou": "NIC, Kerala State Centre", "unread": 2}]
                
SAMPLE;
            $redis->set('UMURS-'.$loggedUser->getUserName(), $samplejson);
            $rstatus = 'DEFAULT-OK';
        } else {
            $payload = json_decode($request->getContent());
            $device_data = base64_decode($payload->data);
            $device_data_obj = json_decode($device_data);
            if ($device_data_obj->email_id) {
                $redis->set('UMURS-'.$device_data_obj->email_id, json_encode($device_data_obj->contacts));
                $rstatus = 'PERFECT-OK';
            } else {
                $samplejson = <<<SAMPLE
                [
                    {"name": "Sunish Kumar", "designation": "Scientist-D","ou": "NIC, Kerala State Centre", "unread": 4},
                    {"name": "Syam Krishna", "designation": "Scientist-B","ou": "NIC, Kerala State Centre", "unread": 2}]
SAMPLE;
                $redis->set('UMURS-'.$loggedUser->getUserName(), $samplejson);
                $rstatus = 'DEFAULT-OK';
            }
        }

        return new JsonResponse([$rstatus]);
    }

    /**
     * @Route("/umurs/status", name="user_message_unread_status")
     */
    public function umursStatus(Request $request)
    {
        $host = $_ENV['REDIS_HOST'];
        $port = $_ENV['REDIS_PORT'];
        $redis = new \Predis\Client(['host' => $host, 'port' => $port]);
        $loggedUser = $this->getUser();
        if ($loggedUser->getUserName()){
            $user_status = $redis->get('UMURS-'.$loggedUser->getUserName());
            $user_status_obj = \json_decode($user_status);
        } else {
            $user_status_obj = null;
        }

        return new JsonResponse($user_status_obj);
    }
}
