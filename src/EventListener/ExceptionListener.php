<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    /**
     * This variable gets kernel container object.
     *
     * @var ContainerInterface
     */
    protected $container;
    protected $templating;
    protected $logger;

    /**
     * This constructor method injects a Container object in order to have access to YML bundle configuration inside the listener.
     *
     * @param ContainerInterface $container
     */
    public function __construct(LoggerInterface $exceptionsLogger, ContainerInterface $container, Environment $templating)
    {
        $this->container = $container;
        $this->templating = $templating;
        $this->logger = $exceptionsLogger;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $env = $this->container->get('kernel')->getEnvironment();
        if ('prod' == $env) {
            $this->logger->info($exception->getMessage());
            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
                if (Response::HTTP_UNAUTHORIZED === $statusCode) {
                    $content = $this->templating->render('/exceptions/error401.html.twig', array());
                    $statusCode = Response::HTTP_UNAUTHORIZED;
                } elseif (Response::HTTP_FORBIDDEN === $statusCode) {
                    $content = $this->templating->render('/exceptions/error404.html.twig', array());
                    $statusCode = Response::HTTP_NOT_FOUND;
                } elseif (Response::HTTP_NOT_FOUND === $statusCode) {
                    $content = $this->templating->render('/exceptions/error404.html.twig', array());
                    $statusCode = Response::HTTP_NOT_FOUND;
                } else {
                    $content = $this->templating->render('/exceptions/error.html.twig', array());
                    $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                }
            } else {
                $content = $this->templating->render('/exceptions/error.html.twig', array());
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            // }
            $event->allowCustomResponseCode();
            $response = new Response($content);
            $response->setStatusCode($statusCode);
            $event->setResponse($response);
        }
    }
}
