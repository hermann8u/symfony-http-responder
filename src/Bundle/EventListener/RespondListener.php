<?php

declare(strict_types=1);

namespace ro0NL\HttpResponder\Bundle\EventListener;

use ro0NL\HttpResponder\Respond\Respond;
use ro0NL\HttpResponder\Responder;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;

final class RespondListener
{
    /** @var Responder */
    private $responder;

    public function __construct(Responder $responder)
    {
        $this->responder = $responder;
    }

    /**
     * @param GetResponseForControllerResultEvent|ViewEvent $event
     */
    public function onKernelView(RequestEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        if (!$controllerResult instanceof Respond) {
            return;
        }

        $event->setResponse($this->responder->respond($controllerResult));
    }
}
