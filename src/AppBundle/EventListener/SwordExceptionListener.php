<?php

namespace AppBundle\EventListener;

use AppBundle\Controller\SwordController;
use AppBundle\Exception\SwordException;
use Monolog\Logger;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Listen for exceptions in the SWORD controller, and produce an error document.
 */
class SwordExceptionListener
{
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * Symfony Controller that generated the exception.
     *
     * @var ControllerInterface
     */
    private $controller;

    /**
     * Monolog logger.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The symfony request stack that generated the exception.
     *
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Set the logger for exceptions.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the Twig Engine for templating and output.
     *
     * @param TwigEngine $templating
     */
    public function setTemplating(TwigEngine $templating)
    {
        $this->templating = $templating;
    }

    /**
     * Set the request stack, so it may be interrogated later.
     *
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Respond to an exception with an error document wrapped in a Response.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // only intercept SwordController exceptions.
        if (!$this->controller[0] instanceof SwordController) {
            return;
        }

        if (!$exception instanceof SwordException) {
            return;
        }

        $this->logger->critical($exception->getMessage().' from '.$this->requestStack->getCurrentRequest()->getClientIp());

        $response = $this->templating->renderResponse(
            'AppBundle:Sword:error.xml.twig',
            array('error' => $exception)
        );
        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode($exception->getStatusCode());
        $event->setResponse($response);
    }

    /**
     * Once the controller has been initialized, this event is fired. Grab
     * a reference to the active controller.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $this->controller = $event->getController();
    }
}
