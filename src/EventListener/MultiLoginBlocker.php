<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\RouterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Custom session listener.
 */
class MultiLoginBlocker
{
    private $tokenStorage;
    private $authorizationChecker;
    private $router;
    private $logger;

    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $generalapiLogger, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->logger = $generalapiLogger;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $this->logger->info('inside MLB onKernelRequest');
        $clientIP = $event->getRequest()->getClientIp();
        $request = $event->getRequest();
        $session = $request->getSession();
        $token = $this->tokenStorage->getToken();

        $user =$token ? $token->getUser() : null;
        
        if ($user) {
           
            if ($user !== 'anon.') {
                $this->logger->info('The sessions [DB] ' .  $user->getSessionId() . '[SS]' . $session->getId());
                if (($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED'))) {
                    $this->logger->info('The sessions [DB] ' .  $user->getSessionId() . '[SS]' . $session->getId());

                    if ($user && $user->getSessionId() !== $session->getId()) {
                        $this->logger->info('Yes blocked');
                        $session->getFlashBag()->set(
                            'danger',
                            'Multiple login is not allowed, Please use the other session'
                        );
                        $this->tokenStorage->setToken(null);
                        $response = new RedirectResponse($this->router->generate('_nlogout'));
                        $event->setResponse($response);
                        return $event;
                    }
                }
            }
        }
    }
}
