<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest()) {
            return;
        }
        $attrs = $request->attributes->all();
        $objid = $this->validateUUID($attrs, 'objid');
        if ($objid){
            $session = $request->getSession();
            $session->start();
            $session->set('lastobjid', $objid);
        }
        $req = $request->request->all();
        $objid = $this->validateUUID($req, 'objid');
        if ($objid){
            $session = $request->getSession();
            $session->start();
            $session->set('lastobjid', $objid);
        }
        $filVal = $request->query->get('filterValue');
        if (!preg_match('/[^A-Za-z,-_.* 0-9-$]/i', $filVal)) {
            // echo "A match was found.";
        } else {
            $event->getRequest()->query->set('filterValue', '');
            // echo "A match was not found.";
        }
    }

    private function validateUUID($array, $keySearch)
    {
        $uuid = false;

        foreach ($array as $key => $item) {
            if ($key == $keySearch) {
                $uuid = $item;
            } elseif (is_array($item) && $this->validateUUID($item, $keySearch)) {
                $uuid = $item[$keySearch];
            }
        }
        if ($uuid) {
            $isValid = \Ramsey\Uuid\Uuid::isValid($uuid);
            if (!$isValid) {
                throw new \Exception(
                    sprintf('Invalid UUID detected')
                );
            }
        }

        return $uuid;
    }
}
