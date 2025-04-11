<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Description of ResponseListner.
 *
 * The purpose of this class is to force the browsers
 * not to store our applications secured pages
 */
class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        // $request = $event->getRequest();
        // $session = $request->getSession();
        // $oldToken = $this->csrfManager->getToken('designation');
        // dump($oldToken);
        // $this->csrfManager->refreshToken($oldToken->getId());
        // $session->set('_csrf/designation', $this->csrfManager->getToken('designation')->getValue());
        // // $request->setSession($session);
        // dump($session);
        // die;

        $response = $event->getResponse();
        $response->headers->add(['Cache-Control' => 'no-cache, no-store, must-revalidate']);
        $response->headers->add(['Pragma' => 'no-cache']);
        $response->headers->add(['Expires' => 'Sun, 28 Dec 1975 12:00:00 GMT']);
        $response->headers->add(['Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT']);
        $response->headers->set('Symfony-Debug-Toolbar-Replace', 1);

        // Remove Access-Control-Allow-Origin header if it is set to '*'
        if ($response->headers->has('Access-Control-Allow-Origin')) {
            $response->headers->remove('Access-Control-Allow-Origin');
        }
        // Add the Permissions-Policy header
        $response->headers->set('Permissions-Policy', 'geolocation=(self), microphone=(), camera=()');

        // $response->headers->set('X-Frame-Options', 'sameorigin');
        // if (!$response->headers->has('X-Xss-Protection')) {
        //     $response->headers->set('X-Xss-Protection', '1; mode=block');
        // }
        // $response->headers->set('X-Content-Type-Options', 'nosniff');
        // $response->headers->set('Content-Security-Policy', "default-src 'self' *.gstatic.com *.digitallocker.gov.in ;style-src 'self' 'unsafe-inline' *.googleapis.com *.digitallocker.gov.in *.google.com; img-src data: 'self' *.digitallocker.gov.in http://csi.gstatic.com;  script-src 'self' 'unsafe-inline'  'unsafe-eval' *.google.com *.digitallocker.gov.in; object-src 'self' data: ");
        $cookie = Cookie::create('rufc', 'false', 0, '/', null, true, true, false, 'Strict');
        $response->headers->setCookie($cookie);
    }
}
