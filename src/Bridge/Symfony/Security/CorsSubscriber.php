<?php

namespace Gandalf\Bridge\Symfony\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds CORS headers to all responses on the API host.
 *
 * Uses Access-Control-Allow-Origin: * which is safe because the API host
 * authenticates via Bearer tokens (not cookies).
 */
class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $apiHost,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 255],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getHost() !== $this->apiHost) {
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            $response = new Response('', 204);
            $this->addCorsHeaders($response);
            $event->setResponse($response);
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($event->getRequest()->getHost() !== $this->apiHost) {
            return;
        }

        $this->addCorsHeaders($event->getResponse());
    }

    private function addCorsHeaders(Response $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}
