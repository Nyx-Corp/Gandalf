<?php

namespace Gandalf\Bridge\Symfony\Security;

use Cortex\Bridge\Symfony\Mcp\ActionToolProvider;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Decorator that applies per-account rate limiting to MCP tool calls.
 *
 * Same pattern as SecuredActionToolProvider — wraps ActionToolProvider.
 * Decoration priority should be lower (e.g., -20) so it runs AFTER security checks.
 */
class RateLimitedActionToolProvider
{
    public function __construct(
        private readonly ActionToolProvider $inner,
        private readonly RateLimiterFactory $limiter,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function getTools(): array
    {
        return $this->inner->getTools();
    }

    public function handleToolCall(string $name, array $args): array
    {
        $key = $this->resolveKey();

        $limiter = $this->limiter->create($key);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'MCP rate limit exceeded.');
        }

        return $this->inner->handleToolCall($name, $args);
    }

    private function resolveKey(): string
    {
        $token = $this->tokenStorage?->getToken();

        return $token?->getUserIdentifier() ?? 'anonymous';
    }
}
