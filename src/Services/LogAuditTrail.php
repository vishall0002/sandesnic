<?php

namespace App\Services;

use App\Entity\Portal\AuditTrail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogAuditTrail
{
    private $emr;
    private $myContainer;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->emr = $entityManager;
        $this->myContainer = $container;
    }

    public function saveAuditTrail($ipAddress, $bundleName, $controllerName, $actionName, $route)
    {
        $em = $this->emr;
        $loggedUser = $this->myContainer->get('security.token_storage')->getToken()->getUser();
        $session = $this->myContainer->get('session');

        $auditTrail = new AuditTrail();
        $auditTrail->setLogTime(new \DateTime('now'));
        $auditTrail->setSessionId($session->getId());
        $auditTrail->setIpAddress($ipAddress);
        $auditTrail->setUserName($loggedUser);
        $auditTrail->setBundleName($bundleName);
        $auditTrail->setControllerName($controllerName);
        $auditTrail->setActionName($actionName);
        $auditTrail->setRoute($route);
        $em->persist($auditTrail);
        $em->flush();
    }
}
