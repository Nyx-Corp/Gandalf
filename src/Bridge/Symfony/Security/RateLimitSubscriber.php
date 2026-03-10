<?php

namespace Gandalf\Bridge\Symfony\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Applies rate limiting per route/context on kernel.request.
 *
 * Route limiters resolve by IP. API limiters resolve by user identifier (authenticated)
 * or IP (anonymous). Configuration is injected from the project via DI.
 *
 * This class lives in Cortex Bridge (future Gandalf) — the rules/config live in the project.
 */
class RateLimitSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<string, RateLimiterFactory> $routeLimiters Route name → limiter factory
     * @param array<string, RateLimiterFactory> $apiLimiters   'authenticated'|'anonymous' → limiter factory
     */
    public function __construct(
        private readonly array $routeLimiters = [],
        private readonly array $apiLimiters = [],
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route', '');

        // Route-specific limiting (by IP)
        if (isset($this->routeLimiters[$route])) {
            $this->consume($this->routeLimiters[$route], $this->resolveIpKey($request));

            return;
        }

        // API limiting (by user or IP)
        if ([] !== $this->apiLimiters && str_starts_with($request->getPathInfo(), '/api')) {
            $userIdentifier = $this->resolveUserIdentifier();
            if ($userIdentifier && isset($this->apiLimiters['authenticated'])) {
                $this->consume($this->apiLimiters['authenticated'], $userIdentifier);
            } elseif (isset($this->apiLimiters['anonymous'])) {
                $this->consume($this->apiLimiters['anonymous'], $this->resolveIpKey($request));
            }
        }
    }

    private function consume(RateLimiterFactory $factory, string $key): void
    {
        $limiter = $factory->create($key);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
        }
    }

    private function resolveIpKey(Request $request): string
    {
        return $request->getClientIp() ?? '0.0.0.0';
    }

    private function resolveUserIdentifier(): ?string
    {
        $token = $this->tokenStorage?->getToken();

        return $token?->getUserIdentifier();
    }
}
