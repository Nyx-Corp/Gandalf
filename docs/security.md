# Gandalf Security Layer

Gandalf provides stateless API authentication, rate limiting, CORS, and MCP access control for Cortex-based projects.

## ApiTokenAuthenticator

Stateless Symfony authenticator for Bearer token auth. Extends `AbstractAuthenticator`.

**Flow:**
1. Checks for `Authorization: Bearer <token>` header
2. Delegates token resolution to the project's `ApiTokenProviderInterface` implementation
3. On success: returns `null` (stateless, request continues)
4. On failure: returns `401 JSON` response with error message

**Project integration:** implement `ApiTokenProviderInterface::findUserByToken()` to hash the raw token, look up the matching `Token` record, verify expiration/scopes, and return a `UserInterface`.

## Token model

Gandalf's `Token` model stores API credentials:

| Field | Description |
|-------|-------------|
| `account` | FK to the owning Account |
| `intention` | Purpose string (e.g. `api_access`, `reset_password`) |
| `tokenHash` | SHA-256 hash of the raw token (64 hex chars) |
| `expiresAt` | Nullable expiration datetime |
| `label` | Human-readable label |
| `scopes` | JSON array of scope patterns (matched via `fnmatch`) |

## TokenHasher

Generates and verifies API tokens.

- **Prefix:** `ct_` (configurable)
- **Entropy:** 32 random bytes, base64url-encoded
- **Hash:** SHA-256
- **Output format:** `ct_<base64url>` (raw), 64-char hex string (hash)

```php
$hasher = new TokenHasher();

// Generate: raw token shown once, hash persisted
['token' => $raw, 'tokenHash' => $hash] = $hasher->generate();

// Verify
$hasher->verify($raw, $storedHash); // true/false (constant-time)
```

## SecuredActionToolProvider

Decorator for Cortex's `ActionToolProvider` that enforces Symfony `access_control` rules on MCP tools.

For each tool, it resolves the corresponding API path (using the same domain/model/action convention as `ApiRouteLoader`) and creates a synthetic `Request` to check against `AccessMapInterface`. Tools whose path doesn't match any `access_control` rule are **denied by default**.

Constructor arguments:
- `$actionMetadata`: same action metadata array as `ApiRouteLoader`
- `$pathPrefix`: must match the project's API path prefix (e.g. `/p`)

Both `getTools()` (filters list) and `handleToolCall()` (checks before delegation) enforce access.

## RateLimitSubscriber

HTTP-level rate limiting for API, MCP, and named routes. Replaces Cortex's `ApiRateLimitWarningSubscriber` when active.

Listens on `kernel.request` at priority 256 (before controllers). Three limiter maps:

| Map | Key resolution | Use case |
|-----|---------------|----------|
| `$routeLimiters` | Route name -> IP | Login, password reset |
| `$apiLimiters` | `authenticated` (user ID) / `anonymous` (IP) | REST API endpoints |
| `$mcpLimiters` | `authenticated` (user ID) / `anonymous` (IP) | MCP endpoint |

The `$apiPathPrefix` argument determines which paths are considered API routes (defaults to `/api`). MCP paths always match `/_mcp`.

Exceeded limits throw `TooManyRequestsHttpException` (429) with a `Retry-After` header.

### Configuration example

```yaml
Gandalf\Bridge\Symfony\Security\RateLimitSubscriber:
    arguments:
        $routeLimiters:
            login_check: '@limiter.login_attempt'
        $apiLimiters:
            authenticated: '@limiter.api_per_account'
            anonymous: '@limiter.api_anonymous'
        $mcpLimiters:
            authenticated: '@limiter.mcp_per_account'
        $apiPathPrefix: '/p'
```

## RateLimitedActionToolProvider

Decorator for `ActionToolProvider` that applies per-account rate limiting to individual MCP tool calls. Uses a single `RateLimiterFactory` keyed by user identifier (or `anonymous`).

Decoration priority should be lower than `SecuredActionToolProvider` so it runs **after** security checks.

Exceeded limits throw `TooManyRequestsHttpException` (429).

## CorsSubscriber

Adds CORS headers to all responses on the configured API host. Safe with `Access-Control-Allow-Origin: *` because authentication uses Bearer tokens, not cookies.

- Handles `OPTIONS` preflight requests (204 response)
- Adds headers to all responses: `Allow-Origin: *`, `Allow-Methods`, `Allow-Headers: Authorization, Content-Type, Accept`, `Max-Age: 3600`

Constructor: `$apiHost` (hostname string, e.g. `api.example.com`).

## Cortex fallback replacement

Cortex ships `ApiRateLimitWarningSubscriber` tagged as `cortex.api.rate_limit_guard`. When Gandalf's bundle compiler pass runs, it replaces that service with `RateLimitSubscriber`, upgrading from warnings to actual enforcement.
