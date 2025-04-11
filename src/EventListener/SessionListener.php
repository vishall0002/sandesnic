<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Redirect;
use Psr\Log\LoggerInterface;

class SessionListener
{
    /** @var int */
    private $expirationTime;
    private $authlogger;

    public function __construct($expirationTime, LoggerInterface $authenticationLogger)
    {
        $this->authlogger = $authenticationLogger;
        if (!is_integer($expirationTime)) {
            throw new \InvalidArgumentException(
               sprintf('$expirationTime is expected be of type integer, %s given', gettype($expirationTime))
           );
        }
        // Parameter is in minutes, just converting it into seconds
        $this->expirationTime = $expirationTime * 60;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMasterRequest()) {
            $request = $event->getRequest();
            $session = $request->getSession();
            $session->start();
            $metaData = $session->getMetadataBag();
            $timeDifference = time() - $metaData->getLastUsed();

            // If Parichay Login then only validate it against each request
            if ("PARICHAY" == $session->get("AUTH-TYPE")){
                
                $parichay = json_decode($session->get("PARICHAY-SESSION"));
                $parichay_user_name = $parichay->userName;
                $parichay_session_id = $parichay->sessionId;
                $parichay_ltid = $parichay->localTokenId;
                $parichay_service_name = "Sandes";
                $parichay_browser_id = $parichay->browserId;
                
                $client = HttpClient::create();

                if ($timeDifference > $this->expirationTime) {
                    $session->invalidate();
                    // return new Redirect('https://parichay.pp.nic.in/Accounts/ClientManagement?sessionTimeOut=true&service=Sandes');
                    // $response = $client->request('GET', 'http://10.122.34.117:8081/Accounts/openam/login/clientSessionTimeout',
                    // [
                    //     'query' => [
                    //         'localTokenId' => $parichay_ltid,
                    //         'userName' => $parichay_user_name,
                    //         'service' =>  $parichay_service_name,
                    //         'browserId' => $parichay_browser_id,
                    //         'sessionId' => $parichay_session_id                
                    //     ]
                    // ]);
                    // $content = $response->getContent();
                    // $this->authlogger->info('PARICHAY-LOGOUT-CHECK'.$content);
                } else {

                    
                    // $response = $client->request('GET', 'http://10.122.34.117:8081/Accounts/openam/login/isTokenValid',
                    // $response = $client->request('GET', 'https://parichay.pp.nic.in:8081/Accounts/openam/login/isTokenValid',
                    $response = $client->request('GET', 'https://parichay.nic.in:8081/Accounts/openam/login/isTokenValid',
                    [
                        'query' => [
                            'localTokenId' => $parichay_ltid,
                            'userName' => $parichay_user_name,
                            'service' =>  $parichay_service_name,
                            'browserId' => $parichay_browser_id,
                            'sessionId' => $parichay_session_id                
                            ]
                            ]);
                            $content = $response->getContent();
                            $this->authlogger->info('PARICHAY-SESSION-CHECK'.$content);
                            $objresponse = json_decode($content);
                            
                            if ($objresponse->tokenValid !== "true"){
                                $this->authlogger->info('PARICHAY-SESSION-CHECK - Closing the session since parichay refused '.$objresponse->tokenValid);
                                // $this->get('security.token_storage')->setToken(null);
                                $session->invalidate();
                            }
                }
            }
                
            if ($timeDifference > $this->expirationTime) {
                $session->invalidate();
            }
        }
    }
}
