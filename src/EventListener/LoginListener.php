<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
/**
 * Custom login listener.
 */
class LoginListener
{
    private $em;
    private $authorizationChecker;
    private $userSession;
    private $redisServer;

    /**
     * Constructor.
     *
     * @param tokenStorage $tokenStorage
     * @param Doctrine        $doctrine
     */
    public function __construct(Doctrine $doctrine, Session $userSession, AuthorizationCheckerInterface $authorizationChecker, $redisServer)
    {
        $this->em = $doctrine->getManager();
        $this->authorizationChecker = $authorizationChecker;
        $this->userSession = $userSession;
        $this->redisServer = $redisServer;
    }

    /**
     * Do the magic.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $sessionId = $this->userSession->getId();
        // $redis = new \Redis();
        // $redis->connect($this->redisServer, 6379);
        // // dump($redis->get('PHPREDIS_SESSION:'.$user->getSessionId()));
        // $redis->delete('PHPREDIS_SESSION:'.$user->getSessionId());
        // $redis->close();
        $user->setSessionId($sessionId);
        $this->em->persist($user);
        $this->em->flush();

    }
}
