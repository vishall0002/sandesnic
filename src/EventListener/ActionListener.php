<?php

namespace App\EventListener;

use App\Interfaces\AuditableControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Services\LogAuditTrail;

class ActionListener
{
    /**
     * This variable gets kernel container object.
     *
     * @var ContainerInterface
     */
    protected $container;
    protected $auditTrailLogger;

    /**
     * This constructor method injects a Container object in order to have access to YML bundle configuration inside the listener.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, LogAuditTrail $auditTrailLogger)
    {
        $this->container = $container;
        $this->auditTrailLogger = $auditTrailLogger;
    }

    /**
     * This method handles kernelControllerEvent checking if token is valid.
     *
     * @param FilterControllerEvent $event
     *
     * @throws AccessDeniedHttpException in case token is not valid
     */
    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();
        $request = $event->getRequest();
        $reqAttribs = $request->attributes->get('_controller');

        if (!is_array($controller)) {
            return;
        }
        
        if ($controller[0] instanceof ExceptionController) {
            return;
        }

        if ($controller[0] instanceof AuditableControllerInterface) {
            $matches = explode('\\', $reqAttribs);
            $laWordAt = count($matches) - 1;
            if ($laWordAt > 1) {
                /* This case occurs when Controllers are called from routes */
                $CandA = explode('::', $matches[$laWordAt]);
                if ($laWordAt > 4) {
                    $BundleName = $matches[4];
                } else {
                    $BundleName = $matches[1];
                }
                $ControllerName = str_replace('Controller', '', $CandA[0]);
                $ActionName = str_replace('Action', '', $CandA[1]);
            } else {
                /* This case occurs when Controllers are called directly from twig */
                $CandA = explode(':', $reqAttribs);
                $BundleName = $CandA[0];
                $ControllerName = str_replace('Controller', '', $CandA[1]);
                $ActionName = str_replace('Action', '', $CandA[2]);
            }
            $route = $request->getPathInfo();
            
            
            $clientIPs = $request->getClientIps();
            $clientIP = json_encode($clientIPs);
            $this->auditTrailLogger->saveAuditTrail($clientIP, $BundleName, $ControllerName, $ActionName, $route);
        }
    }
}
