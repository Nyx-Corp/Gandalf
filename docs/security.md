# Security Components

Gandalf provides stateless API authentication, rate limiting, CORS, and MCP access control for Cortex-based projects.

All services are auto-registered by the `GandalfBundle`. No manual `services.yaml` declarations needed — only project-specific overrides (see [Installation](index.md)).

## Configuration

```yaml
# config/packages/gandalf.yaml
gandalf:
    cors:
        enabled: true          # Register CorsSubscriber
        api_host: '%domain_api%'
    admin:
        enabled: true          # Register admin Twig global
        layout: 'admin/base.html.twig'
        roles: { ROLE_USER: User, ROLE_ADMIN: Admin }
```

## What the project must provide

| Responsibility | Where |
|---------------|-------|
| `ApiTokenProviderInterface` implementation | `Infrastructure/Symfony/Security/` |
| DBAL mappers for Account & Token | `Infrastructure/Doctrine/Mapper/` |
| Symfony `rate_limiter` services | `config/packages/rate_limiter.yaml` |
| `RateLimitSubscriber` arguments override | `config/services.yaml` |
| Firewalls & access_control | `config/packages/security.yaml` |
| UserProvider (loads users from DB) | `Infrastructure/Symfony/Security/` |

## ApiTokenAuthenticator

Stateless Symfony authenticator for Bearer token auth. Extends `AbstractAuthenticator`.

**Flow:**
1. Checks for `Authorization: Bearer <token>` header
2. Delegates token resolution to the project's `ApiTokenProviderInterface` implementation
3. On success: returns `null` (stateless, request continues)
4. On failure: returns `401 JSON` response with error message

**Project integration:** implement `ApiTokenProviderInterface::findUserByToken()` to hash the raw token, look up the matching `Token` record, verify expiration/scopes, and return a `UserInterface`.

## Account model

Gandalf's `Account` model represents a security identity:

| Field | Type | Description |
|-------|------|-------------|
| `username` | `Email` | Login identifier |
| `password` | `?HashedPassword` | Hashed password (nullable for OAuth-only accounts) |
| `acl` | `array` | Symfony role strings (`ROLE_USER`, etc.) |
| `uuid` | `Uuid` | Unique identifier |
| `archivedAt` | `?\DateTimeInterface` | Soft-delete timestamp (via `Archivable` trait) |

## Token model

Gandalf's `Token` model stores API credentials:

| Field | Type | Description |
|-------|------|-------------|
| `account` | `Account` | FK to the owning Account |
| `intention` | `string` | Purpose (`api_access`, `reset_password`, `email_validation`) |
| `tokenHash` | `string` | SHA-256 hash of the raw token (64 hex chars) |
| `expiresAt` | `\DateTimeInterface` | Expiration datetime |
| `label` | `?string` | Human-readable label |
| `scopes` | `?array` | Scope patterns (matched via `fnmatch`) |
| `createdAt` | `?\DateTimeInterface` | Creation timestamp |

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

## Action Handlers

| Handler | Command | Description |
|---------|---------|-------------|
| `AccountCreate\Handler` | `Command(username, plainPassword, acl)` | Create account with hashed password |
| `AccountUpdate\Handler` | `Command(uuid, username, plainPassword?, acl?)` | Update account fields |
| `AccountArchive\Handler` | `Command(account)` | Soft-delete via `archivedAt` |
| `TokenCreate\Handler` | `Command(account, intention, expiresAt, ...)` | Create token, returns raw + hash |
| `TokenRevoke\Handler` | `Command(token)` | Revoke by setting `expiresAt` to now |
| `AskPasswordReset\Handler` | `Command(account)` | Create reset token |
| `CreatePassword\Handler` | `Command(account, token, plainPassword)` | Set new password, consume token |
| `ValidateEmail\Handler` | `Command(account, token)` | Mark email verified, consume token |

## RateLimitSubscriber

HTTP-level rate limiting for API, MCP, and named routes. Listens on `kernel.request` at priority 256.

| Map | Key resolution | Use case |
|-----|---------------|----------|
| `$routeLimiters` | Route name → IP | Login, password reset |
| `$apiLimiters` | `authenticated` (user ID) / `anonymous` (IP) | REST API endpoints |
| `$mcpLimiters` | `authenticated` (user ID) / `anonymous` (IP) | MCP endpoint |

The `$apiPathPrefix` determines which paths are API routes (defaults to `/api`). MCP paths match `/_mcp`.

Exceeded limits throw `TooManyRequestsHttpException` (429) with `Retry-After` header.

**Configuration** — override arguments in your project's `services.yaml`:

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

## CorsSubscriber

Adds CORS headers to all responses on the configured API host. Enabled via `gandalf.cors.enabled: true`.

Safe with `Access-Control-Allow-Origin: *` because authentication uses Bearer tokens, not cookies.

- Handles `OPTIONS` preflight requests (204 response)
- Adds headers: `Allow-Origin: *`, `Allow-Methods`, `Allow-Headers: Authorization, Content-Type, Accept`, `Max-Age: 3600`

## SecuredActionToolProvider

Decorator for Cortex's `ActionToolProvider` that enforces Symfony `access_control` rules on MCP tools.

For each tool, resolves the corresponding API path and creates a synthetic `Request` to check against `AccessMapInterface`. Tools whose path doesn't match any rule are **denied by default**.

Constructor arguments:
- `$actionMetadata`: same metadata as `ApiRouteLoader`
- `$pathPrefix`: must match the project's API path prefix (e.g. `/p`)

## RateLimitedActionToolProvider

Decorator for `ActionToolProvider` that applies per-account rate limiting to individual MCP tool calls. Uses a single `RateLimiterFactory` keyed by user identifier.

Decoration priority should be lower than `SecuredActionToolProvider` so it runs **after** security checks.
